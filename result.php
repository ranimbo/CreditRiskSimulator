<?php
/**
 * Résultat de Scoring — Style Amen Bank
 * 
 * Affiche le score global, la décision, le détail des critères,
 * et les facteurs favorables/défavorables.
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/CreditRequest.php';
require_once __DIR__ . '/classes/ScoringEngine.php';

$pageTitle = 'Résultat de Simulation';

// Récupérer l'ID de la demande
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$requestId) {
    setFlashMessage('error', 'ID de demande invalide.');
    header('Location: history.php');
    exit;
}

$creditRequestModel = new CreditRequest();
$request = $creditRequestModel->findById($requestId);

if (!$request) {
    setFlashMessage('error', 'Demande non trouvée.');
    header('Location: history.php');
    exit;
}

// Détails du score
$scoreDetails = [];
if (!empty($request['detail_par_critere'])) {
    $scoreDetails = json_decode($request['detail_par_critere'], true) ?: [];
}
$criteriaMeta = ScoringEngine::getCriteriaLabels();

// Styles selon la décision — Palette Amen Bank
$resultConfig = match($request['resultat']) {
    'ACCORDE' => [
        'class' => 'accorde',
        'icon' => 'check-circle',
        'color' => '#1A7F3C',
        'scoreColor' => '#1A7F3C',
    ],
    'REFUSE' => [
        'class' => 'refuse',
        'icon' => 'x-circle',
        'color' => '#C0392B',
        'scoreColor' => '#C0392B',
    ],
    'A_ANALYSER' => [
        'class' => 'analyser',
        'icon' => 'alert-triangle',
        'color' => '#B7950B',
        'scoreColor' => '#C8971F',
    ],
    default => [
        'class' => 'analyser',
        'icon' => 'help-circle',
        'color' => '#6B7280',
        'scoreColor' => '#6B7280',
    ],
};

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="dashboard.php">Accueil</a>
    <span class="separator">›</span>
    <a href="history.php">Simulations</a>
    <span class="separator">›</span>
    <span class="current">Résultat #<?php echo $requestId; ?></span>
</div>

<div class="max-w-4xl mx-auto">
    
    <!-- Bannière de décision -->
    <div class="decision-banner <?php echo $resultConfig['class']; ?> mb-8">
        <i data-feather="<?php echo $resultConfig['icon']; ?>" class="w-8 h-8"></i>
        <span><?php echo ScoringEngine::getDecisionLabel($request['resultat']); ?></span>
    </div>
    
    <p class="text-center text-[#6B7280] text-sm -mt-5 mb-8">
        Simulation effectuée le <?php echo date('d/m/Y à H:i', strtotime($request['date_creation'])); ?>
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Score Global -->
        <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] p-6">
            <h2 class="text-lg font-semibold text-[#003366] mb-6 flex items-center gap-3">
                <i data-feather="pie-chart" class="w-5 h-5 text-[#C8971F]"></i>
                Score Global
            </h2>
            
            <div class="text-center mb-6">
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-40 h-40 transform -rotate-90">
                        <circle cx="80" cy="80" r="70" stroke="#E2E8F0" stroke-width="12" fill="none"/>
                        <circle cx="80" cy="80" r="70" 
                                stroke="<?php echo $resultConfig['scoreColor']; ?>" 
                                stroke-width="12" 
                                fill="none"
                                stroke-dasharray="<?php echo (2 * pi() * 70); ?>"
                                stroke-dashoffset="<?php echo (2 * pi() * 70) * (1 - $request['valeur_totale'] / 100); ?>"
                                stroke-linecap="round"
                                style="transition: stroke-dashoffset 1s ease-out;"/>
                    </svg>
                    <div class="absolute text-center">
                        <span class="text-4xl font-bold text-[#003366]"><?php echo number_format($request['valeur_totale'], 0); ?></span>
                        <span class="block text-sm text-[#6B7280]">/100</span>
                    </div>
                </div>
            </div>

            <!-- Détail des critères -->
            <?php if (!empty($scoreDetails)): ?>
            <div class="space-y-4">
                <h3 class="font-medium text-[#003366] border-b border-[#E2E8F0] pb-2 text-sm">Détail des Critères</h3>
                <?php foreach ($scoreDetails as $criterionKey => $scoreValue): ?>
                <?php $meta = $criteriaMeta[$criterionKey] ?? ['label' => $criterionKey, 'max' => 25]; ?>
                <div>
                    <div class="flex justify-between mb-1.5">
                        <span class="text-sm text-[#6B7280]"><?php echo htmlspecialchars($meta['label']); ?></span>
                        <span class="text-sm font-semibold text-[#003366]"><?php echo number_format($scoreValue, 1); ?>/<?php echo $meta['max']; ?></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <?php 
                        $pct = $meta['max'] > 0 ? ($scoreValue / $meta['max']) * 100 : 0;
                        $barColor = $pct >= 70 ? '#1A7F3C' : ($pct >= 40 ? '#C8971F' : '#C0392B');
                        ?>
                        <div class="h-2 rounded-full transition-all duration-500" 
                             style="width: <?php echo $pct; ?>%; background-color: <?php echo $barColor; ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Détails de la demande -->
        <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] p-6">
            <h2 class="text-lg font-semibold text-[#003366] mb-6 flex items-center gap-3">
                <i data-feather="file-text" class="w-5 h-5 text-[#C8971F]"></i>
                Détails de la Demande
            </h2>

            <div class="space-y-4">
                <!-- Info client -->
                <div class="bg-[#F4F6F9] rounded-xl p-4">
                    <h3 class="font-medium text-[#003366] mb-2 flex items-center gap-2 text-sm">
                        <i data-feather="user" class="w-4 h-4 text-[#C8971F]"></i> Client
                    </h3>
                    <p class="text-base font-semibold text-[#333333]">
                        <?php echo htmlspecialchars($request['client_nom']); ?>
                    </p>
                    <p class="text-sm text-[#6B7280]">CIN: <?php echo htmlspecialchars($request['cin']); ?></p>
                </div>

                <!-- Détails crédit -->
                <div class="bg-[#F4F6F9] rounded-xl p-4">
                    <h3 class="font-medium text-[#003366] mb-3 flex items-center gap-2 text-sm">
                        <i data-feather="credit-card" class="w-4 h-4 text-[#C8971F]"></i> Crédit Demandé
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-[#6B7280]">Montant</p>
                            <p class="text-base font-semibold text-[#003366]"><?php echo formatCurrency($request['montant_demande']); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-[#6B7280]">Durée</p>
                            <p class="text-base font-semibold text-[#333333]"><?php echo $request['duree']; ?> mois</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-[#6B7280]">Type</p>
                            <p class="text-sm font-semibold text-[#333333]"><?php echo htmlspecialchars($request['type_credit']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Justification & Facteurs -->
    <?php if (!empty($request['justification'])): ?>
    <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] p-6 mt-8">
        <h2 class="text-lg font-semibold text-[#003366] mb-4 flex items-center gap-3">
            <i data-feather="info" class="w-5 h-5 text-[#C8971F]"></i>
            Justification & Recommandations
        </h2>
        <p class="text-[#333333] leading-relaxed bg-[#F4F6F9] p-4 rounded-xl text-sm">
            <?php echo nl2br(htmlspecialchars($request['justification'])); ?>
        </p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <?php 
                $facteurs_fav = json_decode($request['facteurs_favorables'] ?? '[]', true) ?: [];
                $facteurs_defav = json_decode($request['facteurs_defavorables'] ?? '[]', true) ?: [];
            ?>
            <!-- Facteurs favorables -->
            <div class="p-4 border border-[#E6F4EA] bg-[#E6F4EA]/50 rounded-xl">
                <h3 class="font-medium text-[#1A7F3C] mb-2 text-sm flex items-center gap-2">
                    <i data-feather="thumbs-up" class="w-4 h-4"></i>
                    Facteurs Favorables
                </h3>
                <ul class="list-disc list-inside text-sm text-[#1A7F3C]/80 space-y-1">
                    <?php if (empty($facteurs_fav)): ?>
                    <li>Aucun</li>
                    <?php else: ?>
                    <?php foreach ($facteurs_fav as $f): ?>
                        <li><?php echo htmlspecialchars($f); ?></li>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Facteurs défavorables -->
            <div class="p-4 border border-[#FDEEEE] bg-[#FDEEEE]/50 rounded-xl">
                <h3 class="font-medium text-[#C0392B] mb-2 text-sm flex items-center gap-2">
                    <i data-feather="thumbs-down" class="w-4 h-4"></i>
                    Facteurs Défavorables
                </h3>
                <ul class="list-disc list-inside text-sm text-[#C0392B]/80 space-y-1">
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

    <!-- Boutons d'action -->
    <div class="flex flex-wrap gap-4 justify-center mt-8">
        <button onclick="window.print()" class="btn btn-secondary">
            <i data-feather="printer" class="w-4 h-4"></i>
            Imprimer
        </button>
        <a href="simulation.php?client_id=<?php echo $request['client_id']; ?>" class="btn btn-gold">
            <i data-feather="refresh-cw" class="w-4 h-4"></i>
            Nouvelle Simulation
        </a>
        <a href="history.php" class="btn btn-primary">
            <i data-feather="clock" class="w-4 h-4"></i>
            Historique
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
