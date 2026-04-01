<?php
/**
 * Credit Simulation Page
 * 
 * Form for creating new credit simulations.
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
        'montant' => (float)($_POST['montant'] ?? 0),
        'duree_mois' => (int)($_POST['duree_mois'] ?? 0),
        'taux_annuel' => (float)($_POST['taux_annuel'] ?? 5.00),
        'objet_credit' => trim($_POST['objet_credit'] ?? ''),
    ];
    
    // Validation
    if ($formData['client_id'] <= 0) {
        $errors['client_id'] = 'Veuillez sélectionner un client.';
    }
    
    if ($formData['montant'] <= 0) {
        $errors['montant'] = 'Le montant doit être supérieur à 0.';
    } elseif ($formData['montant'] > 1000000) {
        $errors['montant'] = 'Le montant maximum est de 1 000 000 MAD.';
    }
    
    if ($formData['duree_mois'] < 6) {
        $errors['duree_mois'] = 'La durée minimum est de 6 mois.';
    } elseif ($formData['duree_mois'] > 360) {
        $errors['duree_mois'] = 'La durée maximum est de 360 mois (30 ans).';
    }
    
    if ($formData['taux_annuel'] <= 0 || $formData['taux_annuel'] > 30) {
        $errors['taux_annuel'] = 'Le taux doit être entre 0.01% et 30%.';
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
                'montant' => $formData['montant'],
                'duree_mois' => $formData['duree_mois'],
                'taux_annuel' => $formData['taux_annuel'],
                'objet_credit' => $formData['objet_credit'],
            ];
            
            try {
                $requestId = $creditRequestModel->create($requestData, $scoreResult);
                
                // Redirect to result page
                header('Location: resultat.php?id=' . $requestId);
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
                            data-revenu="<?php echo $client['revenu_mensuel']; ?>"
                            data-charges="<?php echo $client['charges_mensuelles']; ?>"
                            data-situation="<?php echo htmlspecialchars($client['situation_professionnelle']); ?>"
                            data-historique="<?php echo htmlspecialchars($client['historique_credit']); ?>">
                            <?php echo htmlspecialchars($client['cin'] . ' - ' . $client['prenom'] . ' ' . $client['nom']); ?>
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
                        <label for="montant" class="form-label">Montant demandé (MAD) <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            id="montant" 
                            name="montant" 
                            value="<?php echo $formData['montant'] ?? ''; ?>"
                            class="form-input <?php echo isset($errors['montant']) ? 'border-red-500' : ''; ?>"
                            min="1000"
                            max="1000000"
                            step="1000"
                            placeholder="Ex: 100000"
                            required
                            onchange="updateCalculations()"
                        >
                        <?php if (isset($errors['montant'])): ?>
                        <p class="form-error"><?php echo $errors['montant']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Durée -->
                    <div>
                        <label for="duree_mois" class="form-label">Durée (mois) <span class="text-red-500">*</span></label>
                        <select 
                            id="duree_mois" 
                            name="duree_mois" 
                            class="form-input <?php echo isset($errors['duree_mois']) ? 'border-red-500' : ''; ?>"
                            required
                            onchange="updateCalculations()"
                        >
                            <option value="">Sélectionner...</option>
                            <?php 
                            $durees = [6, 12, 24, 36, 48, 60, 72, 84, 96, 120, 180, 240, 300, 360];
                            foreach ($durees as $d): 
                            ?>
                            <option value="<?php echo $d; ?>" <?php echo ($formData['duree_mois'] ?? 0) == $d ? 'selected' : ''; ?>>
                                <?php echo $d; ?> mois (<?php echo round($d/12, 1); ?> ans)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['duree_mois'])): ?>
                        <p class="form-error"><?php echo $errors['duree_mois']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Taux -->
                    <div>
                        <label for="taux_annuel" class="form-label">Taux annuel (%) <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            id="taux_annuel" 
                            name="taux_annuel" 
                            value="<?php echo $formData['taux_annuel'] ?? 5.00; ?>"
                            class="form-input <?php echo isset($errors['taux_annuel']) ? 'border-red-500' : ''; ?>"
                            min="0.01"
                            max="30"
                            step="0.01"
                            required
                            onchange="updateCalculations()"
                        >
                        <?php if (isset($errors['taux_annuel'])): ?>
                        <p class="form-error"><?php echo $errors['taux_annuel']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Objet -->
                    <div>
                        <label for="objet_credit" class="form-label">Objet du crédit</label>
                        <select 
                            id="objet_credit" 
                            name="objet_credit" 
                            class="form-input"
                        >
                            <option value="">Sélectionner...</option>
                            <option value="Achat véhicule" <?php echo ($formData['objet_credit'] ?? '') === 'Achat véhicule' ? 'selected' : ''; ?>>Achat véhicule</option>
                            <option value="Achat immobilier" <?php echo ($formData['objet_credit'] ?? '') === 'Achat immobilier' ? 'selected' : ''; ?>>Achat immobilier</option>
                            <option value="Travaux" <?php echo ($formData['objet_credit'] ?? '') === 'Travaux' ? 'selected' : ''; ?>>Travaux</option>
                            <option value="Équipement" <?php echo ($formData['objet_credit'] ?? '') === 'Équipement' ? 'selected' : ''; ?>>Équipement</option>
                            <option value="Consommation" <?php echo ($formData['objet_credit'] ?? '') === 'Consommation' ? 'selected' : ''; ?>>Consommation</option>
                            <option value="Autre" <?php echo ($formData['objet_credit'] ?? '') === 'Autre' ? 'selected' : ''; ?>>Autre</option>
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
                        <?php echo $selectedClient ? formatCurrency($selectedClient['revenu_mensuel']) : '-'; ?>
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
                        <?php echo $selectedClient ? htmlspecialchars($selectedClient['situation_professionnelle']) : '-'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Historique</p>
                    <p class="text-lg font-semibold text-slate-900" id="client-historique">
                        <?php echo $selectedClient ? htmlspecialchars($selectedClient['historique_credit']) : '-'; ?>
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
                    <p class="text-sm text-slate-500">Mensualité estimée</p>
                    <p class="text-2xl font-bold text-primary-600" id="calc-mensualite">-</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Coût total du crédit</p>
                    <p class="text-lg font-semibold text-slate-900" id="calc-cout-total">-</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Intérêts totaux</p>
                    <p class="text-lg font-semibold text-slate-900" id="calc-interets">-</p>
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
            <div class="mt-4 pt-4 border-t border-slate-200 text-sm">
                <p class="text-slate-600"><strong>Score >= 70:</strong> Approuvé</p>
                <p class="text-slate-600"><strong>Score 50-69:</strong> Révision manuelle</p>
                <p class="text-slate-600"><strong>Score < 50:</strong> Refusé</p>
            </div>
        </div>
    </div>
</div>

<script>
let clientRevenu = <?php echo $selectedClient ? $selectedClient['revenu_mensuel'] : 0; ?>;
let clientCharges = <?php echo $selectedClient ? $selectedClient['charges_mensuelles'] : 0; ?>;

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
    
    document.getElementById('client-revenu').textContent = formatCurrency(clientRevenu);
    document.getElementById('client-charges').textContent = formatCurrency(clientCharges);
    document.getElementById('client-situation').textContent = option.dataset.situation || '-';
    document.getElementById('client-historique').textContent = option.dataset.historique || '-';
    
    panel.style.display = 'block';
    updateCalculations();
}

function updateCalculations() {
    const montant = parseFloat(document.getElementById('montant').value) || 0;
    const duree = parseInt(document.getElementById('duree_mois').value) || 0;
    const taux = parseFloat(document.getElementById('taux_annuel').value) || 0;
    const panel = document.getElementById('calc-preview-panel');
    
    if (montant <= 0 || duree <= 0 || taux <= 0) {
        panel.style.display = 'none';
        return;
    }
    
    const mensualite = calculateMonthlyPayment(montant, taux, duree);
    const coutTotal = mensualite * duree;
    const interets = coutTotal - montant;
    
    document.getElementById('calc-mensualite').textContent = formatCurrency(mensualite);
    document.getElementById('calc-cout-total').textContent = formatCurrency(coutTotal);
    document.getElementById('calc-interets').textContent = formatCurrency(interets);
    
    // Calculate new debt ratio
    if (clientRevenu > 0) {
        const newCharges = clientCharges + mensualite;
        const newRatio = (newCharges / clientRevenu) * 100;
        const ratioEl = document.getElementById('calc-endettement');
        ratioEl.textContent = formatPercentage(newRatio);
        
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
