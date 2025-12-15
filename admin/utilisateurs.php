<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

// Vérifier les permissions
if (!a_la_permission('administrateur')) {
    $_SESSION['error'] = "Accès non autorisé";
    header('Location: tableau_bord.php');
    exit();
}

$page_title = "Gestion des Utilisateurs";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

try {
    switch($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $prenom = trim($_POST['prenom']);
                $nom = trim($_POST['nom']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $telephone = trim($_POST['telephone']);
                
                // Validation
                if (empty($prenom) || empty($nom) || empty($email) || empty($password)) {
                    $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis";
                } else {
                    // Vérifier si l'email existe déjà
                    $sql = "SELECT id FROM utilisateurs WHERE email = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$email]);
                    
                    if ($stmt->fetch()) {
                        $_SESSION['error'] = "Cet email est déjà utilisé";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        $sql = "INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe_hash, role, telephone) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$prenom, $nom, $email, $password_hash, $role, $telephone]);
                        
                        $_SESSION['success'] = "Utilisateur créé avec succès";
                        header('Location: utilisateurs.php');
                        exit();
                    }
                }
            }
            break;
            
        case 'edit':
            if ($id) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $prenom = trim($_POST['prenom']);
                    $nom = trim($_POST['nom']);
                    $email = trim($_POST['email']);
                    $role = $_POST['role'];
                    $telephone = trim($_POST['telephone']);
                    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
                    
                    // Vérifier si l'email existe déjà pour un autre utilisateur
                    $sql = "SELECT id FROM utilisateurs WHERE email = ? AND id != ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$email, $id]);
                    
                    if ($stmt->fetch()) {
                        $_SESSION['error'] = "Cet email est déjà utilisé par un autre utilisateur";
                    } else {
                        $sql = "UPDATE utilisateurs 
                                SET prenom = ?, nom = ?, email = ?, role = ?, telephone = ?, est_actif = ?
                                WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$prenom, $nom, $email, $role, $telephone, $est_actif, $id]);
                        
                        $_SESSION['success'] = "Utilisateur modifié avec succès";
                        header('Location: utilisateurs.php');
                        exit();
                    }
                }
                
                // Récupérer l'utilisateur pour édition
                $sql = "SELECT * FROM utilisateurs WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$utilisateur) {
                    $_SESSION['error'] = "Utilisateur non trouvé";
                    header('Location: utilisateurs.php');
                    exit();
                }
            }
            break;
            
        case 'delete':
            if ($id) {
                // Empêcher la suppression de son propre compte
                if ($id == $_SESSION['admin_id']) {
                    $_SESSION['error'] = "Vous ne pouvez pas supprimer votre propre compte";
                } else {
                    $sql = "DELETE FROM utilisateurs WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id]);
                    
                    $_SESSION['success'] = "Utilisateur supprimé avec succès";
                }
                header('Location: utilisateurs.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Récupérer tous les utilisateurs
            $sql = "SELECT * FROM utilisateurs ORDER BY nom, prenom";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur base de données : " . $e->getMessage();
}

include '../Includes/header.php';
include '../Includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Gestion des Utilisateurs</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Utilisateurs</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php if($action === 'list'): ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des Utilisateurs</h3>
                    <div class="card-tools">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouvel Utilisateur
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Téléphone</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($utilisateurs as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] === 'administrateur' ? 'danger' : ($user['role'] === 'editeur' ? 'warning' : 'info'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['telephone'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['est_actif'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $user['derniere_connexion'] ? date('d/m/Y H:i', strtotime($user['derniere_connexion'])) : 'Jamais'; ?>
                                </td>
                               <td>
    <a href="?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
        <i class="fas fa-edit"></i>
    </a>
    <?php if($user['id'] != $_SESSION['admin_id']): ?>
    <a href="?action=delete&id=<?php echo $user['id']; ?>" 
       class="btn btn-sm btn-danger" 
       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
        <i class="fas fa-trash"></i>
    </a>
    <?php endif; ?>
</td>

                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $action === 'add' ? 'Ajouter un Utilisateur' : 'Modifier l\'Utilisateur'; ?>
                    </h3>
                </div>
                <form method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prénom *</label>
                                    <input type="text" name="prenom" class="form-control" 
                                           value="<?php echo htmlspecialchars($utilisateur['prenom'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nom *</label>
                                    <input type="text" name="nom" class="form-control" 
                                           value="<?php echo htmlspecialchars($utilisateur['nom'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($utilisateur['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input type="text" name="telephone" class="form-control" 
                                           value="<?php echo htmlspecialchars($utilisateur['telephone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Rôle *</label>
                                    <select name="role" class="form-control" required>
                                        <option value="lecteur" <?php echo ($utilisateur['role'] ?? '') === 'lecteur' ? 'selected' : ''; ?>>Lecteur</option>
                                        <option value="editeur" <?php echo ($utilisateur['role'] ?? '') === 'editeur' ? 'selected' : ''; ?>>Éditeur</option>
                                        <option value="administrateur" <?php echo ($utilisateur['role'] ?? '') === 'administrateur' ? 'selected' : ''; ?>>Administrateur</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php if($action === 'add'): ?>
                                <div class="form-group">
                                    <label>Mot de passe *</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                                <?php else: ?>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" name="est_actif" class="custom-control-input" id="est_actif" 
                                               <?php echo ($utilisateur['est_actif'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="est_actif">Utilisateur actif</label>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="utilisateurs.php" class="btn btn-default">Annuler</a>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>