<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Gestion des Témoignages";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

try {
    switch($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $client_name = trim($_POST['client_name']);
                $client_position = trim($_POST['client_position']);
                $client_company = trim($_POST['client_company']);
                $content = trim($_POST['content']);
                $rating = $_POST['rating'];
                $projet_id = $_POST['projet_id'] ?: null;
                $est_en_vedette = isset($_POST['est_en_vedette']) ? 1 : 0;
                $est_approuve = isset($_POST['est_approuve']) ? 1 : 0;
                $display_order = $_POST['display_order'];
                
                // Gestion de l'upload d'avatar
                $client_avatar_url = null;
                if (isset($_FILES['client_avatar']) && $_FILES['client_avatar']['error'] === UPLOAD_ERR_OK) {
                    require_once '../Includes/upload.php';
                    $upload = uploader_fichier($_FILES['client_avatar'], 'temoignages');
                    if ($upload['success']) {
                        $client_avatar_url = $upload['chemin'];
                    }
                }
                
                $sql = "INSERT INTO temoignages (client_name, client_position, client_company, 
                        client_avatar_url, projet_id, content, rating, est_en_vedette, est_approuve, display_order) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$client_name, $client_position, $client_company, $client_avatar_url, 
                              $projet_id, $content, $rating, $est_en_vedette, $est_approuve, $display_order]);
                
                $_SESSION['success'] = "Témoignage créé avec succès";
                header('Location: temoignages.php');
                exit();
            }
            break;
            
        case 'edit':
            if ($id) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $client_name = trim($_POST['client_name']);
                    $client_position = trim($_POST['client_position']);
                    $client_company = trim($_POST['client_company']);
                    $content = trim($_POST['content']);
                    $rating = $_POST['rating'];
                    $projet_id = $_POST['projet_id'] ?: null;
                    $est_en_vedette = isset($_POST['est_en_vedette']) ? 1 : 0;
                    $est_approuve = isset($_POST['est_approuve']) ? 1 : 0;
                    $display_order = $_POST['display_order'];
                    
                    // Gestion de l'upload d'avatar
                    $client_avatar_url = $_POST['avatar_existant'];
                    if (isset($_FILES['client_avatar']) && $_FILES['client_avatar']['error'] === UPLOAD_ERR_OK) {
                        require_once '../Includes/upload.php';
                        $upload = uploader_fichier($_FILES['client_avatar'], 'temoignages');
                        if ($upload['success']) {
                            if ($client_avatar_url) {
                                supprimer_fichier($client_avatar_url);
                            }
                            $client_avatar_url = $upload['chemin'];
                        }
                    }
                    
                    $sql = "UPDATE temoignages 
                            SET client_name = ?, client_position = ?, client_company = ?, 
                                client_avatar_url = ?, projet_id = ?, content = ?, rating = ?,
                                est_en_vedette = ?, est_approuve = ?, display_order = ?
                            WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$client_name, $client_position, $client_company, $client_avatar_url, 
                                  $projet_id, $content, $rating, $est_en_vedette, $est_approuve, $display_order, $id]);
                    
                    $_SESSION['success'] = "Témoignage modifié avec succès";
                    header('Location: temoignages.php');
                    exit();
                }
                
                // Récupérer le témoignage pour édition
                $sql = "SELECT t.*, p.titre as projet_titre 
                        FROM temoignages t 
                        LEFT JOIN projets p ON t.projet_id = p.id 
                        WHERE t.id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $temoignage = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$temoignage) {
                    $_SESSION['error'] = "Témoignage non trouvé";
                    header('Location: temoignages.php');
                    exit();
                }
            }
            break;
            
        case 'delete':
            if ($id) {
                // Récupérer l'avatar pour le supprimer
                $sql = "SELECT client_avatar_url FROM temoignages WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $temoignage = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($temoignage && $temoignage['client_avatar_url']) {
                    require_once '../Includes/upload.php';
                    supprimer_fichier($temoignage['client_avatar_url']);
                }
                
                $sql = "DELETE FROM temoignages WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Témoignage supprimé avec succès";
                header('Location: temoignages.php');
                exit();
            }
            break;
            
        case 'toggle_approval':
            if ($id) {
                $sql = "UPDATE temoignages SET est_approuve = NOT est_approuve WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Statut d'approbation modifié";
                header('Location: temoignages.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Récupérer tous les témoignages
            $sql = "SELECT t.*, p.titre as projet_titre 
                    FROM temoignages t 
                    LEFT JOIN projets p ON t.projet_id = p.id 
                    ORDER BY t.display_order, t.date_creation DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $temoignages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer les projets pour les formulaires
            $sql_projets = "SELECT id, titre FROM projets WHERE est_actif = 1 ORDER BY titre";
            $stmt_projets = $pdo->prepare($sql_projets);
            $stmt_projets->execute();
            $projets = $stmt_projets->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Gestion des Témoignages</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Témoignages</li>
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
                    <h3 class="card-title">Liste des Témoignages</h3>
                    <div class="card-tools">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Témoignage
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Projet</th>
                                <th>Témoignage</th>
                                <th>Note</th>
                                <th>Statut</th>
                                <th>Ordre</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($temoignages as $temoignage): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if($temoignage['client_avatar_url']): ?>
                                        <img src="../Admin/uploads/<?php echo $temoignage['client_avatar_url']; ?>" 
                                             alt="<?php echo htmlspecialchars($temoignage['client_name']); ?>" 
                                             class="img-circle mr-3" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mr-3" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($temoignage['client_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($temoignage['client_position']); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($temoignage['client_company']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($temoignage['projet_titre'] ?? 'Général'); ?></td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;">
                                        <?php echo htmlspecialchars($temoignage['content']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-warning">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i > $temoignage['rating'] ? '' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if($temoignage['est_approuve']): ?>
                                    <span class="badge badge-success">Approuvé</span>
                                    <?php else: ?>
                                    <span class="badge badge-warning">En attente</span>
                                    <?php endif; ?>
                                    <?php if($temoignage['est_en_vedette']): ?>
                                    <br>
                                    <span class="badge badge-info mt-1">Vedette</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $temoignage['display_order']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?action=edit&id=<?php echo $temoignage['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=toggle_approval&id=<?php echo $temoignage['id']; ?>" 
                                           class="btn btn-sm btn-<?php echo $temoignage['est_approuve'] ? 'warning' : 'success'; ?>">
                                            <i class="fas fa-<?php echo $temoignage['est_approuve'] ? 'times' : 'check'; ?>"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $temoignage['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce témoignage ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
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
                        <?php echo $action === 'add' ? 'Ajouter un Témoignage' : 'Modifier le Témoignage'; ?>
                    </h3>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nom du client *</label>
                                    <input type="text" name="client_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($temoignage['client_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Poste du client</label>
                                    <input type="text" name="client_position" class="form-control" 
                                           value="<?php echo htmlspecialchars($temoignage['client_position'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Entreprise du client</label>
                                    <input type="text" name="client_company" class="form-control" 
                                           value="<?php echo htmlspecialchars($temoignage['client_company'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Témoignage *</label>
                            <textarea name="content" class="form-control" rows="4" required><?php echo htmlspecialchars($temoignage['content'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Note (sur 5) *</label>
                                    <select name="rating" class="form-control" required>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo ($temoignage['rating'] ?? 5) == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> étoile<?php echo $i > 1 ? 's' : ''; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Projet associé</label>
                                    <select name="projet_id" class="form-control">
                                        <option value="">Aucun projet spécifique</option>
                                        <?php foreach($projets as $projet): ?>
                                        <option value="<?php echo $projet['id']; ?>" 
                                                <?php echo ($temoignage['projet_id'] ?? '') == $projet['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($projet['titre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Ordre d'affichage</label>
                                    <input type="number" name="display_order" class="form-control" 
                                           value="<?php echo $temoignage['display_order'] ?? 0; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Avatar du client</label>
                            <?php if(isset($temoignage['client_avatar_url']) && $temoignage['client_avatar_url']): ?>
                            <div class="mb-2">
                                <img src="../Admin/uploads/<?php echo $temoignage['client_avatar_url']; ?>" 
                                     alt="Avatar client" style="max-height: 100px;" class="img-thumbnail">
                                <input type="hidden" name="avatar_existant" value="<?php echo $temoignage['client_avatar_url']; ?>">
                                <br>
                                <small>Avatar actuel</small>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="client_avatar" class="form-control-file" accept="image/*">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="est_en_vedette" class="form-check-input" 
                                           id="est_en_vedette" <?php echo ($temoignage['est_en_vedette'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="est_en_vedette">Témoignage en vedette</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="est_approuve" class="form-check-input" 
                                           id="est_approuve" <?php echo ($temoignage['est_approuve'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="est_approuve">Témoignage approuvé</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="temoignages.php" class="btn btn-default">Annuler</a>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>