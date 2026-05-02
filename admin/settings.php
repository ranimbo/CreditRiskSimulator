<?php
/**
 * System Settings Page
 */

require_once '../includes/auth.php';
requireAdmin();

require_once '../classes/Database.php';

$pageTitle = 'Paramètres du Système';

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_settings') {
        $settings = [
            'bank_name'            => trim($_POST['bank_name']            ?? ''),
            'bank_address'         => trim($_POST['bank_address']         ?? ''),
            'bank_phone'           => trim($_POST['bank_phone']           ?? ''),
            'bank_email'           => trim($_POST['bank_email']           ?? ''),
            'default_interest_rate'=> (float)($_POST['default_interest_rate'] ?? 8.5),
            'min_loan_amount'      => (int)($_POST['min_loan_amount']     ?? 5000),
            'max_loan_amount'      => (int)($_POST['max_loan_amount']     ?? 1000000),
            'min_loan_duration'    => (int)($_POST['min_loan_duration']   ?? 6),
            'max_loan_duration'    => (int)($_POST['max_loan_duration']   ?? 84),
        ];

        foreach ($settings as $name => $value) {
            $exists = $db->fetchValue("SELECT COUNT(*) FROM settings WHERE name = ?", [$name]);
            if ($exists) {
                $db->update('settings', ['value' => $value], 'name = ?', [$name]);
            } else {
                $db->insert('settings', ['name' => $name, 'value' => $value]);
            }
        }

        setFlashMessage('success', 'Paramètres mis à jour avec succès.');
        header('Location: settings.php');
        exit;
    }
}

// Get current settings
$rows     = $db->fetchAll("SELECT name, value FROM settings");
$settings = [];
foreach ($rows as $row) {
    $settings[$row['name']] = $row['value'];
}

// Default values
$defaults = [
    'bank_name'             => 'Amen Bank — Credit Risk Simulator',
    'bank_address'          => 'Avenue Mohamed V, Tunis',
    'bank_phone'            => '+216 71 00 00 00',
    'bank_email'            => 'contact@amenbank.com.tn',
    'default_interest_rate' => 8.5,
    'min_loan_amount'       => 5000,
    'max_loan_amount'       => 1000000,
    'min_loan_duration'     => 6,
    'max_loan_duration'     => 84,
];
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) $settings[$key] = $value;
}

require_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-[#003366]">Paramètres du Système</h1>
            <p class="text-[#6B7280] mt-1">Configurez les paramètres de l'application</p>
        </div>
        <a href="index.php" class="btn btn-secondary mt-4 md:mt-0">
            <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
            Retour
        </a>
    </div>

    <form method="POST" class="space-y-8">
        <input type="hidden" name="action" value="update_settings">

        <!-- Bank Information -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
            <div class="section-header">
                <i data-feather="home" class="section-icon"></i>
                <h2>Informations de la Banque</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="bank_name" class="form-label">Nom de la Banque</label>
                    <input type="text" id="bank_name" name="bank_name"
                           value="<?php echo htmlspecialchars($settings['bank_name']); ?>"
                           class="form-input">
                </div>
                <div class="md:col-span-2">
                    <label for="bank_address" class="form-label">Adresse</label>
                    <input type="text" id="bank_address" name="bank_address"
                           value="<?php echo htmlspecialchars($settings['bank_address']); ?>"
                           class="form-input">
                </div>
                <div>
                    <label for="bank_phone" class="form-label">Téléphone</label>
                    <input type="tel" id="bank_phone" name="bank_phone"
                           value="<?php echo htmlspecialchars($settings['bank_phone']); ?>"
                           class="form-input">
                </div>
                <div>
                    <label for="bank_email" class="form-label">Email</label>
                    <input type="email" id="bank_email" name="bank_email"
                           value="<?php echo htmlspecialchars($settings['bank_email']); ?>"
                           class="form-input">
                </div>
            </div>
        </div>

        <!-- Loan Settings -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm overflow-hidden">
            <div class="section-header">
                <i data-feather="credit-card" class="section-icon"></i>
                <h2>Paramètres de Crédit</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="default_interest_rate" class="form-label">Taux d'intérêt par défaut (%)</label>
                    <input type="number" id="default_interest_rate" name="default_interest_rate"
                           step="0.01" min="0" max="30"
                           value="<?php echo $settings['default_interest_rate']; ?>"
                           class="form-input">
                </div>
                <div><!-- spacer --></div>
                <div>
                    <label for="min_loan_amount" class="form-label">Montant minimum (DT)</label>
                    <input type="number" id="min_loan_amount" name="min_loan_amount"
                           min="0" step="1000"
                           value="<?php echo $settings['min_loan_amount']; ?>"
                           class="form-input">
                </div>
                <div>
                    <label for="max_loan_amount" class="form-label">Montant maximum (DT)</label>
                    <input type="number" id="max_loan_amount" name="max_loan_amount"
                           min="0" step="1000"
                           value="<?php echo $settings['max_loan_amount']; ?>"
                           class="form-input">
                </div>
                <div>
                    <label for="min_loan_duration" class="form-label">Durée minimum (mois)</label>
                    <input type="number" id="min_loan_duration" name="min_loan_duration"
                           min="1" max="360"
                           value="<?php echo $settings['min_loan_duration']; ?>"
                           class="form-input">
                </div>
                <div>
                    <label for="max_loan_duration" class="form-label">Durée maximum (mois)</label>
                    <input type="number" id="max_loan_duration" name="max_loan_duration"
                           min="1" max="360"
                           value="<?php echo $settings['max_loan_duration']; ?>"
                           class="form-input">
                </div>
            </div>
        </div>

        <!-- Save -->
        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">
                <i data-feather="save" class="w-4 h-4 mr-2"></i>
                Enregistrer les Paramètres
            </button>
            <a href="index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>

    <!-- System Info -->
    <div class="bg-[#F4F6F9] border border-[#E2E8F0] rounded-xl p-6 mt-8">
        <h3 class="text-lg font-semibold text-[#003366] mb-4">Informations Système</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-0 text-sm divide-y divide-[#E2E8F0]">
            <?php
            $infos = [
                'Version PHP'    => phpversion(),
                'Serveur'        => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'Base de données'=> 'MySQL',
                'Fuseau horaire' => date_default_timezone_get(),
                'Date serveur'   => date('d/m/Y H:i:s'),
                'Mémoire limite' => ini_get('memory_limit'),
            ];
            foreach ($infos as $label => $val): ?>
            <div class="flex justify-between py-2.5">
                <span class="text-[#6B7280]"><?php echo $label; ?></span>
                <span class="font-medium text-[#003366]"><?php echo htmlspecialchars($val); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>