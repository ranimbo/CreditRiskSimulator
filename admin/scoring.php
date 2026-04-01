<?php
/**
 * Scoring Configuration Page
 * Manage scoring criteria weights and thresholds
 */

require_once '../includes/auth.php';
requireAdmin();

require_once '../classes/Database.php';

$pageTitle = 'Configuration du Scoring';

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_criteria') {
        $criteria_id = (int)$_POST['criteria_id'];
        $weight = (float)$_POST['weight'];
        $max_score = (int)$_POST['max_score'];
        $description = sanitize($_POST['description']);
        
        $stmt = $db->prepare("UPDATE scoring_criteria SET weight = ?, max_score = ?, description = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$weight, $max_score, $description, $criteria_id])) {
            setFlashMessage('success', 'Critère mis à jour avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour.');
        }
        redirect('scoring.php');
    }
    
    if ($action === 'update_thresholds') {
        $approval_threshold = (float)$_POST['approval_threshold'];
        $rejection_threshold = (float)$_POST['rejection_threshold'];
        
        // Update in settings table or config
        $stmt = $db->prepare("UPDATE settings SET value = ? WHERE name = 'approval_threshold'");
        $stmt->execute([$approval_threshold]);
        
        $stmt = $db->prepare("UPDATE settings SET value = ? WHERE name = 'rejection_threshold'");
        $stmt->execute([$rejection_threshold]);
        
        setFlashMessage('success', 'Seuils mis à jour avec succès.');
        redirect('scoring.php');
    }
    
    if ($action === 'reset_defaults') {
        // Reset criteria to default values
        $defaults = [
            ['age', 1.0, 15, 'Score basé sur l\'âge du client (25-55 ans optimal)'],
            ['income', 1.5, 25, 'Score basé sur le revenu mensuel et le ratio d\'endettement'],
            ['employment', 1.2, 20, 'Score basé sur la stabilité de l\'emploi'],
            ['debt_ratio', 1.3, 20, 'Score basé sur le ratio charges/revenus'],
            ['credit_history', 1.0, 10, 'Score basé sur l\'historique de crédit'],
            ['amount_ratio', 1.0, 10, 'Score basé sur le ratio montant demandé/revenus']
        ];
        
        foreach ($defaults as $d) {
            $stmt = $db->prepare("UPDATE scoring_criteria SET weight = ?, max_score = ?, description = ? WHERE code = ?");
            $stmt->execute([$d[1], $d[2], $d[3], $d[0]]);
        }
        
        setFlashMessage('success', 'Critères réinitialisés aux valeurs par défaut.');
        redirect('scoring.php');
    }
}

// Get scoring criteria
$stmt = $db->query("SELECT * FROM scoring_criteria ORDER BY id");
$criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get thresholds from settings
$stmt = $db->query("SELECT name, value FROM settings WHERE name IN ('approval_threshold', 'rejection_threshold')");
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['name']] = $row['value'];
}

$approval_threshold = $settings['approval_threshold'] ?? 70;
$rejection_threshold = $settings['rejection_threshold'] ?? 50;

// Calculate total max score and weights
$totalMaxScore = array_sum(array_column($criteria, 'max_score'));
$totalWeight = array_sum(array_column($criteria, 'weight'));

require_once '../includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Configuration du Scoring</h1>
            <p class="text-slate-600 mt-1">Gérez les critères et seuils de décision</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="index.php" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </a>
            <form method="POST" class="inline" onsubmit="return confirm('Réinitialiser tous les critères aux valeurs par défaut?');">
                <input type="hidden" name="action" value="reset_defaults">
                <button type="submit" class="btn btn-warning">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Réinitialiser
                </button>
            </form>
        </div>
    </div>

    <!-- Decision Thresholds -->
    <div class="card mb-8">
        <h2 class="text-xl font-semibold text-slate-900 mb-6">Seuils de Décision</h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="update_thresholds">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="approval_threshold" class="block text-sm font-medium text-slate-700 mb-1">
                        Seuil d'approbation automatique
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="range" id="approval_threshold" name="approval_threshold" 
                               min="0" max="100" step="1"
                               value="<?php echo $approval_threshold; ?>"
                               class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer"
                               oninput="document.getElementById('approval_value').textContent = this.value">
                        <span id="approval_value" class="w-12 text-center font-bold text-emerald-600">
                            <?php echo $approval_threshold; ?>
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">
                        Score >= ce seuil = Crédit approuvé automatiquement
                    </p>
                </div>
                
                <div>
                    <label for="rejection_threshold" class="block text-sm font-medium text-slate-700 mb-1">
                        Seuil de refus automatique
                    </label>
                    <div class="flex items-center gap-3">
                        <input type="range" id="rejection_threshold" name="rejection_threshold" 
                               min="0" max="100" step="1"
                               value="<?php echo $rejection_threshold; ?>"
                               class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer"
                               oninput="document.getElementById('rejection_value').textContent = this.value">
                        <span id="rejection_value" class="w-12 text-center font-bold text-red-600">
                            <?php echo $rejection_threshold; ?>
                        </span>
                    </div>
                    <p class="text-sm text-slate-500 mt-1">
                        Score < ce seuil = Crédit refusé automatiquement
                    </p>
                </div>
            </div>
            
            <!-- Threshold Visualization -->
            <div class="bg-slate-50 rounded-xl p-4">
                <p class="text-sm font-medium text-slate-700 mb-3">Visualisation des zones de décision:</p>
                <div class="relative h-8 bg-gradient-to-r from-red-500 via-amber-500 to-emerald-500 rounded-full">
                    <div class="absolute top-0 bottom-0 left-0 bg-red-500 rounded-l-full" 
                         style="width: <?php echo $rejection_threshold; ?>%"></div>
                    <div class="absolute top-0 bottom-0 right-0 bg-emerald-500 rounded-r-full" 
                         style="width: <?php echo 100 - $approval_threshold; ?>%"></div>
                    
                    <!-- Markers -->
                    <div class="absolute top-full mt-1 text-xs text-slate-600" style="left: 0%">0</div>
                    <div class="absolute top-full mt-1 text-xs text-red-600 font-medium transform -translate-x-1/2" 
                         style="left: <?php echo $rejection_threshold; ?>%"><?php echo $rejection_threshold; ?></div>
                    <div class="absolute top-full mt-1 text-xs text-emerald-600 font-medium transform -translate-x-1/2" 
                         style="left: <?php echo $approval_threshold; ?>%"><?php echo $approval_threshold; ?></div>
                    <div class="absolute top-full mt-1 text-xs text-slate-600" style="right: 0%">100</div>
                </div>
                <div class="flex justify-between mt-6 text-xs">
                    <span class="text-red-600 font-medium">Refusé</span>
                    <span class="text-amber-600 font-medium">Étude manuelle</span>
                    <span class="text-emerald-600 font-medium">Approuvé</span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Enregistrer les Seuils
            </button>
        </form>
    </div>

    <!-- Scoring Criteria -->
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-slate-900">Critères de Scoring</h2>
            <div class="text-sm text-slate-600">
                Score total max: <span class="font-bold"><?php echo $totalMaxScore; ?></span> points
            </div>
        </div>
        
        <div class="space-y-6">
            <?php foreach ($criteria as $c): ?>
            <form method="POST" class="border border-slate-200 rounded-xl p-4 hover:border-blue-300 transition-colors">
                <input type="hidden" name="action" value="update_criteria">
                <input type="hidden" name="criteria_id" value="<?php echo $c['id']; ?>">
                
                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                    <div class="lg:w-1/4">
                        <h3 class="font-semibold text-slate-900"><?php echo htmlspecialchars($c['name']); ?></h3>
                        <p class="text-sm text-slate-500">Code: <?php echo htmlspecialchars($c['code']); ?></p>
                    </div>
                    
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Poids</label>
                            <input type="number" name="weight" step="0.1" min="0" max="5"
                                   value="<?php echo $c['weight']; ?>"
                                   class="form-input text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Score Max</label>
                            <input type="number" name="max_score" min="1" max="50"
                                   value="<?php echo $c['max_score']; ?>"
                                   class="form-input text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Description</label>
                            <input type="text" name="description"
                                   value="<?php echo htmlspecialchars($c['description'] ?? ''); ?>"
                                   class="form-input text-sm">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Weight visualization -->
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">Contribution:</span>
                        <div class="flex-1 h-2 bg-slate-200 rounded-full">
                            <?php $contribution = ($c['max_score'] / $totalMaxScore) * 100; ?>
                            <div class="h-2 bg-blue-500 rounded-full" style="width: <?php echo $contribution; ?>%"></div>
                        </div>
                        <span class="text-xs font-medium text-slate-700"><?php echo number_format($contribution, 1); ?>%</span>
                    </div>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Help Section -->
    <div class="card mt-8 bg-blue-50 border-blue-200">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">Guide de Configuration</h3>
        <div class="prose prose-sm prose-blue max-w-none">
            <ul class="space-y-2 text-blue-800">
                <li><strong>Poids:</strong> Multiplicateur appliqué au score brut du critère. Un poids plus élevé donne plus d'importance au critère.</li>
                <li><strong>Score Max:</strong> Le score maximum qu'un client peut obtenir pour ce critère.</li>
                <li><strong>Seuil d'approbation:</strong> Score total au-dessus duquel un crédit est automatiquement approuvé.</li>
                <li><strong>Seuil de refus:</strong> Score total en-dessous duquel un crédit est automatiquement refusé.</li>
                <li><strong>Zone intermédiaire:</strong> Entre les deux seuils, le dossier nécessite une étude manuelle.</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
