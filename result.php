<?php
/**
 * Result Display Page
 * Shows the credit simulation result with score breakdown
 */

require_once 'includes/auth.php';
requireLogin();

require_once 'classes/CreditRequest.php';
require_once 'classes/Client.php';

$pageTitle = 'Résultat de Simulation';

// Get request ID
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$requestId) {
    setFlashMessage('error', 'ID de demande invalide.');
    redirect('history.php');
}

// Get the credit request with full details
$creditRequest = new CreditRequest();
$request = $creditRequest->getRequestWithDetails($requestId);

if (!$request) {
    setFlashMessage('error', 'Demande non trouvée.');
    redirect('history.php');
}

// Get client details
$client = new Client();
$clientData = $client->getById($request['client_id']);

// Parse score details if JSON
$scoreDetails = [];
if (!empty($request['score_details'])) {
    $scoreDetails = json_decode($request['score_details'], true) ?: [];
}

// Determine result styling
$resultClass = '';
$resultIcon = '';
$resultBg = '';

switch ($request['decision']) {
    case 'approved':
        $resultClass = 'text-green-600';
        $resultIcon = 'fa-check-circle';
        $resultBg = 'bg-green-50 border-green-200';
        break;
    case 'rejected':
        $resultClass = 'text-red-600';
        $resultIcon = 'fa-times-circle';
        $resultBg = 'bg-red-50 border-red-200';
        break;
    case 'manual_review':
        $resultClass = 'text-yellow-600';
        $resultIcon = 'fa-exclamation-circle';
        $resultBg = 'bg-yellow-50 border-yellow-200';
        break;
}

include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Result Header -->
    <div class="<?php echo $resultBg; ?> border-2 rounded-xl p-8 mb-8 text-center">
        <i class="fas <?php echo $resultIcon; ?> text-6xl <?php echo $resultClass; ?> mb-4"></i>
        <h1 class="text-3xl font-bold <?php echo $resultClass; ?> mb-2">
            <?php echo getDecisionLabel($request['decision']); ?>
        </h1>
        <p class="text-gray-600">
            Simulation effectuée le <?php echo formatDateTime($request['created_at']); ?>
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Score Card -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-chart-pie text-primary mr-3"></i>
                Score Global
            </h2>
            
            <div class="text-center mb-6">
                <div class="relative inline-flex items-center justify-center">
                    <svg class="w-40 h-40 transform -rotate-90">
                        <circle cx="80" cy="80" r="70" stroke="#e5e7eb" stroke-width="12" fill="none"/>
                        <circle cx="80" cy="80" r="70" 
                                stroke="<?php echo getScoreColor($request['total_score']); ?>" 
                                stroke-width="12" 
                                fill="none"
                                stroke-dasharray="<?php echo (2 * pi() * 70); ?>"
                                stroke-dashoffset="<?php echo (2 * pi() * 70) * (1 - $request['total_score'] / 100); ?>"
                                stroke-linecap="round"/>
                    </svg>
                    <span class="absolute text-4xl font-bold text-gray-800">
                        <?php echo number_format($request['total_score'], 1); ?>
                    </span>
                </div>
                <p class="text-gray-500 mt-2">sur 100 points</p>
            </div>

            <!-- Score Breakdown -->
            <?php if (!empty($scoreDetails)): ?>
            <div class="space-y-4">
                <h3 class="font-medium text-gray-700 border-b pb-2">Détail des Critères</h3>
                <?php foreach ($scoreDetails as $criterion => $data): ?>
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($data['name'] ?? $criterion); ?></span>
                            <span class="text-sm font-medium"><?php echo number_format($data['score'], 1); ?>/<?php echo $data['max_score']; ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-500" 
                                 style="width: <?php echo ($data['score'] / $data['max_score']) * 100; ?>%; background-color: <?php echo getScoreColor(($data['score'] / $data['max_score']) * 100); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Request Details -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-file-invoice text-primary mr-3"></i>
                Détails de la Demande
            </h2>

            <div class="space-y-4">
                <!-- Client Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium text-gray-700 mb-3">
                        <i class="fas fa-user mr-2"></i>Client
                    </h3>
                    <p class="text-lg font-semibold text-gray-800">
                        <?php echo htmlspecialchars($clientData['first_name'] . ' ' . $clientData['last_name']); ?>
                    </p>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($clientData['email']); ?></p>
                </div>

                <!-- Credit Details -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium text-gray-700 mb-3">
                        <i class="fas fa-money-bill-wave mr-2"></i>Crédit Demandé
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Montant</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo formatCurrency($request['amount']); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Durée</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo $request['duration']; ?> mois</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Taux</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo number_format($request['interest_rate'], 2); ?>%</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Mensualité</p>
                            <p class="text-lg font-semibold text-gray-800"><?php echo formatCurrency($request['monthly_payment']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Purpose -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="font-medium text-gray-700 mb-2">
                        <i class="fas fa-tag mr-2"></i>Objet du Crédit
                    </h3>
                    <p class="text-gray-800"><?php echo getCreditPurposeLabel($request['purpose']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendation Section -->
    <?php if (!empty($request['recommendation'])): ?>
    <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-lightbulb text-yellow-500 mr-3"></i>
            Recommandation
        </h2>
        <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($request['recommendation'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4 justify-center mt-8">
        <a href="pdf-report.php?id=<?php echo $requestId; ?>" 
           class="btn-primary inline-flex items-center" target="_blank">
            <i class="fas fa-file-pdf mr-2"></i>
            Télécharger PDF
        </a>
        <a href="simulation.php?client_id=<?php echo $request['client_id']; ?>" 
           class="btn-secondary inline-flex items-center">
            <i class="fas fa-redo mr-2"></i>
            Nouvelle Simulation
        </a>
        <a href="history.php" class="btn-outline inline-flex items-center">
            <i class="fas fa-history mr-2"></i>
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

include 'includes/footer.php';
?>
