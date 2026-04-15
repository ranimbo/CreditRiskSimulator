<?php
/**
 * User Model Class
 * 
 * Handles all user-related database operations including
 * authentication, CRUD operations, and user management.
 * Adapté au nouveau schéma : utilisateur, admin, agent_bancaire.
 */

require_once __DIR__ . '/Database.php';

class User {
    private Database $db;
    
    // User properties
    public ?int $id = null;
    public ?string $email = null;
    public ?string $nom = null;
    public ?string $mot_de_passe = null;
    
    // Role specific
    public ?string $role_type = null; // 'admin' or 'agent'
    public ?string $matricule = null;
    public ?string $role = null; // Pour admin
    public ?string $derniere_connexion = null; // Pour admin
    public ?string $agence = null; // Pour agent
    public ?string $date_creation = null; // Pour agent

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
        $sql = "SELECT u.*, 
                       a.matricule as admin_matricule, a.role as admin_role, a.derniere_connexion,
                       ag.matricule as agent_matricule, ag.agence, ag.date_creation
                FROM utilisateur u
                LEFT JOIN admin a ON u.id = a.id
                LEFT JOIN agent_bancaire ag ON u.id = ag.id
                WHERE u.email = ?";
        $userData = $this->db->fetchOne($sql, [$email]);
        
        if (!$userData) {
            return false;
        }
        
        if ($password !== $userData['mot_de_passe']) {
            return false;
        }
        
        // Populate user object
        $this->fillFromArray($userData);
        
        // Update last login time if admin
        if ($this->isAdmin()) {
            $this->db->update('admin', 
                ['derniere_connexion' => date('Y-m-d')],
                'id = ?',
                [$this->id]
            );
        }
        
        return $this;
    }
    
    /**
     * Find user by ID
     * 
     * @param int $id User ID
     * @return User|false
     */
    public function findById(int $id): User|false {
        $sql = "SELECT u.*, 
                       a.matricule as admin_matricule, a.role as admin_role, a.derniere_connexion,
                       ag.matricule as agent_matricule, ag.agence, ag.date_creation
                FROM utilisateur u
                LEFT JOIN admin a ON u.id = a.id
                LEFT JOIN agent_bancaire ag ON u.id = ag.id
                WHERE u.id = ?";
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
        $sql = "SELECT u.*, 
                       a.matricule as admin_matricule, a.role as admin_role, a.derniere_connexion,
                       ag.matricule as agent_matricule, ag.agence, ag.date_creation
                FROM utilisateur u
                LEFT JOIN admin a ON u.id = a.id
                LEFT JOIN agent_bancaire ag ON u.id = ag.id
                WHERE u.email = ?";
        $userData = $this->db->fetchOne($sql, [$email]);
        
        if (!$userData) {
            return false;
        }
        
        $this->fillFromArray($userData);
        return $this;
    }
    
    /**
     * Get all users
     */
    public function getAll(): array {
        $sql = "SELECT u.id, u.email, u.nom,
                       IF(a.id IS NOT NULL, 'admin', 'agent') as role_type,
                       IF(a.id IS NOT NULL, a.matricule, ag.matricule) as matricule
                FROM utilisateur u
                LEFT JOIN admin a ON u.id = a.id
                LEFT JOIN agent_bancaire ag ON u.id = ag.id";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Get all agents (for dropdown lists)
     * 
     * @return array Array of agents
     */
    public function getAgents(): array {
        $sql = "SELECT u.id, u.nom, u.email, ag.matricule, ag.agence 
                FROM utilisateur u 
                JOIN agent_bancaire ag ON u.id = ag.id 
                ORDER BY u.nom";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Create a new user (with specialized role)
     * 
     * @param array $data User data
     * @param string $type 'admin' or 'agent'
     * @return int New user ID
     */
    public function create(array $data, string $type): int {
        if ($this->db->exists('utilisateur', 'email', $data['email'])) {
            throw new Exception("L'adresse email existe déjà.");
        }
        
        $this->db->beginTransaction();
        try {
            // Store plaintext password
            $userId = $this->db->insert('utilisateur', [
                'nom' => $data['nom'],
                'email' => $data['email'],
                'mot_de_passe' => $data['mot_de_passe']
            ]);

            if ($type === 'admin') {
                $this->db->insert('admin', [
                    'id' => $userId,
                    'matricule' => $data['matricule'],
                    'role' => 'Standard'
                ]);
            } else {
                $this->db->insert('agent_bancaire', [
                    'id' => $userId,
                    'matricule' => $data['matricule'],
                    'agence' => $data['agence'] ?? 'Siège',
                    'date_creation' => date('Y-m-d')
                ]);
            }
            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Initializer à partir de la BD
     */
    private function fillFromArray(array $data): void {
        $this->id = (int) $data['id'];
        $this->email = $data['email'];
        $this->nom = $data['nom'];
        $this->mot_de_passe = $data['mot_de_passe'];
        
        if (isset($data['admin_matricule'])) {
            $this->role_type = 'admin';
            $this->matricule = $data['admin_matricule'];
            $this->role = $data['admin_role'];
            $this->derniere_connexion = $data['derniere_connexion'];
        } elseif (isset($data['agent_matricule'])) {
            $this->role_type = 'agent';
            $this->matricule = $data['agent_matricule'];
            $this->agence = $data['agence'];
            $this->date_creation = $data['date_creation'];
        }
    }

    public function getFullName(): string {
        return trim($this->nom);
    }
    
    public function isAdmin(): bool {
        return $this->role_type === 'admin';
    }
}
