<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Gestion des Médias";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

try {
    switch($action) {
        case 'upload':
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['fichiers'])) {
                $uploaded_files = [];
                $errors = [];
                
                require_once '../Includes/upload.php';
                
                foreach ($_FILES['fichiers']['name'] as $key => $name) {
                    if ($_FILES['fichiers']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $name,
                            'type' => $_FILES['fichiers']['type'][$key],
                            'tmp_name' => $_FILES['fichiers']['tmp_name'][$key],
                            'error' => $_FILES['fichiers']['error'][$key],
                            'size' => $_FILES['fichiers']['size'][$key]
                        ];
                        
                        $upload = uploader_fichier($file, 'generale');
                        if ($upload['success']) {
                            // Enregistrer en base de données
                            $sql = "INSERT INTO fichiers_media (nom_fichier, nom_original, chemin_fichier, 
                                    taille_fichier, type_mime, alt_text, legende, upload_par) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $upload['nom_fichier'],
                                $name,
                                $upload['chemin'],
                                $file['size'],
                                $file['type'],
                                $_POST['alt_text'] ?? '',
                                $_POST['legende'] ?? '',
                                $_SESSION['admin_id']
                            ]);
                            
                            $uploaded_files[] = $upload['nom_fichier'];
                        } else {
                            $errors[] = $name . ': ' . $upload['message'];
                        }
                    }
                }
                
                if (!empty($uploaded_files)) {
                    $_SESSION['success'] = count($uploaded_files) . " fichier(s) uploadé(s) avec succès";
                }
                if (!empty($errors)) {
                    $_SESSION['error'] = implode('<br>', $errors);
                }
                
                header('Location: medias.php');
                exit();
            }
            break;
            
        case 'edit':
            if ($id && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $alt_text = trim($_POST['alt_text']);
                $legende = trim($_POST['legende']);
                
                $sql = "UPDATE fichiers_media 
                        SET alt_text = ?, legende = ? 
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$alt_text, $legende, $id]);
                
                $_SESSION['success'] = "Fichier modifié avec succès";
                header('Location: medias.php');
                exit();
            }
            
            // Récupérer le fichier pour édition
            $sql = "SELECT * FROM fichiers_media WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $fichier = $stmt->fetch(PDO::FETCH_ASSOC);
            break;
            
        case 'delete':
            if ($id) {
                // Récupérer le fichier pour le supprimer
                $sql = "SELECT chemin_fichier FROM fichiers_media WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $fichier = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($fichier) {
                    require_once '../Includes/upload.php';
                    if (supprimer_fichier($fichier['chemin_fichier'])) {
                        // Supprimer de la base de données
                        $sql = "DELETE FROM fichiers_media WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$id]);
                        
                        $_SESSION['success'] = "Fichier supprimé avec succès";
                    } else {
                        $_SESSION['error'] = "Erreur lors de la suppression du fichier";
                    }
                }
                
                header('Location: medias.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Récupérer tous les fichiers médias
            $sql = "SELECT fm.*, u.prenom as uploader_prenom, u.nom as uploader_nom 
                    FROM fichiers_media fm 
                    LEFT JOIN utilisateurs u ON fm.upload_par = u.id 
                    ORDER BY fm.date_creation DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $medias = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Gestion des Médias</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Médias</li>
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
                    <h3 class="card-title">Bibliothèque des Médias</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
                            <i class="fas fa-upload"></i> Uploader des fichiers
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($medias as $media): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card">
                                <?php if(strpos($media['type_mime'], 'image/') === 0): ?>
                                <img src="../Admin/uploads/<?php echo $media['chemin_fichier']; ?>" 
                                     alt="<?php echo htmlspecialchars($media['alt_text']); ?>" 
                                     class="card-img-top" style="height: 150px; object-fit: cover;">
                                <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                     style="height: 150px;">
                                    <i class="fas fa-file fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h6 class="card-title text-truncate"><?php echo htmlspecialchars($media['nom_original']); ?></h6>
                                    <p class="card-text small text-muted">
                                        <?php echo $media['type_mime']; ?><br>
                                        <?php echo round($media['taille_fichier'] / 1024, 2); ?> KB<br>
                                        Uploadé par <?php echo htmlspecialchars($media['uploader_prenom'] . ' ' . $media['uploader_nom']); ?><br>
                                        <?php echo date('d/m/Y H:i', strtotime($media['date_creation'])); ?>
                                    </p>
                                    
                                    <?php if($media['alt_text']): ?>
                                    <p class="card-text small">
                                        <strong>Alt:</strong> <?php echo htmlspecialchars($media['alt_text']); ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="../Admin/uploads/<?php echo $media['chemin_fichier']; ?>" 
                                           target="_blank" class="btn btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $media['id']; ?>" 
                                           class="btn btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $media['id']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fichier ?')"
                                           title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Modal d'upload -->
            <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Uploader des fichiers</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <form method="post" action="?action=upload" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Fichiers</label>
                                    <input type="file" name="fichiers[]" class="form-control-file" multiple accept="image/*,application/pdf">
                                    <small class="form-text text-muted">
                                        Formats acceptés : JPEG, PNG, GIF, PDF. Taille max : 5MB par fichier.
                                    </small>
                                </div>
                                <div class="form-group">
                                    <label>Texte alternatif (pour les images)</label>
                                    <input type="text" name="alt_text" class="form-control" placeholder="Description de l'image">
                                </div>
                                <div class="form-group">
                                    <label>Légende</label>
                                    <input type="text" name="legende" class="form-control" placeholder="Légende optionnelle">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Uploader</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php elseif($action === 'edit'): ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Modifier le Fichier</h3>
                </div>
                <form method="post">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <?php if(strpos($fichier['type_mime'], 'image/') === 0): ?>
                                <img src="../Admin/uploads/<?php echo $fichier['chemin_fichier']; ?>" 
                                     alt="<?php echo htmlspecialchars($fichier['alt_text']); ?>" 
                                     class="img-fluid rounded">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                     style="height: 200px;">
                                    <i class="fas fa-file fa-4x text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nom du fichier</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($fichier['nom_original']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Type MIME</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($fichier['type_mime']); ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Taille</label>
                                    <input type="text" class="form-control" value="<?php echo round($fichier['taille_fichier'] / 1024, 2); ?> KB" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Texte alternatif</label>
                                    <input type="text" name="alt_text" class="form-control" 
                                           value="<?php echo htmlspecialchars($fichier['alt_text']); ?>">
                                    <small class="form-text text-muted">Description pour l'accessibilité (SEO)</small>
                                </div>
                                <div class="form-group">
                                    <label>Légende</label>
                                    <input type="text" name="legende" class="form-control" 
                                           value="<?php echo htmlspecialchars($fichier['legende']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="medias.php" class="btn btn-default">Annuler</a>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>