<?php
/**
 * Login Page
 * 
 * Handles user authentication.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/User.php';

// Redirect if already logged in
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $user = new User();
        $authenticatedUser = $user->authenticate($email, $password);
        
        if ($authenticatedUser) {
            setUserSession($authenticatedUser);
            
            // Redirect to intended page or dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = 'Votre session a expiré. Veuillez vous reconnecter.';
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Credit Risk Simulator</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                    },
                },
            },
        }
    </script>
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body class="h-full bg-slate-50">
    <div class="min-h-full flex">
        <!-- Left side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-primary-600 p-12 flex-col justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-semibold text-white">Credit Risk Simulator</span>
                </div>
            </div>
            
            <div class="space-y-6">
                <h1 class="text-4xl font-bold text-white leading-tight">
                    Évaluez le risque crédit en toute confiance
                </h1>
                <p class="text-lg text-primary-100">
                    Analysez les demandes de crédit avec notre système de scoring avancé. Prenez des décisions éclairées basées sur des données fiables.
                </p>
                
                <div class="grid grid-cols-2 gap-4 pt-4">
                    <div class="bg-white/10 rounded-lg p-4">
                        <div class="text-3xl font-bold text-white">6</div>
                        <div class="text-sm text-primary-100">Critères d'évaluation</div>
                    </div>
                    <div class="bg-white/10 rounded-lg p-4">
                        <div class="text-3xl font-bold text-white">100</div>
                        <div class="text-sm text-primary-100">Score maximum</div>
                    </div>
                </div>
            </div>
            
            <div class="text-sm text-primary-200">
                &copy; <?php echo date('Y'); ?> Credit Risk Simulator. Tous droits réservés.
            </div>
        </div>
        
        <!-- Right side - Login Form -->
        <div class="flex-1 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile logo -->
                <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-semibold text-slate-900">Credit Risk</span>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-slate-900">Connexion</h2>
                        <p class="text-sm text-slate-500 mt-1">Accédez à votre espace agent</p>
                    </div>
                    
                    <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php" class="space-y-5">
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Adresse email
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($email); ?>"
                                required
                                autofocus
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                placeholder="agent@bank.com"
                            >
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Mot de passe
                            </label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                placeholder="••••••••"
                            >
                        </div>
                        
                        <button 
                            type="submit" 
                            class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors flex items-center justify-center gap-2"
                        >
                            <span>Se connecter</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                        </button>
                    </form>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-slate-500">
                        Comptes de démonstration:
                    </p>
                    <div class="mt-2 space-y-1 text-xs text-slate-400">
                        <p><strong>Admin:</strong> admin@bank.com / admin123</p>
                        <p><strong>Agent:</strong> agent@bank.com / agent123</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
