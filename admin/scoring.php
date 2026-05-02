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
        $poids       = (float)$_POST['poids'];
        $points_max  = (int)$_POST['points_max'];
        $libelle     = trim($_POST['libelle']);
        
        $stmt = $db->getConnection()->prepare(
            "UPDATE critere_scoring SET poids = ?, points_max = ?, libelle = ? WHERE id = ?"
        );
        if ($stmt->execute([$poids, $points_max, $libelle, $criteria_id])) {
            setFlashMessage('success', 'Critère mis à jour avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour.');
        }
        header('Location: scoring.php');
        exit;
    }
    
    if ($action === 'reset_defaults') {
        $defaults = [
            ["Revenu Mensuel",            1.0, 25, 1],
            ["Taux d'endettement",        1.0, 25, 2],
            ["Situation professionnelle", 1.0, 15, 3],
            ["Ancienneté d'emploi",       1.0, 10, 4],
            ["Historique crédit",         1.0, 15, 5],
            ["Âge",                       1.0, 10, 6],
        ];
        
        $db->getConnection()->exec("TRUNCATE TABLE critere_scoring");
        foreach ($defaults as $d) {
            $stmt = $db->getConnection()->prepare(
                "INSERT INTO critere_scoring (libelle, poids, points_max, actif, ordre) VALUES (?, ?, ?, 1, ?)"
            );
            $stmt->execute([$d[0], $d[1], $d[2], $d[3]]);
        }
        
        setFlashMessage('success', 'Critères réinitialisés aux valeurs par défaut.');
        header('Location: scoring.php');
        exit;
    }
}

// Get scoring criteria
$criteria      = $db->fetchAll("SELECT * FROM critere_scoring ORDER BY ordre ASC, id ASC");
$totalMaxScore = array_sum(array_column($criteria, 'points_max'));

require_once '../includes/header.php';
?>

<div class="max-w-5xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-[#003366]">Configuration du Scoring</h1>
            <p class="text-[#6B7280] mt-1">Gérez les critères et leurs poids</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="index.php" class="btn btn-secondary">
                <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
                Retour
            </a>
            <form method="POST" class="inline"
                  onsubmit="return confirm('Réinitialiser tous les critères aux valeurs par défaut ?');">
                <input type="hidden" name="action" value="reset_defaults">
                <button type="submit" class="btn btn-gold flex items-center gap-2">
                    <i data-feather="refresh-cw" class="w-4 h-4"></i>
                    Réinitialiser
                </button>
            </form>
        </div>
    </div>

    <!-- Scoring Criteria -->
    <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-[#003366]">Critères de Scoring</h2>
            <div class="text-sm text-[#6B7280]">
                Score total max : <span class="font-bold text-[#003366]"><?php echo $totalMaxScore; ?></span> pts
            </div>
        </div>

        <div class="space-y-5">
            <?php if (empty($criteria)): ?>
            <div class="text-center py-10 text-[#6B7280]">
                Aucun critère configuré. Cliquez sur <strong>Réinitialiser</strong>.
            </div>
            <?php endif; ?>

            <?php foreach ($criteria as $c): ?>
            <form method="POST" class="border border-[#E2E8F0] rounded-xl p-5 hover:border-[#C8971F]/40 transition-colors">
                <input type="hidden" name="action"      value="update_criteria">
                <input type="hidden" name="criteria_id" value="<?php echo $c['id']; ?>">

                <div class="flex flex-col md:flex-row md:items-center gap-4">
                    <!-- Libellé -->
                    <div class="md:w-1/3">
                        <label class="block text-xs font-medium text-[#6B7280] mb-1">Libellé du Critère</label>
                        <input type="text" name="libelle"
                               value="<?php echo htmlspecialchars($c['libelle'] ?? ''); ?>"
                               class="form-input text-sm font-semibold text-[#003366]">
                    </div>

                    <!-- Poids + Points max -->
                    <div class="flex-1 flex gap-4">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-[#6B7280] mb-1">Poids</label>
                            <input type="number" name="poids" step="0.1" min="0" max="10"
                                   value="<?php echo $c['poids']; ?>"
                                   class="form-input text-sm">
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-[#6B7280] mb-1">Points Max</label>
                            <input type="number" name="points_max" min="1" max="100"
                                   value="<?php echo $c['points_max']; ?>"
                                   class="form-input text-sm">
                        </div>
                    </div>

                    <!-- Save button -->
                    <div class="mt-4 md:mt-5">
                        <button type="submit" class="btn btn-primary px-5 flex items-center gap-2">
                            <i data-feather="save" class="w-4 h-4"></i>
                            Sauvegarder
                        </button>
                    </div>
                </div>

                <!-- Barre de contribution -->
                <div class="mt-4 pt-3 border-t border-[#F4F6F9]">
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-[#6B7280] whitespace-nowrap">Contribution :</span>
                        <div class="flex-1 h-2 bg-[#F4F6F9] rounded-full overflow-hidden">
                            <?php $pct = $totalMaxScore > 0 ? ($c['points_max'] / $totalMaxScore) * 100 : 0; ?>
                            <div class="h-2 bg-[#003366] rounded-full" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                        <span class="text-xs font-medium text-[#003366] w-12 text-right">
                            <?php echo number_format($pct, 1); ?>%
                        </span>
                    </div>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Guide -->
    <div class="bg-[#EBF5FF] border border-[#003366]/20 rounded-xl p-6 mt-8">
        <h3 class="text-base font-semibold text-[#003366] mb-3 flex items-center gap-2">
            <i data-feather="help-circle" class="w-5 h-5"></i>
            Guide de Configuration
        </h3>
        <div class="text-sm text-[#003366]/80 space-y-1.5">
            <p><strong>Poids :</strong> multiplicateur appliqué au score de base du critère.</p>
            <p><strong>Points Max :</strong> contribution maximale de ce critère au score total sur 100.</p>
            <p><strong>Libellé :</strong> nom affiché dans les rapports de simulation.</p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>