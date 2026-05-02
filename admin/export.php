<?php
/**
 * Data Export Page — Export data to CSV format
 */

require_once '../includes/auth.php';
requireAdmin();

require_once '../classes/Database.php';

$pageTitle = 'Exportation des Données';

$db = Database::getInstance();

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export_' . $exportType . '_' . date('Y-m-d_H-i-s') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    switch ($exportType) {

        case 'clients':
            fputcsv($output, ['ID','Nom','Date Naissance','CIN','Situation Pro','Revenu Mensuel Net','Charges Mensuelles','Ancienneté (mois)','Historique Crédit']);
            $rows = $db->fetchAll("SELECT * FROM client ORDER BY id");
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['id'], $row['nom'], $row['date_naissance'], $row['cin'],
                    $row['situation_pro'], $row['revenu_mensuel_net'],
                    $row['charges_mensuelles'], $row['anciennete_emploi'],
                    $row['historique_credit'] ? 'Bon' : 'Incidents',
                ]);
            }
            break;

        case 'simulations':
            fputcsv($output, ['ID','Client','Montant','Durée (mois)','Type','Taux endettement','Statut','Agent','Date']);
            $rows = $db->fetchAll("
                SELECT dc.*,
                       c.nom  as client_nom,
                       u.nom  as agent_nom
                FROM demande_credit dc
                LEFT JOIN client c          ON dc.client_id = c.id
                LEFT JOIN agent_bancaire ab ON dc.agent_id  = ab.id
                LEFT JOIN utilisateur u     ON ab.id        = u.id
                ORDER BY dc.id
            ");
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['id'], $row['client_nom'], $row['montant_demande'],
                    $row['duree'], $row['type_credit'],
                    number_format($row['taux_endettement_calc'] ?? 0, 2) . '%',
                    $row['statut'], $row['agent_nom'], $row['date_creation'],
                ]);
            }
            break;

        case 'scores':
            fputcsv($output, ['Demande ID','Client','Score Total','Décision','Date Calcul','Détail JSON']);
            $rows = $db->fetchAll("
                SELECT s.*, c.nom as client_nom, d.resultat
                FROM score s
                LEFT JOIN demande_credit dc ON s.demande_id = dc.id
                LEFT JOIN client c          ON dc.client_id = c.id
                LEFT JOIN decision d        ON s.id         = d.score_id
                ORDER BY s.demande_id
            ");
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['demande_id'], $row['client_nom'],
                    $row['valeur_totale'], $row['resultat'] ?? 'N/A',
                    $row['date_calcul'], $row['detail_par_critere'],
                ]);
            }
            break;

        case 'users':
            fputcsv($output, ['ID','Nom','Email','Rôle','Matricule']);
            $rows = $db->fetchAll("
                SELECT u.id, u.nom, u.email,
                       IF(a.id IS NOT NULL, 'Administrateur', 'Agent') as role_label,
                       IF(a.id IS NOT NULL, a.matricule, ag.matricule) as matricule
                FROM utilisateur u
                LEFT JOIN admin a           ON u.id = a.id
                LEFT JOIN agent_bancaire ag ON u.id = ag.id
                ORDER BY u.id
            ");
            foreach ($rows as $row) {
                fputcsv($output, [$row['id'], $row['nom'], $row['email'], $row['role_label'], $row['matricule']]);
            }
            break;
    }

    fclose($output);
    exit;
}

// Stats for display
$stats = [
    'clients'     => $db->fetchValue("SELECT COUNT(*) FROM client"),
    'simulations' => $db->fetchValue("SELECT COUNT(*) FROM demande_credit"),
    'scores'      => $db->fetchValue("SELECT COUNT(*) FROM score"),
    'users'       => $db->fetchValue("SELECT COUNT(*) FROM utilisateur"),
];

require_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-[#003366]">Exportation des Données</h1>
            <p class="text-[#6B7280] mt-1">Téléchargez les données au format CSV</p>
        </div>
        <a href="index.php" class="btn btn-secondary mt-4 md:mt-0">
            <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
            Retour
        </a>
    </div>

    <!-- Export Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Clients -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-feather="users" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#003366]">Clients</h3>
                    <p class="text-[#6B7280] text-sm mt-1">Informations personnelles et financières de tous les clients.</p>
                    <p class="text-sm text-[#6B7280] mt-2">
                        <span class="font-semibold text-[#003366]"><?php echo number_format($stats['clients']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=clients" class="btn btn-primary mt-4 inline-flex">
                        <i data-feather="download" class="w-4 h-4 mr-2"></i>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Simulations -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-feather="bar-chart-2" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#003366]">Simulations</h3>
                    <p class="text-[#6B7280] text-sm mt-1">Toutes les demandes de crédit avec montants, durées et décisions.</p>
                    <p class="text-sm text-[#6B7280] mt-2">
                        <span class="font-semibold text-[#003366]"><?php echo number_format($stats['simulations']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=simulations" class="btn btn-primary mt-4 inline-flex">
                        <i data-feather="download" class="w-4 h-4 mr-2"></i>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Scores -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-feather="pie-chart" class="w-6 h-6 text-amber-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#003366]">Scores Détaillés</h3>
                    <p class="text-[#6B7280] text-sm mt-1">Scores totaux et décisions par simulation.</p>
                    <p class="text-sm text-[#6B7280] mt-2">
                        <span class="font-semibold text-[#003366]"><?php echo number_format($stats['scores']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=scores" class="btn btn-primary mt-4 inline-flex">
                        <i data-feather="download" class="w-4 h-4 mr-2"></i>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Users -->
        <div class="bg-white rounded-xl border border-[#E2E8F0] shadow-sm p-6 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i data-feather="user-check" class="w-6 h-6 text-purple-600"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-[#003366]">Utilisateurs</h3>
                    <p class="text-[#6B7280] text-sm mt-1">Agents et administrateurs du système.</p>
                    <p class="text-sm text-[#6B7280] mt-2">
                        <span class="font-semibold text-[#003366]"><?php echo number_format($stats['users']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=users" class="btn btn-primary mt-4 inline-flex">
                        <i data-feather="download" class="w-4 h-4 mr-2"></i>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info -->
    <div class="bg-[#F4F6F9] border border-[#E2E8F0] rounded-xl p-5 mt-8 flex items-start gap-4">
        <i data-feather="info" class="w-5 h-5 text-[#6B7280] flex-shrink-0 mt-0.5"></i>
        <div>
            <h3 class="font-semibold text-[#003366]">Format d'export</h3>
            <p class="text-[#6B7280] text-sm mt-1">
                Fichiers CSV encodés UTF-8, compatibles Excel, Google Sheets et tout outil d'analyse de données.
            </p>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>