<?php
/**
 * Client Form Page
 * 
 * Add or edit a client.
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/Client.php';

$clientModel = new Client();
$isEdit = false;
$client = null;
$errors = [];

// Check if editing
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    
    // Collect form data
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
        $errors['cin'] = 'Format CIN invalide (ex: AB123456).';
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
    
    // Save if no errors
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
    
    // Keep form data on error
    $client = $data;
    if ($isEdit) {
        $client['id'] = $_GET['id'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center gap-4 mb-2">
        <a href="clients.php" class="text-slate-400 hover:text-slate-600 transition-colors">
            <i data-feather="arrow-left" class="w-5 h-5"></i>
        </a>
        <h1 class="text-2xl font-bold text-slate-900"><?php echo $pageTitle; ?></h1>
    </div>
    <p class="text-slate-500 ml-9">
        <?php echo $isEdit ? 'Modifiez les informations du client.' : 'Remplissez les informations pour créer un nouveau client.'; ?>
    </p>
</div>

<?php if (!empty($errors['general'])): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
    <i data-feather="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
    <span><?php echo htmlspecialchars($errors['general']); ?></span>
</div>
<?php endif; ?>

<form method="POST" class="space-y-6">
    <?php echo csrfField(); ?>
    
    <!-- Personal Information -->
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Informations personnelles</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- CIN -->
            <div>
                <label for="cin" class="form-label">CIN <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    id="cin" 
                    name="cin" 
                    value="<?php echo htmlspecialchars($client['cin'] ?? ''); ?>"
                    class="form-input <?php echo isset($errors['cin']) ? 'border-red-500' : ''; ?>"
                    placeholder="AB123456"
                    required
                >
                <?php if (isset($errors['cin'])): ?>
                <p class="form-error"><?php echo $errors['cin']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Date de naissance -->
            <div>
                <label for="date_naissance" class="form-label">Date de naissance <span class="text-red-500">*</span></label>
                <input 
                    type="date" 
                    id="date_naissance" 
                    name="date_naissance" 
                    value="<?php echo htmlspecialchars($client['date_naissance'] ?? ''); ?>"
                    class="form-input <?php echo isset($errors['date_naissance']) ? 'border-red-500' : ''; ?>"
                    max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>"
                    required
                >
                <?php if (isset($errors['date_naissance'])): ?>
                <p class="form-error"><?php echo $errors['date_naissance']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Nom Complet -->
            <div class="md:col-span-2">
                <label for="nom" class="form-label">Nom complet <span class="text-red-500">*</span></label>
                <input 
                    type="text" 
                    id="nom" 
                    name="nom" 
                    value="<?php echo htmlspecialchars($client['nom'] ?? ''); ?>"
                    class="form-input <?php echo isset($errors['nom']) ? 'border-red-500' : ''; ?>"
                    placeholder="Nom complet du client"
                    required
                >
                <?php if (isset($errors['nom'])): ?>
                <p class="form-error"><?php echo $errors['nom']; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Professional Information -->
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Informations professionnelles et antécédents</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Situation professionnelle -->
            <div>
                <label for="situation_pro" class="form-label">Situation professionnelle <span class="text-red-500">*</span></label>
                <select 
                    id="situation_pro" 
                    name="situation_pro" 
                    class="form-input <?php echo isset($errors['situation_pro']) ? 'border-red-500' : ''; ?>"
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
                <p class="text-xs text-slate-500 mt-1">
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
    
    <!-- Financial Information -->
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Informations financières</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Revenu mensuel net -->
            <div>
                <label for="revenu_mensuel_net" class="form-label">Revenu mensuel net (MAD) <span class="text-red-500">*</span></label>
                <input 
                    type="number" 
                    id="revenu_mensuel_net" 
                    name="revenu_mensuel_net" 
                    value="<?php echo (float)($client['revenu_mensuel_net'] ?? 0); ?>"
                    class="form-input <?php echo isset($errors['revenu_mensuel_net']) ? 'border-red-500' : ''; ?>"
                    min="0"
                    step="0.01"
                    required
                >
                <?php if (isset($errors['revenu_mensuel_net'])): ?>
                <p class="form-error"><?php echo $errors['revenu_mensuel_net']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Charges mensuelles -->
            <div>
                <label for="charges_mensuelles" class="form-label">Charges mensuelles (MAD)</label>
                <input 
                    type="number" 
                    id="charges_mensuelles" 
                    name="charges_mensuelles" 
                    value="<?php echo (float)($client['charges_mensuelles'] ?? 0); ?>"
                    class="form-input <?php echo isset($errors['charges_mensuelles']) ? 'border-red-500' : ''; ?>"
                    min="0"
                    step="0.01"
                >
                <?php if (isset($errors['charges_mensuelles'])): ?>
                <p class="form-error"><?php echo $errors['charges_mensuelles']; ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Calculated debt ratio -->
            <div class="md:col-span-2">
                <div class="bg-slate-50 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-700">Taux d'endettement actuel</p>
                            <p class="text-xs text-slate-500">Calculé automatiquement (charges / revenus)</p>
                        </div>
                        <div id="debt-ratio-display" class="text-2xl font-bold text-slate-900">
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
    
    <!-- Form Actions -->
    <div class="flex items-center justify-end gap-4">
        <a href="clients.php" class="btn btn-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">
            <i data-feather="save" class="w-4 h-4"></i>
            <?php echo $isEdit ? 'Mettre à jour' : 'Créer le client'; ?>
        </button>
    </div>
</form>

<script>
// Real-time debt ratio calculation
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
        
        // simple formatting since JS doesn't have our formatPercentage natively injected
        ratioDisplay.textContent = ratio.toFixed(1) + '%';
        
        // Update color based on ratio
        ratioDisplay.className = 'text-2xl font-bold ';
        if (ratio < 30) {
            ratioDisplay.className += 'text-green-600';
        } else if (ratio < 50) {
            ratioDisplay.className += 'text-yellow-600';
        } else {
            ratioDisplay.className += 'text-red-600';
        }
    }
    
    revenuInput.addEventListener('input', updateDebtRatio);
    chargesInput.addEventListener('input', updateDebtRatio);
    
    // Initial calculation
    updateDebtRatio();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
