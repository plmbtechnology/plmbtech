<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Gestion de l'Équipe";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

try {
    switch($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $prenom = trim($_POST['prenom']);
                $nom = trim($_POST['nom']);
                $position = trim($_POST['position']);
                $bio = trim($_POST['bio']);
                $email = trim($_POST['email']);
                $telephone = trim($_POST['telephone']);
                $competences = json_encode(explode(',', $_POST['competences']));
                $display_order = $_POST['display_order'];
                $est_actif = isset($_POST['est_actif']) ? 1 : 0;
                $joined_date = $_POST['joined_date'];
                
                // Liens sociaux
                $social_links = [
                    'linkedin' => trim($_POST['linkedin'] ?? ''),
                    'twitter' => trim($_POST['twitter'] ?? ''),
                    'github' => trim($_POST['github'] ?? '')
                ];
                
                // Gestion de l'upload d'avatar
                $avatar_url = null;
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    require_once '../Includes/upload.php';
                    $upload = uploader_fichier($_FILES['avatar'], 'equipe');
                    if ($upload['success']) {
                        $avatar_url = $upload['chemin'];
                    }
                }
                
                $sql = "INSERT INTO membres_equipe (prenom, nom, position, bio, email, telephone, 
                        avatar_url, competences, liens_sociaux, display_order, est_actif, joined_date) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$prenom, $nom, $position, $bio, $email, $telephone, 
                              $avatar_url, $competences, json_encode($social_links), 
                              $display_order, $est_actif, $joined_date]);
                
                $_SESSION['success'] = "Membre d'équipe créé avec succès";
                header('Location: equipe.php');
                exit();
            }
            break;
            
        case 'edit':
            if ($id) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $prenom = trim($_POST['prenom']);
                    $nom = trim($_POST['nom']);
                    $position = trim($_POST['position']);
                    $bio = trim($_POST['bio']);
                    $email = trim($_POST['email']);
                    $telephone = trim($_POST['telephone']);
                    $competences = json_encode(explode(',', $_POST['competences']));
                    $display_order = $_POST['display_order'];
                    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
                    $joined_date = $_POST['joined_date'];
                    
                    // Liens sociaux
                    $social_links = [
                        'linkedin' => trim($_POST['linkedin'] ?? ''),
                        'twitter' => trim($_POST['twitter'] ?? ''),
                        'github' => trim($_POST['github'] ?? '')
                    ];
                    
                    // Gestion de l'upload d'avatar
                    $avatar_url = $_POST['avatar_existant'];
                    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                        require_once '../Includes/upload.php';
                        $upload = uploader_fichier($_FILES['avatar'], 'equipe');
                        if ($upload['success']) {
                            if ($avatar_url) {
                                supprimer_fichier($avatar_url);
                            }
                            $avatar_url = $upload['chemin'];
                        }
                    }
                    
                    $sql = "UPDATE membres_equipe 
                            SET prenom = ?, nom = ?, position = ?, bio = ?, email = ?, telephone = ?,
                                avatar_url = ?, competences = ?, liens_sociaux = ?, 
                                display_order = ?, est_actif = ?, joined_date = ?
                            WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$prenom, $nom, $position, $bio, $email, $telephone, 
                                  $avatar_url, $competences, json_encode($social_links), 
                                  $display_order, $est_actif, $joined_date, $id]);
                    
                    $_SESSION['success'] = "Membre d'équipe modifié avec succès";
                    header('Location: equipe.php');
                    exit();
                }
                
                // Récupérer le membre pour édition
                $sql = "SELECT * FROM membres_equipe WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $membre = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$membre) {
                    $_SESSION['error'] = "Membre d'équipe non trouvé";
                    header('Location: equipe.php');
                    exit();
                }
                
                // Décoder les données JSON
                if ($membre['competences']) {
                    $membre['competences_list'] = json_decode($membre['competences'], true);
                }
                if ($membre['liens_sociaux']) {
                    $membre['social_links'] = json_decode($membre['liens_sociaux'], true);
                }
            }
            break;
            
        case 'view':
            if ($id) {
                $sql = "SELECT * FROM membres_equipe WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $membre = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$membre) {
                    $_SESSION['error'] = "Membre d'équipe non trouvé";
                    header('Location: equipe.php');
                    exit();
                }
                
                // Décoder les données JSON
                if ($membre['competences']) {
                    $membre['competences_list'] = json_decode($membre['competences'], true);
                }
                if ($membre['liens_sociaux']) {
                    $membre['social_links'] = json_decode($membre['liens_sociaux'], true);
                }
            }
            break;
            
        case 'delete':
            if ($id) {
                // Récupérer l'avatar pour le supprimer
                $sql = "SELECT avatar_url FROM membres_equipe WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $membre = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($membre && $membre['avatar_url']) {
                    require_once '../Includes/upload.php';
                    supprimer_fichier($membre['avatar_url']);
                }
                
                $sql = "DELETE FROM membres_equipe WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Membre d'équipe supprimé avec succès";
                header('Location: equipe.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Récupérer tous les membres d'équipe
            $sql = "SELECT * FROM membres_equipe ORDER BY display_order, nom, prenom";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $membres = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Gestion de l'Équipe</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Équipe</li>
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
                    <h3 class="card-title">Liste des Membres de l'Équipe</h3>
                    <div class="card-tools">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Membre
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($membres as $membre): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card card-widget widget-user">
                                <div class="widget-user-header bg-primary">
                                    <h3 class="widget-user-username"><?php echo htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']); ?></h3>
                                    <h5 class="widget-user-desc"><?php echo htmlspecialchars($membre['position']); ?></h5>
                                </div>
                                <div class="widget-user-image">
                                    <?php if($membre['avatar_url']): ?>
                                    <img src="../Admin/uploads/<?php echo $membre['avatar_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']); ?>" 
                                         class="img-circle elevation-2">
                                    <?php else: ?>
                                    <div class="img-circle elevation-2 bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 90px; height: 90px;">
                                        <i class="fas fa-user fa-2x text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="row">
                                        <div class="col-sm-4 border-right">
                                            <div class="description-block">
                                                <h5 class="description-header"><?php echo $membre['display_order']; ?></h5>
                                                <span class="description-text">Ordre</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 border-right">
                                            <div class="description-block">
                                                <h5 class="description-header">
                                                    <?php if($membre['est_actif']): ?>
                                                    <i class="fas fa-check text-success"></i>
                                                    <?php else: ?>
                                                    <i class="fas fa-times text-danger"></i>
                                                    <?php endif; ?>
                                                </h5>
                                                <span class="description-text">Statut</span>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="description-block">
                                                <div class="btn-group">
                                                    <a href="?action=view&id=<?php echo $membre['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?action=edit&id=<?php echo $membre['id']; ?>" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=delete&id=<?php echo $membre['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce membre ?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <?php elseif($action === 'view'): ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails du Membre</h3>
                    <div class="card-tools">
                        <a href="equipe.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <?php if($membre['avatar_url']): ?>
                            <img src="../Admin/uploads/<?php echo $membre['avatar_url']; ?>" 
                                 alt="<?php echo htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']); ?>" 
                                 class="img-fluid rounded-circle mb-3" style="max-width: 200px;">
                            <?php else: ?>
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                                 style="width: 200px; height: 200px;">
                                <i class="fas fa-user fa-4x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($membre['prenom'] . ' ' . $membre['nom']); ?></h3>
                            <h5 class="text-muted"><?php echo htmlspecialchars($membre['position']); ?></h5>
                            
                            <div class="mt-3">
                                <?php if($membre['est_actif']): ?>
                                <span class="badge badge-success">Actif</span>
                                <?php else: ?>
                                <span class="badge badge-secondary">Inactif</span>
                                <?php endif; ?>
                                <span class="badge badge-info">Ordre: <?php echo $membre['display_order']; ?></span>
                            </div>
                            
                            <?php if(isset($membre['social_links'])): ?>
                            <div class="mt-3">
                                <?php if(!empty($membre['social_links']['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($membre['social_links']['linkedin']); ?>" 
                                   target="_blank" class="btn btn-sm btn-primary mr-1">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                                <?php endif; ?>
                                <?php if(!empty($membre['social_links']['twitter'])): ?>
                                <a href="<?php echo htmlspecialchars($membre['social_links']['twitter']); ?>" 
                                   target="_blank" class="btn btn-sm btn-info mr-1">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <?php endif; ?>
                                <?php if(!empty($membre['social_links']['github'])): ?>
                                <a href="<?php echo htmlspecialchars($membre['social_links']['github']); ?>" 
                                   target="_blank" class="btn btn-sm btn-dark">
                                    <i class="fab fa-github"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Informations Personnelles</h4>
                                </div>
                                <div class="card-body">
                                    <p><strong>Email :</strong> 
                                        <?php if($membre['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($membre['email']); ?>"><?php echo htmlspecialchars($membre['email']); ?></a>
                                        <?php else: ?>
                                        <span class="text-muted">Non renseigné</span>
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Téléphone :</strong> 
                                        <?php echo htmlspecialchars($membre['telephone'] ?: 'Non renseigné'); ?>
                                    </p>
                                    <p><strong>Date d'intégration :</strong> 
                                        <?php echo $membre['joined_date'] ? date('d/m/Y', strtotime($membre['joined_date'])) : 'Non renseignée'; ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4>Biographie</h4>
                                </div>
                                <div class="card-body">
                                    <?php if($membre['bio']): ?>
                                    <p><?php echo nl2br(htmlspecialchars($membre['bio'])); ?></p>
                                    <?php else: ?>
                                    <p class="text-muted">Aucune biographie renseignée</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if(isset($membre['competences_list']) && !empty($membre['competences_list'])): ?>
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h4>Compétences</h4>
                                </div>
                                <div class="card-body">
                                    <?php foreach($membre['competences_list'] as $competence): ?>
                                    <span class="badge badge-light border mr-1 mb-1"><?php echo htmlspecialchars($competence); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?action=edit&id=<?php echo $membre['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="equipe.php" class="btn btn-default">Retour à la liste</a>
                </div>
            </div>
            
            <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $action === 'add' ? 'Ajouter un Membre' : 'Modifier le Membre'; ?>
                    </h3>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prénom *</label>
                                    <input type="text" name="prenom" class="form-control" 
                                           value="<?php echo htmlspecialchars($membre['prenom'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nom *</label>
                                    <input type="text" name="nom" class="form-control" 
                                           value="<?php echo htmlspecialchars($membre['nom'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Poste *</label>
                            <input type="text" name="position" class="form-control" 
                                   value="<?php echo htmlspecialchars($membre['position'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Biographie</label>
                            <textarea name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($membre['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($membre['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input type="text" name="telephone" class="form-control" 
                                           value="<?php echo htmlspecialchars($membre['telephone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date d'intégration</label>
                                    <input type="date" name="joined_date" class="form-control" 
                                           value="<?php echo $membre['joined_date'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Ordre d'affichage</label>
                                    <input type="number" name="display_order" class="form-control" 
                                           value="<?php echo $membre['display_order'] ?? 0; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Compétences (séparées par des virgules)</label>
                            <input type="text" name="competences" class="form-control" 
                                   value="<?php 
                                   if (isset($membre['competences_list'])) {
                                       echo htmlspecialchars(implode(', ', $membre['competences_list']));
                                   }
                                   ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Avatar</label>
                            <?php if(isset($membre['avatar_url']) && $membre['avatar_url']): ?>
                            <div class="mb-2">
                                <img src="../Admin/uploads/<?php echo $membre['avatar_url']; ?>" 
                                     alt="Avatar" style="max-height: 100px;" class="img-thumbnail">
                                <input type="hidden" name="avatar_existant" value="<?php echo $membre['avatar_url']; ?>">
                                <br>
                                <small>Avatar actuel</small>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="avatar" class="form-control-file" accept="image/*">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>LinkedIn</label>
                                    <input type="url" name="linkedin" class="form-control" 
                                           value="<?php echo htmlspecialchars($membre['social_links']['linkedin'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Twitter</label>
                                    <input type="url" name="twitter" class="form-control" 
                                           value="<?php echo htmlspecialchars($membre['social_links']['twitter'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>GitHub</label>
                                    <input type="url" name="github" class="form-control" 
                                           value="<?php echo htmlspecialchars($membre['social_links']['github'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" name="est_actif" class="form-check-input" 
                                   id="est_actif" <?php echo ($membre['est_actif'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="est_actif">Membre actif</label>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="equipe.php" class="btn btn-default">Annuler</a>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>