<?php
/**
 * Historique des Simulations — Style Amen Bank
 * 
 * Liste de toutes les simulations avec filtres,
 * table avec en-tête navy et badges de décision Amen.
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

// Filtres
$filters = [
    'statut' => $_GET['statut'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Récupérer les demandes
$requests = $creditRequestModel->getAll($filters, $perPage, $offset);
$totalRequests = $creditRequestModel->count($filters);
$totalPages = ceil($totalRequests / $perPage);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <a href="dashboard.php">Accueil</a>
    <span class="separator">›</span>
    <span class="current">Historique</span>
</div>

<div class="max-w-7xl mx-auto">
    <!-- En-tête de la page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="page-title-accent">
            <h1 class="text-2xl font-bold text-[#003366]">Historique des Simulations</h1>
            <p class="text-[#6B7280] mt-1"><?php echo $totalRequests; ?> simulation(s) au total</p>
        </div>
        <a href="simulation.php" class="btn btn-gold">
            <i data-feather="plus" class="w-4 h-4"></i>
            Nouvelle Simulation
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] p-5 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-[#003366] mb-1.5">Recherche</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>"
                       placeholder="Nom du client, CIN ou N° Demande..."
                       class="form-input">
            </div>
            <div>
                <label class="block text-xs font-medium text-[#003366] mb-1.5">Décision</label>
                <select name="statut" class="form-input">
                    <option value="">Toutes</option>
                    <option value="ACCORDE" <?php echo $filters['statut'] === 'ACCORDE' ? 'selected' : ''; ?>>Accordé</option>
                    <option value="REFUSE" <?php echo $filters['statut'] === 'REFUSE' ? 'selected' : ''; ?>>Refusé</option>
                    <option value="A_ANALYSER" <?php echo $filters['statut'] === 'A_ANALYSER' ? 'selected' : ''; ?>>À analyser</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary h-[42px]">
                    <i data-feather="search" class="w-4 h-4"></i>
                    Filtrer
                </button>
                <a href="history.php" class="btn btn-secondary h-[42px]">
                    <i data-feather="x" class="w-4 h-4"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table des résultats -->
    <div class="bg-white rounded-xl shadow-sm border border-[#E2E8F0] overflow-hidden">
        <?php if (empty($requests)): ?>
        <div class="p-12 text-center">
            <div class="w-16 h-16 bg-[#F4F6F9] rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-feather="inbox" class="w-8 h-8 text-gray-300"></i>
            </div>
            <h3 class="text-lg font-medium text-[#003366] mb-2">Aucune simulation trouvée</h3>
            <p class="text-[#6B7280] mb-6">Commencez par créer une nouvelle simulation de crédit.</p>
            <a href="simulation.php" class="btn btn-gold">
                <i data-feather="plus" class="w-4 h-4"></i>
                Nouvelle Simulation
            </a>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Demande</th>
                        <th>Score</th>
                        <th>Décision</th>
                        <th>Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-[#003366]/10 flex items-center justify-center text-[#003366] font-semibold text-sm">
                                    <?php echo strtoupper(substr($req['client_nom'], 0, 2)); ?>
                                </div>
                                <div>
                                    <div class="font-medium text-[#333333]">
                                        <?php echo htmlspecialchars($req['client_nom']); ?>
                                    </div>
                                    <div class="text-xs text-[#6B7280]">
                                        CIN: <?php echo htmlspecialchars($req['cin']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="font-semibold text-[#003366]"><?php echo formatCurrency($req['montant_demande']); ?></div>
                            <div class="text-xs text-[#6B7280]"><?php echo $req['duree']; ?> mois — <?php echo $req['type_credit']; ?></div>
                        </td>
                        <td>
                            <?php if ($req['valeur_totale']): ?>
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-100 rounded-full h-2">
                                    <?php 
                                    $scoreColor = '#003366';
                                    if ($req['valeur_totale'] >= 70) $scoreColor = '#1A7F3C';
                                    elseif ($req['valeur_totale'] >= 40) $scoreColor = '#C8971F';
                                    else $scoreColor = '#C0392B';
                                    ?>
                                    <div class="h-2 rounded-full" 
                                         style="width: <?php echo $req['valeur_totale']; ?>%; background-color: <?php echo $scoreColor; ?>"></div>
                                </div>
                                <span class="font-medium text-sm" style="color: <?php echo $scoreColor; ?>"><?php echo number_format($req['valeur_totale'], 0); ?></span>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-300">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($req['resultat']): ?>
                            <?php
                            $badgeClass = match($req['resultat']) {
                                'ACCORDE' => 'badge-accorde',
                                'REFUSE' => 'badge-refuse',
                                'A_ANALYSER' => 'badge-analyser',
                                default => 'badge-attente',
                            };
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo ScoringEngine::getDecisionLabel($req['resultat']); ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-400 text-sm">En cours</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-[#6B7280] whitespace-nowrap">
                            <?php echo date('d/m/Y', strtotime($req['date_creation'])); ?>
                        </td>
                        <td class="text-right">
                            <a href="result.php?id=<?php echo $req['id']; ?>" 
                               class="inline-flex items-center gap-1 text-sm text-[#C8971F] hover:text-[#A07820] font-medium transition-colors"
                               title="Voir détails">
                                <i data-feather="eye" class="w-4 h-4"></i>
                                Détails
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-[#F8FAFC] border-t border-[#E2E8F0] flex items-center justify-between">
            <div class="text-sm text-[#6B7280]">
                Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $perPage, $totalRequests); ?> sur <?php echo $totalRequests; ?> résultats
            </div>
            <div class="flex gap-1">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-3 py-1 border border-[#E2E8F0] rounded text-sm hover:bg-[#F4F6F9] transition-colors text-[#003366]">
                    &laquo;
                </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-3 py-1 border rounded text-sm transition-colors <?php echo $i === $page ? 'bg-[#003366] text-white border-[#003366]' : 'border-[#E2E8F0] hover:bg-[#F4F6F9] text-[#333333]'; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>" 
                   class="px-3 py-1 border border-[#E2E8F0] rounded text-sm hover:bg-[#F4F6F9] transition-colors text-[#003366]">
                    &raquo;
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
