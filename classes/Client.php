<?php
/**
 * Client Model Class
 * 
 * Handles all client-related database operations including
 * CRUD operations, search, and validation.
 * Adapté au nouveau schéma : table client unique sans prenom/email
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
        $sql = "SELECT * FROM client WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    /**
     * Find client by CIN
     * 
     * @param string $cin CIN number
     * @return array|false Client data or false
     */
    public function findByCIN(string $cin): array|false {
        $sql = "SELECT * FROM client WHERE cin = ?";
        return $this->db->fetchOne($sql, [$cin]);
    }
    
    /**
     * Get all clients with optional filtering and pagination
     * 
     * @param array $filters Filters (search, situation_pro)
     * @param int $limit Number of results
     * @param int $offset Offset for pagination
     * @return array Clients list
     */
    public function getAll(array $filters = [], int $limit = 10, int $offset = 0): array {
        $sql = "SELECT * FROM client WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (nom LIKE ? OR cin LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search]);
        }
        
        if (!empty($filters['situation_pro'])) {
            $sql .= " AND situation_pro = ?";
            $params[] = $filters['situation_pro'];
        }
        
        $sql .= " ORDER BY id DESC";
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
        $sql = "SELECT COUNT(*) FROM client WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (nom LIKE ? OR cin LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search]);
        }
        
        if (!empty($filters['situation_pro'])) {
            $sql .= " AND situation_pro = ?";
            $params[] = $filters['situation_pro'];
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
        if ($this->db->exists('client', 'cin', $data['cin'])) {
            throw new Exception("Un client avec ce CIN existe déjà.");
        }
        
        return $this->db->insert('client', $data);
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
        if (!empty($data['cin']) && $this->db->exists('client', 'cin', $data['cin'], $id)) {
            throw new Exception("Un autre client avec ce CIN existe déjà.");
        }
        
        return $this->db->update('client', $data, 'id = ?', [$id]);
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
            "SELECT COUNT(*) FROM demande_credit WHERE client_id = ?",
            [$id]
        );
        
        if ($hasRequests > 0) {
            throw new Exception("Impossible de supprimer ce client car il a des demandes de crédit associées.");
        }
        
        return $this->db->delete('client', 'id = ?', [$id]);
    }
    
    /**
     * Get client statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array {
        return [
            'total' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM client"),
            'by_situation' => $this->db->fetchAll(
                "SELECT situation_pro, COUNT(*) as count 
                 FROM client 
                 GROUP BY situation_pro 
                 ORDER BY count DESC"
            ),
            'recent' => (int) $this->db->fetchValue(
                "SELECT COUNT(*) FROM client ORDER BY id DESC LIMIT 5"
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
        $sql = "SELECT * FROM client ORDER BY id DESC LIMIT ?";
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
        $sql = "SELECT id, cin, nom 
                FROM client 
                WHERE nom LIKE ? OR cin LIKE ? 
                ORDER BY nom 
                LIMIT ?";
        $search = '%' . $query . '%';
        return $this->db->fetchAll($sql, [$search, $search, $limit]);
    }
    
    /**
     * Get client's credit requests
     * 
     * @param int $clientId Client ID
     * @return array Credit requests
     */
    public function getCreditRequests(int $clientId): array {
        $sql = "SELECT dc.*, s.valeur_totale, d.resultat 
                FROM demande_credit dc 
                LEFT JOIN score s ON dc.id = s.demande_id 
                LEFT JOIN decision d ON s.id = d.score_id 
                WHERE dc.client_id = ? 
                ORDER BY dc.date_creation DESC";
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
        
        if (!$client || $client['revenu_mensuel_net'] <= 0) {
            return 100;
        }
        
        $totalCharges = $client['charges_mensuelles'] + $newMonthlyPayment;
        return ($totalCharges / $client['revenu_mensuel_net']) * 100;
    }
    
    /**
     * Get professional situations for dropdown
     * 
     * @return array Situations
     */
    public static function getSituations(): array {
        return [
            'CDI' => 'CDI (Contrat à durée indéterminée)',
            'FONCTIONNAIRE' => 'Fonctionnaire',
            'INDEPENDANT' => 'Travailleur indépendant',
            'SANS_EMPLOI' => 'Sans emploi'
        ];
    }
    
    /**
     * Get credit history options for dropdown
     * 
     * @return array History options
     */
    public static function getCreditHistoryOptions(): array {
        return [
            1 => 'Bon historique (Aucun incident)',
            0 => 'Mauvais historique (Incidents)',
        ];
    }
}
