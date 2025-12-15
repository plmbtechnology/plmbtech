<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'PLMB Technologie - Solutions Numériques'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="Assets/css/responsive.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .hero-gradient {
            background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-laptop-code text-blue-600 text-2xl mr-2"></i>
                        <span class="font-bold text-xl text-gray-800">PLMB Technologie</span>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-blue-600 font-medium border-b-2 border-blue-600 px-1' : 'text-gray-600 hover:text-blue-600 font-medium transition duration-150'; ?>">Accueil</a>
                    <a href="apropos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'apropos.php' ? 'text-blue-600 font-medium border-b-2 border-blue-600 px-1' : 'text-gray-600 hover:text-blue-600 font-medium transition duration-150'; ?>">À propos</a>
                    <a href="services.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'text-blue-600 font-medium border-b-2 border-blue-600 px-1' : 'text-gray-600 hover:text-blue-600 font-medium transition duration-150'; ?>">Services</a>
                    <a href="projets.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'projets.php' ? 'text-blue-600 font-medium border-b-2 border-blue-600 px-1' : 'text-gray-600 hover:text-blue-600 font-medium transition duration-150'; ?>">Projets</a>
                    <a href="blog.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'blog.php' ? 'text-blue-600 font-medium border-b-2 border-blue-600 px-1' : 'text-gray-600 hover:text-blue-600 font-medium transition duration-150'; ?>">Blog</a>
                    <a href="contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'text-blue-600 font-medium border-b-2 border-blue-600 px-1' : 'text-gray-600 hover:text-blue-600 font-medium transition duration-150'; ?>">Contact</a>
                </div>
                <div class="md:hidden flex items-center">
                    <button class="text-gray-600 hover:text-blue-600 focus:outline-none" id="menu-mobile">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Menu mobile -->
    <div class="md:hidden hidden bg-white shadow-lg" id="menu-mobile-content">
        <div class="px-4 py-2 space-y-2">
            <a href="index.php" class="block py-2 text-gray-600 hover:text-blue-600">Accueil</a>
            <a href="apropos.php" class="block py-2 text-gray-600 hover:text-blue-600">À propos</a>
            <a href="services.php" class="block py-2 text-gray-600 hover:text-blue-600">Services</a>
            <a href="projets.php" class="block py-2 text-gray-600 hover:text-blue-600">Projets</a>
            <a href="blog.php" class="block py-2 text-gray-600 hover:text-blue-600">Blog</a>
            <a href="contact.php" class="block py-2 text-gray-600 hover:text-blue-600">Contact</a>
        </div>
    </div>