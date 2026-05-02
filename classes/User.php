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
    
    public ?int    $id               = null;
    public ?string $email            = null;
    public ?string $nom              = null;
    public ?string $mot_de_passe     = null;
    public ?string $role_type        = null;
    public ?string $matricule        = null;
    public ?string $role             = null;
    public ?string $derniere_connexion = null;
    public ?string $agence           = null;
    public ?string $date_creation    = null;

    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate user
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
        
        if (!$userData) return false;
        if ($password !== $userData['mot_de_passe']) return false;
        
        $this->fillFromArray($userData);
        
        if ($this->isAdmin()) {
            $this->db->update('admin', ['derniere_connexion' => date('Y-m-d')], 'id = ?', [$this->id]);
        }
        
        return $this;
    }
    
    /**
     * Find user by ID
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
        if (!$userData) return false;
        $this->fillFromArray($userData);
        return $this;
    }
    
    /**
     * Find user by email
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
        if (!$userData) return false;
        $this->fillFromArray($userData);
        return $this;
    }

    /**
     * Get all users (returns raw arrays for table display)
     */
    public function getAll(): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.email, u.nom,
                    IF(a.id IS NOT NULL, 'admin', 'agent') as role_type,
                    IF(a.id IS NOT NULL, a.matricule, ag.matricule) as matricule
             FROM utilisateur u
             LEFT JOIN admin a ON u.id = a.id
             LEFT JOIN agent_bancaire ag ON u.id = ag.id
             ORDER BY u.id DESC"
        );
    }
    
    /**
     * Count total users — used by admin dashboard
     */
    public function count(): int {
        return (int) $this->db->fetchValue("SELECT COUNT(*) FROM utilisateur");
    }
    
    /**
     * Get recent users — used by admin dashboard
     */
    public function getRecent(int $limit = 5): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.email, u.nom,
                    IF(a.id IS NOT NULL, 'admin', 'agent') as role_type,
                    IF(a.id IS NOT NULL, a.matricule, ag.matricule) as matricule,
                    u.id as created_at
             FROM utilisateur u
             LEFT JOIN admin a ON u.id = a.id
             LEFT JOIN agent_bancaire ag ON u.id = ag.id
             ORDER BY u.id DESC
             LIMIT ?",
            [$limit]
        );
    }
    
    /**
     * Get all agents (for dropdown lists)
     */
    public function getAgents(): array {
        return $this->db->fetchAll(
            "SELECT u.id, u.nom, u.email, ag.matricule, ag.agence 
             FROM utilisateur u 
             JOIN agent_bancaire ag ON u.id = ag.id 
             ORDER BY u.nom"
        );
    }
    
    /**
     * Create a new user with specialized role
     */
    public function create(array $data, string $type = 'agent'): int {
        // Support both calling conventions:
        // create($data, 'admin') from User class
        // create($data) from admin/users.php — role inside $data['role']
        if (isset($data['role'])) {
            $type = $data['role'];
        }

        if ($this->db->exists('utilisateur', 'email', $data['email'])) {
            throw new Exception("L'adresse email existe déjà.");
        }
        
        $this->db->beginTransaction();
        try {
            $userId = $this->db->insert('utilisateur', [
                'nom'          => $data['nom'],
                'email'        => $data['email'],
                'mot_de_passe' => $data['password'] ?? $data['mot_de_passe'],
            ]);

            if ($type === 'admin') {
                $this->db->insert('admin', [
                    'id'        => $userId,
                    'matricule' => $data['matricule'] ?? '',
                    'role'      => 'Standard',
                ]);
            } else {
                $this->db->insert('agent_bancaire', [
                    'id'           => $userId,
                    'matricule'    => $data['matricule'] ?? '',
                    'agence'       => $data['agence'] ?? 'Siège',
                    'date_creation'=> date('Y-m-d'),
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
     * Update user — used by admin/users.php
     */
    public function update(int $id, array $data): bool {
        $this->db->beginTransaction();
        try {
            $utilisateurData = ['nom' => $data['nom'], 'email' => $data['email']];
            if (!empty($data['password'])) {
                $utilisateurData['mot_de_passe'] = $data['password'];
            }
            $this->db->update('utilisateur', $utilisateurData, 'id = ?', [$id]);

            // Update matricule in role table
            $isAdmin = (bool) $this->db->fetchValue("SELECT COUNT(*) FROM admin WHERE id = ?", [$id]);
            if ($isAdmin) {
                $this->db->update('admin', ['matricule' => $data['matricule'] ?? ''], 'id = ?', [$id]);
            } else {
                $this->db->update('agent_bancaire', ['matricule' => $data['matricule'] ?? ''], 'id = ?', [$id]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete user — used by admin/users.php
     */
    public function delete(int $id): bool {
        return $this->db->delete('utilisateur', 'id = ?', [$id]) > 0;
    }

    /**
     * Check email existence — used by admin/users.php
     */
    public function emailExists(string $email): bool {
        return $this->db->exists('utilisateur', 'email', $email);
    }

    /**
     * Get user by email (returns array) — used by admin/users.php
     */
    public function getByEmail(string $email): array|false {
        return $this->db->fetchOne("SELECT * FROM utilisateur WHERE email = ?", [$email]);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function fillFromArray(array $data): void {
        $this->id          = (int) $data['id'];
        $this->email       = $data['email'];
        $this->nom         = $data['nom'];
        $this->mot_de_passe = $data['mot_de_passe'];
        
        if (!empty($data['admin_matricule'])) {
            $this->role_type         = 'admin';
            $this->matricule         = $data['admin_matricule'];
            $this->role              = $data['admin_role'];
            $this->derniere_connexion = $data['derniere_connexion'];
        } else {
            $this->role_type     = 'agent';
            $this->matricule     = $data['agent_matricule'] ?? null;
            $this->agence        = $data['agence'] ?? null;
            $this->date_creation = $data['date_creation'] ?? null;
        }
    }

    public function getFullName(): string {
        return trim($this->nom ?? '');
    }
    
    public function isAdmin(): bool {
        return $this->role_type === 'admin';
    }
}