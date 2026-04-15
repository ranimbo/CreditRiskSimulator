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
    
    // Scoring thresholds
    private int $approvalThreshold = 70;
    private int $reviewThreshold = 50;
    
    // Score breakdown
    private array $scores = [];
    private array $favorableFactors = [];
    private array $unfavorableFactors = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
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
            $creditRequest['montant_demande'],
            5.00, // Default 5% as no taux in new schema
            $creditRequest['duree']
        );
        
        // Calculate debt ratio including new credit
        $totalCharges = $client['charges_mensuelles'] + $monthlyPayment;
        $debtRatio = ($client['revenu_mensuel_net'] > 0) 
            ? ($totalCharges / $client['revenu_mensuel_net']) * 100 
            : 100;
        
        // Calculate age
        $age = calculateAge($client['date_naissance']);
        
        // 1. Score Revenu Mensuel (25 points max)
        $this->scores['score_revenu'] = $this->scoreRevenu($client['revenu_mensuel_net']);
        
        // 2. Score Taux d'Endettement (25 points max)
        $this->scores['score_endettement'] = $this->scoreEndettement($debtRatio);
        
        // 3. Score Situation Professionnelle (15 points max)
        $this->scores['score_situation_pro'] = $this->scoreSituationPro($client['situation_pro']);
        
        // 4. Score Ancienneté Emploi (10 points max)
        $this->scores['score_anciennete'] = $this->scoreAnciennete($client['anciennete_emploi']);
        
        // 5. Score Historique Crédit (15 points max)
        $this->scores['score_historique'] = $this->scoreHistorique((bool)$client['historique_credit']);
        
        // 6. Score Âge (10 points max)
        $this->scores['score_age'] = $this->scoreAge($age);
        
        // Calculate total score
        $totalScore = array_sum($this->scores);
        
        // Determine decision
        $decision = $this->determineDecision($totalScore);
        
        // Calculate repayment capacity
        $repaymentCapacity = $client['revenu_mensuel_net'] - $client['charges_mensuelles'];
        
        return [
            'score_revenu' => $this->scores['score_revenu'],
            'score_endettement' => $this->scores['score_endettement'],
            'score_situation_pro' => $this->scores['score_situation_pro'],
            'score_anciennete' => $this->scores['score_anciennete'],
            'score_historique' => $this->scores['score_historique'],
            'score_age' => $this->scores['score_age'],
            'score_total' => $totalScore,
            'taux_endettement' => round($debtRatio, 2),
            'capacite_remboursement' => $repaymentCapacity,
            'mensualite' => round($monthlyPayment, 2),
            'cout_total' => round($monthlyPayment * $creditRequest['duree'], 2),
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
            'FONCTIONNAIRE' => 14,
            'INDEPENDANT' => 10,
            'SANS_EMPLOI' => 0,
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
    private function scoreHistorique(bool $bonHistorique): int {
        if ($bonHistorique) {
            $this->favorableFactors[] = "Aucun incident de paiement";
            return 15;
        } else {
            $this->unfavorableFactors[] = "Incidents de paiement présents";
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
            return 'ACCORDE';
        } elseif ($score >= $this->reviewThreshold) {
            return 'A_ANALYSER';
        } else {
            return 'REFUSE';
        }
    }
    
    /**
     * Generate justification text for the decision
     */
    private function generateJustification(int $score, string $decision): string {
        $justifications = [];
        
        switch ($decision) {
            case 'ACCORDE':
                $justifications[] = "Score de $score/100 - Profil éligible au crédit.";
                if (count($this->favorableFactors) > 0) {
                    $justifications[] = "Points forts: " . implode(', ', array_slice($this->favorableFactors, 0, 3));
                }
                break;
                
            case 'A_ANALYSER':
                $justifications[] = "Score de $score/100 - Profil nécessitant une analyse approfondie.";
                $justifications[] = "La décision finale doit être prise par un superviseur après examen du dossier.";
                break;
                
            case 'REFUSE':
                $justifications[] = "Score de $score/100 - Profil à risque élevé.";
                if (count($this->unfavorableFactors) > 0) {
                    $justifications[] = "Motifs principaux: " . implode(', ', array_slice($this->unfavorableFactors, 0, 3));
                }
                break;
        }
        
        return implode(' ', $justifications);
    }
    
    /**
     * Get decision label in French
     */
    public static function getDecisionLabel(string $decision): string {
        $labels = [
            'ACCORDE' => 'Approuvé',
            'REFUSE' => 'Refusé',
            'A_ANALYSER' => 'À Analyser',
            'en_attente' => 'En attente',
        ];
        return $labels[$decision] ?? $decision;
    }
    
    /**
     * Get decision color class
     */
    public static function getDecisionColorClass(string $decision): string {
        return match($decision) {
            'ACCORDE' => 'text-green-600 bg-green-100',
            'REFUSE' => 'text-red-600 bg-red-100',
            'A_ANALYSER' => 'text-yellow-600 bg-yellow-100',
            default => 'text-slate-600 bg-slate-100',
        };
    }
    
    /**
     * Get criteria metadata
     */
    public static function getCriteriaLabels(): array {
        return [
            'score_revenu' => ['label' => 'Revenu Mensuel', 'max' => 25],
            'score_endettement' => ['label' => 'Taux d\'endettement', 'max' => 25],
            'score_situation_pro' => ['label' => 'Situation professionnelle', 'max' => 15],
            'score_anciennete' => ['label' => 'Ancienneté d\'emploi', 'max' => 10],
            'score_historique' => ['label' => 'Historique crédit', 'max' => 15],
            'score_age' => ['label' => 'Âge', 'max' => 10],
        ];
    }
}
