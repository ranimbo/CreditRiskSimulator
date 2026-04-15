<?php
/**
 * Credit Request Model Class
 * 
 * Handles all credit request-related database operations.
 * Adapté au nouveau schéma : demande_credit, score, decision
 */

require_once __DIR__ . '/Database.php';

class CreditRequest {
    private Database $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find credit request by ID with related data
     * 
     * @param int $id Credit request ID
     * @return array|false Request data or false
     */
    public function findById(int $id): array|false {
        $sql = "SELECT dc.*, 
                c.nom as client_nom, c.cin, c.situation_pro,
                c.revenu_mensuel_net, c.charges_mensuelles,
                c.historique_credit, c.anciennete_emploi, c.date_naissance,
                u.nom as agent_nom,
                s.valeur_totale, s.detail_par_critere, s.date_calcul,
                d.resultat, d.justification, d.facteurs_favorables, d.facteurs_defavorables, d.date_decision
                FROM demande_credit dc
                LEFT JOIN client c ON dc.client_id = c.id
                LEFT JOIN agent_bancaire ab ON dc.agent_id = ab.id
                LEFT JOIN utilisateur u ON ab.id = u.id
                LEFT JOIN score s ON dc.id = s.demande_id
                LEFT JOIN decision d ON s.id = d.score_id
                WHERE dc.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Get all credit requests with filters and pagination
     */
    public function getAll(array $filters = [], int $limit = 10, int $offset = 0): array {
        $sql = "SELECT dc.*, 
                c.nom as client_nom, c.cin,
                u.nom as agent_nom,
                s.valeur_totale, d.resultat
                FROM demande_credit dc
                LEFT JOIN client c ON dc.client_id = c.id
                LEFT JOIN agent_bancaire ab ON dc.agent_id = ab.id
                LEFT JOIN utilisateur u ON ab.id = u.id
                LEFT JOIN score s ON dc.id = s.demande_id
                LEFT JOIN decision d ON s.id = d.score_id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.nom LIKE ? OR c.cin LIKE ? OR dc.id = ?)";
            $search = '%' . $filters['search'] . '%';
            $searchId = intval($filters['search']);
            $params = array_merge($params, [$search, $search, $searchId]);
        }
        
        if (!empty($filters['statut'])) {
            $sql .= " AND dc.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['agent_id'])) {
            $sql .= " AND dc.agent_id = ?";
            $params[] = $filters['agent_id'];
        }
        
        if (!empty($filters['client_id'])) {
            $sql .= " AND dc.client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        $sql .= " ORDER BY dc.date_creation DESC";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Count credit requests with filters
     */
    public function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM demande_credit dc
                LEFT JOIN client c ON dc.client_id = c.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.nom LIKE ? OR c.cin LIKE ? OR dc.id = ?)";
            $search = '%' . $filters['search'] . '%';
            $searchId = intval($filters['search']);
            $params = array_merge($params, [$search, $search, $searchId]);
        }
        
        if (!empty($filters['statut'])) {
            $sql .= " AND dc.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['agent_id'])) {
            $sql .= " AND dc.agent_id = ?";
            $params[] = $filters['agent_id'];
        }
        
        if (!empty($filters['client_id'])) {
            $sql .= " AND dc.client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        return (int) $this->db->fetchValue($sql, $params);
    }
    
    /**
     * Create a new credit request with score
     */
    public function create(array $requestData, array $scoreData): int {
        $this->db->beginTransaction();
        
        try {
            // Set status based on decision
            $requestData['statut'] = $scoreData['decision'];
            $requestData['date_creation'] = date('Y-m-d H:i:s');
            
            // Insert credit request
            $requestId = $this->db->insert('demande_credit', $requestData);
            
            // Generate detail JSON
            $detailCritere = json_encode([
                'revenu' => $scoreData['score_revenu'],
                'endettement' => $scoreData['score_endettement'],
                'situation_pro' => $scoreData['score_situation_pro'],
                'anciennete' => $scoreData['score_anciennete'],
                'historique' => $scoreData['score_historique'],
                'age' => $scoreData['score_age']
            ], JSON_UNESCAPED_UNICODE);

            // Insert score
            $scoreInsertData = [
                'demande_id' => $requestId,
                'valeur_totale' => $scoreData['score_total'],
                'date_calcul' => date('Y-m-d H:i:s'),
                'detail_par_critere' => $detailCritere,
                'version_moteur' => '1.0',
                'temps_calcul' => 0.05
            ];
            $scoreId = $this->db->insert('score', $scoreInsertData);
            
            // Insert decision
            $decisionData = [
                'score_id' => $scoreId,
                'resultat' => $scoreData['decision'],
                'justification' => $scoreData['justification'],
                'facteurs_favorables' => json_encode($scoreData['facteurs_favorables'], JSON_UNESCAPED_UNICODE),
                'facteurs_defavorables' => json_encode($scoreData['facteurs_defavorables'], JSON_UNESCAPED_UNICODE),
                'date_decision' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('decision', $decisionData);
            
            $this->db->commit();
            
            return $requestId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update credit request decision
     */
    public function updateDecision(int $id, string $decision, string $justification): bool {
        $this->db->beginTransaction();
        
        try {
            // Update credit request status
            $this->db->update('demande_credit', [
                'statut' => $decision
            ], 'id = ?', [$id]);
            
            // Need to find score ID to update decision
            $scoreId = $this->db->fetchValue("SELECT id FROM score WHERE demande_id = ?", [$id]);
            if ($scoreId) {
                // Update decision record
                $this->db->update('decision', [
                    'resultat' => $decision,
                    'justification' => $justification,
                    'date_decision' => date('Y-m-d H:i:s')
                ], 'score_id = ?', [$scoreId]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get statistics
     */
    public function getStats(): array {
        return [
            'total' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM demande_credit"),
            'approved' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'ACCORDE'"),
            'refused' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'REFUSE'"),
            'pending' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'en_attente'"),
            'in_review' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM demande_credit WHERE statut = 'A_ANALYSER'"),
            'total_amount' => (float) $this->db->fetchValue("SELECT COALESCE(SUM(montant_demande), 0) FROM demande_credit WHERE statut = 'ACCORDE'"),
            'avg_score' => (float) $this->db->fetchValue("SELECT COALESCE(AVG(valeur_totale), 0) FROM score"),
        ];
    }
    
    /**
     * Get status options
     */
    public static function getStatusOptions(): array {
        return [
            'en_attente' => 'En attente',
            'ACCORDE' => 'Approuvé',
            'REFUSE' => 'Refusé',
            'A_ANALYSER' => 'À Analyser',
        ];
    }
}
