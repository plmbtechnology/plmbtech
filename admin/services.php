<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Gestion des Services";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

try {
    switch($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $titre = trim($_POST['titre']);
                $slug = trim($_POST['slug']);
                $description_courte = trim($_POST['description_courte']);
                $description_complete = trim($_POST['description_complete']);
                $categorie_id = $_POST['categorie_id'];
                $classe_icone = trim($_POST['classe_icone']);
                $technologies = json_encode(explode(',', $_POST['technologies']));
                $fonctionnalites = json_encode(explode(',', $_POST['fonctionnalites']));
                $ordre_affichage = $_POST['ordre_affichage'];
                $est_en_vedette = isset($_POST['est_en_vedette']) ? 1 : 0;
                $est_actif = isset($_POST['est_actif']) ? 1 : 0;
                
                // Gestion de l'upload d'image
                $url_image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    require_once '../Includes/upload.php';
                    $upload = uploader_fichier($_FILES['image'], 'services');
                    if ($upload['success']) {
                        $url_image = $upload['chemin'];
                    }
                }
                
                $sql = "INSERT INTO services (titre, slug, description_courte, description_complete, 
                        categorie_id, classe_icone, url_image, technologies, fonctionnalites, 
                        ordre_affichage, est_en_vedette, est_actif) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titre, $slug, $description_courte, $description_complete, $categorie_id, 
                              $classe_icone, $url_image, $technologies, $fonctionnalites, 
                              $ordre_affichage, $est_en_vedette, $est_actif]);
                
                $_SESSION['success'] = "Service créé avec succès";
                header('Location: services.php');
                exit();
            }
            break;
            
        case 'edit':
            if ($id) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $titre = trim($_POST['titre']);
                    $slug = trim($_POST['slug']);
                    $description_courte = trim($_POST['description_courte']);
                    $description_complete = trim($_POST['description_complete']);
                    $categorie_id = $_POST['categorie_id'];
                    $classe_icone = trim($_POST['classe_icone']);
                    $technologies = json_encode(explode(',', $_POST['technologies']));
                    $fonctionnalites = json_encode(explode(',', $_POST['fonctionnalites']));
                    $ordre_affichage = $_POST['ordre_affichage'];
                    $est_en_vedette = isset($_POST['est_en_vedette']) ? 1 : 0;
                    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
                    
                    // Gestion de l'upload d'image
                    $url_image = $_POST['image_existante'];
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        require_once '../Includes/upload.php';
                        $upload = uploader_fichier($_FILES['image'], 'services');
                        if ($upload['success']) {
                            // Supprimer l'ancienne image si elle existe
                            if ($url_image) {
                                supprimer_fichier($url_image);
                            }
                            $url_image = $upload['chemin'];
                        }
                    }
                    
                    $sql = "UPDATE services 
                            SET titre = ?, slug = ?, description_courte = ?, description_complete = ?,
                                categorie_id = ?, classe_icone = ?, url_image = ?, technologies = ?, 
                                fonctionnalites = ?, ordre_affichage = ?, est_en_vedette = ?, est_actif = ?
                            WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$titre, $slug, $description_courte, $description_complete, $categorie_id, 
                                  $classe_icone, $url_image, $technologies, $fonctionnalites, 
                                  $ordre_affichage, $est_en_vedette, $est_actif, $id]);
                    
                    $_SESSION['success'] = "Service modifié avec succès";
                    header('Location: services.php');
                    exit();
                }
                
                // Récupérer le service pour édition
                $sql = "SELECT s.*, cs.nom as categorie_nom 
                        FROM services s 
                        LEFT JOIN categories_service cs ON s.categorie_id = cs.id 
                        WHERE s.id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$service) {
                    $_SESSION['error'] = "Service non trouvé";
                    header('Location: services.php');
                    exit();
                }
            }
            break;
            
        case 'delete':
            if ($id) {
                // Récupérer l'image pour la supprimer
                $sql = "SELECT url_image FROM services WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($service && $service['url_image']) {
                    require_once '../Includes/upload.php';
                    supprimer_fichier($service['url_image']);
                }
                
                $sql = "DELETE FROM services WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Service supprimé avec succès";
                header('Location: services.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Récupérer tous les services avec leurs catégories
            $sql = "SELECT s.*, cs.nom as categorie_nom 
                    FROM services s 
                    LEFT JOIN categories_service cs ON s.categorie_id = cs.id 
                    ORDER BY s.ordre_affichage, s.titre";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer les catégories pour les formulaires
            $sql_categories = "SELECT * FROM categories_service WHERE est_actif = 1 ORDER BY ordre_affichage";
            $stmt_categories = $pdo->prepare($sql_categories);
            $stmt_categories->execute();
            $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Gestion des Services</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Services</li>
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
                    <h3 class="card-title">Liste des Services</h3>
                    <div class="card-tools">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Service
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Ordre</th>
                                <th>Icône</th>
                                <th>Titre</th>
                                <th>Catégorie</th>
                                <th>Statut</th>
                                <th>Vedette</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($services as $service): ?>
                            <tr>
                                <td><?php echo $service['ordre_affichage']; ?></td>
                                <td>
                                    <i class="<?php echo htmlspecialchars($service['classe_icone']); ?>"></i>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($service['titre']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($service['description_courte']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($service['categorie_nom'] ?? 'Non catégorisé'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $service['est_actif'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $service['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($service['est_en_vedette']): ?>
                                    <i class="fas fa-star text-warning"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $service['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce service ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
                        <?php echo $action === 'add' ? 'Ajouter un Service' : 'Modifier le Service'; ?>
                    </h3>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Titre *</label>
                                    <input type="text" name="titre" class="form-control" 
                                           value="<?php echo htmlspecialchars($service['titre'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Slug *</label>
                                    <input type="text" name="slug" class="form-control" 
                                           value="<?php echo htmlspecialchars($service['slug'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description courte *</label>
                            <textarea name="description_courte" class="form-control" rows="2" required><?php echo htmlspecialchars($service['description_courte'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Description complète</label>
                            <textarea name="description_complete" class="form-control" rows="5"><?php echo htmlspecialchars($service['description_complete'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Catégorie</label>
                                    <select name="categorie_id" class="form-control">
                                        <option value="">Sélectionnez une catégorie</option>
                                        <?php foreach($categories as $categorie): ?>
                                        <option value="<?php echo $categorie['id']; ?>" 
                                                <?php echo ($service['categorie_id'] ?? '') == $categorie['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categorie['nom']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Classe icône (Font Awesome)</label>
                                    <input type="text" name="classe_icone" class="form-control" 
                                           value="<?php echo htmlspecialchars($service['classe_icone'] ?? 'fas fa-cog'); ?>" 
                                           placeholder="fas fa-cog">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ordre d'affichage</label>
                                    <input type="number" name="ordre_affichage" class="form-control" 
                                           value="<?php echo $service['ordre_affichage'] ?? 0; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Technologies (séparées par des virgules)</label>
                                    <input type="text" name="technologies" class="form-control" 
                                           value="<?php 
                                           if (isset($service['technologies'])) {
                                               $techs = json_decode($service['technologies'], true);
                                               echo htmlspecialchars(implode(', ', $techs));
                                           }
                                           ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Fonctionnalités (séparées par des virgules)</label>
                                    <input type="text" name="fonctionnalites" class="form-control" 
                                           value="<?php 
                                           if (isset($service['fonctionnalites'])) {
                                               $funcs = json_decode($service['fonctionnalites'], true);
                                               echo htmlspecialchars(implode(', ', $funcs));
                                           }
                                           ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Image du service</label>
                            <?php if(isset($service['url_image']) && $service['url_image']): ?>
                            <div class="mb-2">
                                <img src="../Admin/uploads/<?php echo $service['url_image']; ?>" 
                                     alt="Image service" style="max-height: 100px;" class="img-thumbnail">
                                <input type="hidden" name="image_existante" value="<?php echo $service['url_image']; ?>">
                                <br>
                                <small>Image actuelle</small>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control-file" accept="image/*">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="est_en_vedette" class="form-check-input" 
                                           id="est_en_vedette" <?php echo ($service['est_en_vedette'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="est_en_vedette">Service en vedette</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="est_actif" class="form-check-input" 
                                           id="est_actif" <?php echo ($service['est_actif'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="est_actif">Service actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="services.php" class="btn btn-default">Annuler</a>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>