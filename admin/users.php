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
            'nom' => sanitize($_POST['nom']),
            'email' => sanitize($_POST['email']),
            'password' => $_POST['password'],
            'role' => sanitize($_POST['role']),
            'matricule' => sanitize($_POST['matricule'] ?? '')
        ];
        
        // Validate
        if (empty($data['nom']) || empty($data['email']) || empty($data['password'])) {
            setFlashMessage('error', 'Tous les champs obligatoires sont requis.');
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
        header('Location: users.php');
        exit;
    }
    
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $data = [
            'nom' => sanitize($_POST['nom']),
            'email' => sanitize($_POST['email']),
            'role' => sanitize($_POST['role']),
            'matricule' => sanitize($_POST['matricule'] ?? '')
        ];
        
        // Check if password is being updated
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 6) {
                setFlashMessage('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                header('Location: users.php?action=edit&id=' . $id);
                exit;
            }
            $data['password'] = $_POST['password'];
        }
        
        // Check email uniqueness (excluding current user)
        $existingUser = $user->getByEmail($data['email']);
        if ($existingUser && $existingUser['id'] != $id) {
            setFlashMessage('error', 'Cet email est déjà utilisé.');
            header('Location: users.php?action=edit&id=' . $id);
            exit;
        }
        
        if ($user->update($id, $data)) {
            setFlashMessage('success', 'Utilisateur mis à jour avec succès.');
        } else {
            setFlashMessage('error', 'Erreur lors de la mise à jour.');
        }
        header('Location: users.php');
        exit;
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
        header('Location: users.php');
        exit;
    }
}

// Get action mode
$mode = $_GET['action'] ?? 'list';
$editUser = null;

if ($mode === 'edit' && isset($_GET['id'])) {
    $editUser = $user->findById((int)$_GET['id']);
    if (!$editUser) {
        setFlashMessage('error', 'Utilisateur non trouvé.');
        header('Location: users.php');
        exit;
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
                <i data-feather="arrow-left" class="w-5 h-5 mr-2"></i>
                Retour
            </a>
            <?php if ($mode !== 'add'): ?>
            <a href="users.php?action=add" class="btn btn-primary">
                <i data-feather="plus" class="w-5 h-5 mr-2"></i>
                Nouvel Utilisateur
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($mode === 'add' || $mode === 'edit'): ?>
    <!-- Add/Edit Form -->
    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-2xl">
        <h2 class="text-xl font-semibold text-slate-900 mb-6">
            <?php echo $mode === 'add' ? 'Nouvel Utilisateur' : 'Modifier l\'Utilisateur'; ?>
        </h2>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="<?php echo $mode === 'add' ? 'create' : 'update'; ?>">
            <?php if ($editUser): ?>
            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
            <?php endif; ?>
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="nom" class="block text-sm font-medium text-slate-700 mb-1">Nom Complet *</label>
                    <input type="text" id="nom" name="nom" required
                           value="<?php echo htmlspecialchars($editUser['nom'] ?? ''); ?>"
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
                    <select id="role" name="role" required class="form-input">
                        <option value="agent" <?php echo ($editUser['role_type'] ?? '') === 'agent' ? 'selected' : ''; ?>>Agent Bancaire</option>
                        <option value="admin" <?php echo ($editUser['role_type'] ?? '') === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                    </select>
                </div>
                
                <div>
                    <label for="matricule" class="block text-sm font-medium text-slate-700 mb-1">Matricule</label>
                    <input type="text" id="matricule" name="matricule"
                           value="<?php echo htmlspecialchars($editUser['matricule'] ?? ''); ?>"
                           class="form-input">
                </div>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="btn btn-primary">
                    <i data-feather="save" class="w-5 h-5 mr-2"></i>
                    <?php echo $mode === 'add' ? 'Créer' : 'Enregistrer'; ?>
                </button>
                <a href="users.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
    
    <?php else: ?>
    <!-- Users List -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Utilisateur</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Email</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Rôle</th>
                        <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase">Matricule</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600">
                                        <?php echo strtoupper(substr($u['nom'], 0, 2)); ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">
                                        <?php echo htmlspecialchars($u['nom']); ?>
                                    </p>
                                    <p class="text-sm text-slate-500">ID: <?php echo $u['id']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo $u['role_type'] === 'admin' ? 'bg-primary-100 text-primary-700' : 'bg-slate-100 text-slate-700'; ?>">
                                <?php echo $u['role_type'] === 'admin' ? 'Administrateur' : 'Agent'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            <?php echo htmlspecialchars($u['matricule'] ?? 'N/A'); ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="users.php?action=edit&id=<?php echo $u['id']; ?>" 
                                   class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                   title="Modifier">
                                    <i data-feather="edit-2" class="w-4 h-4"></i>
                                </a>
                                
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur? Cette action est irréversible.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" 
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Supprimer">
                                        <i data-feather="trash-2" class="w-4 h-4"></i>
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
