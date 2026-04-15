<?php
/**
 * Clients List Page
 * 
 * Display all clients with search, filter, and pagination.
 */

$pageTitle = 'Gestion des clients';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Client.php';

$clientModel = new Client();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$situation_pro = $_GET['situation_pro'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Build filters
$filters = [];
if ($search) $filters['search'] = $search;
if ($situation_pro) $filters['situation_pro'] = $situation_pro;

// Get data
$totalClients = $clientModel->count($filters);
$pagination = getPagination($totalClients, $page, $perPage);
$clients = $clientModel->getAll($filters, $perPage, $pagination['offset']);

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['csrf_token'])) {
    if (validateCsrfToken($_GET['csrf_token'])) {
        try {
            $clientModel->delete((int)$_GET['delete']);
            setFlashMessage('success', 'Client supprimé avec succès.');
        } catch (Exception $e) {
            setFlashMessage('error', $e->getMessage());
        }
        header('Location: clients.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Gestion des clients</h1>
        <p class="text-slate-500 mt-1"><?php echo number_format($totalClients); ?> client(s) au total</p>
    </div>
    <a href="client-form.php" class="btn btn-primary">
        <i data-feather="plus" class="w-4 h-4"></i>
        Nouveau client
    </a>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
    <form method="GET" action="clients.php" class="flex flex-col sm:flex-row gap-4">
        <div class="flex-1">
            <div class="relative">
                <i data-feather="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400"></i>
                <input 
                    type="text" 
                    name="search" 
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Rechercher par nom ou CIN..."
                    class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
            </div>
        </div>
        <div class="sm:w-48">
            <select 
                name="situation_pro" 
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            >
                <option value="">Toutes les situations</option>
                <?php foreach (Client::getSituations() as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo $situation_pro === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">
            <i data-feather="filter" class="w-4 h-4"></i>
            Filtrer
        </button>
        <?php if ($search || $situation_pro): ?>
        <a href="clients.php" class="btn btn-secondary">
            <i data-feather="x" class="w-4 h-4"></i>
            Réinitialiser
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Clients Table -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <?php if (empty($clients)): ?>
    <div class="p-12 text-center">
        <i data-feather="users" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
        <h3 class="text-lg font-medium text-slate-900 mb-1">Aucun client trouvé</h3>
        <p class="text-slate-500 mb-4">
            <?php echo $search || $situation_pro ? 'Aucun client ne correspond à vos critères de recherche.' : 'Commencez par ajouter votre premier client.'; ?>
        </p>
        <?php if (!$search && !$situation_pro): ?>
        <a href="client-form.php" class="btn btn-primary">
            <i data-feather="plus" class="w-4 h-4"></i>
            Ajouter un client
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>CIN</th>
                    <th>Situation</th>
                    <th>Revenu mensuel net</th>
                    <th>Taux d'endettement</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                <?php 
                    $debtRatio = calculateDebtRatio($client['charges_mensuelles'], $client['revenu_mensuel_net']);
                    $age = calculateAge($client['date_naissance']);
                ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-primary-600">
                                    <?php echo strtoupper(substr($client['nom'], 0, 2)); ?>
                                </span>
                            </div>
                            <div>
                                <p class="font-medium text-slate-900">
                                    <?php echo htmlspecialchars($client['nom']); ?>
                                </p>
                                <p class="text-sm text-slate-500">
                                    <?php echo $age; ?> ans
                                </p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="font-mono text-sm"><?php echo htmlspecialchars($client['cin']); ?></span>
                    </td>
                    <td>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                            <?php echo match($client['situation_pro']) {
                                'CDI', 'FONCTIONNAIRE' => 'bg-green-100 text-green-800',
                                'INDEPENDANT' => 'bg-blue-100 text-blue-800',
                                default => 'bg-red-100 text-red-800'
                            }; ?>">
                            <?php echo htmlspecialchars($client['situation_pro']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="font-medium"><?php echo formatCurrency($client['revenu_mensuel_net']); ?></span>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-16 bg-slate-200 rounded-full h-2">
                                <div class="<?php echo $debtRatio < 30 ? 'bg-green-500' : ($debtRatio < 50 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-2 rounded-full" 
                                     style="width: <?php echo min(100, $debtRatio); ?>%"></div>
                            </div>
                            <span class="text-sm <?php echo $debtRatio < 30 ? 'text-green-600' : ($debtRatio < 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                <?php echo formatPercentage($debtRatio, 1); ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center justify-end gap-2">
                            <a href="simulation.php?client_id=<?php echo $client['id']; ?>" 
                               class="p-2 text-slate-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                               title="Nouvelle simulation">
                                <i data-feather="calculator" class="w-4 h-4"></i>
                            </a>
                            <a href="client-form.php?id=<?php echo $client['id']; ?>" 
                               class="p-2 text-slate-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                               title="Modifier">
                                <i data-feather="edit-2" class="w-4 h-4"></i>
                            </a>
                            <a href="clients.php?delete=<?php echo $client['id']; ?>&csrf_token=<?php echo generateCsrfToken(); ?>" 
                               class="p-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                               title="Supprimer"
                               data-delete-confirm="Êtes-vous sûr de vouloir supprimer ce client ?">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
        <p class="text-sm text-slate-500">
            Affichage de <?php echo $pagination['offset'] + 1; ?> à <?php echo min($pagination['offset'] + $perPage, $totalClients); ?> sur <?php echo $totalClients; ?> clients
        </p>
        <div class="pagination">
            <?php if ($pagination['has_prev']): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&situation_pro=<?php echo urlencode($situation_pro); ?>" 
               class="pagination-item">
                <i data-feather="chevron-left" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($pagination['total_pages'], $page + 2); $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&situation_pro=<?php echo urlencode($situation_pro); ?>" 
               class="pagination-item <?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($pagination['has_next']): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&situation_pro=<?php echo urlencode($situation_pro); ?>" 
               class="pagination-item">
                <i data-feather="chevron-right" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
