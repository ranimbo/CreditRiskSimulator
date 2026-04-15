<?php
/**
 * Simulation History Page
 * Lists all credit simulations for the current user
 */

$pageTitle = 'Historique des Simulations';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/CreditRequest.php';
require_once __DIR__ . '/classes/ScoringEngine.php';

$creditRequestModel = new CreditRequest();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Filters
$filters = [
    'statut' => $_GET['statut'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Optionally restrict to current agent
// $filters['agent_id'] = getCurrentUserId();

// Get requests with pagination
$requests = $creditRequestModel->getAll($filters, $perPage, $offset);
$totalRequests = $creditRequestModel->count($filters);
$totalPages = ceil($totalRequests / $perPage);

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Historique des Simulations</h1>
            <p class="text-slate-600 mt-1"><?php echo $totalRequests; ?> simulation(s) au total</p>
        </div>
        <a href="simulation.php" class="btn btn-primary inline-flex items-center gap-2">
            <i data-feather="plus" class="w-4 h-4"></i>
            Nouvelle Simulation
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Recherche</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Nom du client, CIN ou N° Demande..."
                       class="form-input w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Décision</label>
                <select name="statut" class="form-input w-full px-4 py-2 border rounded-lg">
                    <option value="">Toutes</option>
                    <option value="ACCORDE" <?php echo $filters['statut'] === 'ACCORDE' ? 'selected' : ''; ?>>Approuvé</option>
                    <option value="REFUSE" <?php echo $filters['statut'] === 'REFUSE' ? 'selected' : ''; ?>>Refusé</option>
                    <option value="A_ANALYSER" <?php echo $filters['statut'] === 'A_ANALYSER' ? 'selected' : ''; ?>>À analyser</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary px-4 h-[42px]">
                    <i data-feather="search" class="w-5 h-5"></i>
                </button>
                <a href="history.php" class="btn btn-secondary px-4 h-[42px] flex items-center justify-center">
                    <i data-feather="x" class="w-5 h-5"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <?php if (empty($requests)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-feather="inbox" class="w-8 h-8 text-slate-400"></i>
            </div>
            <h3 class="text-xl font-medium text-slate-800 mb-2">Aucune simulation trouvée</h3>
            <p class="text-slate-500 mb-6">Commencez par créer une nouvelle simulation de crédit.</p>
            <a href="simulation.php" class="btn btn-primary inline-flex items-center gap-2">
                <i data-feather="plus" class="w-4 h-4"></i>
                Nouvelle Simulation
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Demande</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Décision</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($requests as $req): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center mr-3 text-primary-700 font-semibold text-sm">
                                    <?php echo strtoupper(substr($req['client_nom'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div class="font-medium text-slate-900">
                                        <?php echo htmlspecialchars($req['client_nom']); ?>
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        CIN: <?php echo htmlspecialchars($req['cin']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-semibold text-slate-900"><?php echo formatCurrency($req['montant_demande']); ?></div>
                            <div class="text-xs text-slate-500"><?php echo $req['duree']; ?> mois - <?php echo $req['type_credit']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($req['valeur_totale']): ?>
                            <div class="flex items-center">
                                <div class="w-16 bg-slate-200 rounded-full h-2 mr-2">
                                    <div class="h-2 rounded-full <?php echo str_replace('text-', 'bg-', explode(' ', ScoringEngine::getDecisionColorClass($req['resultat'] ?? ''))[0] ?? 'bg-primary-600'); ?>" 
                                         style="width: <?php echo $req['valeur_totale']; ?>%;">
                                    </div>
                                </div>
                                <span class="font-medium text-sm text-slate-700"><?php echo number_format($req['valeur_totale'], 0); ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($req['resultat']): ?>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo ScoringEngine::getDecisionColorClass($req['resultat']); ?>">
                                <?php echo ScoringEngine::getDecisionLabel($req['resultat']); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-slate-400">En cours</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <?php echo date('d/m/Y', strtotime($req['date_creation'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="result.php?id=<?php echo $req['id']; ?>" 
                               class="text-primary-600 hover:text-primary-800 p-2 ml-auto inline-block" title="Voir détails">
                                <i data-feather="eye" class="w-4 h-4"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-600">
                Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $perPage, $totalRequests); ?> sur <?php echo $totalRequests; ?> résultats
            </div>
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-3 py-1 border border-slate-300 rounded-md hover:bg-slate-100 transition-colors">
                    &laquo;
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-3 py-1 border rounded-md transition-colors <?php echo $i === $page ? 'bg-primary-600 text-white border-primary-600' : 'border-slate-300 hover:bg-slate-100 bg-white text-slate-700'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-3 py-1 border border-slate-300 rounded-md hover:bg-slate-100 transition-colors">
                    &raquo;
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
