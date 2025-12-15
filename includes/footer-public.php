    <!-- Footer -->
    <footer class="bg-gray-900 text-white pt-12 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center mb-4">
                        <i class="fas fa-laptop-code text-blue-500 text-2xl mr-2"></i>
                        <span class="font-bold text-xl">PLMB Technologie</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Votre partenaire de confiance pour toutes vos solutions numériques et digitales.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-lg mb-4">Navigation</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-400 hover:text-white transition duration-300">Accueil</a></li>
                        <li><a href="apropos.php" class="text-gray-400 hover:text-white transition duration-300">À propos</a></li>
                        <li><a href="services.php" class="text-gray-400 hover:text-white transition duration-300">Services</a></li>
                        <li><a href="projets.php" class="text-gray-400 hover:text-white transition duration-300">Projets</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white transition duration-300">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-lg mb-4">Services</h3>
                    <ul class="space-y-2">
                        <li><a href="services.php" class="text-gray-400 hover:text-white transition duration-300">Développement Web</a></li>
                        <li><a href="services.php" class="text-gray-400 hover:text-white transition duration-300">Applications Mobiles</a></li>
                        <li><a href="services.php" class="text-gray-400 hover:text-white transition duration-300">Solutions Cloud</a></li>
                        <li><a href="services.php" class="text-gray-400 hover:text-white transition duration-300">Cybersécurité</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-lg mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <?php
                        // Récupérer les paramètres de contact depuis la base
                        try {
                            require_once 'Includes/config.php';
                            $sql = "SELECT valeur_parametre FROM parametres_site WHERE cle_parametre IN ('adresse_entreprise', 'telephone_entreprise', 'email_entreprise')";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute();
                            $parametres = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        } catch(PDOException $e) {
                            $parametres = [];
                        }
                        ?>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-blue-500 mt-1 mr-2"></i>
                            <span class="text-gray-400"><?php echo $parametres['adresse_entreprise'] ?? '123 Avenue de la Technologie, 75000 Paris'; ?></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone text-blue-500 mr-2"></i>
                            <span class="text-gray-400"><?php echo $parametres['telephone_entreprise'] ?? '+33 1 23 45 67 89'; ?></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-blue-500 mr-2"></i>
                            <span class="text-gray-400"><?php echo $parametres['email_entreprise'] ?? 'contact@plmb-technologie.fr'; ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> PLMB Technologie. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="Assets/js/main.js"></script>
    
    <!-- Script pour le menu mobile -->
    <script>
        document.getElementById('menu-mobile').addEventListener('click', function() {
            const menu = document.getElementById('menu-mobile-content');
            menu.classList.toggle('hidden');
        });
    </script>
</body>
</html>