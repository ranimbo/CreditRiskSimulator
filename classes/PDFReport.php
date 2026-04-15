<?php
/**
 * PDF Report Generator using TCPDF
 * Generates professional credit simulation reports
 */

require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';
require_once __DIR__ . '/ScoringEngine.php';

class PDFReport extends TCPDF {
    
    private $companyName = 'BankCredit Simulator';
    private $companyAddress = '123 Avenue de la Finance, 75008 Paris';
    private $companyPhone = '+33 1 23 45 67 89';
    
    /**
     * Custom header for the PDF
     */
    public function Header() {
        // Logo placeholder (you can add a logo image)
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(0, 82, 155);
        $this->Cell(0, 15, $this->companyName, 0, false, 'L', 0, '', 0, false, 'M', 'M');
        
        // Header line
        $this->SetDrawColor(0, 82, 155);
        $this->SetLineWidth(0.5);
        $this->Line(10, 25, 200, 25);
        
        $this->Ln(15);
    }

    /**
     * Custom footer for the PDF
     */
    public function Footer() {
        $this->SetY(-20);
        $this->SetFont('helvetica', '', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Footer content
        $this->Cell(0, 5, $this->companyAddress . ' | ' . $this->companyPhone, 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages() . ' | Document généré le ' . date('d/m/Y à H:i'), 0, 0, 'C');
    }

    /**
     * Generate a credit simulation report
     */
    public function generateCreditReport($request, $scoreDetails) {
        $this->AddPage();
        
        // Title
        $this->SetFont('helvetica', 'B', 18);
        $this->SetTextColor(51, 51, 51);
        $this->Cell(0, 10, 'Rapport de Simulation de Crédit', 0, 1, 'C');
        $this->Ln(5);
        
        // Reference number
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(128, 128, 128);
        $this->Cell(0, 5, 'Référence: SIM-' . str_pad($request['id'], 6, '0', STR_PAD_LEFT), 0, 1, 'C');
        $this->Ln(10);
        
        // Decision Box
        $this->drawDecisionBox($request['resultat'] ?? 'A_ANALYSER', $request['valeur_totale'] ?? 0);
        $this->Ln(15);
        
        // Two column layout
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(0, 82, 155);
        $this->Cell(0, 8, 'INFORMATIONS CLIENT', 0, 1, 'L');
        $this->SetDrawColor(0, 82, 155);
        $this->Line(10, $this->GetY(), 100, $this->GetY());
        $this->Ln(3);
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        
        $clientInfo = [
            'Nom complet' => $request['client_nom'] ?? 'N/A',
            'CIN' => $request['cin'] ?? 'N/A',
            'Situation' => $request['situation_pro'] ?? 'N/A',
            'Revenu net' => number_format($request['revenu_mensuel_net'] ?? 0, 2, ',', ' ') . ' MAD',
            'Charges' => number_format($request['charges_mensuelles'] ?? 0, 2, ',', ' ') . ' MAD'
        ];
        
        foreach ($clientInfo as $label => $value) {
            $this->Cell(50, 6, $label . ':', 0, 0, 'L');
            $this->Cell(0, 6, $value, 0, 1, 'L');
        }
        
        $this->Ln(10);
        
        // Credit Request Details
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(0, 82, 155);
        $this->Cell(0, 8, 'DÉTAILS DU CRÉDIT', 0, 1, 'L');
        $this->Line(10, $this->GetY(), 100, $this->GetY());
        $this->Ln(3);
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(51, 51, 51);
        
        $creditInfo = [
            'Montant demandé' => number_format($request['montant_demande'] ?? 0, 2, ',', ' ') . ' MAD',
            'Durée' => ($request['duree'] ?? 0) . ' mois',
            'Type' => $request['type_credit'] ?? 'N/A',
        ];
        
        foreach ($creditInfo as $label => $value) {
            $this->Cell(50, 6, $label . ':', 0, 0, 'L');
            $this->Cell(0, 6, $value, 0, 1, 'L');
        }
        
        $this->Ln(10);
        
        // Score Breakdown
        $this->SetFont('helvetica', 'B', 12);
        $this->SetTextColor(0, 82, 155);
        $this->Cell(0, 8, 'ANALYSE DU SCORE', 0, 1, 'L');
        $this->Line(10, $this->GetY(), 100, $this->GetY());
        $this->Ln(3);
        
        $this->drawScoreTable($scoreDetails, $request['valeur_totale'] ?? 0);
        
        $this->Ln(10);
        
        // Justification
        if (!empty($request['justification'])) {
            $this->SetFont('helvetica', 'B', 12);
            $this->SetTextColor(0, 82, 155);
            $this->Cell(0, 8, 'JUSTIFICATION & RECOMMANDATIONS', 0, 1, 'L');
            $this->Line(10, $this->GetY(), 100, $this->GetY());
            $this->Ln(3);
            
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(51, 51, 51);
            $this->MultiCell(0, 6, $request['justification'], 0, 'L');
        }
        
        // Signature section
        $this->Ln(20);
        $this->SetFont('helvetica', '', 10);
        $this->Cell(95, 6, 'Agent bancaire:', 0, 0, 'L');
        $this->Cell(95, 6, 'Date:', 0, 1, 'L');
        $this->Ln(5);
        $this->Cell(95, 6, $request['agent_nom'] ?? 'Agent', 0, 0, 'L');
        $this->Cell(95, 6, date('d/m/Y'), 0, 1, 'L');
        
        // Disclaimer
        $this->Ln(15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        $this->MultiCell(0, 4, 'Ce document est une simulation indicative et ne constitue pas une offre de crédit ferme. La décision finale est soumise à l\'approbation du comité de crédit et à la vérification des pièces justificatives. Les taux et conditions peuvent varier selon l\'analyse complète du dossier.', 0, 'J');
    }
    
    /**
     * Draw the decision box with color coding
     */
    private function drawDecisionBox($decision, $score) {
        $colors = [
            'ACCORDE' => [34, 197, 94],      // Green
            'REFUSE' => [239, 68, 68],       // Red
            'A_ANALYSER' => [245, 158, 11]  // Yellow/Orange
        ];
        
        $labels = [
            'ACCORDE' => 'CRÉDIT APPROUVÉ',
            'REFUSE' => 'CRÉDIT REFUSÉ',
            'A_ANALYSER' => 'ÉTUDE MANUELLE REQUISE'
        ];
        
        $color = $colors[$decision] ?? [128, 128, 128];
        $label = $labels[$decision] ?? 'INCONNU';
        
        // Box background
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->RoundedRect(10, $this->GetY(), 190, 25, 3, '1111', 'F');
        
        // Decision text
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(130, 25, $label, 0, 0, 'C');
        
        // Score
        $this->SetFont('helvetica', 'B', 24);
        $this->Cell(60, 25, number_format($score, 1) . '/100', 0, 1, 'C');
        
        // Reset colors
        $this->SetTextColor(51, 51, 51);
    }
    
    /**
     * Draw score breakdown table
     */
    private function drawScoreTable($scoreDetails, $totalScore) {
        $this->SetFont('helvetica', 'B', 9);
        $this->SetFillColor(240, 240, 240);
        $this->SetDrawColor(200, 200, 200);
        
        // Header
        $this->Cell(80, 8, 'Critère', 1, 0, 'L', true);
        $this->Cell(35, 8, 'Score', 1, 0, 'C', true);
        $this->Cell(35, 8, 'Maximum', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Pourcentage', 1, 1, 'C', true);
        
        $this->SetFont('helvetica', '', 9);
        
        if (!empty($scoreDetails)) {
            $criteriaMeta = ScoringEngine::getCriteriaLabels();
            foreach ($scoreDetails as $criterion => $scoreValue) {
                $meta = $criteriaMeta[$criterion] ?? ['label' => $criterion, 'max' => 25];
                $percentage = ($scoreValue / $meta['max']) * 100;
                
                $this->Cell(80, 7, $meta['label'], 1, 0, 'L');
                $this->Cell(35, 7, number_format($scoreValue, 1), 1, 0, 'C');
                $this->Cell(35, 7, number_format($meta['max'], 1), 1, 0, 'C');
                $this->Cell(40, 7, number_format($percentage, 1) . '%', 1, 1, 'C');
            }
        }
        
        // Total row
        $this->SetFont('helvetica', 'B', 10);
        $this->SetFillColor(0, 82, 155);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(80, 8, 'SCORE TOTAL', 1, 0, 'L', true);
        $this->Cell(35, 8, number_format($totalScore, 1), 1, 0, 'C', true);
        $this->Cell(35, 8, '100', 1, 0, 'C', true);
        $this->Cell(40, 8, number_format($totalScore, 1) . '%', 1, 1, 'C', true);
        
        $this->SetTextColor(51, 51, 51);
    }
}
