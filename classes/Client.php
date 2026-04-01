<?php
/**
 * Client Model Class
 * 
 * Handles all client-related database operations including
 * CRUD operations, search, and validation.
 */

require_once __DIR__ . '/Database.php';

class Client {
    private Database $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find client by ID
     * 
     * @param int $id Client ID
     * @return array|false Client data or false
     */
    public function findById(int $id): array|false {
        $sql = "SELECT c.*, u.nom as agent_nom, u.prenom as agent_prenom 
                FROM clients c 
                LEFT JOIN users u ON c.agent_id = u.id 
                WHERE c.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Find client by CIN
     * 
     * @param string $cin CIN number
     * @return array|false Client data or false
     */
    public function findByCIN(string $cin): array|false {
        $sql = "SELECT * FROM clients WHERE cin = ?";
        return $this->db->fetchOne($sql, [$cin]);
    }
    
    /**
     * Get all clients with optional filtering and pagination
     * 
     * @param array $filters Filters (search, situation, agent_id)
     * @param int $limit Number of results
     * @param int $offset Offset for pagination
     * @return array Clients list
     */
    public function getAll(array $filters = [], int $limit = 10, int $offset = 0): array {
        $sql = "SELECT c.*, u.nom as agent_nom, u.prenom as agent_prenom 
                FROM clients c 
                LEFT JOIN users u ON c.agent_id = u.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.nom LIKE ? OR c.prenom LIKE ? OR c.cin LIKE ? OR c.email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['situation'])) {
            $sql .= " AND c.situation_professionnelle = ?";
            $params[] = $filters['situation'];
        }
        
        if (!empty($filters['agent_id'])) {
            $sql .= " AND c.agent_id = ?";
            $params[] = $filters['agent_id'];
        }
        
        $sql .= " ORDER BY c.date_creation DESC";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Count clients with filters
     * 
     * @param array $filters Filters
     * @return int Total count
     */
    public function count(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM clients c WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (c.nom LIKE ? OR c.prenom LIKE ? OR c.cin LIKE ? OR c.email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['situation'])) {
            $sql .= " AND c.situation_professionnelle = ?";
            $params[] = $filters['situation'];
        }
        
        if (!empty($filters['agent_id'])) {
            $sql .= " AND c.agent_id = ?";
            $params[] = $filters['agent_id'];
        }
        
        return (int) $this->db->fetchValue($sql, $params);
    }
    
    /**
     * Create a new client
     * 
     * @param array $data Client data
     * @return int New client ID
     * @throws Exception If CIN already exists
     */
    public function create(array $data): int {
        // Validate CIN uniqueness
        if ($this->db->exists('clients', 'cin', $data['cin'])) {
            throw new Exception("Un client avec ce CIN existe déjà.");
        }
        
        return $this->db->insert('clients', $data);
    }
    
    /**
     * Update client
     * 
     * @param int $id Client ID
     * @param array $data Client data
     * @return int Affected rows
     * @throws Exception If CIN already exists for another client
     */
    public function update(int $id, array $data): int {
        // Validate CIN uniqueness
        if (!empty($data['cin']) && $this->db->exists('clients', 'cin', $data['cin'], $id)) {
            throw new Exception("Un autre client avec ce CIN existe déjà.");
        }
        
        return $this->db->update('clients', $data, 'id = ?', [$id]);
    }
    
    /**
     * Delete client
     * 
     * @param int $id Client ID
     * @return int Affected rows
     * @throws Exception If client has credit requests
     */
    public function delete(int $id): int {
        // Check for credit requests
        $hasRequests = $this->db->fetchValue(
            "SELECT COUNT(*) FROM credit_requests WHERE client_id = ?",
            [$id]
        );
        
        if ($hasRequests > 0) {
            throw new Exception("Impossible de supprimer ce client car il a des demandes de crédit associées.");
        }
        
        return $this->db->delete('clients', 'id = ?', [$id]);
    }
    
    /**
     * Get client statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array {
        return [
            'total' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM clients"),
            'by_situation' => $this->db->fetchAll(
                "SELECT situation_professionnelle, COUNT(*) as count 
                 FROM clients 
                 GROUP BY situation_professionnelle 
                 ORDER BY count DESC"
            ),
            'recent' => (int) $this->db->fetchValue(
                "SELECT COUNT(*) FROM clients WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ),
        ];
    }
    
    /**
     * Get recent clients
     * 
     * @param int $limit Number of clients to return
     * @return array Recent clients
     */
    public function getRecent(int $limit = 5): array {
        $sql = "SELECT c.*, u.nom as agent_nom, u.prenom as agent_prenom 
                FROM clients c 
                LEFT JOIN users u ON c.agent_id = u.id 
                ORDER BY c.date_creation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Search clients for autocomplete
     * 
     * @param string $query Search query
     * @param int $limit Max results
     * @return array Matching clients
     */
    public function search(string $query, int $limit = 10): array {
        $sql = "SELECT id, cin, nom, prenom, email 
                FROM clients 
                WHERE nom LIKE ? OR prenom LIKE ? OR cin LIKE ? 
                ORDER BY nom, prenom 
                LIMIT ?";
        $search = '%' . $query . '%';
        return $this->db->fetchAll($sql, [$search, $search, $search, $limit]);
    }
    
    /**
     * Get client's credit requests
     * 
     * @param int $clientId Client ID
     * @return array Credit requests
     */
    public function getCreditRequests(int $clientId): array {
        $sql = "SELECT cr.*, s.score_total, d.decision 
                FROM credit_requests cr 
                LEFT JOIN scores s ON cr.id = s.credit_request_id 
                LEFT JOIN decisions d ON cr.id = d.credit_request_id 
                WHERE cr.client_id = ? 
                ORDER BY cr.date_demande DESC";
        return $this->db->fetchAll($sql, [$clientId]);
    }
    
    /**
     * Calculate client's current debt ratio including new potential credit
     * 
     * @param int $clientId Client ID
     * @param float $newMonthlyPayment New monthly payment to add
     * @return float Debt ratio percentage
     */
    public function calculateDebtRatio(int $clientId, float $newMonthlyPayment = 0): float {
        $client = $this->findById($clientId);
        
        if (!$client || $client['revenu_mensuel'] <= 0) {
            return 100;
        }
        
        $totalCharges = $client['charges_mensuelles'] + $newMonthlyPayment;
        return ($totalCharges / $client['revenu_mensuel']) * 100;
    }
    
    /**
     * Get professional situations for dropdown
     * 
     * @return array Situations
     */
    public static function getSituations(): array {
        return [
            'CDI' => 'CDI (Contrat à durée indéterminée)',
            'CDD' => 'CDD (Contrat à durée déterminée)',
            'Fonctionnaire' => 'Fonctionnaire',
            'Independant' => 'Travailleur indépendant',
            'Sans emploi' => 'Sans emploi',
            'Retraite' => 'Retraité(e)',
        ];
    }
    
    /**
     * Get credit history options for dropdown
     * 
     * @return array History options
     */
    public static function getCreditHistoryOptions(): array {
        return [
            'Aucun incident' => 'Aucun incident',
            'Un incident' => 'Un incident',
            'Plusieurs incidents' => 'Plusieurs incidents',
        ];
    }
}
