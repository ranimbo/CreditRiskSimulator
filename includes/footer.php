</main>
        
        <!-- Footer — Style Amen Bank -->
        <footer class="bg-[#003366] border-t border-[#002244] mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="flex items-center gap-3">
                        <p class="text-sm text-white/60">
                            &copy; <?php echo date('Y'); ?> Amen Bank &middot; Tous droits réservés
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-white/40">Credit Risk Simulator v2.0</span>
                        <span class="text-xs text-white/30">|</span>
                        <span class="text-xs text-white/40 flex items-center gap-1">
                            <i data-feather="shield" class="w-3 h-3"></i>
                            Connexion sécurisée SSL
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <?php
    // Chemin dynamique selon le dossier courant
    $isAdminPage = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
    $baseJs = $isAdminPage ? '../' : '';
    ?>
    <!-- Custom JavaScript — chemin dynamique -->
    <script src="<?php echo $baseJs; ?>assets/js/app.js"></script>
    
    <!-- Initialize Feather Icons -->
    <script>feather.replace();</script>
    
    <!-- Navigation Scripts -->
    <script>
        function toggleUserMenu() {
            document.getElementById('user-menu').classList.toggle('hidden');
        }
        function toggleMobileMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }
        document.addEventListener('click', function(event) {
            const btn  = document.getElementById('user-menu-button');
            const menu = document.getElementById('user-menu');
            if (btn && menu && !btn.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>