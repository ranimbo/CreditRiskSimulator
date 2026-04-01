<?php
/**
 * PDF Report Generation Endpoint
 * Generates and outputs a PDF report for a credit simulation
 */

require_once 'includes/auth.php';
requireLogin();

require_once 'classes/CreditRequest.php';
require_once 'classes/Client.php';
require_once 'classes/User.php';
require_once 'classes/PDFReport.php';

// Get request ID
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$requestId) {
    die('ID de demande invalide.');
}

// Get the credit request with full details
$creditRequest = new CreditRequest();
$request = $creditRequest->getRequestWithDetails($requestId);

if (!$request) {
    die('Demande non trouvée.');
}

// Get client details
$client = new Client();
$clientData = $client->getById($request['client_id']);

if (!$clientData) {
    die('Client non trouvé.');
}

// Get user details (agent who created the simulation)
$user = new User();
$userData = $user->getById($request['user_id']);

if (!$userData) {
    die('Utilisateur non trouvé.');
}

// Parse score details
$scoreDetails = [];
if (!empty($request['score_details'])) {
    $scoreDetails = json_decode($request['score_details'], true) ?: [];
}

// Generate PDF
try {
    $pdf = new PDFReport(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Credit Risk Simulator');
    $pdf->SetAuthor($userData['first_name'] . ' ' . $userData['last_name']);
    $pdf->SetTitle('Rapport de Simulation - ' . $clientData['first_name'] . ' ' . $clientData['last_name']);
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
    $pdf->generateCreditReport($request, $clientData, $scoreDetails, $userData);
    
    // Output PDF
    $filename = 'Simulation_' . str_pad($requestId, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf';
    $pdf->Output($filename, 'I'); // 'I' for inline display, 'D' for download
    
} catch (Exception $e) {
    die('Erreur lors de la génération du PDF: ' . $e->getMessage());
}
