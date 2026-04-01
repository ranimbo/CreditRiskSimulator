<?php
/**
 * System Settings Page
 * Configure application settings
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
            'bank_name' => sanitize($_POST['bank_name']),
            'bank_address' => sanitize($_POST['bank_address']),
            'bank_phone' => sanitize($_POST['bank_phone']),
            'bank_email' => sanitize($_POST['bank_email']),
            'default_interest_rate' => (float)$_POST['default_interest_rate'],
            'min_loan_amount' => (int)$_POST['min_loan_amount'],
            'max_loan_amount' => (int)$_POST['max_loan_amount'],
            'min_loan_duration' => (int)$_POST['min_loan_duration'],
            'max_loan_duration' => (int)$_POST['max_loan_duration'],
        ];
        
        foreach ($settings as $name => $value) {
            $stmt = $db->prepare("INSERT INTO settings (name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$name, $value, $value]);
        }
        
        setFlashMessage('success', 'Paramètres mis à jour avec succès.');
        redirect('settings.php');
    }
}

// Get current settings
$stmt = $db->query("SELECT name, value FROM settings");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['name']] = $row['value'];
}

// Default values
$defaults = [
    'bank_name' => 'Banque Crédit Simulator',
    'bank_address' => '123 Avenue Mohammed V, Casablanca',
    'bank_phone' => '+212 5 22 00 00 00',
    'bank_email' => 'contact@banque.ma',
    'default_interest_rate' => 8.5,
    'min_loan_amount' => 5000,
    'max_loan_amount' => 1000000,
    'min_loan_duration' => 6,
    'max_loan_duration' => 84,
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

require_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Paramètres du Système</h1>
            <p class="text-slate-600 mt-1">Configurez les paramètres de l'application</p>
        </div>
        <a href="index.php" class="btn btn-secondary mt-4 md:mt-0">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>

    <form method="POST" class="space-y-8">
        <input type="hidden" name="action" value="update_settings">
        
        <!-- Bank Information -->
        <div class="card">
            <h2 class="text-xl font-semibold text-slate-900 mb-6">Informations de la Banque</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="bank_name" class="block text-sm font-medium text-slate-700 mb-1">
                        Nom de la Banque
                    </label>
                    <input type="text" id="bank_name" name="bank_name" 
                           value="<?php echo htmlspecialchars($settings['bank_name']); ?>"
                           class="form-input">
                </div>
                
                <div class="md:col-span-2">
                    <label for="bank_address" class="block text-sm font-medium text-slate-700 mb-1">
                        Adresse
                    </label>
                    <input type="text" id="bank_address" name="bank_address" 
                           value="<?php echo htmlspecialchars($settings['bank_address']); ?>"
                           class="form-input">
                </div>
                
                <div>
                    <label for="bank_phone" class="block text-sm font-medium text-slate-700 mb-1">
                        Téléphone
                    </label>
                    <input type="tel" id="bank_phone" name="bank_phone" 
                           value="<?php echo htmlspecialchars($settings['bank_phone']); ?>"
                           class="form-input">
                </div>
                
                <div>
                    <label for="bank_email" class="block text-sm font-medium text-slate-700 mb-1">
                        Email
                    </label>
                    <input type="email" id="bank_email" name="bank_email" 
                           value="<?php echo htmlspecialchars($settings['bank_email']); ?>"
                           class="form-input">
                </div>
            </div>
        </div>

        <!-- Loan Settings -->
        <div class="card">
            <h2 class="text-xl font-semibold text-slate-900 mb-6">Paramètres de Crédit</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="default_interest_rate" class="block text-sm font-medium text-slate-700 mb-1">
                        Taux d'intérêt par défaut (%)
                    </label>
                    <input type="number" id="default_interest_rate" name="default_interest_rate" 
                           step="0.01" min="0" max="30"
                           value="<?php echo $settings['default_interest_rate']; ?>"
                           class="form-input">
                </div>
                
                <div>
                    <!-- Spacer for alignment -->
                </div>
                
                <div>
                    <label for="min_loan_amount" class="block text-sm font-medium text-slate-700 mb-1">
                        Montant minimum (DH)
                    </label>
                    <input type="number" id="min_loan_amount" name="min_loan_amount" 
                           min="0" step="1000"
                           value="<?php echo $settings['min_loan_amount']; ?>"
                           class="form-input">
                </div>
                
                <div>
                    <label for="max_loan_amount" class="block text-sm font-medium text-slate-700 mb-1">
                        Montant maximum (DH)
                    </label>
                    <input type="number" id="max_loan_amount" name="max_loan_amount" 
                           min="0" step="1000"
                           value="<?php echo $settings['max_loan_amount']; ?>"
                           class="form-input">
                </div>
                
                <div>
                    <label for="min_loan_duration" class="block text-sm font-medium text-slate-700 mb-1">
                        Durée minimum (mois)
                    </label>
                    <input type="number" id="min_loan_duration" name="min_loan_duration" 
                           min="1" max="360"
                           value="<?php echo $settings['min_loan_duration']; ?>"
                           class="form-input">
                </div>
                
                <div>
                    <label for="max_loan_duration" class="block text-sm font-medium text-slate-700 mb-1">
                        Durée maximum (mois)
                    </label>
                    <input type="number" id="max_loan_duration" name="max_loan_duration" 
                           min="1" max="360"
                           value="<?php echo $settings['max_loan_duration']; ?>"
                           class="form-input">
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer les Paramètres
            </button>
        </div>
    </form>

    <!-- System Info -->
    <div class="card mt-8 bg-slate-50 border-slate-200">
        <h3 class="text-lg font-semibold text-slate-900 mb-4">Informations Système</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="flex justify-between py-2 border-b border-slate-200">
                <span class="text-slate-600">Version PHP</span>
                <span class="font-medium text-slate-900"><?php echo phpversion(); ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-200">
                <span class="text-slate-600">Serveur</span>
                <span class="font-medium text-slate-900"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-200">
                <span class="text-slate-600">Base de données</span>
                <span class="font-medium text-slate-900">MySQL</span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-200">
                <span class="text-slate-600">Fuseau horaire</span>
                <span class="font-medium text-slate-900"><?php echo date_default_timezone_get(); ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-200">
                <span class="text-slate-600">Date du serveur</span>
                <span class="font-medium text-slate-900"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>
            <div class="flex justify-between py-2 border-b border-slate-200">
                <span class="text-slate-600">Mémoire limite</span>
                <span class="font-medium text-slate-900"><?php echo ini_get('memory_limit'); ?></span>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
