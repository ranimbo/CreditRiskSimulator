<?php
/**
 * Data Export Page
 * Export data to CSV format
 */

require_once '../includes/auth.php';
requireAdmin();

require_once '../classes/Database.php';
require_once '../classes/Client.php';
require_once '../classes/CreditRequest.php';
require_once '../classes/User.php';

$pageTitle = 'Exportation des Données';

// Handle export requests
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    $db = Database::getInstance();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="export_' . $exportType . '_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    switch ($exportType) {
        case 'clients':
            fputcsv($output, ['ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Date Naissance', 'Profession', 'Employeur', 'Revenu Mensuel', 'Charges Mensuelles', 'Ville', 'Date Création']);
            
            $stmt = $db->query("SELECT * FROM clients ORDER BY id");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['first_name'],
                    $row['last_name'],
                    $row['email'],
                    $row['phone'],
                    $row['birth_date'],
                    $row['profession'],
                    $row['employer'],
                    $row['monthly_income'],
                    $row['monthly_expenses'],
                    $row['city'],
                    $row['created_at']
                ]);
            }
            break;
            
        case 'simulations':
            fputcsv($output, ['ID', 'Client', 'Montant', 'Durée (mois)', 'Taux', 'Score Total', 'Décision', 'Agent', 'Date']);
            
            $stmt = $db->query("
                SELECT cr.*, 
                       CONCAT(c.first_name, ' ', c.last_name) as client_name,
                       CONCAT(u.first_name, ' ', u.last_name) as agent_name
                FROM credit_requests cr
                LEFT JOIN clients c ON cr.client_id = c.id
                LEFT JOIN users u ON cr.user_id = u.id
                ORDER BY cr.id
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $decisionLabel = match($row['decision']) {
                    'approved' => 'Approuvé',
                    'rejected' => 'Refusé',
                    default => 'En révision'
                };
                fputcsv($output, [
                    $row['id'],
                    $row['client_name'],
                    $row['amount'],
                    $row['duration'],
                    $row['interest_rate'],
                    $row['total_score'],
                    $decisionLabel,
                    $row['agent_name'],
                    $row['created_at']
                ]);
            }
            break;
            
        case 'scores':
            fputcsv($output, ['Simulation ID', 'Client', 'Critère', 'Score', 'Score Max', 'Pourcentage', 'Détails']);
            
            $stmt = $db->query("
                SELECT s.*, 
                       sc.name as criteria_name, sc.max_score,
                       CONCAT(c.first_name, ' ', c.last_name) as client_name
                FROM scores s
                LEFT JOIN scoring_criteria sc ON s.criteria_id = sc.id
                LEFT JOIN credit_requests cr ON s.credit_request_id = cr.id
                LEFT JOIN clients c ON cr.client_id = c.id
                ORDER BY s.credit_request_id, s.criteria_id
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $percentage = $row['max_score'] > 0 ? ($row['score'] / $row['max_score']) * 100 : 0;
                fputcsv($output, [
                    $row['credit_request_id'],
                    $row['client_name'],
                    $row['criteria_name'],
                    $row['score'],
                    $row['max_score'],
                    number_format($percentage, 1) . '%',
                    $row['details']
                ]);
            }
            break;
            
        case 'users':
            fputcsv($output, ['ID', 'Prénom', 'Nom', 'Email', 'Rôle', 'Statut', 'Dernière Connexion', 'Date Création']);
            
            $stmt = $db->query("SELECT * FROM users ORDER BY id");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['first_name'],
                    $row['last_name'],
                    $row['email'],
                    $row['role'] === 'admin' ? 'Administrateur' : 'Agent',
                    $row['status'] === 'active' ? 'Actif' : 'Inactif',
                    $row['last_login'] ?? 'Jamais',
                    $row['created_at']
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}

// Get statistics for display
$db = Database::getInstance();

$stats = [
    'clients' => $db->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'simulations' => $db->query("SELECT COUNT(*) FROM credit_requests")->fetchColumn(),
    'scores' => $db->query("SELECT COUNT(*) FROM scores")->fetchColumn(),
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
];

require_once '../includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Exportation des Données</h1>
            <p class="text-slate-600 mt-1">Téléchargez les données au format CSV</p>
        </div>
        <a href="index.php" class="btn btn-secondary mt-4 md:mt-0">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>

    <!-- Export Options -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Clients Export -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-slate-900">Clients</h3>
                    <p class="text-slate-600 text-sm mt-1">
                        Exportez la liste complète des clients avec leurs informations personnelles et financières.
                    </p>
                    <p class="text-sm text-slate-500 mt-2">
                        <span class="font-medium"><?php echo number_format($stats['clients']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=clients" class="btn btn-primary mt-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Simulations Export -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-slate-900">Simulations</h3>
                    <p class="text-slate-600 text-sm mt-1">
                        Exportez toutes les simulations de crédit avec les montants, durées, scores et décisions.
                    </p>
                    <p class="text-sm text-slate-500 mt-2">
                        <span class="font-medium"><?php echo number_format($stats['simulations']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=simulations" class="btn btn-primary mt-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Scores Export -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-slate-900">Scores Détaillés</h3>
                    <p class="text-slate-600 text-sm mt-1">
                        Exportez le détail des scores par critère pour chaque simulation.
                    </p>
                    <p class="text-sm text-slate-500 mt-2">
                        <span class="font-medium"><?php echo number_format($stats['scores']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=scores" class="btn btn-primary mt-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Users Export -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-slate-900">Utilisateurs</h3>
                    <p class="text-slate-600 text-sm mt-1">
                        Exportez la liste des utilisateurs du système (agents et administrateurs).
                    </p>
                    <p class="text-sm text-slate-500 mt-2">
                        <span class="font-medium"><?php echo number_format($stats['users']); ?></span> enregistrements
                    </p>
                    <a href="export.php?export=users" class="btn btn-primary mt-4">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Télécharger CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="card mt-8 bg-slate-50 border-slate-200">
        <div class="flex items-start gap-4">
            <svg class="w-6 h-6 text-slate-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="font-semibold text-slate-900">Format d'export</h3>
                <p class="text-slate-600 text-sm mt-1">
                    Les fichiers sont exportés au format CSV (Comma-Separated Values), compatible avec Excel, Google Sheets et la plupart des outils d'analyse de données. L'encodage UTF-8 est utilisé pour supporter les caractères spéciaux.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
