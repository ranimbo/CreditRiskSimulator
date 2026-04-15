<?php
/**
 * PDF Report Generation Endpoint
 * Generates and outputs a PDF report for a credit simulation
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/classes/CreditRequest.php';
require_once __DIR__ . '/classes/PDFReport.php';

// Get request ID
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$requestId) {
    die('ID de demande invalide.');
}

// Get the credit request with full details
$creditRequestModel = new CreditRequest();
$request = $creditRequestModel->findById($requestId);

if (!$request) {
    die('Demande non trouvée.');
}

// Parse score details
$scoreDetails = [];
if (!empty($request['detail_par_critere'])) {
    $scoreDetails = json_decode($request['detail_par_critere'], true) ?: [];
}

// Generate PDF
try {
    $pdf = new PDFReport(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Credit Risk Simulator');
    $pdf->SetAuthor($request['agent_nom'] ?? 'Agent');
    $pdf->SetTitle('Rapport de Simulation - ' . $request['client_nom']);
    $pdf->SetSubject('Simulation de Crédit');
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(10, 30, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(15);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Generate the report
    $pdf->generateCreditReport($request, $scoreDetails);
    
    // Output PDF
    $filename = 'Simulation_' . str_pad($requestId, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf';
    $pdf->Output($filename, 'I'); // 'I' for inline display, 'D' for download
    
} catch (Exception $e) {
    die('Erreur lors de la génération du PDF: ' . $e->getMessage());
}
