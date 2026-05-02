<?php
/**
 * Header Template — Style Amen Bank
 * Chemins dynamiques : fonctionne depuis / et /admin/
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Détecter si on est dans un sous-dossier /admin/
$isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$base    = $isAdmin ? '../' : '';   // chemin vers la racine du projet
$adminBase = $isAdmin ? ''   : 'admin/'; // chemin vers /admin/
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Credit Risk Simulator — Amen Bank</title>
    <meta name="description" content="Credit Risk Simulator — Évaluation du risque crédit. Système de scoring bancaire inspiré d'Amen Bank.">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'amen-navy': {
                            50: '#e6edf5', 100: '#ccdaeb', 200: '#99b5d6',
                            300: '#6690c2', 400: '#336bad', 500: '#003366',
                            600: '#002952', 700: '#001f3d', 800: '#001429', 900: '#000a14',
                        },
                        'amen-gold': {
                            50: '#fdf8ed', 100: '#faefd4', 200: '#f5dfaa',
                            300: '#f0cf7f', 400: '#d4a832', 500: '#C8971F',
                            600: '#A07820', 700: '#7a5c18', 800: '#533f11', 900: '#2b210a',
                        },
                        'amen-green': '#00a651',
                        primary: {
                            50: '#e6edf5', 100: '#ccdaeb', 200: '#99b5d6',
                            300: '#6690c2', 400: '#336bad', 500: '#003366',
                            600: '#002952', 700: '#001f3d', 800: '#001429', 900: '#000a14',
                        },
                    },
                    fontFamily: { sans: ['Inter', 'Segoe UI', 'system-ui', 'sans-serif'] },
                },
            },
        }
    </script>
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS — chemin dynamique -->
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>
<body class="h-full bg-[#F4F6F9] font-sans antialiased">
    <div class="min-h-full flex flex-col">

        <!-- ========== NAVBAR ========== -->
        <nav class="navbar-amen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    <!-- Logo + liens principaux -->
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="<?php echo $base; ?>dashboard.php" class="flex items-center gap-3">
                                <img src="<?php echo $base; ?>assets/images/logo_amen_bank.png"
                                     alt="Amen Bank"
                                     class="h-10 bg-white rounded px-2 py-1"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                <span class="text-white font-bold text-base hidden" style="display:none;">AMEN BANK</span>
                                <span class="text-white/60 text-xs hidden sm:inline">|</span>
                                <span class="text-white/80 text-xs font-medium hidden sm:inline">Credit Risk</span>
                            </a>
                        </div>

                        <!-- Navigation Desktop -->
                        <div class="hidden md:ml-8 md:flex md:items-center md:space-x-1">
                            <a href="<?php echo $base; ?>dashboard.php"
                               class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                                <i data-feather="grid" class="w-4 h-4"></i>
                                Tableau de bord
                            </a>
                            <a href="<?php echo $base; ?>clients.php"
                               class="nav-link <?php echo in_array($currentPage, ['clients','client-form']) ? 'active' : ''; ?>">
                                <i data-feather="users" class="w-4 h-4"></i>
                                Clients
                            </a>
                            <a href="<?php echo $base; ?>simulation.php"
                               class="nav-link <?php echo $currentPage === 'simulation' ? 'active' : ''; ?>">
                                <i data-feather="calculator" class="w-4 h-4"></i>
                                Simulations
                            </a>
                            <a href="<?php echo $base; ?>history.php"
                               class="nav-link <?php echo $currentPage === 'history' ? 'active' : ''; ?>">
                                <i data-feather="clock" class="w-4 h-4"></i>
                                Historique
                            </a>
                            <?php if (isAdmin()): ?>
                            <a href="<?php echo $base; ?>admin/index.php"
                               class="nav-link <?php echo $isAdmin ? 'active' : ''; ?>">
                                <i data-feather="settings" class="w-4 h-4"></i>
                                Admin
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Droite : notif + user -->
                    <div class="flex items-center gap-3">
                        <button class="relative p-2 rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-colors">
                            <i data-feather="bell" class="w-5 h-5"></i>
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#C8971F] rounded-full"></span>
                        </button>

                        <!-- User dropdown -->
                        <div class="relative">
                            <button type="button" onclick="toggleUserMenu()"
                                    class="flex items-center gap-2 text-sm focus:outline-none"
                                    id="user-menu-button">
                                <div class="w-8 h-8 bg-white/20 text-white rounded-full flex items-center justify-center font-semibold text-xs">
                                    <?php echo strtoupper(substr($currentUser['nom'], 0, 2)); ?>
                                </div>
                                <div class="hidden lg:block text-left">
                                    <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                                    <p class="text-xs text-white/50"><?php echo ucfirst($currentUser['role']); ?></p>
                                </div>
                                <i data-feather="chevron-down" class="w-4 h-4 text-white/50 hidden lg:block"></i>
                            </button>

                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl py-1 ring-1 ring-black/5 z-50">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-medium text-[#003366]"><?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                                    <p class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                </div>
                                <a href="#" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#F4F6F9] transition-colors">
                                    <i data-feather="user" class="w-4 h-4"></i> Profil
                                </a>
                                <a href="#" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-[#F4F6F9] transition-colors">
                                    <i data-feather="settings" class="w-4 h-4"></i> Paramètres
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="<?php echo $base; ?>logout.php"
                                   class="flex items-center gap-2 px-4 py-2.5 text-sm text-[#C0392B] hover:bg-red-50 transition-colors">
                                    <i data-feather="log-out" class="w-4 h-4"></i> Déconnexion
                                </a>
                            </div>
                        </div>

                        <!-- Mobile menu button -->
                        <div class="flex items-center md:hidden">
                            <button type="button" onclick="toggleMobileMenu()"
                                    class="p-2 rounded-lg text-white/70 hover:text-white hover:bg-white/10">
                                <i data-feather="menu" class="w-6 h-6"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden md:hidden border-t border-white/10">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="<?php echo $base; ?>dashboard.php"
                       class="<?php echo $currentPage === 'dashboard' ? 'bg-white/10 text-white' : 'text-white/70'; ?> block px-3 py-2 rounded-lg text-base font-medium flex items-center gap-3">
                        <i data-feather="grid" class="w-4 h-4"></i> Tableau de bord
                    </a>
                    <a href="<?php echo $base; ?>clients.php"
                       class="<?php echo $currentPage === 'clients' ? 'bg-white/10 text-white' : 'text-white/70'; ?> block px-3 py-2 rounded-lg text-base font-medium flex items-center gap-3">
                        <i data-feather="users" class="w-4 h-4"></i> Clients
                    </a>
                    <a href="<?php echo $base; ?>simulation.php"
                       class="<?php echo $currentPage === 'simulation' ? 'bg-white/10 text-white' : 'text-white/70'; ?> block px-3 py-2 rounded-lg text-base font-medium flex items-center gap-3">
                        <i data-feather="calculator" class="w-4 h-4"></i> Simulations
                    </a>
                    <a href="<?php echo $base; ?>history.php"
                       class="<?php echo $currentPage === 'history' ? 'bg-white/10 text-white' : 'text-white/70'; ?> block px-3 py-2 rounded-lg text-base font-medium flex items-center gap-3">
                        <i data-feather="clock" class="w-4 h-4"></i> Historique
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo $base; ?>admin/index.php"
                       class="text-white/70 block px-3 py-2 rounded-lg text-base font-medium flex items-center gap-3">
                        <i data-feather="settings" class="w-4 h-4"></i> Administration
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <!-- Flash Messages -->
        <?php if (hasFlashMessages()): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <?php foreach (getFlashMessages() as $flash): ?>
            <div class="flash-message alert <?php
                echo match($flash['type']) {
                    'success' => 'alert-success',
                    'error'   => 'alert-error',
                    'warning' => 'alert-warning',
                    default   => 'alert-info',
                };
            ?>">
                <i data-feather="<?php
                    echo match($flash['type']) {
                        'success' => 'check-circle',
                        'error'   => 'alert-circle',
                        'warning' => 'alert-triangle',
                        default   => 'info',
                    };
                ?>" class="w-5 h-5 flex-shrink-0"></i>
                <p class="text-sm flex-1"><?php echo htmlspecialchars($flash['message']); ?></p>
                <button type="button" onclick="this.parentElement.remove()" class="ml-auto opacity-60 hover:opacity-100">
                    <i data-feather="x" class="w-4 h-4"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8">