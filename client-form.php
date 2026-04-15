<?php
/**
 * Formulaire Client — Style Amen Bank
 * 
 * Ajout ou modification d'un client.
 * Sections avec en-têtes gris, astérisques gold, bordure accent navy.
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/Client.php';

$clientModel = new Client();
$isEdit = false;
$client = null;
$errors = [];

// Vérifier si modification
if (isset($_GET['id'])) {
    $client = $clientModel->findById((int)$_GET['id']);
    if (!$client) {
        setFlashMessage('error', 'Client non trouvé.');
        header('Location: clients.php');
        exit;
    }
    $isEdit = true;
}

$pageTitle = $isEdit ? 'Modifier le client' : 'Nouveau client';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    
    // Collecter les données
    $data = [
        'cin' => strtoupper(trim($_POST['cin'] ?? '')),
        'nom' => trim($_POST['nom'] ?? ''),
        'date_naissance' => $_POST['date_naissance'] ?? '',
        'situation_pro' => $_POST['situation_pro'] ?? '',
        'anciennete_emploi' => (int)($_POST['anciennete_emploi'] ?? 0),
        'revenu_mensuel_net' => (float)($_POST['revenu_mensuel_net'] ?? 0),
        'charges_mensuelles' => (float)($_POST['charges_mensuelles'] ?? 0),
        'historique_credit' => (int)($_POST['historique_credit'] ?? 1),
    ];
    
    // Validation
    if (empty($data['cin'])) {
        $errors['cin'] = 'Le CIN est requis.';
    } elseif (!isValidCIN($data['cin'])) {
        $errors['cin'] = 'Format CIN invalide (8 chiffres).';
    }
    
    if (empty($data['nom'])) {
        $errors['nom'] = 'Le nom est requis.';
    }
    
    if (empty($data['date_naissance'])) {
        $errors['date_naissance'] = 'La date de naissance est requise.';
    } else {
        $age = calculateAge($data['date_naissance']);
        if ($age < 18) {
            $errors['date_naissance'] = 'Le client doit avoir au moins 18 ans.';
        }
    }
    
    if (empty($data['situation_pro'])) {
        $errors['situation_pro'] = 'La situation professionnelle est requise.';
    }
    
    if ($data['revenu_mensuel_net'] < 0) {
        $errors['revenu_mensuel_net'] = 'Le revenu mensuel ne peut pas être négatif.';
    }
    
    if ($data['charges_mensuelles'] < 0) {
        $errors['charges_mensuelles'] = 'Les charges mensuelles ne peuvent pas être négatives.';
    }
    
    // Sauvegarder si pas d'erreurs
    if (empty($errors)) {
        try {
            if ($isEdit) {
                $clientModel->update($client['id'], $data);
                setFlashMessage('success', 'Client mis à jour avec succès.');
            } else {
                $clientModel->create($data);
                setFlashMessage('success', 'Client créé avec succès.');
            }
            header('Location: clients.php');
            exit;
        } catch (Exception $e) {
            $errors['general'] = $e->getMessage();
        }
    }
    
    // Conserver les données en cas d'erreur
    $client = $data;
    if ($isEdit) {
        $client['id'] = $_GET['id'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="dashboard.php">Accueil</a>
    <span class="separator">›</span>
    <a href="clients.php">Clients</a>
    <span class="separator">›</span>
    <span class="current"><?php echo $isEdit ? 'Modifier' : 'Nouveau client'; ?></span>
</div>

<!-- En-tête de la page -->
<div class="mb-6">
    <div class="flex items-center gap-4 mb-2">
        <a href="clients.php" class="text-[#6B7280] hover:text-[#003366] transition-colors">
            <i data-feather="arrow-left" class="w-5 h-5"></i>
        </a>
        <div class="page-title-accent">
            <h1 class="text-2xl font-bold text-[#003366]"><?php echo $pageTitle; ?></h1>
        </div>
    </div>
    <p class="text-[#6B7280] ml-9">
        <?php echo $isEdit ? 'Modifiez les informations du client.' : 'Remplissez les informations pour créer un nouveau client.'; ?>
    </p>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-error mb-6">
    <i data-feather="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
    <span><?php echo htmlspecialchars($errors['general']); ?></span>
</div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <?php echo csrfField(); ?>
    
    <!-- Section : Informations personnelles -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] overflow-hidden shadow-sm">
        <div class="section-header">
            <i data-feather="user" class="section-icon"></i>
            <h2>Informations personnelles</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- CIN -->
            <div>
                <label for="cin" class="form-label">CIN <span class="text-[#C8971F]">*</span></label>
                <input 
                    type="text" 
                    id="cin" 
                    name="cin" 
                    value="<?php echo htmlspecialchars($client['cin'] ?? ''); ?>"
                    class="form-input <?php echo isset($errors['cin']) ? 'border-[#C0392B]' : ''; ?>"
                    placeholder="12345678"
                    required
                >
                <?php if (isset($errors['cin'])): ?>
                <p class="form-error"><?php echo $errors['cin']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Date de naissance -->
            <div>
                <label for="date_naissance" class="form-label">Date de naissance <span class="text-[#C8971F]">*</span></label>
                <input 
                    type="date" 
                    id="date_naissance" 
                    name="date_naissance" 
                    value="<?php echo htmlspecialchars($client['date_naissance'] ?? ''); ?>"
                    class="form-input <?php echo isset($errors['date_naissance']) ? 'border-[#C0392B]' : ''; ?>"
                    max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                    required
                >
                <?php if (isset($errors['date_naissance'])): ?>
                <p class="form-error"><?php echo $errors['date_naissance']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Nom Complet -->
            <div class="md:col-span-2">
                <label for="nom" class="form-label">Nom complet <span class="text-[#C8971F]">*</span></label>
                <input 
                    type="text" 
                    id="nom" 
                    name="nom" 
                    value="<?php echo htmlspecialchars($client['nom'] ?? ''); ?>"
                    class="form-input <?php echo isset($errors['nom']) ? 'border-[#C0392B]' : ''; ?>"
                    placeholder="Nom complet du client"
                    required
                >
                <?php if (isset($errors['nom'])): ?>
                <p class="form-error"><?php echo $errors['nom']; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Section : Situation professionnelle -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] overflow-hidden shadow-sm">
        <div class="section-header">
            <i data-feather="briefcase" class="section-icon"></i>
            <h2>Situation professionnelle et historique</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Situation professionnelle -->
            <div>
                <label for="situation_pro" class="form-label">Situation professionnelle <span class="text-[#C8971F]">*</span></label>
                <select 
                    id="situation_pro" 
                    name="situation_pro" 
                    class="form-input <?php echo isset($errors['situation_pro']) ? 'border-[#C0392B]' : ''; ?>"
                    required
                >
                    <option value="">Sélectionner...</option>
                    <?php foreach (Client::getSituations() as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($client['situation_pro'] ?? '') === $key ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['situation_pro'])): ?>
                <p class="form-error"><?php echo $errors['situation_pro']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Ancienneté -->
            <div>
                <label for="anciennete_emploi" class="form-label">Ancienneté (mois)</label>
                <input 
                    type="number" 
                    id="anciennete_emploi" 
                    name="anciennete_emploi" 
                    value="<?php echo (int)($client['anciennete_emploi'] ?? 0); ?>"
                    class="form-input"
                    min="0"
                    placeholder="0"
                >
                <p class="text-xs text-[#6B7280] mt-1">
                    <?php echo getAncienneteLabel((int)($client['anciennete_emploi'] ?? 0)); ?>
                </p>
            </div>
            
            <!-- Historique crédit -->
            <div>
                <label for="historique_credit" class="form-label">Historique crédit</label>
                <select 
                    id="historique_credit" 
                    name="historique_credit" 
                    class="form-input"
                >
                    <?php foreach (Client::getCreditHistoryOptions() as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php echo (int)($client['historique_credit'] ?? 1) === $val ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Section : Informations financières -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] overflow-hidden shadow-sm">
        <div class="section-header">
            <i data-feather="dollar-sign" class="section-icon"></i>
            <h2>Informations financières</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Revenu mensuel net -->
            <div>
                <label for="revenu_mensuel_net" class="form-label">Revenu mensuel net (DT) <span class="text-[#C8971F]">*</span></label>
                <input 
                    type="number" 
                    id="revenu_mensuel_net" 
                    name="revenu_mensuel_net" 
                    value="<?php echo (float)($client['revenu_mensuel_net'] ?? 0); ?>"
                    class="form-input <?php echo isset($errors['revenu_mensuel_net']) ? 'border-[#C0392B]' : ''; ?>"
                    min="0"
                    step="0.001"
                    required
                >
                <?php if (isset($errors['revenu_mensuel_net'])): ?>
                <p class="form-error"><?php echo $errors['revenu_mensuel_net']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Charges mensuelles -->
            <div>
                <label for="charges_mensuelles" class="form-label">Charges mensuelles (DT)</label>
                <input 
                    type="number" 
                    id="charges_mensuelles" 
                    name="charges_mensuelles" 
                    value="<?php echo (float)($client['charges_mensuelles'] ?? 0); ?>"
                    class="form-input <?php echo isset($errors['charges_mensuelles']) ? 'border-[#C0392B]' : ''; ?>"
                    min="0"
                    step="0.001"
                >
                <?php if (isset($errors['charges_mensuelles'])): ?>
                <p class="form-error"><?php echo $errors['charges_mensuelles']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Taux d'endettement calculé -->
            <div class="md:col-span-2">
                <div class="bg-[#F4F6F9] rounded-xl p-4 border border-[#E2E8F0]">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-[#003366]">Taux d'endettement actuel</p>
                            <p class="text-xs text-[#6B7280]">Calculé automatiquement (charges / revenus)</p>
                        </div>
                        <div id="debt-ratio-display" class="text-2xl font-bold text-[#333333]">
                            <?php 
                                $debtRatio = 0;
                                if (isset($client['revenu_mensuel_net']) && $client['revenu_mensuel_net'] > 0) {
                                    $debtRatio = ($client['charges_mensuelles'] / $client['revenu_mensuel_net']) * 100;
                                }
                                echo formatPercentage($debtRatio, 1);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Barre d'actions sticky -->
    <div class="sticky-action-bar rounded-xl">
        <a href="clients.php" class="btn btn-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">
            <i data-feather="save" class="w-4 h-4"></i>
            <?php echo $isEdit ? 'Mettre à jour' : 'Enregistrer le client'; ?>
        </button>
    </div>
</form>

<script>
// Calcul taux endettement en temps réel
document.addEventListener('DOMContentLoaded', function() {
    const revenuInput = document.getElementById('revenu_mensuel_net');
    const chargesInput = document.getElementById('charges_mensuelles');
    const ratioDisplay = document.getElementById('debt-ratio-display');
    
    function updateDebtRatio() {
        const revenu = parseFloat(revenuInput.value) || 0;
        const charges = parseFloat(chargesInput.value) || 0;
        
        let ratio = 0;
        if (revenu > 0) {
            ratio = (charges / revenu) * 100;
        }
        
        ratioDisplay.textContent = ratio.toFixed(1) + '%';
        
        // Couleurs Amen Bank
        ratioDisplay.className = 'text-2xl font-bold ';
        if (ratio < 30) {
            ratioDisplay.className += 'text-[#1A7F3C]';
        } else if (ratio < 50) {
            ratioDisplay.className += 'text-[#C8971F]';
        } else {
            ratioDisplay.className += 'text-[#C0392B]';
        }
    }
    
    revenuInput.addEventListener('input', updateDebtRatio);
    chargesInput.addEventListener('input', updateDebtRatio);
    
    // Calcul initial
    updateDebtRatio();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
