        </main>
        
        <!-- Footer — Style Amen Bank -->
        <footer class="bg-[#003366] border-t border-[#002244] mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="flex items-center gap-3">
                        <img src="https://www.amenbank.com.tn/wp-content/themes/amenbank/images/logo.png" 
                             alt="Amen Bank" 
                             class="h-6 brightness-0 invert opacity-60"
                             onerror="this.style.display='none'">
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
    
    <!-- Custom JavaScript -->
    <script src="assets/js/app.js"></script>
    
    <!-- Initialize Feather Icons -->
    <script>
        feather.replace();
    </script>
    
    <!-- Navigation Scripts -->
    <script>
        function toggleUserMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }
        
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
        
        // Close menus when clicking outside
        document.addEventListener('click', function(event) {
            const userButton = document.getElementById('user-menu-button');
            const userMenu = document.getElementById('user-menu');
            
            if (userButton && userMenu && !userButton.contains(event.target) && !userMenu.contains(event.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
