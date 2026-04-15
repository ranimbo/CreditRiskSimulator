<?php
/**
 * Result Display Page
 * Shows the credit simulation result with score breakdown
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/CreditRequest.php';
require_once __DIR__ . '/classes/ScoringEngine.php';

$pageTitle = 'Résultat de Simulation';

// Get request ID
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$requestId) {
    setFlashMessage('error', 'ID de demande invalide.');
    header('Location: history.php');
    exit;
}

// Get the credit request with full details
$creditRequestModel = new CreditRequest();
$request = $creditRequestModel->findById($requestId);

if (!$request) {
    setFlashMessage('error', 'Demande non trouvée.');
    header('Location: history.php');
    exit;
}

// Parse score details if JSON
$scoreDetails = [];
if (!empty($request['detail_par_critere'])) {
    $scoreDetails = json_decode($request['detail_par_critere'], true) ?: [];
}
// Get criteria metadata
$criteriaMeta = ScoringEngine::getCriteriaLabels();

// Determine result styling
$resultClass = '';
$resultIcon = '';
$resultBg = '';

switch ($request['resultat']) {
    case 'ACCORDE':
        $resultClass = 'text-green-600';
        $resultIcon = 'check-circle';
        $resultBg = 'bg-green-50 border-green-200';
        break;
    case 'REFUSE':
        $resultClass = 'text-red-600';
        $resultIcon = 'x-circle';
        $resultBg = 'bg-red-50 border-red-200';
        break;
    case 'A_ANALYSER':
        $resultClass = 'text-yellow-600';
        $resultIcon = 'alert-triangle';
        $resultBg = 'bg-yellow-50 border-yellow-200';
        break;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Result Header -->
    <div class="<?php echo $resultBg; ?> border-2 rounded-xl p-8 mb-8 text-center flex flex-col items-center">
        <i data-feather="<?php echo $resultIcon; ?>" class="w-16 h-16 <?php echo $resultClass; ?> mb-4"></i>
        <h1 class="text-3xl font-bold <?php echo $resultClass; ?> mb-2">
            <?php echo ScoringEngine::getDecisionLabel($request['resultat']); ?>
        </h1>
        <p class="text-slate-600">
            Simulation effectuée le <?php echo date('d/m/Y H:i', strtotime($request['date_creation'])); ?>
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Score Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-semibold text-slate-800 mb-6 flex items-center gap-3">
                <i data-feather="pie-chart" class="w-5 h-5 text-primary-600"></i>
                Score Global
            </h2>
            
            <div class="text-center mb-6">
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-40 h-40 transform -rotate-90">
                        <circle cx="80" cy="80" r="70" stroke="#e5e7eb" stroke-width="12" fill="none"/>
                        <circle cx="80" cy="80" r="70" 
                                stroke="<?php echo getScoreColor($request['valeur_totale']); ?>" 
                                stroke-width="12" 
                                fill="none"
                                stroke-dasharray="<?php echo (2 * pi() * 70); ?>"
                                stroke-dashoffset="<?php echo (2 * pi() * 70) * (1 - $request['valeur_totale'] / 100); ?>"
                                stroke-linecap="round"/>
                    </svg>
                    <span class="absolute text-4xl font-bold text-slate-800">
                        <?php echo number_format($request['valeur_totale'], 0); ?>
                    </span>
                </div>
                <p class="text-slate-500 mt-2">sur 100 points</p>
            </div>

            <!-- Score Breakdown -->
            <?php if (!empty($scoreDetails)): ?>
            <div class="space-y-4">
                <h3 class="font-medium text-slate-700 border-b border-slate-100 pb-2">Détail des Critères</h3>
                <?php foreach ($scoreDetails as $criterionKey => $scoreValue): ?>
                <?php $meta = $criteriaMeta[$criterionKey] ?? ['label' => $criterionKey, 'max' => 25]; ?>
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm text-slate-600"><?php echo htmlspecialchars($meta['label']); ?></span>
                            <span class="text-sm font-medium"><?php echo number_format($scoreValue, 1); ?>/<?php echo $meta['max']; ?></span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-500" 
                                 style="width: <?php echo ($scoreValue / $meta['max']) * 100; ?>%; background-color: <?php echo getScoreColor(($scoreValue / $meta['max']) * 100); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Request Details -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-semibold text-slate-800 mb-6 flex items-center gap-3">
                <i data-feather="file-text" class="w-5 h-5 text-primary-600"></i>
                Détails de la Demande
            </h2>

            <div class="space-y-4">
                <!-- Client Info -->
                <div class="bg-slate-50 rounded-lg p-4">
                    <h3 class="font-medium text-slate-700 mb-3 flex items-center gap-2">
                        <i data-feather="user" class="w-4 h-4"></i> Client
                    </h3>
                    <p class="text-lg font-semibold text-slate-800">
                        <?php echo htmlspecialchars($request['client_nom']); ?>
                    </p>
                    <p class="text-sm text-slate-500">CIN: <?php echo htmlspecialchars($request['cin']); ?></p>
                </div>

                <!-- Credit Details -->
                <div class="bg-slate-50 rounded-lg p-4">
                    <h3 class="font-medium text-slate-700 mb-3 flex items-center gap-2">
                        <i data-feather="dollar-sign" class="w-4 h-4"></i> Crédit Demandé
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-slate-500">Montant</p>
                            <p class="text-lg font-semibold text-slate-800"><?php echo formatCurrency($request['montant_demande']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Durée</p>
                            <p class="text-lg font-semibold text-slate-800"><?php echo $request['duree']; ?> mois</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-500">Type</p>
                            <p class="text-md font-semibold text-slate-800"><?php echo htmlspecialchars($request['type_credit']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Justification Section -->
    <?php if (!empty($request['justification'])): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mt-8">
        <h2 class="text-xl font-semibold text-slate-800 mb-4 flex items-center gap-3">
            <i data-feather="info" class="w-5 h-5 text-yellow-500"></i>
            Justification & Recommandations
        </h2>
        <p class="text-slate-700 leading-relaxed bg-slate-50 p-4 rounded-lg">
            <?php echo nl2br(htmlspecialchars($request['justification'])); ?>
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <?php 
                $facteurs_fav = json_decode($request['facteurs_favorables'] ?? '[]', true) ?: [];
                $facteurs_defav = json_decode($request['facteurs_defavorables'] ?? '[]', true) ?: [];
            ?>
            <div class="p-4 border border-green-100 bg-green-50 rounded-lg">
                <h3 class="font-medium text-green-800 mb-2">Facteurs Favorables</h3>
                <ul class="list-disc list-inside text-sm text-green-700 space-y-1">
                    <?php if (empty($facteurs_fav)): ?>
                    <li>Aucun</li>
                    <?php else: ?>
                    <?php foreach ($facteurs_fav as $f): ?>
                        <li><?php echo htmlspecialchars($f); ?></li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="p-4 border border-red-100 bg-red-50 rounded-lg">
                <h3 class="font-medium text-red-800 mb-2">Facteurs Défavorables</h3>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    <?php if (empty($facteurs_defav)): ?>
                    <li>Aucun</li>
                    <?php else: ?>
                    <?php foreach ($facteurs_defav as $f): ?>
                        <li><?php echo htmlspecialchars($f); ?></li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4 justify-center mt-8">
        <button onclick="window.print()" class="btn btn-secondary inline-flex items-center gap-2">
            <i data-feather="printer" class="w-4 h-4"></i>
            Imprimer
        </button>
        <a href="simulation.php?client_id=<?php echo $request['client_id']; ?>" 
           class="btn btn-primary inline-flex items-center gap-2">
            <i data-feather="refresh-cw" class="w-4 h-4"></i>
            Nouvelle Simulation
        </a>
        <a href="history.php" class="btn btn-secondary inline-flex items-center gap-2">
            <i data-feather="clock" class="w-4 h-4"></i>
            Historique
        </a>
    </div>
</div>

<?php
// Helper function for score color
function getScoreColor($score) {
    if ($score >= 70) return '#10b981'; // green
    if ($score >= 50) return '#f59e0b'; // yellow
    return '#ef4444'; // red
}

require_once __DIR__ . '/includes/footer.php';
?>
