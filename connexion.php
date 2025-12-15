<?php
session_start();
require_once 'Includes/config.php';

// Rediriger si déjà connecté
if (isset($_SESSION['admin_id'])) {
    header('Location: Admin/tableau_bord.php');
    exit();
}

// Gestion du message de déconnexion
$success_message = '';
if (isset($_GET['message']) && $_GET['message'] === 'deconnected') {
    $success_message = 'Vous avez été déconnecté avec succès.';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        try {
            $sql = "SELECT * FROM utilisateurs WHERE email = ? AND est_actif = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['mot_de_passe_hash'])) {
                // Connexion réussie
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_email'] = $user['email'];
                $_SESSION['admin_prenom'] = $user['prenom'];
                $_SESSION['admin_nom'] = $user['nom'];
                $_SESSION['admin_role'] = $user['role'];
                
                // Mettre à jour la dernière connexion
                $sql_update = "UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$user['id']]);
                
                // Redirection vers le tableau de bord
                header('Location: Admin/tableau_bord.php');
                exit();
            } else {
                $error = "Email ou mot de passe incorrect";
            }
        } catch(PDOException $e) {
            $error = "Erreur de connexion à la base de données";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion Administrateur - PLMB Technologie</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-logo {
            text-align: center;
            padding: 30px 0;
            background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
            border-radius: 15px 15px 0 0;
            color: white;
        }
        .login-logo i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .login-form {
            padding: 30px;
        }
        .form-control:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
            border: none;
            padding: 12px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #1E40AF 0%, #3B82F6 100%);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="fas fa-laptop-code"></i>
                <h2>PLMB Technologie</h2>
                <p class="mb-0">Espace Administrateur</p>
            </div>
                 <?php if(!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
            <div class="login-form">
                <?php if(isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="post" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-2 text-primary"></i>Adresse email
                        </label>
                        <input 
                            type="email" 
                            class="form-control form-control-lg" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            placeholder="votre@email.com"
                            required
                        >
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-2 text-primary"></i>Mot de passe
                        </label>
                        <input 
                            type="password" 
                            class="form-control form-control-lg" 
                            id="password" 
                            name="password" 
                            placeholder="Votre mot de passe"
                            required
                        >
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Se connecter
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <a href="../index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        Retour au site
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Animation au chargement
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.classList.add('animate__animated', 'animate__fadeInUp');
        });
        
        // Validation côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            if (!email.value || !password.value) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs');
                return false;
            }
            
            if (!isValidEmail(email.value)) {
                e.preventDefault();
                alert('Veuillez entrer une adresse email valide');
                email.focus();
                return false;
            }
        });
        
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    </script>
</body>
</html>