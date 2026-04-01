<?php
/**
 * Scoring Engine Class
 * 
 * Handles credit risk scoring calculations based on multiple criteria.
 * Each criterion contributes to a total score out of 100 points.
 */

require_once __DIR__ . '/Database.php';

class ScoringEngine {
    private Database $db;
    
    // Scoring thresholds (can be loaded from settings)
    private int $approvalThreshold = 70;
    private int $reviewThreshold = 50;
    
    // Score breakdown
    private array $scores = [];
    private array $favorableFactors = [];
    private array $unfavorableFactors = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadThresholds();
    }
    
    /**
     * Load thresholds from database settings
     */
    private function loadThresholds(): void {
        $approval = $this->db->fetchOne("SELECT valeur FROM settings WHERE cle = 'seuil_approbation'");
        if ($approval) {
            $this->approvalThreshold = (int) $approval['valeur'];
        }
        
        $review = $this->db->fetchOne("SELECT valeur FROM settings WHERE cle = 'seuil_revision'");
        if ($review) {
            $this->reviewThreshold = (int) $review['valeur'];
        }
    }
    
    /**
     * Calculate complete score for a client and credit request
     * 
     * @param array $client Client data
     * @param array $creditRequest Credit request data
     * @return array Complete scoring result
     */
    public function calculateScore(array $client, array $creditRequest): array {
        // Reset scores and factors
        $this->scores = [];
        $this->favorableFactors = [];
        $this->unfavorableFactors = [];
        
        // Calculate monthly payment
        $monthlyPayment = calculateMonthlyPayment(
            $creditRequest['montant'],
            $creditRequest['taux_annuel'],
            $creditRequest['duree_mois']
        );
        
        // Calculate debt ratio including new credit
        $totalCharges = $client['charges_mensuelles'] + $monthlyPayment;
        $debtRatio = ($client['revenu_mensuel'] > 0) 
            ? ($totalCharges / $client['revenu_mensuel']) * 100 
            : 100;
        
        // Calculate age
        $age = calculateAge($client['date_naissance']);
        
        // 1. Score Revenu Mensuel (25 points max)
        $this->scores['revenu'] = $this->scoreRevenu($client['revenu_mensuel']);
        
        // 2. Score Taux d'Endettement (25 points max)
        $this->scores['endettement'] = $this->scoreEndettement($debtRatio);
        
        // 3. Score Situation Professionnelle (15 points max)
        $this->scores['situation_pro'] = $this->scoreSituationPro($client['situation_professionnelle']);
        
        // 4. Score Ancienneté Emploi (10 points max)
        $this->scores['anciennete'] = $this->scoreAnciennete($client['anciennete_emploi']);
        
        // 5. Score Historique Crédit (15 points max)
        $this->scores['historique'] = $this->scoreHistorique($client['historique_credit']);
        
        // 6. Score Âge (10 points max)
        $this->scores['age'] = $this->scoreAge($age);
        
        // Calculate total score
        $totalScore = array_sum($this->scores);
        
        // Determine decision
        $decision = $this->determineDecision($totalScore);
        
        // Calculate repayment capacity
        $repaymentCapacity = $client['revenu_mensuel'] - $client['charges_mensuelles'];
        
        return [
            'score_revenu' => $this->scores['revenu'],
            'score_endettement' => $this->scores['endettement'],
            'score_situation_pro' => $this->scores['situation_pro'],
            'score_anciennete' => $this->scores['anciennete'],
            'score_historique' => $this->scores['historique'],
            'score_age' => $this->scores['age'],
            'score_total' => $totalScore,
            'taux_endettement' => round($debtRatio, 2),
            'capacite_remboursement' => $repaymentCapacity,
            'mensualite' => round($monthlyPayment, 2),
            'cout_total' => round($monthlyPayment * $creditRequest['duree_mois'], 2),
            'facteurs_favorables' => $this->favorableFactors,
            'facteurs_defavorables' => $this->unfavorableFactors,
            'decision' => $decision,
            'justification' => $this->generateJustification($totalScore, $decision),
        ];
    }
    
    /**
     * Score monthly income (25 points max)
     */
    private function scoreRevenu(float $revenu): int {
        if ($revenu >= 5000) {
            $this->favorableFactors[] = "Revenu mensuel excellent (" . formatCurrency($revenu) . ")";
            return 25;
        } elseif ($revenu >= 3000) {
            $this->favorableFactors[] = "Revenu mensuel très bon (" . formatCurrency($revenu) . ")";
            return 20;
        } elseif ($revenu >= 2000) {
            return 15;
        } elseif ($revenu >= 1000) {
            $this->unfavorableFactors[] = "Revenu mensuel modeste (" . formatCurrency($revenu) . ")";
            return 10;
        } else {
            $this->unfavorableFactors[] = "Revenu mensuel faible (" . formatCurrency($revenu) . ")";
            return 5;
        }
    }
    
    /**
     * Score debt ratio (25 points max)
     */
    private function scoreEndettement(float $ratio): int {
        if ($ratio < 30) {
            $this->favorableFactors[] = "Taux d'endettement faible (" . formatPercentage($ratio, 1) . ")";
            return 25;
        } elseif ($ratio < 40) {
            return 15;
        } elseif ($ratio < 50) {
            $this->unfavorableFactors[] = "Taux d'endettement élevé (" . formatPercentage($ratio, 1) . ")";
            return 8;
        } else {
            $this->unfavorableFactors[] = "Taux d'endettement critique (" . formatPercentage($ratio, 1) . ")";
            return 0;
        }
    }
    
    /**
     * Score professional situation (15 points max)
     */
    private function scoreSituationPro(string $situation): int {
        $scores = [
            'CDI' => 15,
            'Fonctionnaire' => 14,
            'Independant' => 10,
            'Retraite' => 8,
            'CDD' => 6,
            'Sans emploi' => 0,
        ];
        
        $score = $scores[$situation] ?? 0;
        
        if ($score >= 14) {
            $this->favorableFactors[] = "Emploi stable ($situation)";
        } elseif ($score <= 6) {
            $this->unfavorableFactors[] = "Situation professionnelle instable ($situation)";
        }
        
        return $score;
    }
    
    /**
     * Score employment tenure (10 points max)
     */
    private function scoreAnciennete(int $mois): int {
        if ($mois >= 60) {
            $this->favorableFactors[] = "Ancienneté excellente (" . getAncienneteLabel($mois) . ")";
            return 10;
        } elseif ($mois >= 24) {
            $this->favorableFactors[] = "Bonne ancienneté (" . getAncienneteLabel($mois) . ")";
            return 7;
        } elseif ($mois >= 12) {
            return 4;
        } else {
            $this->unfavorableFactors[] = "Ancienneté faible (" . getAncienneteLabel($mois) . ")";
            return 2;
        }
    }
    
    /**
     * Score credit history (15 points max)
     */
    private function scoreHistorique(string $historique): int {
        switch ($historique) {
            case 'Aucun incident':
                $this->favorableFactors[] = "Aucun incident de paiement";
                return 15;
            case 'Un incident':
                $this->unfavorableFactors[] = "Un incident de paiement dans l'historique";
                return 8;
            case 'Plusieurs incidents':
                $this->unfavorableFactors[] = "Plusieurs incidents de paiement";
                return 0;
            default:
                return 0;
        }
    }
    
    /**
     * Score age (10 points max)
     */
    private function scoreAge(int $age): int {
        if ($age >= 25 && $age <= 45) {
            $this->favorableFactors[] = "Âge optimal ($age ans)";
            return 10;
        } elseif ($age > 45 && $age <= 60) {
            return 8;
        } elseif ($age > 60) {
            $this->unfavorableFactors[] = "Âge avancé ($age ans)";
            return 5;
        } else {
            $this->unfavorableFactors[] = "Jeune profil ($age ans)";
            return 4;
        }
    }
    
    /**
     * Determine decision based on total score
     */
    private function determineDecision(int $score): string {
        if ($score >= $this->approvalThreshold) {
            return 'approuve';
        } elseif ($score >= $this->reviewThreshold) {
            return 'en_revision';
        } else {
            return 'refuse';
        }
    }
    
    /**
     * Generate justification text for the decision
     */
    private function generateJustification(int $score, string $decision): string {
        $justifications = [];
        
        switch ($decision) {
            case 'approuve':
                $justifications[] = "Score de $score/100 - Profil éligible au crédit.";
                if (count($this->favorableFactors) > 0) {
                    $justifications[] = "Points forts: " . implode(', ', array_slice($this->favorableFactors, 0, 3));
                }
                break;
                
            case 'en_revision':
                $justifications[] = "Score de $score/100 - Profil nécessitant une analyse approfondie.";
                $justifications[] = "La décision finale doit être prise par un superviseur après examen du dossier.";
                break;
                
            case 'refuse':
                $justifications[] = "Score de $score/100 - Profil à risque élevé.";
                if (count($this->unfavorableFactors) > 0) {
                    $justifications[] = "Motifs principaux: " . implode(', ', array_slice($this->unfavorableFactors, 0, 3));
                }
                break;
        }
        
        return implode(' ', $justifications);
    }
    
    /**
     * Get score criteria labels
     */
    public static function getCriteriaLabels(): array {
        return [
            'revenu' => ['label' => 'Revenu mensuel', 'max' => 25, 'weight' => '25%'],
            'endettement' => ['label' => 'Taux d\'endettement', 'max' => 25, 'weight' => '25%'],
            'situation_pro' => ['label' => 'Situation professionnelle', 'max' => 15, 'weight' => '15%'],
            'anciennete' => ['label' => 'Ancienneté emploi', 'max' => 10, 'weight' => '10%'],
            'historique' => ['label' => 'Historique crédit', 'max' => 15, 'weight' => '15%'],
            'age' => ['label' => 'Âge', 'max' => 10, 'weight' => '10%'],
        ];
    }
    
    /**
     * Get decision label in French
     */
    public static function getDecisionLabel(string $decision): string {
        $labels = [
            'approuve' => 'Approuvé',
            'refuse' => 'Refusé',
            'en_revision' => 'En révision manuelle',
            'en_attente' => 'En attente',
        ];
        return $labels[$decision] ?? $decision;
    }
    
    /**
     * Get decision color class
     */
    public static function getDecisionColorClass(string $decision): string {
        return match($decision) {
            'approuve' => 'text-green-600 bg-green-100',
            'refuse' => 'text-red-600 bg-red-100',
            'en_revision' => 'text-yellow-600 bg-yellow-100',
            default => 'text-slate-600 bg-slate-100',
        };
    }
}
