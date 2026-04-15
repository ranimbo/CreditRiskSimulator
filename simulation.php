<?php
/**
 * Simulation de Crédit — Style Amen Bank
 * 
 * Formulaire avec sélecteur de client, paramètres de crédit,
 * et aperçu en temps réel avec mensualité en gold.
 */

$pageTitle = 'Nouvelle simulation';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/Client.php';
require_once __DIR__ . '/classes/CreditRequest.php';
require_once __DIR__ . '/classes/ScoringEngine.php';

$clientModel = new Client();
$creditRequestModel = new CreditRequest();

// Client pré-sélectionné
$selectedClientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
$selectedClient = null;
if ($selectedClientId) {
    $selectedClient = $clientModel->findById($selectedClientId);
}

// Tous les clients pour le dropdown
$clients = $clientModel->getAll([], 1000, 0);

$errors = [];
$formData = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    
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
    
    // Traiter si pas d'erreurs
    if (empty($errors)) {
        $client = $clientModel->findById($formData['client_id']);
        
        if (!$client) {
            $errors['client_id'] = 'Client non trouvé.';
        } else {
            $scoringEngine = new ScoringEngine();
            $scoreResult = $scoringEngine->calculateScore($client, $formData);
            
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

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="dashboard.php">Accueil</a>
    <span class="separator">›</span>
    <a href="history.php">Simulations</a>
    <span class="separator">›</span>
    <span class="current">Nouvelle simulation</span>
</div>

<!-- En-tête -->
<div class="mb-6">
    <div class="flex items-center gap-4 mb-2">
        <a href="dashboard.php" class="text-[#6B7280] hover:text-[#003366] transition-colors">
            <i data-feather="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="page-title-accent">
            <h1 class="text-2xl font-bold text-[#003366]">Nouvelle simulation de crédit</h1>
        </div>
    </div>
    <p class="text-[#6B7280] ml-9">Évaluez le risque crédit et obtenez une décision instantanée.</p>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-error mb-6">
    <i data-feather="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
    <span><?php echo htmlspecialchars($errors['general']); ?></span>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulaire de simulation -->
    <div class="lg:col-span-2">
        <form method="POST" id="simulation-form" class="space-y-6">
            <?php echo csrfField(); ?>
            
            <!-- Sélection du client -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] overflow-hidden shadow-sm">
                <div class="section-header">
                    <i data-feather="user" class="section-icon"></i>
                    <h2>Sélection du client</h2>
                </div>
                <div class="p-6">
                    <label for="client_id" class="form-label">Client <span class="text-[#C8971F]">*</span></label>
                    <select 
                        id="client_id" 
                        name="client_id" 
                        class="form-input <?php echo isset($errors['client_id']) ? 'border-[#C0392B]' : ''; ?>"
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
                            <?php echo htmlspecialchars($client['cin'] . ' — ' . $client['nom']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['client_id'])): ?>
                    <p class="form-error"><?php echo $errors['client_id']; ?></p>
                    <?php endif; ?>
                    
                    <p class="text-sm text-[#6B7280] mt-2">
                        <a href="client-form.php" class="text-[#C8971F] hover:text-[#A07820] font-medium transition-colors">
                            + Créer un nouveau client
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Détails du crédit -->
            <div class="bg-white rounded-xl border border-[#E2E8F0] overflow-hidden shadow-sm">
                <div class="section-header">
                    <i data-feather="credit-card" class="section-icon"></i>
                    <h2>Détails du crédit</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Montant -->
                    <div>
                        <label for="montant_demande" class="form-label">Montant demandé (DT) <span class="text-[#C8971F]">*</span></label>
                        <input 
                            type="number" 
                            id="montant_demande" 
                            name="montant_demande" 
                            value="<?php echo $formData['montant_demande'] ?? ''; ?>"
                            class="form-input <?php echo isset($errors['montant_demande']) ? 'border-[#C0392B]' : ''; ?>"
                            min="1000"
                            max="10000000"
                            step="1000"
                            placeholder="Ex: 50000"
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
                        <label for="duree" class="form-label">Durée (mois) <span class="text-[#C8971F]">*</span></label>
                        <select 
                            id="duree" 
                            name="duree" 
                            class="form-input <?php echo isset($errors['duree']) ? 'border-[#C0392B]' : ''; ?>"
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
                        <label for="type_credit" class="form-label">Type du crédit <span class="text-[#C8971F]">*</span></label>
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
            
            <!-- Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-gold">
                    <i data-feather="play" class="w-4 h-4"></i>
                    Lancer le scoring
                </button>
            </div>
        </form>
    </div>
    
    <!-- Panneau latéral -->
    <div class="space-y-6">
        <!-- Infos client -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm" id="client-info-panel" style="<?php echo $selectedClient ? '' : 'display: none;'; ?>">
            <div class="section-header">
                <i data-feather="info" class="section-icon"></i>
                <h3 class="text-sm font-semibold text-[#003366]">Informations client</h3>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <p class="text-xs text-[#6B7280]">Revenu mensuel</p>
                    <p class="text-lg font-semibold text-[#003366]" id="client-revenu">
                        <?php echo $selectedClient ? formatCurrency($selectedClient['revenu_mensuel_net']) : '-'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-[#6B7280]">Charges actuelles</p>
                    <p class="text-lg font-semibold text-[#333333]" id="client-charges">
                        <?php echo $selectedClient ? formatCurrency($selectedClient['charges_mensuelles']) : '-'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-[#6B7280]">Situation</p>
                    <p class="text-sm font-semibold text-[#333333]" id="client-situation">
                        <?php echo $selectedClient ? htmlspecialchars($selectedClient['situation_pro']) : '-'; ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-[#6B7280]">Historique</p>
                    <p class="text-sm font-semibold text-[#333333]" id="client-historique">
                        <?php echo $selectedClient ? ($selectedClient['historique_credit'] ? 'Bon historique' : 'Incidents') : '-'; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Récapitulatif de la simulation -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm" id="calc-preview-panel" style="display: none;">
            <div class="p-5 border-b border-[#E2E8F0]">
                <h3 class="text-sm font-semibold text-[#003366]">Récapitulatif de la simulation</h3>
            </div>
            <div class="p-5 space-y-5">
                <div class="text-center p-4 bg-[#FDF8ED] rounded-xl border border-[#C8971F]/20">
                    <p class="text-xs text-[#6B7280] mb-1">Mensualité estimée</p>
                    <p class="text-2xl font-bold text-[#C8971F]" id="calc-mensualite">-</p>
                    <p class="text-[10px] text-[#6B7280] mt-1">Taux standard 5%</p>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-[#6B7280]">Nouveau taux d'endettement</p>
                        <p class="text-sm font-bold" id="calc-endettement">-</p>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full transition-all duration-300" id="calc-endettement-bar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Critères d'évaluation -->
        <div class="bg-[#F4F6F9] rounded-xl border border-[#E2E8F0] p-5">
            <h3 class="font-semibold text-[#003366] mb-4 text-sm">Critères d'évaluation</h3>
            <ul class="space-y-2.5 text-sm text-[#6B7280]">
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-[#003366] rounded-full"></span>
                    Revenu mensuel (20 pts)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-[#003366] rounded-full"></span>
                    Taux d'endettement (20 pts)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-[#C8971F] rounded-full"></span>
                    Situation professionnelle (20 pts)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-[#C8971F] rounded-full"></span>
                    Ancienneté emploi (15 pts)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-[#6B7280] rounded-full"></span>
                    Historique crédit (15 pts)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-[#6B7280] rounded-full"></span>
                    Âge (10 pts)
                </li>
            </ul>
        </div>
    </div>
</div>

<script>
let clientRevenu = <?php echo $selectedClient ? $selectedClient['revenu_mensuel_net'] : 0; ?>;
let clientCharges = <?php echo $selectedClient ? $selectedClient['charges_mensuelles'] : 0; ?>;

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
    
    document.getElementById('client-revenu').textContent = clientRevenu.toFixed(3) + ' DT';
    document.getElementById('client-charges').textContent = clientCharges.toFixed(3) + ' DT';
    document.getElementById('client-situation').textContent = option.dataset.situation || '-';
    document.getElementById('client-historique').textContent = option.dataset.historique || '-';
    
    panel.style.display = 'block';
    updateCalculations();
}

function updateCalculations() {
    const montant = parseFloat(document.getElementById('montant_demande').value) || 0;
    const duree = parseInt(document.getElementById('duree').value) || 0;
    const taux = 5.00;
    const panel = document.getElementById('calc-preview-panel');
    
    if (montant <= 0 || duree <= 0) {
        panel.style.display = 'none';
        return;
    }
    
    const mensualite = calculateMonthlyPaymentJS(montant, taux, duree);
    document.getElementById('calc-mensualite').textContent = mensualite.toFixed(3) + ' DT';
    
    // Calculer le taux d'endettement
    if (clientRevenu > 0) {
        const newCharges = clientCharges + mensualite;
        const newRatio = (newCharges / clientRevenu) * 100;
        const ratioEl = document.getElementById('calc-endettement');
        const barEl = document.getElementById('calc-endettement-bar');
        ratioEl.textContent = newRatio.toFixed(1) + '%';
        barEl.style.width = Math.min(100, newRatio) + '%';
        
        if (newRatio < 30) {
            ratioEl.className = 'text-sm font-bold text-[#1A7F3C]';
            barEl.style.backgroundColor = '#1A7F3C';
        } else if (newRatio < 40) {
            ratioEl.className = 'text-sm font-bold text-[#C8971F]';
            barEl.style.backgroundColor = '#C8971F';
        } else {
            ratioEl.className = 'text-sm font-bold text-[#C0392B]';
            barEl.style.backgroundColor = '#C0392B';
        }
    } else {
        document.getElementById('calc-endettement').textContent = '-';
    }
    
    panel.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    const clientId = document.getElementById('client_id').value;
    if (clientId) {
        loadClientInfo(clientId);
    }
    updateCalculations();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
