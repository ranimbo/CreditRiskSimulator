<?php
/**
 * User Management Page
 * CRUD operations for system users
 */

require_once '../includes/auth.php';
requireAdmin();

require_once '../classes/User.php';

$pageTitle = 'Gestion des Utilisateurs';
$user = new User();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $data = [
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'email' => sanitize($_POST['email']),
            'password' => $_POST['password'],
            'role' => sanitize($_POST['role']),
            'status' => 'active'
        ];
        
        // Validate
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password'])) {
            setFlashMessage('error', 'Tous les champs sont requis.');
        } elseif ($user->emailExists($data['email'])) {
            setFlashMessage('error', 'Cet email est déjà utilisé.');
        } elseif (strlen($data['password']) < 6) {
            setFlashMessage('error', 'Le mot de passe doit contenir au moins 6 caractères.');
        } else {
            if ($user->create($data)) {
                setFlashMessage('success', 'Utilisateur créé avec succès.');
            } else {
                setFlashMessage('error', 'Erreur lors de la création de l\'utilisateur.');
            }
        }
        redirect('users.php');
    }
    
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $data = [
            'first_name' => sanitize($_POST['first_name']),
            'last_name' => sanitize($_POST['last_name']),
            'email' => sanitize($_POST['email']),
            'role' => sanitize($_POST['role']),
            'status' => sanitize($_POST['status'])
        ];
        
        // Check if password is being updated
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) {
                setFlashMessage('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                redirect('users.php?action=edit&id=' . $id);
            }
            $data['password'] = $_POST['password'];
        }
        
        // Check email uniqueness (excluding current user)
        $existingUser = $user->getByEmail($data['email']);
        if ($existingUser && $existingUser['id'] != $id) {
            setFlashMessage('error', 'Cet email est déjà utilisé.');
            redirect('users.php?action=edit&id=' . $id);
        }
        
        if ($user->update($id, $data)) {
            setFlashMessage('success', 'Utilisateur mis à jour avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour.');
        }
        redirect('users.php');
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Prevent self-deletion
        if ($id === $_SESSION['user_id']) {
            setFlashMessage('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        } else {
            if ($user->delete($id)) {
                setFlashMessage('success', 'Utilisateur supprimé avec succès.');
            } else {
                setFlashMessage('error', 'Erreur lors de la suppression.');
            }
        }
        redirect('users.php');
    }
    
    if ($action === 'toggle_status') {
        $id = (int)$_POST['id'];
        
        // Prevent self-deactivation
        if ($id === $_SESSION['user_id']) {
            setFlashMessage('error', 'Vous ne pouvez pas désactiver votre propre compte.');
        } else {
            if ($user->toggleStatus($id)) {
                setFlashMessage('success', 'Statut mis à jour avec succès.');
            } else {
                setFlashMessage('error', 'Erreur lors de la mise à jour du statut.');
            }
        }
        redirect('users.php');
    }
}

// Get action mode
$mode = $_GET['action'] ?? 'list';
$editUser = null;

if ($mode === 'edit' && isset($_GET['id'])) {
    $editUser = $user->getById((int)$_GET['id']);
    if (!$editUser) {
        setFlashMessage('error', 'Utilisateur non trouvé.');
        redirect('users.php');
    }
}

// Get all users
$users = $user->getAll();

require_once '../includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">Gestion des Utilisateurs</h1>
            <p class="text-slate-600 mt-1"><?php echo count($users); ?> utilisateur(s) enregistré(s)</p>
        </div>
        <div class="flex gap-3 mt-4 md:mt-0">
            <a href="index.php" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </a>
            <?php if ($mode !== 'add'): ?>
            <a href="users.php?action=add" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Nouvel Utilisateur
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($mode === 'add' || $mode === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="card max-w-2xl">
        <h2 class="text-xl font-semibold text-slate-900 mb-6">
            <?php echo $mode === 'add' ? 'Nouvel Utilisateur' : 'Modifier l\'Utilisateur'; ?>
        </h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="<?php echo $mode === 'add' ? 'create' : 'update'; ?>">
            <?php if ($editUser): ?>
            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">Prénom *</label>
                    <input type="text" id="first_name" name="first_name" required
                           value="<?php echo htmlspecialchars($editUser['first_name'] ?? ''); ?>"
                           class="form-input">
                </div>
                
                <div>
                    <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">Nom *</label>
                    <input type="text" id="last_name" name="last_name" required
                           value="<?php echo htmlspecialchars($editUser['last_name'] ?? ''); ?>"
                           class="form-input">
                </div>
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email *</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>"
                       class="form-input">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                    Mot de passe <?php echo $mode === 'add' ? '*' : '(laisser vide pour ne pas changer)'; ?>
                </label>
                <input type="password" id="password" name="password" 
                       <?php echo $mode === 'add' ? 'required' : ''; ?>
                       minlength="6"
                       class="form-input">
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700 mb-1">Rôle *</label>
                    <select id="role" name="role" required class="form-select">
                        <option value="agent" <?php echo ($editUser['role'] ?? '') === 'agent' ? 'selected' : ''; ?>>Agent</option>
                        <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                    </select>
                </div>
                
                <?php if ($mode === 'edit'): ?>
                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700 mb-1">Statut *</label>
                    <select id="status" name="status" required class="form-select">
                        <option value="active" <?php echo ($editUser['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Actif</option>
                        <option value="inactive" <?php echo ($editUser['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactif</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <?php echo $mode === 'add' ? 'Créer' : 'Enregistrer'; ?>
                </button>
                <a href="users.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
    
    <?php else: ?>
    <!-- Users List -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600">
                                        <?php echo strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">
                                        <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                                    </p>
                                    <p class="text-sm text-slate-500">ID: <?php echo $u['id']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $u['role'] === 'admin' ? 'badge-primary' : 'badge-secondary'; ?>">
                                <?php echo $u['role'] === 'admin' ? 'Administrateur' : 'Agent'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $u['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $u['status'] === 'active' ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </td>
                        <td class="text-slate-600">
                            <?php echo $u['last_login'] ? formatDate($u['last_login']) : 'Jamais'; ?>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" 
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                   title="Modifier">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir changer le statut de cet utilisateur?');">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" 
                                            class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="<?php echo $u['status'] === 'active' ? 'Désactiver' : 'Activer'; ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                </form>
                                
                                <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur? Cette action est irréversible.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" 
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
