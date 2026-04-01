<?php
/**
 * User Model Class
 * 
 * Handles all user-related database operations including
 * authentication, CRUD operations, and user management.
 */

require_once __DIR__ . '/Database.php';

class User {
    private Database $db;
    
    // User properties
    public ?int $id = null;
    public ?string $email = null;
    public ?string $nom = null;
    public ?string $prenom = null;
    public ?string $role = null;
    public ?string $statut = null;
    public ?string $date_creation = null;
    public ?string $derniere_connexion = null;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate user with email and password
     * 
     * @param string $email User email
     * @param string $password Plain text password
     * @return User|false User object if authenticated, false otherwise
     */
    public function authenticate(string $email, string $password): User|false {
        $sql = "SELECT * FROM users WHERE email = ? AND statut = 'actif'";
        $userData = $this->db->fetchOne($sql, [$email]);
        
        if (!$userData) {
            return false;
        }
        
        if (!password_verify($password, $userData['password'])) {
            return false;
        }
        
        // Update last login time
        $this->db->update('users', 
            ['derniere_connexion' => date('Y-m-d H:i:s')],
            'id = ?',
            [$userData['id']]
        );
        
        // Populate user object
        $this->fillFromArray($userData);
        
        return $this;
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return User|false
     */
    public function findById(int $id): User|false {
        $sql = "SELECT * FROM users WHERE id = ?";
        $userData = $this->db->fetchOne($sql, [$id]);
        
        if (!$userData) {
            return false;
        }
        
        $this->fillFromArray($userData);
        return $this;
    }
    
    /**
     * Find user by email
     * 
     * @param string $email User email
     * @return User|false
     */
    public function findByEmail(string $email): User|false {
        $sql = "SELECT * FROM users WHERE email = ?";
        $userData = $this->db->fetchOne($sql, [$email]);
        
        if (!$userData) {
            return false;
        }
        
        $this->fillFromArray($userData);
        return $this;
    }
    
    /**
     * Get all users with optional filtering
     * 
     * @param array $filters Optional filters (role, statut, search)
     * @return array Array of user data
     */
    public function getAll(array $filters = []): array {
        $sql = "SELECT id, email, nom, prenom, role, statut, date_creation, derniere_connexion FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($filters['role'])) {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
        }
        
        if (!empty($filters['statut'])) {
            $sql .= " AND statut = ?";
            $params[] = $filters['statut'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $sql .= " ORDER BY date_creation DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Get all agents (for dropdown lists)
     * 
     * @return array Array of agents
     */
    public function getAgents(): array {
        $sql = "SELECT id, nom, prenom, email FROM users WHERE role = 'agent' AND statut = 'actif' ORDER BY nom, prenom";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data
     * @return int New user ID
     * @throws Exception If email already exists
     */
    public function create(array $data): int {
        // Check if email already exists
        if ($this->db->exists('users', 'email', $data['email'])) {
            throw new Exception("L'adresse email existe déjà.");
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return $this->db->insert('users', $data);
    }
    
    /**
     * Update user
     * 
     * @param int $id User ID
     * @param array $data User data to update
     * @return int Number of affected rows
     * @throws Exception If email already exists for another user
     */
    public function update(int $id, array $data): int {
        // Check if email already exists for another user
        if (!empty($data['email']) && $this->db->exists('users', 'email', $data['email'], $id)) {
            throw new Exception("L'adresse email existe déjà.");
        }
        
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        
        return $this->db->update('users', $data, 'id = ?', [$id]);
    }
    
    /**
     * Delete user
     * 
     * @param int $id User ID
     * @return int Number of affected rows
     * @throws Exception If user has associated records
     */
    public function delete(int $id): int {
        // Check if user has clients or credit requests
        $hasClients = $this->db->fetchValue(
            "SELECT COUNT(*) FROM clients WHERE agent_id = ?",
            [$id]
        );
        
        if ($hasClients > 0) {
            throw new Exception("Impossible de supprimer cet utilisateur car il a des clients associés.");
        }
        
        return $this->db->delete('users', 'id = ?', [$id]);
    }
    
    /**
     * Toggle user status (actif/inactif)
     * 
     * @param int $id User ID
     * @return string New status
     */
    public function toggleStatus(int $id): string {
        $currentStatus = $this->db->fetchValue(
            "SELECT statut FROM users WHERE id = ?",
            [$id]
        );
        
        $newStatus = ($currentStatus === 'actif') ? 'inactif' : 'actif';
        
        $this->db->update('users', ['statut' => $newStatus], 'id = ?', [$id]);
        
        return $newStatus;
    }
    
    /**
     * Get user statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array {
        return [
            'total' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users"),
            'admins' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE role = 'admin'"),
            'agents' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE role = 'agent'"),
            'actifs' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE statut = 'actif'"),
            'inactifs' => (int) $this->db->fetchValue("SELECT COUNT(*) FROM users WHERE statut = 'inactif'"),
        ];
    }
    
    /**
     * Get full name
     * 
     * @return string Full name
     */
    public function getFullName(): string {
        return trim($this->prenom . ' ' . $this->nom);
    }
    
    /**
     * Check if user is admin
     * 
     * @return bool
     */
    public function isAdmin(): bool {
        return $this->role === 'admin';
    }
    
    /**
     * Fill object properties from array
     * 
     * @param array $data Data array
     */
    private function fillFromArray(array $data): void {
        $this->id = (int) $data['id'];
        $this->email = $data['email'];
        $this->nom = $data['nom'];
        $this->prenom = $data['prenom'];
        $this->role = $data['role'];
        $this->statut = $data['statut'];
        $this->date_creation = $data['date_creation'];
        $this->derniere_connexion = $data['derniere_connexion'] ?? null;
    }
}
