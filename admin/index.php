<?php
/**
 * Admin Dashboard
 * Overview of system statistics and recent activity
 */

require_once '../includes/auth.php';
requireAdmin();

require_once '../classes/User.php';
require_once '../classes/Client.php';
require_once '../classes/CreditRequest.php';

$pageTitle = 'Administration';

$user          = new User();
$client        = new Client();
$creditRequest = new CreditRequest();

// Get statistics
$stats = [
    'total_users'       => $user->count(),
    'total_clients'     => $client->count(),
    'total_simulations' => $creditRequest->count(),
    'approved'          => $creditRequest->countByDecision('approved'),
    'rejected'          => $creditRequest->countByDecision('rejected'),
    'review'            => $creditRequest->countByDecision('manual_review'),
];

$stats['approval_rate'] = $stats['total_simulations'] > 0
    ? round(($stats['approved'] / $stats['total_simulations']) * 100, 1)
    : 0;

// Recent activity
$recentSimulations = $creditRequest->getRecent(5);
$recentUsers       = $user->getRecent(5);

require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Tableau de Bord Administration</h1>
        <p class="text-slate-600 mt-1">Vue d'ensemble du système</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Users -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Utilisateurs</p>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['total_users']; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                </div>
            </div>
            <a href="users.php" class="mt-4 text-sm text-blue-600 hover:text-blue-800 inline-flex items-center">
                Gérer les utilisateurs
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Total Clients -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Clients</p>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['total_clients']; ?></p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <a href="../clients.php" class="mt-4 text-sm text-emerald-600 hover:text-emerald-800 inline-flex items-center">
                Voir les clients
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Total Simulations -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Simulations</p>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['total_simulations']; ?></p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <a href="../history.php" class="mt-4 text-sm text-amber-600 hover:text-amber-800 inline-flex items-center">
                Voir l'historique
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Approval Rate -->
        <div class="card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600">Taux d'approbation</p>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['approval_rate']; ?>%</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex gap-4 text-sm">
                <span class="text-emerald-600"><?php echo $stats['approved']; ?> approuvés</span>
                <span class="text-red-600"><?php echo $stats['rejected']; ?> refusés</span>
            </div>
        </div>
    </div>

    <!-- Decision Distribution + Quick Actions -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="card">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Distribution des Décisions</h3>
            <div class="space-y-4">
                <?php
                $total = $stats['total_simulations'];
                $approvedPct = $total > 0 ? ($stats['approved'] / $total) * 100 : 0;
                $reviewPct   = $total > 0 ? ($stats['review']   / $total) * 100 : 0;
                $rejectedPct = $total > 0 ? ($stats['rejected'] / $total) * 100 : 0;
                ?>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-600">Approuvés</span>
                        <span class="font-medium text-emerald-600"><?php echo $stats['approved']; ?></span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="bg-emerald-500 h-3 rounded-full" style="width: <?php echo $approvedPct; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-600">En révision</span>
                        <span class="font-medium text-amber-600"><?php echo $stats['review']; ?></span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="bg-amber-500 h-3 rounded-full" style="width: <?php echo $reviewPct; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-600">Refusés</span>
                        <span class="font-medium text-red-600"><?php echo $stats['rejected']; ?></span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-3">
                        <div class="bg-red-500 h-3 rounded-full" style="width: <?php echo $rejectedPct; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card lg:col-span-2">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Actions Rapides</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="users.php?action=add" class="p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Nouvel Agent</span>
                </a>
                <a href="scoring.php" class="p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto text-emerald-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Scoring</span>
                </a>
                <a href="export.php" class="p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto text-amber-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Exporter</span>
                </a>
                <a href="settings.php" class="p-4 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors text-center">
                    <svg class="w-8 h-8 mx-auto text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    <span class="text-sm font-medium text-slate-700">Paramètres</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Simulations -->
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Simulations Récentes</h3>
                <a href="../history.php" class="text-sm text-blue-600 hover:text-blue-800">Voir tout</a>
            </div>
            <?php if (empty($recentSimulations)): ?>
            <p class="text-slate-500 text-center py-8">Aucune simulation récente</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentSimulations as $sim): ?>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center">
                            <span class="text-sm font-medium text-slate-600">
                                <?php echo strtoupper(substr($sim['client_name'] ?? 'N', 0, 2)); ?>
                            </span>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900"><?php echo htmlspecialchars($sim['client_name'] ?? 'Client'); ?></p>
                            <p class="text-sm text-slate-500"><?php echo formatCurrency($sim['montant_demande']); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php
                        $badgeClass = match($sim['decision'] ?? '') {
                            'ACCORDE'    => 'badge-accorde',
                            'REFUSE'     => 'badge-refuse',
                            'A_ANALYSER' => 'badge-analyser',
                            default      => 'badge-attente',
                        };
                        $badgeText = match($sim['decision'] ?? '') {
                            'ACCORDE'    => 'Approuvé',
                            'REFUSE'     => 'Refusé',
                            'A_ANALYSER' => 'En révision',
                            default      => 'En attente',
                        };
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                        <p class="text-xs text-slate-500 mt-1">
                            <?php echo date('d/m/Y', strtotime($sim['date_creation'])); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Users -->
        <div class="card">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Utilisateurs Récents</h3>
                <a href="users.php" class="text-sm text-blue-600 hover:text-blue-800">Voir tout</a>
            </div>
            <?php if (empty($recentUsers)): ?>
            <p class="text-slate-500 text-center py-8">Aucun utilisateur récent</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($recentUsers as $u): ?>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-sm font-medium text-blue-600">
                                <?php echo strtoupper(substr($u['nom'], 0, 2)); ?>
                            </span>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900"><?php echo htmlspecialchars($u['nom']); ?></p>
                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($u['email']); ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="badge <?php echo $u['role_type'] === 'admin' ? 'badge-attente' : 'badge-analyser'; ?>">
                            <?php echo $u['role_type'] === 'admin' ? 'Admin' : 'Agent'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>