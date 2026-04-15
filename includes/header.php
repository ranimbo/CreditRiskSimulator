<?php
/**
 * Header Template
 * 
 * Common header included on all authenticated pages.
 * Includes Tailwind CSS, navigation, and user menu.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Credit Risk Simulator</title>
    
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
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="h-full bg-slate-50 font-sans antialiased">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo and Main Nav -->
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <a href="dashboard.php" class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-primary-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <span class="text-lg font-semibold text-slate-900">Credit Risk</span>
                            </a>
                        </div>
                        
                        <!-- Main Navigation -->
                        <div class="hidden sm:ml-8 sm:flex sm:space-x-1">
                            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?> px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2">
                                <i data-feather="home" class="w-4 h-4"></i>
                                Tableau de bord
                            </a>
                            <a href="clients.php" class="<?php echo $currentPage === 'clients' || $currentPage === 'client-form' ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?> px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2">
                                <i data-feather="users" class="w-4 h-4"></i>
                                Clients
                            </a>
                            <a href="simulation.php" class="<?php echo $currentPage === 'simulation' ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?> px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2">
                                <i data-feather="calculator" class="w-4 h-4"></i>
                                Simulation
                            </a>
                            <a href="history.php" class="<?php echo $currentPage === 'history' ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?> px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2">
                                <i data-feather="clock" class="w-4 h-4"></i>
                                Historique
                            </a>
                            <?php if (isAdmin()): ?>
                            <a href="admin/index.php" class="<?php echo strpos($currentPage, 'admin') !== false ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'; ?> px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-2">
                                <i data-feather="settings" class="w-4 h-4"></i>
                                Administration
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="flex items-center gap-4">
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" onclick="toggleUserMenu()" class="flex items-center gap-3 text-sm focus:outline-none" id="user-menu-button">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center font-medium">
                                        <?php echo strtoupper(substr($currentUser['nom'], 0, 2)); ?>
                                    </div>
                                    <div class="hidden md:block text-left">
                                        <p class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo ucfirst($currentUser['role']); ?></p>
                                    </div>
                                    <i data-feather="chevron-down" class="w-4 h-4 text-slate-400"></i>
                                </div>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 ring-1 ring-black ring-opacity-5 z-50">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                </div>
                                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <i data-feather="log-out" class="w-4 h-4"></i>
                                    Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="flex items-center sm:hidden">
                        <button type="button" onclick="toggleMobileMenu()" class="p-2 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100">
                            <i data-feather="menu" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden sm:hidden border-t border-slate-200">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard' ? 'bg-primary-50 text-primary-700' : 'text-slate-600'; ?> block px-3 py-2 rounded-lg text-base font-medium">
                        Tableau de bord
                    </a>
                    <a href="clients.php" class="<?php echo $currentPage === 'clients' ? 'bg-primary-50 text-primary-700' : 'text-slate-600'; ?> block px-3 py-2 rounded-lg text-base font-medium">
                        Clients
                    </a>
                    <a href="simulation.php" class="<?php echo $currentPage === 'simulation' ? 'bg-primary-50 text-primary-700' : 'text-slate-600'; ?> block px-3 py-2 rounded-lg text-base font-medium">
                        Simulation
                    </a>
                    <a href="history.php" class="<?php echo $currentPage === 'history' ? 'bg-primary-50 text-primary-700' : 'text-slate-600'; ?> block px-3 py-2 rounded-lg text-base font-medium">
                        Historique
                    </a>
                    <?php if (isAdmin()): ?>
                    <a href="admin/index.php" class="text-slate-600 block px-3 py-2 rounded-lg text-base font-medium">
                        Administration
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        
        <!-- Flash Messages -->
        <?php if (hasFlashMessages()): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <?php foreach (getFlashMessages() as $flash): ?>
            <div class="flash-message rounded-lg p-4 mb-3 flex items-center gap-3 <?php 
                echo match($flash['type']) {
                    'success' => 'bg-green-50 text-green-800 border border-green-200',
                    'error' => 'bg-red-50 text-red-800 border border-red-200',
                    'warning' => 'bg-yellow-50 text-yellow-800 border border-yellow-200',
                    default => 'bg-blue-50 text-blue-800 border border-blue-200',
                };
            ?>">
                <i data-feather="<?php 
                    echo match($flash['type']) {
                        'success' => 'check-circle',
                        'error' => 'alert-circle',
                        'warning' => 'alert-triangle',
                        default => 'info',
                    };
                ?>" class="w-5 h-5 flex-shrink-0"></i>
                <p class="text-sm"><?php echo htmlspecialchars($flash['message']); ?></p>
                <button type="button" onclick="this.parentElement.remove()" class="ml-auto">
                    <i data-feather="x" class="w-4 h-4"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
