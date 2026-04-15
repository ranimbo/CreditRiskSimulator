<?php
/**
 * Dashboard — Style Amen Bank
 * 
 * Tableau de bord principal avec KPI, graphiques Chart.js
 * et activité récente. Palette navy/gold/vert.
 */

$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Client.php';

$db = Database::getInstance();

// Récupérer les statistiques
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

// Taux d'approbation
$stats['approval_rate'] = $stats['total_requests'] > 0 
    ? round(($stats['approved'] / $stats['total_requests']) * 100, 1) 
    : 0;

// Demandes récentes
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

// Clients récents
$clientModel = new Client();
$recentClients = $clientModel->getRecent(5);

// Données mensuelles pour les charts (6 derniers mois)
$monthlyData = $db->fetchAll("
    SELECT 
        DATE_FORMAT(date_creation, '%Y-%m') as mois,
        SUM(CASE WHEN statut = 'ACCORDE' THEN 1 ELSE 0 END) as accordes,
        SUM(CASE WHEN statut = 'REFUSE' THEN 1 ELSE 0 END) as refuses,
        SUM(CASE WHEN statut = 'A_ANALYSER' THEN 1 ELSE 0 END) as en_attente,
        COUNT(*) as total
    FROM demande_credit
    WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_creation, '%Y-%m')
    ORDER BY mois ASC
");

// Préparer les données pour Chart.js
$chartLabels = [];
$chartAccordes = [];
$chartRefuses = [];
$chartEnAttente = [];

$moisFrancais = [
    '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juil', '08' => 'Août',
    '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'
];

foreach ($monthlyData as $row) {
    $parts = explode('-', $row['mois']);
    $chartLabels[] = $moisFrancais[$parts[1]] ?? $parts[1];
    $chartAccordes[] = (int) $row['accordes'];
    $chartRefuses[] = (int) $row['refuses'];
    $chartEnAttente[] = (int) $row['en_attente'];
}

// Inclure header
require_once __DIR__ . '/includes/header.php';
?>

<!-- En-tête de la page -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-[#003366]">Tableau de bord</h1>
    <p class="text-[#6B7280] mt-1">Bienvenue, <?php echo htmlspecialchars($currentUser['nom']); ?>. Voici un aperçu de votre activité.</p>
</div>

<!-- ========================================
     CARTES KPI (4 cartes avec bordure latérale)
     ======================================== -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Total Demandes -->
    <div class="kpi-card accent-navy p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#6B7280]">Total demandes</p>
                <p class="text-3xl font-bold text-[#003366] mt-1"><?php echo number_format($stats['total_requests']); ?></p>
                <p class="text-xs text-[#6B7280] mt-2 flex items-center gap-1">
                    <i data-feather="users" class="w-3.5 h-3.5"></i>
                    <?php echo number_format($stats['total_clients']); ?> clients
                </p>
            </div>
            <div class="w-12 h-12 bg-[#003366]/10 rounded-xl flex items-center justify-center">
                <i data-feather="file-text" class="w-6 h-6 text-[#003366]"></i>
            </div>
        </div>
    </div>
    
    <!-- Dossiers Accordés -->
    <div class="kpi-card accent-green p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#6B7280]">Dossiers accordés</p>
                <p class="text-3xl font-bold text-[#1A7F3C] mt-1"><?php echo number_format($stats['approved']); ?></p>
                <p class="text-xs text-[#6B7280] mt-2 flex items-center gap-1">
                    <i data-feather="trending-up" class="w-3.5 h-3.5 text-[#1A7F3C]"></i>
                    <?php echo $stats['approval_rate']; ?>% taux d'approbation
                </p>
            </div>
            <div class="w-12 h-12 bg-[#E6F4EA] rounded-xl flex items-center justify-center">
                <i data-feather="check-circle" class="w-6 h-6 text-[#1A7F3C]"></i>
            </div>
        </div>
    </div>
    
    <!-- Dossiers Refusés -->
    <div class="kpi-card accent-red p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#6B7280]">Dossiers refusés</p>
                <p class="text-3xl font-bold text-[#C0392B] mt-1"><?php echo number_format($stats['refused']); ?></p>
                <p class="text-xs text-[#6B7280] mt-2 flex items-center gap-1">
                    <i data-feather="alert-triangle" class="w-3.5 h-3.5 text-[#B7950B]"></i>
                    <?php echo number_format($stats['in_review']); ?> à analyser
                </p>
            </div>
            <div class="w-12 h-12 bg-[#FDEEEE] rounded-xl flex items-center justify-center">
                <i data-feather="x-circle" class="w-6 h-6 text-[#C0392B]"></i>
            </div>
        </div>
    </div>
    
    <!-- Score Moyen -->
    <div class="kpi-card accent-gold p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-[#6B7280]">Score moyen</p>
                <p class="text-3xl font-bold text-[#C8971F] mt-1"><?php echo round($stats['avg_score']); ?><span class="text-lg text-[#6B7280]">/100</span></p>
                <p class="text-xs text-[#6B7280] mt-2">
                    Niveau : 
                    <?php 
                    $avgScore = round($stats['avg_score']);
                    if ($avgScore >= 70) echo '<span class="text-[#1A7F3C] font-medium">Bon</span>';
                    elseif ($avgScore >= 40) echo '<span class="text-[#B7950B] font-medium">Moyen</span>';
                    else echo '<span class="text-[#C0392B] font-medium">Faible</span>';
                    ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-[#C8971F]/10 rounded-xl flex items-center justify-center">
                <i data-feather="bar-chart-2" class="w-6 h-6 text-[#C8971F]"></i>
            </div>
        </div>
    </div>
</div>

<!-- ========================================
     GRAPHIQUES Chart.js
     ======================================== -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Graphique barres — Évolution mensuelle -->
    <div class="lg:col-span-2 chart-card">
        <div class="p-5 border-b border-[#E2E8F0]">
            <h2 class="chart-title">Évolution mensuelle des demandes</h2>
            <p class="chart-subtitle mt-0.5">6 derniers mois</p>
        </div>
        <div class="p-5">
            <canvas id="monthlyChart" height="260"></canvas>
        </div>
    </div>
    
    <!-- Graphique donut — Répartition -->
    <div class="chart-card">
        <div class="p-5 border-b border-[#E2E8F0]">
            <h2 class="chart-title">Répartition des décisions</h2>
            <p class="chart-subtitle mt-0.5">Toutes périodes</p>
        </div>
        <div class="p-5 flex items-center justify-center">
            <canvas id="decisionChart" height="260"></canvas>
        </div>
    </div>
</div>

<!-- ========================================
     STATUT DES DEMANDES + ACTIONS RAPIDES
     ======================================== -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm">
            <div class="p-5 border-b border-[#E2E8F0]">
                <h2 class="text-base font-semibold text-[#003366]">Statut des demandes</h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-[#E6F4EA] rounded-xl">
                        <div class="text-2xl font-bold text-[#1A7F3C]"><?php echo $stats['approved']; ?></div>
                        <div class="text-sm text-[#1A7F3C]/80 mt-1">Approuvées</div>
                    </div>
                    <div class="text-center p-4 bg-[#FDEEEE] rounded-xl">
                        <div class="text-2xl font-bold text-[#C0392B]"><?php echo $stats['refused']; ?></div>
                        <div class="text-sm text-[#C0392B]/80 mt-1">Refusées</div>
                    </div>
                    <div class="text-center p-4 bg-[#FEF9E7] rounded-xl">
                        <div class="text-2xl font-bold text-[#B7950B]"><?php echo $stats['in_review']; ?></div>
                        <div class="text-sm text-[#B7950B]/80 mt-1">À analyser</div>
                    </div>
                    <div class="text-center p-4 bg-blue-50 rounded-xl">
                        <div class="text-2xl font-bold text-[#003366]"><?php echo $stats['pending']; ?></div>
                        <div class="text-sm text-[#003366]/60 mt-1">En attente</div>
                    </div>
                </div>
                
                <!-- Barres de progression -->
                <div class="mt-6 space-y-3">
                    <?php if ($stats['total_requests'] > 0): ?>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-[#6B7280]">Approuvées</span>
                            <span class="text-[#333333] font-medium"><?php echo round(($stats['approved'] / $stats['total_requests']) * 100); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-[#1A7F3C] h-2 rounded-full transition-all" style="width: <?php echo ($stats['approved'] / $stats['total_requests']) * 100; ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-[#6B7280]">Refusées</span>
                            <span class="text-[#333333] font-medium"><?php echo round(($stats['refused'] / $stats['total_requests']) * 100); ?>%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="bg-[#C0392B] h-2 rounded-full transition-all" style="width: <?php echo ($stats['refused'] / $stats['total_requests']) * 100; ?>%"></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-sm text-[#6B7280] text-center py-4">Aucune demande enregistrée</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm">
        <div class="p-5 border-b border-[#E2E8F0]">
            <h2 class="text-base font-semibold text-[#003366]">Actions rapides</h2>
        </div>
        <div class="p-5 space-y-3">
            <a href="client-form.php" class="flex items-center gap-3 p-3 rounded-xl border border-[#E2E8F0] hover:bg-[#F4F6F9] hover:border-[#C8971F]/30 transition-all group">
                <div class="w-10 h-10 bg-[#003366]/10 rounded-lg flex items-center justify-center group-hover:bg-[#003366]/15 transition-colors">
                    <i data-feather="user-plus" class="w-5 h-5 text-[#003366]"></i>
                </div>
                <div>
                    <p class="font-medium text-[#333333]">Nouveau client</p>
                    <p class="text-xs text-[#6B7280]">Ajouter un client</p>
                </div>
            </a>
            <a href="simulation.php" class="flex items-center gap-3 p-3 rounded-xl border border-[#E2E8F0] hover:bg-[#F4F6F9] hover:border-[#C8971F]/30 transition-all group">
                <div class="w-10 h-10 bg-[#C8971F]/10 rounded-lg flex items-center justify-center group-hover:bg-[#C8971F]/15 transition-colors">
                    <i data-feather="calculator" class="w-5 h-5 text-[#C8971F]"></i>
                </div>
                <div>
                    <p class="font-medium text-[#333333]">Nouvelle simulation</p>
                    <p class="text-xs text-[#6B7280]">Évaluer un crédit</p>
                </div>
            </a>
            <a href="history.php" class="flex items-center gap-3 p-3 rounded-xl border border-[#E2E8F0] hover:bg-[#F4F6F9] hover:border-[#C8971F]/30 transition-all group">
                <div class="w-10 h-10 bg-[#00a651]/10 rounded-lg flex items-center justify-center group-hover:bg-[#00a651]/15 transition-colors">
                    <i data-feather="clock" class="w-5 h-5 text-[#00a651]"></i>
                </div>
                <div>
                    <p class="font-medium text-[#333333]">Historique</p>
                    <p class="text-xs text-[#6B7280]">Voir les demandes</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- ========================================
     ACTIVITÉ RÉCENTE
     ======================================== -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Demandes récentes -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm">
        <div class="p-5 border-b border-[#E2E8F0] flex items-center justify-between">
            <h2 class="text-base font-semibold text-[#003366]">Demandes récentes</h2>
            <a href="history.php" class="text-sm text-[#C8971F] hover:text-[#A07820] font-medium transition-colors">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-100">
            <?php if (empty($recentRequests)): ?>
            <div class="p-6 text-center text-[#6B7280]">
                <i data-feather="inbox" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                <p>Aucune demande récente</p>
            </div>
            <?php else: ?>
            <?php foreach ($recentRequests as $request): ?>
            <div class="p-4 hover:bg-[#F8FAFC] transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#003366]/10 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-[#003366]">
                                <?php echo strtoupper(substr($request['client_nom'], 0, 2)); ?>
                            </span>
                        </div>
                        <div>
                            <p class="font-medium text-[#333333]">
                                <?php echo htmlspecialchars($request['client_nom']); ?>
                            </p>
                            <p class="text-xs text-[#6B7280]">
                                #<?php echo htmlspecialchars($request['id']); ?> — <?php echo formatCurrency($request['montant_demande']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php 
                        $badgeClass = match($request['statut'] ?? '') {
                            'ACCORDE' => 'badge-accorde',
                            'REFUSE' => 'badge-refuse',
                            'A_ANALYSER' => 'badge-analyser',
                            default => 'badge-attente',
                        };
                        $badgeLabel = match($request['statut'] ?? '') {
                            'ACCORDE' => 'Accordé',
                            'REFUSE' => 'Refusé',
                            'A_ANALYSER' => 'À analyser',
                            default => 'En attente',
                        };
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeLabel; ?></span>
                        <?php if ($request['valeur_totale']): ?>
                        <p class="text-xs mt-1 <?php echo getScoreColorClass($request['valeur_totale']); ?>">
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
    
    <!-- Clients récents -->
    <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm">
        <div class="p-5 border-b border-[#E2E8F0] flex items-center justify-between">
            <h2 class="text-base font-semibold text-[#003366]">Clients récents</h2>
            <a href="clients.php" class="text-sm text-[#C8971F] hover:text-[#A07820] font-medium transition-colors">Voir tout</a>
        </div>
        <div class="divide-y divide-gray-100">
            <?php if (empty($recentClients)): ?>
            <div class="p-6 text-center text-[#6B7280]">
                <i data-feather="users" class="w-12 h-12 mx-auto text-gray-300 mb-3"></i>
                <p>Aucun client récent</p>
            </div>
            <?php else: ?>
            <?php foreach ($recentClients as $client): ?>
            <div class="p-4 hover:bg-[#F8FAFC] transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-[#C8971F]/10 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-[#C8971F]">
                                <?php echo strtoupper(substr($client['nom'], 0, 2)); ?>
                            </span>
                        </div>
                        <div>
                            <p class="font-medium text-[#333333]">
                                <?php echo htmlspecialchars($client['nom']); ?>
                            </p>
                            <p class="text-xs text-[#6B7280]">
                                <?php echo htmlspecialchars($client['cin']); ?> — <?php echo htmlspecialchars($client['situation_pro']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-[#003366]"><?php echo formatCurrency($client['revenu_mensuel_net']); ?></p>
                        <p class="text-xs text-[#6B7280]">revenu mensuel</p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================
     INITIALISATION Chart.js
     ======================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuration commune Chart.js — Palette Amen Bank
    const amenNavy = '#003366';
    const amenGold = '#C8971F';
    const amenGray = '#6B7280';
    const amenGreen = '#00a651';
    const amenRed = '#C0392B';
    
    const chartFontConfig = {
        family: "'Inter', 'Segoe UI', sans-serif",
        size: 12,
        weight: 400,
    };

    // ---- Graphique barres : Évolution mensuelle ----
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [
                    {
                        label: 'Accordé',
                        data: <?php echo json_encode($chartAccordes); ?>,
                        backgroundColor: amenNavy,
                        borderRadius: 4,
                        barPercentage: 0.7,
                    },
                    {
                        label: 'Refusé',
                        data: <?php echo json_encode($chartRefuses); ?>,
                        backgroundColor: amenGold,
                        borderRadius: 4,
                        barPercentage: 0.7,
                    },
                    {
                        label: 'À analyser',
                        data: <?php echo json_encode($chartEnAttente); ?>,
                        backgroundColor: amenGray,
                        borderRadius: 4,
                        barPercentage: 0.7,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: amenNavy,
                            font: chartFontConfig,
                            usePointStyle: true,
                            pointStyle: 'rectRounded',
                            padding: 16,
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: amenGray, font: { size: 11 } },
                        grid: { color: '#F4F6F9' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            color: amenGray, 
                            font: { size: 11 },
                            stepSize: 1,
                        },
                        grid: { color: '#F4F6F9' },
                    }
                }
            }
        });
    }

    // ---- Graphique donut : Répartition des décisions ----
    const decisionCtx = document.getElementById('decisionChart');
    if (decisionCtx) {
        new Chart(decisionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Accordé', 'Refusé', 'À analyser', 'En attente'],
                datasets: [{
                    data: [
                        <?php echo $stats['approved']; ?>,
                        <?php echo $stats['refused']; ?>,
                        <?php echo $stats['in_review']; ?>,
                        <?php echo $stats['pending']; ?>
                    ],
                    backgroundColor: [amenNavy, amenGold, amenGray, '#D1D5DB'],
                    borderWidth: 0,
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: amenNavy,
                            font: chartFontConfig,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 12,
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
