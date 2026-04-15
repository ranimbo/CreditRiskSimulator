<?php
/**
 * Dashboard Page
 * 
 * Main dashboard with statistics and recent activity.
 */

$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Client.php';

$db = Database::getInstance();

// Get statistics
$stats = [
    'total_clients' => (int) $db->fetchValue("SELECT COUNT(*) FROM client"),
    'total_requests' => (int) $db->fetchValue("SELECT COUNT(*) FROM demande_credit"),
    'approved' => (int) $db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'ACCORDE'"),
    'refused' => (int) $db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'REFUSE'"),
    'pending' => (int) $db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'en_attente'"),
    'in_review' => (int) $db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'A_ANALYSER'"),
    'total_amount_approved' => (float) $db->fetchValue("SELECT COALESCE(SUM(montant_demande), 0) FROM demande_credit WHERE statut = 'ACCORDE'"),
    'avg_score' => (float) $db->fetchValue("SELECT COALESCE(AVG(valeur_totale), 0) FROM score"),
];

// Calculate approval rate
$stats['approval_rate'] = $stats['total_requests'] > 0 
    ? round(($stats['approved'] / $stats['total_requests']) * 100, 1) 
    : 0;

// Get recent requests
$recentRequests = $db->fetchAll("
    SELECT dc.*, c.nom as client_nom, c.cin,
           s.valeur_totale, u.nom as agent_nom
    FROM demande_credit dc
    LEFT JOIN client c ON dc.client_id = c.id
    LEFT JOIN score s ON dc.id = s.demande_id
    LEFT JOIN agent_bancaire ab ON dc.agent_id = ab.id
    LEFT JOIN utilisateur u ON ab.id = u.id
    ORDER BY dc.date_creation DESC
    LIMIT 5
");

// Get recent clients
$clientModel = new Client();
$recentClients = $clientModel->getRecent(5);

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Tableau de bord</h1>
    <p class="text-slate-500 mt-1">Bienvenue, <?php echo htmlspecialchars($currentUser['nom']); ?>. Voici un aperçu de votre activité.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Clients -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Total Clients</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?php echo number_format($stats['total_clients']); ?></p>
            </div>
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                <i data-feather="users" class="w-6 h-6 text-primary-600"></i>
            </div>
        </div>
    </div>
    
    <!-- Total Requests -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Demandes de crédit</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?php echo number_format($stats['total_requests']); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i data-feather="file-text" class="w-6 h-6 text-blue-600"></i>
            </div>
        </div>
    </div>
    
    <!-- Approval Rate -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Taux d'approbation</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?php echo $stats['approval_rate']; ?>%</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i data-feather="trending-up" class="w-6 h-6 text-green-600"></i>
            </div>
        </div>
    </div>
    
    <!-- Average Score -->
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Score moyen</p>
                <p class="text-3xl font-bold text-slate-900 mt-1"><?php echo round($stats['avg_score']); ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i data-feather="bar-chart-2" class="w-6 h-6 text-yellow-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Request Status Summary -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200">
            <div class="p-6 border-b border-slate-200">
                <h2 class="text-lg font-semibold text-slate-900">Statut des demandes</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-lg">
                        <div class="text-2xl font-bold text-green-600"><?php echo $stats['approved']; ?></div>
                        <div class="text-sm text-green-700">Approuvées</div>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-lg">
                        <div class="text-2xl font-bold text-red-600"><?php echo $stats['refused']; ?></div>
                        <div class="text-sm text-red-700">Refusées</div>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <div class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending']; ?></div>
                        <div class="text-sm text-yellow-700">En attente</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $stats['in_review']; ?></div>
                        <div class="text-sm text-blue-700">À analyser</div>
                    </div>
                </div>
                
                <!-- Progress bars -->
                <div class="mt-6 space-y-3">
                    <?php if ($stats['total_requests'] > 0): ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600">Approuvées</span>
                            <span class="text-slate-900 font-medium"><?php echo round(($stats['approved'] / $stats['total_requests']) * 100); ?>%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo ($stats['approved'] / $stats['total_requests']) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600">Refusées</span>
                            <span class="text-slate-900 font-medium"><?php echo round(($stats['refused'] / $stats['total_requests']) * 100); ?>%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo ($stats['refused'] / $stats['total_requests']) * 100; ?>%"></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-slate-500 text-center py-4">Aucune demande enregistrée</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="p-6 border-b border-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Actions rapides</h2>
        </div>
        <div class="p-6 space-y-3">
            <a href="client-form.php" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                    <i data-feather="user-plus" class="w-5 h-5 text-primary-600"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-900">Nouveau client</p>
                    <p class="text-sm text-slate-500">Ajouter un client</p>
                </div>
            </a>
            <a href="simulation.php" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i data-feather="calculator" class="w-5 h-5 text-green-600"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-900">Nouvelle simulation</p>
                    <p class="text-sm text-slate-500">Évaluer un crédit</p>
                </div>
            </a>
            <a href="history.php" class="flex items-center gap-3 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i data-feather="clock" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-900">Historique</p>
                    <p class="text-sm text-slate-500">Voir les demandes</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Requests -->
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Demandes récentes</h2>
            <a href="history.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Voir tout</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($recentRequests)): ?>
            <div class="p-6 text-center text-slate-500">
                <i data-feather="inbox" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                <p>Aucune demande récente</p>
            </div>
            <?php else: ?>
            <?php foreach ($recentRequests as $request): ?>
            <div class="p-4 hover:bg-slate-50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-slate-600">
                                <?php echo strtoupper(substr($request['client_nom'], 0, 2)); ?>
                            </span>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900">
                                <?php echo htmlspecialchars($request['client_nom']); ?>
                            </p>
                            <p class="text-sm text-slate-500">
                                #<?php echo htmlspecialchars($request['id']); ?> - <?php echo formatCurrency($request['montant_demande']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php echo getStatusBadge($request['statut'], 'credit'); ?>
                        <?php if ($request['valeur_totale']): ?>
                        <p class="text-sm <?php echo getScoreColorClass($request['valeur_totale']); ?> mt-1">
                            Score: <?php echo $request['valeur_totale']; ?>/100
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Clients -->
    <div class="bg-white rounded-xl border border-slate-200">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Clients récents</h2>
            <a href="clients.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Voir tout</a>
        </div>
        <div class="divide-y divide-slate-100">
            <?php if (empty($recentClients)): ?>
            <div class="p-6 text-center text-slate-500">
                <i data-feather="users" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                <p>Aucun client récent</p>
            </div>
            <?php else: ?>
            <?php foreach ($recentClients as $client): ?>
            <div class="p-4 hover:bg-slate-50 transition-colors">
                <div class="flex items-center justify-between">
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
                                <?php echo htmlspecialchars($client['cin']); ?> - <?php echo htmlspecialchars($client['situation_pro']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-slate-900"><?php echo formatCurrency($client['revenu_mensuel_net']); ?></p>
                        <p class="text-xs text-slate-500">revenu mensuel</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
