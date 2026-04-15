<?php
/**
 * Credit Simulation Page
 * 
 * Form for creating new credit simulations.
 * Adapté au nouveau schéma : type_credit, montant_demande, duree
 */

$pageTitle = 'Nouvelle simulation';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/Client.php';
require_once __DIR__ . '/classes/CreditRequest.php';
require_once __DIR__ . '/classes/ScoringEngine.php';

$clientModel = new Client();
$creditRequestModel = new CreditRequest();

// Check if client is pre-selected
$selectedClientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
$selectedClient = null;
if ($selectedClientId) {
    $selectedClient = $clientModel->findById($selectedClientId);
}

// Get all clients for dropdown
$clients = $clientModel->getAll([], 1000, 0);

$errors = [];
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    
    // Collect form data
    $formData = [
        'client_id' => (int)($_POST['client_id'] ?? 0),
        'montant_demande' => (float)($_POST['montant_demande'] ?? 0),
        'duree' => (int)($_POST['duree'] ?? 0),
        'type_credit' => trim($_POST['type_credit'] ?? ''),
    ];
    
    // Validation
    if ($formData['client_id'] <= 0) {
        $errors['client_id'] = 'Veuillez sélectionner un client.';
    }
    
    if ($formData['montant_demande'] <= 0) {
        $errors['montant_demande'] = 'Le montant doit être supérieur à 0.';
    } elseif ($formData['montant_demande'] > 10000000) {
        $errors['montant_demande'] = 'Le montant est excessif.';
    }
    
    if ($formData['duree'] < 6) {
        $errors['duree'] = 'La durée minimum est de 6 mois.';
    } elseif ($formData['duree'] > 360) {
        $errors['duree'] = 'La durée maximum est de 360 mois (30 ans).';
    }
    
    // Process if no errors
    if (empty($errors)) {
        $client = $clientModel->findById($formData['client_id']);
        
        if (!$client) {
            $errors['client_id'] = 'Client non trouvé.';
        } else {
            // Calculate score
            $scoringEngine = new ScoringEngine();
            $scoreResult = $scoringEngine->calculateScore($client, $formData);
            
            // Create credit request
            $requestData = [
                'client_id' => $formData['client_id'],
                'agent_id' => getCurrentUserId(),
                'montant_demande' => $formData['montant_demande'],
                'duree' => $formData['duree'],
                'type_credit' => $formData['type_credit'],
                'taux_endettement_calc' => $scoreResult['taux_endettement']
            ];
            
            try {
                $requestId = $creditRequestModel->create($requestData, $scoreResult);
                
                // Redirect to result page
                header('Location: result.php?id=' . $requestId);
                exit;
                
            } catch (Exception $e) {
                $errors['general'] = 'Erreur lors de la création de la demande: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center gap-4 mb-2">
        <a href="dashboard.php" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i data-feather="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold text-slate-900">Nouvelle simulation de crédit</h1>
    </div>
    <p class="text-slate-500 ml-9">Évaluez le risque crédit et obtenez une décision instantanée.</p>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
    <i data-feather="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
    <span><?php echo htmlspecialchars($errors['general']); ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Simulation Form -->
    <div class="lg:col-span-2">
        <form method="POST" id="simulation-form" class="space-y-6">
            <?php echo csrfField(); ?>
            
            <!-- Client Selection -->
            <div class="bg-white rounded-xl border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Sélection du client</h2>
                </div>
                <div class="p-6">
                    <label for="client_id" class="form-label">Client <span class="text-red-500">*</span></label>
                    <select 
                        id="client_id" 
                        name="client_id" 
                        class="form-input <?php echo isset($errors['client_id']) ? 'border-red-500' : ''; ?>"
                        required
                        onchange="loadClientInfo(this.value)"
                    >
                        <option value="">Sélectionner un client...</option>
                        <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id']; ?>" 
                            <?php echo ($selectedClient && $selectedClient['id'] == $client['id']) || ($formData['client_id'] ?? 0) == $client['id'] ? 'selected' : ''; ?>
                            data-revenu="<?php echo $client['revenu_mensuel_net']; ?>"
                            data-charges="<?php echo $client['charges_mensuelles']; ?>"
                            data-situation="<?php echo htmlspecialchars($client['situation_pro']); ?>"
                            data-historique="<?php echo htmlspecialchars($client['historique_credit'] ? 'Bon historique' : 'Incidents'); ?>">
                            <?php echo htmlspecialchars($client['cin'] . ' - ' . $client['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['client_id'])): ?>
                    <p class="form-error"><?php echo $errors['client_id']; ?></p>
                    <?php endif; ?>
                    
                    <p class="text-sm text-slate-500 mt-2">
                        <a href="client-form.php" class="text-primary-600 hover:text-primary-700">
                            + Créer un nouveau client
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Credit Details -->
            <div class="bg-white rounded-xl border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900">Détails du crédit</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Montant -->
                    <div>
                        <label for="montant_demande" class="form-label">Montant demandé (MAD) <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            id="montant_demande" 
                            name="montant_demande" 
                            value="<?php echo $formData['montant_demande'] ?? ''; ?>"
                            class="form-input <?php echo isset($errors['montant_demande']) ? 'border-red-500' : ''; ?>"
                            min="1000"
                            max="10000000"
                            step="1000"
                            placeholder="Ex: 100000"
                            required
                            onkeyup="updateCalculations()"
                            onchange="updateCalculations()"
                        >
                        <?php if (isset($errors['montant_demande'])): ?>
                        <p class="form-error"><?php echo $errors['montant_demande']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Durée -->
                    <div>
                        <label for="duree" class="form-label">Durée (mois) <span class="text-red-500">*</span></label>
                        <select 
                            id="duree" 
                            name="duree" 
                            class="form-input <?php echo isset($errors['duree']) ? 'border-red-500' : ''; ?>"
                            required
                            onchange="updateCalculations()"
                        >
                            <option value="">Sélectionner...</option>
                            <?php 
                            $durees = [6, 12, 24, 36, 48, 60, 72, 84, 96, 120, 180, 240, 300, 360];
                            foreach ($durees as $d): 
                            ?>
                            <option value="<?php echo $d; ?>" <?php echo ($formData['duree'] ?? 0) == $d ? 'selected' : ''; ?>>
                                <?php echo $d; ?> mois (<?php echo round($d/12, 1); ?> ans)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['duree'])): ?>
                        <p class="form-error"><?php echo $errors['duree']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Type Credit -->
                    <div class="md:col-span-2">
                        <label for="type_credit" class="form-label">Type du crédit</label>
                        <select 
                            id="type_credit" 
                            name="type_credit" 
                            class="form-input"
                            required
                        >
                            <option value="">Sélectionner...</option>
                            <option value="CONSOMMATION" <?php echo ($formData['type_credit'] ?? '') === 'CONSOMMATION' ? 'selected' : ''; ?>>Consommation</option>
                            <option value="IMMOBILIER" <?php echo ($formData['type_credit'] ?? '') === 'IMMOBILIER' ? 'selected' : ''; ?>>Immobilier</option>
                            <option value="PROFESSIONNEL" <?php echo ($formData['type_credit'] ?? '') === 'PROFESSIONNEL' ? 'selected' : ''; ?>>Professionnel</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Submit -->
            <div class="flex items-center justify-end gap-4">
                <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i data-feather="play" class="w-4 h-4"></i>
                    Lancer la simulation
                </button>
            </div>
        </form>
    </div>
    
    <!-- Side Panel -->
    <div class="space-y-6">
        <!-- Client Info -->
        <div class="bg-white rounded-xl border border-slate-200" id="client-info-panel" style="<?php echo $selectedClient ? '' : 'display: none;'; ?>">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Informations client</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm text-slate-500">Revenu mensuel</p>
                    <p class="text-lg font-semibold text-slate-900" id="client-revenu">
                        <?php echo $selectedClient ? formatCurrency($selectedClient['revenu_mensuel_net']) : '-'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Charges actuelles</p>
                    <p class="text-lg font-semibold text-slate-900" id="client-charges">
                        <?php echo $selectedClient ? formatCurrency($selectedClient['charges_mensuelles']) : '-'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Situation</p>
                    <p class="text-lg font-semibold text-slate-900" id="client-situation">
                        <?php echo $selectedClient ? htmlspecialchars($selectedClient['situation_pro']) : '-'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Historique</p>
                    <p class="text-lg font-semibold text-slate-900" id="client-historique">
                        <?php echo $selectedClient ? ($selectedClient['historique_credit'] ? 'Bon historique' : 'Incidents') : '-'; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Calculation Preview -->
        <div class="bg-white rounded-xl border border-slate-200" id="calc-preview-panel" style="display: none;">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Aperçu</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm text-slate-500">Mensualité estimée (Taux standard 5%)</p>
                    <p class="text-2xl font-bold text-primary-600" id="calc-mensualite">-</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Nouveau taux d'endettement</p>
                    <p class="text-lg font-semibold" id="calc-endettement">-</p>
                </div>
            </div>
        </div>
        
        <!-- Scoring Criteria Info -->
        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6">
            <h3 class="font-semibold text-slate-900 mb-4">Critères d'évaluation</h3>
            <ul class="space-y-2 text-sm text-slate-600">
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                    Revenu mensuel (25%)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                    Taux d'endettement (25%)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                    Situation professionnelle (15%)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                    Ancienneté emploi (10%)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                    Historique crédit (15%)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-primary-500 rounded-full"></span>
                    Âge (10%)
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
let clientRevenu = <?php echo $selectedClient ? $selectedClient['revenu_mensuel_net'] : 0; ?>;
let clientCharges = <?php echo $selectedClient ? $selectedClient['charges_mensuelles'] : 0; ?>;

// Quick function instead of fetching full PHP one for UI preview
function calculateMonthlyPaymentJS(principal, annualRate, months) {
    const r = (annualRate / 100) / 12;
    if (r === 0) return principal / months;
    return (principal * r * Math.pow(1 + r, months)) / (Math.pow(1 + r, months) - 1);
}

function loadClientInfo(clientId) {
    const select = document.getElementById('client_id');
    const option = select.options[select.selectedIndex];
    const panel = document.getElementById('client-info-panel');
    
    if (!clientId) {
        panel.style.display = 'none';
        clientRevenu = 0;
        clientCharges = 0;
        return;
    }
    
    clientRevenu = parseFloat(option.dataset.revenu) || 0;
    clientCharges = parseFloat(option.dataset.charges) || 0;
    
    // just dummy format
    document.getElementById('client-revenu').textContent = clientRevenu.toFixed(2) + ' MAD';
    document.getElementById('client-charges').textContent = clientCharges.toFixed(2) + ' MAD';
    document.getElementById('client-situation').textContent = option.dataset.situation || '-';
    document.getElementById('client-historique').textContent = option.dataset.historique || '-';
    
    panel.style.display = 'block';
    updateCalculations();
}

function updateCalculations() {
    const montant = parseFloat(document.getElementById('montant_demande').value) || 0;
    const duree = parseInt(document.getElementById('duree').value) || 0;
    const taux = 5.00; // default standard matching PHP Engine
    const panel = document.getElementById('calc-preview-panel');
    
    if (montant <= 0 || duree <= 0) {
        panel.style.display = 'none';
        return;
    }
    
    const mensualite = calculateMonthlyPaymentJS(montant, taux, duree);
    
    document.getElementById('calc-mensualite').textContent = mensualite.toFixed(2) + ' MAD';
    
    // Calculate new debt ratio
    if (clientRevenu > 0) {
        const newCharges = clientCharges + mensualite;
        const newRatio = (newCharges / clientRevenu) * 100;
        const ratioEl = document.getElementById('calc-endettement');
        ratioEl.textContent = newRatio.toFixed(1) + '%';
        
        if (newRatio < 30) {
            ratioEl.className = 'text-lg font-semibold text-green-600';
        } else if (newRatio < 50) {
            ratioEl.className = 'text-lg font-semibold text-yellow-600';
        } else {
            ratioEl.className = 'text-lg font-semibold text-red-600';
        }
    } else {
        document.getElementById('calc-endettement').textContent = '-';
    }
    
    panel.style.display = 'block';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const clientId = document.getElementById('client_id').value;
    if (clientId) {
        loadClientInfo(clientId);
    }
    updateCalculations();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
