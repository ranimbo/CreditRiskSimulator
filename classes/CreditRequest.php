<?php
/**
 * Credit Request Model Class
 * 
 * Handles all credit request-related database operations.
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
        $sql = "SELECT cr.*, 
                c.nom as client_nom, c.prenom as client_prenom, c.cin, c.email as client_email,
                c.telephone as client_telephone, c.date_naissance, c.situation_professionnelle,
                c.employeur, c.anciennete_emploi, c.revenu_mensuel, c.charges_mensuelles,
                c.historique_credit, c.adresse, c.ville,
                u.nom as agent_nom, u.prenom as agent_prenom, u.email as agent_email,
                s.score_revenu, s.score_endettement, s.score_situation_pro, s.score_anciennete,
                s.score_historique, s.score_age, s.score_total, s.taux_endettement,
                s.capacite_remboursement, s.facteurs_favorables, s.facteurs_defavorables,
                d.decision, d.justification, d.conditions, d.date_decision
                FROM credit_requests cr
                LEFT JOIN clients c ON cr.client_id = c.id
                LEFT JOIN users u ON cr.agent_id = u.id
                LEFT JOIN scores s ON cr.id = s.credit_request_id
                LEFT JOIN decisions d ON cr.id = d.credit_request_id
                WHERE cr.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Find credit request by reference
     * 
     * @param string $reference Reference number
     * @return array|false Request data or false
     */
    public function findByReference(string $reference): array|false {
        $sql = "SELECT cr.*, c.nom as client_nom, c.prenom as client_prenom
                FROM credit_requests cr
                LEFT JOIN clients c ON cr.client_id = c.id
                WHERE cr.reference = ?";
        return $this->db->fetchOne($sql, [$reference]);
    }
    
    /**
     * Get all credit requests with filters and pagination
     * 
     * @param array $filters Filters
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Credit requests
     */
    public function getAll(array $filters = [], int $limit = 10, int $offset = 0): array {
        $sql = "SELECT cr.*, 
                c.nom as client_nom, c.prenom as client_prenom, c.cin,
                u.nom as agent_nom, u.prenom as agent_prenom,
                s.score_total
                FROM credit_requests cr
                LEFT JOIN clients c ON cr.client_id = c.id
                LEFT JOIN users u ON cr.agent_id = u.id
                LEFT JOIN scores s ON cr.id = s.credit_request_id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (cr.reference LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ? OR c.cin LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['statut'])) {
            $sql .= " AND cr.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['agent_id'])) {
            $sql .= " AND cr.agent_id = ?";
            $params[] = $filters['agent_id'];
        }
        
        if (!empty($filters['client_id'])) {
            $sql .= " AND cr.client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(cr.date_demande) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(cr.date_demande) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " ORDER BY cr.date_demande DESC";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Count credit requests with filters
     * 
     * @param array $filters Filters
     * @return int Count
     */
    public function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM credit_requests cr
                LEFT JOIN clients c ON cr.client_id = c.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (cr.reference LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ? OR c.cin LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['statut'])) {
            $sql .= " AND cr.statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['agent_id'])) {
            $sql .= " AND cr.agent_id = ?";
            $params[] = $filters['agent_id'];
        }
        
        if (!empty($filters['client_id'])) {
            $sql .= " AND cr.client_id = ?";
            $params[] = $filters['client_id'];
        }
        
        return (int) $this->db->fetchValue($sql, $params);
    }
    
    /**
     * Create a new credit request with score
     * 
     * @param array $requestData Credit request data
     * @param array $scoreData Score data
     * @return int New credit request ID
     */
    public function create(array $requestData, array $scoreData): int {
        $this->db->beginTransaction();
        
        try {
            // Generate unique reference
            $requestData['reference'] = $this->generateUniqueReference();
            
            // Set status based on decision
            $requestData['statut'] = $scoreData['decision'];
            $requestData['mensualite'] = $scoreData['mensualite'];
            $requestData['cout_total'] = $scoreData['cout_total'];
            
            if ($scoreData['decision'] !== 'en_revision') {
                $requestData['date_decision'] = date('Y-m-d H:i:s');
            }
            
            // Insert credit request
            $requestId = $this->db->insert('credit_requests', $requestData);
            
            // Insert score
            $scoreInsertData = [
                'credit_request_id' => $requestId,
                'score_revenu' => $scoreData['score_revenu'],
                'score_endettement' => $scoreData['score_endettement'],
                'score_situation_pro' => $scoreData['score_situation_pro'],
                'score_anciennete' => $scoreData['score_anciennete'],
                'score_historique' => $scoreData['score_historique'],
                'score_age' => $scoreData['score_age'],
                'score_total' => $scoreData['score_total'],
                'taux_endettement' => $scoreData['taux_endettement'],
                'capacite_remboursement' => $scoreData['capacite_remboursement'],
                'facteurs_favorables' => json_encode($scoreData['facteurs_favorables'], JSON_UNESCAPED_UNICODE),
                'facteurs_defavorables' => json_encode($scoreData['facteurs_defavorables'], JSON_UNESCAPED_UNICODE),
            ];
            $this->db->insert('scores', $scoreInsertData);
            
            // Insert decision
            $decisionData = [
                'credit_request_id' => $requestId,
                'agent_id' => $requestData['agent_id'],
                'decision' => $scoreData['decision'],
                'justification' => $scoreData['justification'],
            ];
            $this->db->insert('decisions', $decisionData);
            
            $this->db->commit();
            
            return $requestId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update credit request decision
     * 
     * @param int $id Credit request ID
     * @param string $decision New decision
     * @param string $justification Justification
     * @param int $agentId Agent making the decision
     * @return bool Success
     */
    public function updateDecision(int $id, string $decision, string $justification, int $agentId): bool {
        $this->db->beginTransaction();
        
        try {
            // Update credit request status
            $this->db->update('credit_requests', [
                'statut' => $decision,
                'date_decision' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            
            // Update decision record
            $this->db->update('decisions', [
                'decision' => $decision,
                'justification' => $justification,
                'agent_id' => $agentId,
                'date_decision' => date('Y-m-d H:i:s'),
            ], 'credit_request_id = ?', [$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Generate unique reference number
     * 
     * @return string Unique reference
     */
    private function generateUniqueReference(): string {
        do {
            $reference = 'CR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            $exists = $this->db->exists('credit_requests', 'reference', $reference);
        } while ($exists);
        
        return $reference;
    }
    
    /**
     * Get statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array {
        return [
            'total' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM credit_requests"),
            'approved' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM credit_requests WHERE statut = 'approuve'"),
            'refused' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM credit_requests WHERE statut = 'refuse'"),
            'pending' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM credit_requests WHERE statut = 'en_attente'"),
            'in_review' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM credit_requests WHERE statut = 'en_revision'"),
            'total_amount' => (float) $this->db->fetchValue("SELECT COALESCE(SUM(montant), 0) FROM credit_requests WHERE statut = 'approuve'"),
            'avg_score' => (float) $this->db->fetchValue("SELECT COALESCE(AVG(score_total), 0) FROM scores"),
        ];
    }
    
    /**
     * Get status options
     * 
     * @return array Status options
     */
    public static function getStatusOptions(): array {
        return [
            'en_attente' => 'En attente',
            'approuve' => 'Approuvé',
            'refuse' => 'Refusé',
            'en_revision' => 'En révision',
        ];
    }
}
