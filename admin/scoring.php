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
        $poids = (float)$_POST['poids'];
        $points_max = (int)$_POST['points_max'];
        $libelle = trim($_POST['libelle']);
        
        $stmt = $db->prepare("UPDATE critere_scoring SET poids = ?, points_max = ?, libelle = ? WHERE id = ?");
        if ($stmt->execute([$poids, $points_max, $libelle, $criteria_id])) {
            setFlashMessage('success', 'Critère mis à jour avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour.');
        }
        header('Location: scoring.php');
        exit;
    }
    
    if ($action === 'reset_defaults') {
        // Reset criteria to default values
        $defaults = [
            ['Revenu Mensuel', 1.0, 25, 1],
            ['Taux d\'endettement', 1.0, 25, 2],
            ['Situation professionnelle', 1.0, 15, 3],
            ['Ancienneté d\'emploi', 1.0, 10, 4],
            ['Historique crédit', 1.0, 15, 5],
            ['Âge', 1.0, 10, 6]
        ];
        
        $db->execute("TRUNCATE TABLE critere_scoring");
        foreach ($defaults as $d) {
            $stmt = $db->prepare("INSERT INTO critere_scoring (libelle, poids, points_max, actif, ordre) VALUES (?, ?, ?, 1, ?)");
            $stmt->execute([$d[0], $d[1], $d[2], $d[3]]);
        }
        
        setFlashMessage('success', 'Critères réinitialisés aux valeurs par défaut.');
        header('Location: scoring.php');
        exit;
    }
}

// Get scoring criteria
$stmt = $db->query("SELECT * FROM critere_scoring ORDER BY ordre ASC, id ASC");
$criteria = [];
if ($stmt) {
    $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate total max score and weights
$totalMaxScore = array_sum(array_column($criteria, 'points_max'));
$totalWeight = array_sum(array_column($criteria, 'poids'));

require_once '../includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Configuration du Scoring</h1>
            <p class="text-slate-600 mt-1">Gérez les critères de décision (nouveau schéma)</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="index.php" class="btn btn-secondary">
                <i data-feather="arrow-left" class="w-5 h-5 mr-2"></i>
                Retour
            </a>
            <form method="POST" class="inline" onsubmit="return confirm('Réinitialiser tous les critères aux valeurs par défaut?');">
                <input type="hidden" name="action" value="reset_defaults">
                <button type="submit" class="btn bg-yellow-500 hover:bg-yellow-600 text-white flex items-center justify-center p-2 rounded-lg">
                    <i data-feather="refresh-cw" class="w-5 h-5 mr-2"></i>
                    Réinitialiser
                </button>
            </form>
        </div>
    </div>

    <!-- Scoring Criteria -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-slate-900">Critères de Scoring</h2>
            <div class="text-sm text-slate-600">
                Score total max: <span class="font-bold"><?php echo $totalMaxScore; ?></span> points
            </div>
        </div>
        
        <div class="space-y-6">
            <?php if (empty($criteria)): ?>
            <div class="text-center py-8 text-slate-500">
                Aucun critère configuré. Cliquez sur Réinitialiser.
            </div>
            <?php endif; ?>

            <?php foreach ($criteria as $c): ?>
            <form method="POST" class="border border-slate-200 rounded-xl p-4 hover:border-primary-300 transition-colors">
                <input type="hidden" name="action" value="update_criteria">
                <input type="hidden" name="criteria_id" value="<?php echo $c['id']; ?>">
                
                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <div class="md:w-1/3">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Libellé du Critère</label>
                        <input type="text" name="libelle"
                               value="<?php echo htmlspecialchars($c['libelle'] ?? ''); ?>"
                               class="form-input text-sm font-semibold text-slate-900">
                    </div>
                    
                    <div class="flex-1 flex gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Poids</label>
                            <input type="number" name="poids" step="0.1" min="0" max="10"
                                   value="<?php echo $c['poids']; ?>"
                                   class="form-input text-sm">
                        </div>
                        
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-slate-500 mb-1">Points Max</label>
                            <input type="number" name="points_max" min="1" max="100"
                                   value="<?php echo $c['points_max']; ?>"
                                   class="form-input text-sm">
                        </div>
                    </div>
                    
                    <div class="mt-4 md:mt-0 pt-4 md:pt-0">
                        <button type="submit" class="btn btn-primary w-full md:w-auto h-full px-4 rounded-lg flex items-center justify-center">
                            <i data-feather="save" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Weight visualization -->
                <div class="mt-4 pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 whitespace-nowrap">Contribution finale:</span>
                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                            <?php $contribution = $totalMaxScore > 0 ? ($c['points_max'] / $totalMaxScore) * 100 : 0; ?>
                            <div class="h-2 bg-primary-500 rounded-full" style="width: <?php echo $contribution; ?>%"></div>
                        </div>
                        <span class="text-xs font-medium text-slate-700 w-12 text-right"><?php echo number_format($contribution, 1); ?>%</span>
                    </div>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Help Section -->
    <div class="bg-primary-50 border border-primary-200 rounded-xl p-6 mt-8">
        <h3 class="text-lg font-semibold text-primary-900 mb-4 flex items-center gap-2">
            <i data-feather="help-circle" class="w-5 h-5"></i>
            Guide de Configuration
        </h3>
        <div class="text-sm text-primary-800 space-y-2">
            <p><strong>Poids:</strong> Multiplicateur appliqué au score de base du critère (non encore entièrement lié au moteur).</p>
            <p><strong>Points Max:</strong> Nombre maximum de points qu'apporte ce critère au score total sur 100.</p>
            <p><strong>Libellé:</strong> Nom d'affichage du critère dans les rapports.</p>
            <p><em>Note:</em> Après modification, les futures simulations prendront en compte ces paramètres de base.</p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
