<?php
/**
 * Simulation History Page
 * Lists all credit simulations for the current user
 */

require_once 'includes/auth.php';
requireLogin();

require_once 'classes/CreditRequest.php';

$pageTitle = 'Historique des Simulations';

$creditRequest = new CreditRequest();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filters
$filters = [
    'decision' => $_GET['decision'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Get requests with pagination
$userId = $_SESSION['user_id'];
$requests = $creditRequest->getUserRequests($userId, $filters, $perPage, $offset);
$totalRequests = $creditRequest->countUserRequests($userId, $filters);
$totalPages = ceil($totalRequests / $perPage);

include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Historique des Simulations</h1>
            <p class="text-gray-600 mt-1"><?php echo $totalRequests; ?> simulation(s) au total</p>
        </div>
        <a href="simulation.php" class="btn-primary mt-4 md:mt-0">
            <i class="fas fa-plus mr-2"></i>
            Nouvelle Simulation
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Nom du client..."
                       class="form-input w-full">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Décision</label>
                <select name="decision" class="form-select w-full">
                    <option value="">Toutes</option>
                    <option value="approved" <?php echo $filters['decision'] === 'approved' ? 'selected' : ''; ?>>Approuvé</option>
                    <option value="rejected" <?php echo $filters['decision'] === 'rejected' ? 'selected' : ''; ?>>Refusé</option>
                    <option value="manual_review" <?php echo $filters['decision'] === 'manual_review' ? 'selected' : ''; ?>>Étude manuelle</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>"
                       class="form-input w-full">
            </div>
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>"
                           class="form-input w-full">
                </div>
                <button type="submit" class="btn-primary px-4">
                    <i class="fas fa-search"></i>
                </button>
                <a href="history.php" class="btn-outline px-4">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <?php if (empty($requests)): ?>
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-gray-300 text-5xl mb-4"></i>
            <h3 class="text-xl font-medium text-gray-600 mb-2">Aucune simulation trouvée</h3>
            <p class="text-gray-500 mb-6">Commencez par créer une nouvelle simulation de crédit.</p>
            <a href="simulation.php" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Nouvelle Simulation
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Client
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Montant
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Durée
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Score
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Décision
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Date
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($requests as $req): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                    <span class="text-primary font-semibold">
                                        <?php echo strtoupper(substr($req['first_name'], 0, 1) . substr($req['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($req['email']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-semibold text-gray-900"><?php echo formatCurrency($req['amount']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                            <?php echo $req['duration']; ?> mois
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="h-2 rounded-full" 
                                         style="width: <?php echo $req['total_score']; ?>%; background-color: <?php echo getScoreColorHistory($req['total_score']); ?>">
                                    </div>
                                </div>
                                <span class="font-medium"><?php echo number_format($req['total_score'], 1); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $badgeClass = '';
                            switch ($req['decision']) {
                                case 'approved':
                                    $badgeClass = 'bg-green-100 text-green-800';
                                    break;
                                case 'rejected':
                                    $badgeClass = 'bg-red-100 text-red-800';
                                    break;
                                case 'manual_review':
                                    $badgeClass = 'bg-yellow-100 text-yellow-800';
                                    break;
                            }
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $badgeClass; ?>">
                                <?php echo getDecisionLabel($req['decision']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                            <?php echo formatDateTime($req['created_at']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex justify-end space-x-2">
                                <a href="result.php?id=<?php echo $req['id']; ?>" 
                                   class="text-primary hover:text-primary-dark" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="pdf-report.php?id=<?php echo $req['id']; ?>" 
                                   class="text-red-500 hover:text-red-700" title="PDF" target="_blank">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-gray-50 border-t flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $perPage, $totalRequests); ?> sur <?php echo $totalRequests; ?> résultats
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-4 py-2 border rounded-lg transition-colors <?php echo $i === $page ? 'bg-primary text-white' : 'hover:bg-gray-100'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-4 py-2 border rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
function getScoreColorHistory($score) {
    if ($score >= 70) return '#10b981';
    if ($score >= 50) return '#f59e0b';
    return '#ef4444';
}

include 'includes/footer.php';
?>
