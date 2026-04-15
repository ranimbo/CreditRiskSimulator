<?php
/**
 * Page de Connexion — Style Amen Bank
 * 
 * Split screen : panneau gauche navy avec branding,
 * panneau droit avec formulaire de connexion.
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/User.php';

// Rediriger si déjà connecté
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

// Traitement du formulaire de connexion
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
            
            // Rediriger vers la page prévue ou le tableau de bord
            $redirect = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Identifiant ou mot de passe incorrect.';
        }
    }
}

// Message de timeout
if (isset($_GET['timeout'])) {
    $error = 'Votre session a expiré. Veuillez vous reconnecter.';
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Credit Risk Simulator · Amen Bank</title>
    <meta name="description" content="Connectez-vous au Credit Risk Simulator — Système d'évaluation du risque crédit d'Amen Bank.">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'amen-navy': '#003366',
                        'amen-gold': '#C8971F',
                        'amen-green': '#00a651',
                    },
                    fontFamily: {
                        sans: ['Inter', 'Segoe UI', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    
    <style>
        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; }
        
        /* Panneau gauche — dégradé navy avec décorations */
        .login-brand-panel {
            background: linear-gradient(160deg, #003366 0%, #002244 50%, #001a33 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Cercles décoratifs */
        .login-brand-panel::before {
            content: '';
            position: absolute;
            bottom: -120px;
            left: -80px;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: rgba(200, 151, 31, 0.06);
        }
        
        .login-brand-panel::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
        }
        
        /* Animation d'entrée du formulaire */
        .login-card {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        
        /* Focus gold sur inputs */
        .login-input:focus {
            border-color: #C8971F;
            box-shadow: 0 0 0 3px rgba(200, 151, 31, 0.15);
            outline: none;
        }

        /* Checkbox custom */
        .checkbox-gold:checked {
            background-color: #C8971F;
            border-color: #C8971F;
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-full flex">
        
        <!-- ========================================
             PANNEAU GAUCHE — Branding Amen Bank
             ======================================== -->
        <div class="hidden lg:flex lg:w-[42%] login-brand-panel p-12 flex-col justify-between relative z-10">
            
            <!-- Logo et titre -->
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <img src="https://www.amenbank.com.tn/wp-content/themes/amenbank/images/logo.png" 
                         alt="Amen Bank" 
                         class="h-12 brightness-0 invert"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <span class="text-2xl font-bold text-white hidden" style="display:none;">AMEN BANK</span>
                </div>
                <p class="text-white/40 text-sm mt-1">بنك الأمان</p>
            </div>
            
            <!-- Contenu central -->
            <div class="space-y-8 relative z-10">
                <div>
                    <h1 class="text-3xl font-bold text-white leading-tight mb-4">
                        Votre partenaire<br>financier de confiance
                    </h1>
                    <p class="text-white/60 text-base leading-relaxed max-w-sm">
                        Évaluez le risque crédit avec notre système de scoring avancé. 
                        Prenez des décisions éclairées basées sur des données fiables.
                    </p>
                </div>
                
                <!-- Stats rapides -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/[0.07] backdrop-blur-sm rounded-xl p-5 border border-white/[0.08]">
                        <div class="text-3xl font-bold text-white">6</div>
                        <div class="text-sm text-white/50 mt-1">Critères de scoring</div>
                    </div>
                    <div class="bg-white/[0.07] backdrop-blur-sm rounded-xl p-5 border border-white/[0.08]">
                        <div class="text-3xl font-bold text-[#C8971F]">100</div>
                        <div class="text-sm text-white/50 mt-1">Score maximum</div>
                    </div>
                </div>
                
                <!-- Badges sécurité -->
                <div class="flex flex-wrap gap-3">
                    <div class="inline-flex items-center gap-2 px-3 py-2 bg-white/[0.06] rounded-lg border border-white/[0.08]">
                        <i data-feather="shield" class="w-4 h-4 text-[#C8971F]"></i>
                        <span class="text-xs text-white/60">Sécurisé SSL</span>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3 py-2 bg-white/[0.06] rounded-lg border border-white/[0.08]">
                        <i data-feather="clock" class="w-4 h-4 text-[#C8971F]"></i>
                        <span class="text-xs text-white/60">24/7</span>
                    </div>
                    <div class="inline-flex items-center gap-2 px-3 py-2 bg-white/[0.06] rounded-lg border border-white/[0.08]">
                        <i data-feather="award" class="w-4 h-4 text-[#C8971F]"></i>
                        <span class="text-xs text-white/60">Certifié</span>
                    </div>
                </div>
            </div>
            
            <!-- Bottom badge -->
            <div class="relative z-10">
                <div class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/[0.08] rounded-lg border border-white/[0.1]">
                    <div class="w-2 h-2 bg-[#00a651] rounded-full animate-pulse"></div>
                    <span class="text-sm text-white/70 font-medium">@mennet — Espace Professionnel</span>
                </div>
                <p class="text-white/30 text-xs mt-4">
                    &copy; <?php echo date('Y'); ?> Amen Bank &middot; Tous droits réservés
                </p>
            </div>

            <!-- Vague décorative SVG -->
            <svg class="absolute bottom-0 left-0 right-0 opacity-[0.04]" viewBox="0 0 1440 320" preserveAspectRatio="none">
                <path fill="#ffffff" d="M0,256L48,245.3C96,235,192,213,288,186.7C384,160,480,128,576,128C672,128,768,160,864,181.3C960,203,1056,213,1152,197.3C1248,181,1344,139,1392,117.3L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
        
        <!-- ========================================
             PANNEAU DROIT — Formulaire de connexion
             ======================================== -->
        <div class="flex-1 flex flex-col bg-white">
            
            <!-- Sélecteur de langue -->
            <div class="flex justify-end p-4">
                <div class="flex items-center gap-1 text-sm">
                    <span class="px-2 py-1 rounded text-amen-navy font-semibold bg-gray-50 cursor-pointer">FR</span>
                    <span class="px-2 py-1 rounded text-gray-400 hover:text-amen-navy hover:bg-gray-50 cursor-pointer transition-colors">AR</span>
                    <span class="px-2 py-1 rounded text-gray-400 hover:text-amen-navy hover:bg-gray-50 cursor-pointer transition-colors">EN</span>
                </div>
            </div>
            
            <div class="flex-1 flex items-center justify-center px-6 pb-8">
                <div class="w-full max-w-[420px] login-card">
                    
                    <!-- Logo mobile -->
                    <div class="lg:hidden flex items-center justify-center gap-3 mb-8">
                        <img src="https://www.amenbank.com.tn/wp-content/themes/amenbank/images/logo.png" 
                             alt="Amen Bank" 
                             class="h-10"
                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <span class="text-xl font-bold text-amen-navy hidden" style="display:none;">AMEN BANK</span>
                    </div>
                    
                    <!-- Card du formulaire -->
                    <div class="bg-white rounded-2xl shadow-[0_4px_24px_rgba(0,0,0,0.08)] border border-gray-100 p-8">
                        <!-- Titre -->
                        <div class="text-center mb-8">
                            <h2 class="text-[1.5rem] font-bold text-[#003366]">Connexion</h2>
                            <p class="text-sm text-gray-500 mt-2">
                                Credit Risk Simulator — Évaluation du risque crédit
                            </p>
                        </div>
                        
                        <!-- Message d'erreur -->
                        <?php if ($error): ?>
                        <div class="bg-[#FDEEEE] border-l-4 border-[#C0392B] text-[#C0392B] px-4 py-3 rounded mb-6 flex items-center gap-3 text-sm">
                            <i data-feather="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Formulaire -->
                        <form method="POST" action="login.php" class="space-y-5">
                            <!-- Identifiant -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Identifiant
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-feather="user" class="w-[18px] h-[18px] text-gray-400"></i>
                                    </div>
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        value="<?php echo htmlspecialchars($email); ?>"
                                        required
                                        autofocus
                                        class="login-input w-full pl-10 pr-4 py-2.5 border border-[#E2E8F0] rounded text-sm text-gray-900 placeholder-gray-400 transition-all"
                                        placeholder="agent@amenbank.com.tn"
                                    >
                                </div>
                            </div>
                            
                            <!-- Mot de passe -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Mot de passe
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-feather="lock" class="w-[18px] h-[18px] text-gray-400"></i>
                                    </div>
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        required
                                        class="login-input w-full pl-10 pr-12 py-2.5 border border-[#E2E8F0] rounded text-sm text-gray-900 placeholder-gray-400 transition-all"
                                        placeholder="••••••••"
                                    >
                                    <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-[#003366] transition-colors">
                                        <i data-feather="eye" id="eye-icon" class="w-[18px] h-[18px]"></i>
                                        <i data-feather="eye-off" id="eye-off-icon" class="w-[18px] h-[18px] hidden"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Remember + Forgot -->
                            <div class="flex items-center justify-between">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remember" class="w-4 h-4 rounded border-gray-300 text-[#C8971F] focus:ring-[#C8971F]">
                                    <span class="text-sm text-gray-600">Mémoriser l'identifiant</span>
                                </label>
                                <a href="#" class="text-sm text-[#C8971F] hover:text-[#A07820] font-medium transition-colors">
                                    Mot de passe oublié ?
                                </a>
                            </div>
                            
                            <!-- Bouton de connexion -->
                            <button 
                                type="submit" 
                                class="w-full bg-[#003366] hover:bg-[#004080] text-white font-medium py-3 px-4 rounded text-sm transition-all flex items-center justify-center gap-2 mt-2 shadow-sm hover:shadow-md"
                            >
                                <span>Se connecter</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Comptes de démonstration -->
                    <div class="mt-6 bg-[#F4F6F9] rounded-xl p-4 border border-[#E2E8F0]">
                        <p class="text-xs font-medium text-[#003366] mb-2 flex items-center gap-1.5">
                            <i data-feather="info" class="w-3.5 h-3.5"></i>
                            Comptes de démonstration
                        </p>
                        <div class="space-y-1.5 text-xs text-gray-500">
                            <p><span class="font-semibold text-[#003366]">Admin :</span> admin@bank.com / admin123</p>
                            <p><span class="font-semibold text-[#003366]">Agent :</span> agent@bank.com / agent123</p>
                        </div>
                    </div>
                    
                    <!-- Badges de sécurité footer -->
                    <div class="mt-6 flex items-center justify-center gap-4 text-xs text-gray-400">
                        <span class="flex items-center gap-1">
                            <i data-feather="shield" class="w-3.5 h-3.5"></i>
                            SSL 256-bit
                        </span>
                        <span>·</span>
                        <span>Banque Centrale de Tunisie</span>
                    </div>
                    
                    <!-- Copyright mobile -->
                    <p class="mt-6 text-center text-xs text-gray-400">
                        &copy; <?php echo date('Y'); ?> Amen Bank &middot; Tous droits réservés
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Feather Icons Init -->
    <script>
        feather.replace();
        
        // Toggle password visibility
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            const eyeOffIcon = document.getElementById('eye-off-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        }
    </script>
</body>
</html>
