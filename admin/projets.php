<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Gestion des Projets";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

try {
    // Récupérer les catégories pour les formulaires
    $sql_categories = "SELECT * FROM categories_projet WHERE est_actif = 1 ORDER BY ordre_affichage";
    $stmt_categories = $pdo->prepare($sql_categories);
    $stmt_categories->execute();
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    switch($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $titre = trim($_POST['titre']);
                $slug = trim($_POST['slug']);
                $description_courte = trim($_POST['description_courte']);
                $description_complete = trim($_POST['description_complete']);
                $nom_client = trim($_POST['nom_client']);
                $date_projet = $_POST['date_projet'];
                $url_projet = trim($_POST['url_projet']);
                $url_github = trim($_POST['url_github']);
                $technologies = json_encode(explode(',', $_POST['technologies']));
                $defis = json_encode(explode(',', $_POST['defis']));
                $solutions = json_encode(explode(',', $_POST['solutions']));
                $resultats = json_encode(explode(',', $_POST['resultats']));
                $ordre_affichage = $_POST['ordre_affichage'];
                $est_en_vedette = isset($_POST['est_en_vedette']) ? 1 : 0;
                $est_actif = isset($_POST['est_actif']) ? 1 : 0;
                $note = $_POST['note'];
                
                // Gestion de l'upload d'image
                $url_image = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    require_once '../Includes/upload.php';
                    $upload = uploader_fichier($_FILES['image'], 'projets');
                    if ($upload['success']) {
                        $url_image = $upload['chemin'];
                    }
                }
                
                // Gestion de l'upload du logo client
                $url_logo_client = null;
                if (isset($_FILES['logo_client']) && $_FILES['logo_client']['error'] === UPLOAD_ERR_OK) {
                    require_once '../Includes/upload.php';
                    $upload = uploader_fichier($_FILES['logo_client'], 'projets');
                    if ($upload['success']) {
                        $url_logo_client = $upload['chemin'];
                    }
                }
                
                $sql = "INSERT INTO projets (titre, slug, description_courte, description_complete, 
                        nom_client, url_logo_client, date_projet, url_projet, url_github, 
                        technologies, defis, solutions, resultats, url_image, 
                        ordre_affichage, est_en_vedette, est_actif, note) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titre, $slug, $description_courte, $description_complete, $nom_client, 
                              $url_logo_client, $date_projet, $url_projet, $url_github, $technologies, 
                              $defis, $solutions, $resultats, $url_image, $ordre_affichage, 
                              $est_en_vedette, $est_actif, $note]);
                
                $projet_id = $pdo->lastInsertId();
                
                // Gestion des catégories
                if (isset($_POST['categories']) && is_array($_POST['categories'])) {
                    foreach ($_POST['categories'] as $categorie_id) {
                        $sql = "INSERT INTO relations_projet_categorie (projet_id, categorie_id) VALUES (?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$projet_id, $categorie_id]);
                    }
                }
                
                $_SESSION['success'] = "Projet créé avec succès";
                header('Location: projets.php');
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
                    $nom_client = trim($_POST['nom_client']);
                    $date_projet = $_POST['date_projet'];
                    $url_projet = trim($_POST['url_projet']);
                    $url_github = trim($_POST['url_github']);
                    $technologies = json_encode(explode(',', $_POST['technologies']));
                    $defis = json_encode(explode(',', $_POST['defis']));
                    $solutions = json_encode(explode(',', $_POST['solutions']));
                    $resultats = json_encode(explode(',', $_POST['resultats']));
                    $ordre_affichage = $_POST['ordre_affichage'];
                    $est_en_vedette = isset($_POST['est_en_vedette']) ? 1 : 0;
                    $est_actif = isset($_POST['est_actif']) ? 1 : 0;
                    $note = $_POST['note'];
                    
                    // Gestion de l'upload d'image
                    $url_image = $_POST['image_existante'];
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        require_once '../Includes/upload.php';
                        $upload = uploader_fichier($_FILES['image'], 'projets');
                        if ($upload['success']) {
                            if ($url_image) {
                                supprimer_fichier($url_image);
                            }
                            $url_image = $upload['chemin'];
                        }
                    }
                    
                    // Gestion de l'upload du logo client
                    $url_logo_client = $_POST['logo_client_existant'];
                    if (isset($_FILES['logo_client']) && $_FILES['logo_client']['error'] === UPLOAD_ERR_OK) {
                        require_once '../Includes/upload.php';
                        $upload = uploader_fichier($_FILES['logo_client'], 'projets');
                        if ($upload['success']) {
                            if ($url_logo_client) {
                                supprimer_fichier($url_logo_client);
                            }
                            $url_logo_client = $upload['chemin'];
                        }
                    }
                    
                    $sql = "UPDATE projets 
                            SET titre = ?, slug = ?, description_courte = ?, description_complete = ?,
                                nom_client = ?, url_logo_client = ?, date_projet = ?, url_projet = ?, 
                                url_github = ?, technologies = ?, defis = ?, solutions = ?, resultats = ?, 
                                url_image = ?, ordre_affichage = ?, est_en_vedette = ?, est_actif = ?, note = ?
                            WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$titre, $slug, $description_courte, $description_complete, $nom_client, 
                                  $url_logo_client, $date_projet, $url_projet, $url_github, $technologies, 
                                  $defis, $solutions, $resultats, $url_image, $ordre_affichage, 
                                  $est_en_vedette, $est_actif, $note, $id]);
                    
                    // Mise à jour des catégories
                    // Supprimer les anciennes relations
                    $sql = "DELETE FROM relations_projet_categorie WHERE projet_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id]);
                    
                    // Ajouter les nouvelles relations
                    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
                        foreach ($_POST['categories'] as $categorie_id) {
                            $sql = "INSERT INTO relations_projet_categorie (projet_id, categorie_id) VALUES (?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$id, $categorie_id]);
                        }
                    }
                    
                    $_SESSION['success'] = "Projet modifié avec succès";
                    header('Location: projets.php');
                    exit();
                }
                
                // Récupérer le projet pour édition
                $sql = "SELECT * FROM projets WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $projet = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$projet) {
                    $_SESSION['error'] = "Projet non trouvé";
                    header('Location: projets.php');
                    exit();
                }
                
                // Récupérer les catégories du projet
                $sql = "SELECT categorie_id FROM relations_projet_categorie WHERE projet_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $categories_projet = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            break;
            
        case 'view':
            if ($id) {
                // Récupérer le projet avec ses catégories
                $sql = "SELECT p.*, 
                        GROUP_CONCAT(cp.nom) as categories_noms
                        FROM projets p
                        LEFT JOIN relations_projet_categorie rpc ON p.id = rpc.projet_id
                        LEFT JOIN categories_projet cp ON rpc.categorie_id = cp.id
                        WHERE p.id = ?
                        GROUP BY p.id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $projet = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$projet) {
                    $_SESSION['error'] = "Projet non trouvé";
                    header('Location: projets.php');
                    exit();
                }
            }
            break;
            
        case 'delete':
            if ($id) {
                // Récupérer les images pour les supprimer
                $sql = "SELECT url_image, url_logo_client FROM projets WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $projet = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($projet) {
                    require_once '../Includes/upload.php';
                    if ($projet['url_image']) {
                        supprimer_fichier($projet['url_image']);
                    }
                    if ($projet['url_logo_client']) {
                        supprimer_fichier($projet['url_logo_client']);
                    }
                }
                
                // Supprimer les relations de catégories
                $sql = "DELETE FROM relations_projet_categorie WHERE projet_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                // Supprimer le projet
                $sql = "DELETE FROM projets WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Projet supprimé avec succès";
                header('Location: projets.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Récupérer tous les projets avec leurs catégories
            $sql = "SELECT p.*, 
                    GROUP_CONCAT(cp.nom) as categories_noms
                    FROM projets p
                    LEFT JOIN relations_projet_categorie rpc ON p.id = rpc.projet_id
                    LEFT JOIN categories_projet cp ON rpc.categorie_id = cp.id
                    GROUP BY p.id
                    ORDER BY p.ordre_affichage, p.date_projet DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Gestion des Projets</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Projets</li>
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
                    <h3 class="card-title">Liste des Projets</h3>
                    <div class="card-tools">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Projet
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Titre</th>
                                <th>Client</th>
                                <th>Catégories</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Note</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($projets as $projet): ?>
                            <tr>
                                <td>
                                    <?php if($projet['url_image']): ?>
                                    <img src="../Admin/uploads/<?php echo $projet['url_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($projet['titre']); ?>" 
                                         style="width: 60px; height: 40px; object-fit: cover;" class="img-thumbnail">
                                    <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 40px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($projet['titre']); ?></strong>
                                    <?php if($projet['est_en_vedette']): ?>
                                    <i class="fas fa-star text-warning ml-1" title="Projet en vedette"></i>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($projet['description_courte']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($projet['nom_client'] ?? '-'); ?></td>
                                <td>
                                    <?php if($projet['categories_noms']): ?>
                                    <?php 
                                    $cats = explode(',', $projet['categories_noms']);
                                    foreach($cats as $cat): ?>
                                    <span class="badge badge-info mr-1"><?php echo htmlspecialchars(trim($cat)); ?></span>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <span class="text-muted">Aucune</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $projet['date_projet'] ? date('d/m/Y', strtotime($projet['date_projet'])) : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $projet['est_actif'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $projet['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-warning">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i > $projet['note'] ? '-half-alt' : ''; ?>"></i>
                                        <?php endfor; ?>
                                        <small class="text-muted ml-1">(<?php echo $projet['note']; ?>)</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?action=view&id=<?php echo $projet['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $projet['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $projet['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')"
                                           title="Supprimer">
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
            
            <?php elseif($action === 'view'): ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails du Projet</h3>
                    <div class="card-tools">
                        <a href="projets.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if($projet['url_image']): ?>
                            <img src="../Admin/uploads/<?php echo $projet['url_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($projet['titre']); ?>" 
                                 class="img-fluid rounded mb-3">
                            <?php endif; ?>
                            
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">Client</span>
                                    <span class="info-box-number"><?php echo htmlspecialchars($projet['nom_client'] ?? 'Non spécifié'); ?></span>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <div class="info-box-content">
                                    <span class="info-box-text">Date du projet</span>
                                    <span class="info-box-number"><?php echo $projet['date_projet'] ? date('d/m/Y', strtotime($projet['date_projet'])) : 'Non spécifiée'; ?></span>
                                </div>
                            </div>
                            
                            <?php if($projet['url_projet']): ?>
                            <a href="<?php echo htmlspecialchars($projet['url_projet']); ?>" 
                               target="_blank" class="btn btn-primary btn-block mb-2">
                                <i class="fas fa-external-link-alt"></i> Voir le projet
                            </a>
                            <?php endif; ?>
                            
                            <?php if($projet['url_github']): ?>
                            <a href="<?php echo htmlspecialchars($projet['url_github']); ?>" 
                               target="_blank" class="btn btn-dark btn-block">
                                <i class="fab fa-github"></i> Code source
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($projet['titre']); ?></h3>
                            
                            <div class="mb-3">
                                <?php if($projet['est_en_vedette']): ?>
                                <span class="badge badge-warning mr-2">Projet en vedette</span>
                                <?php endif; ?>
                                <span class="badge badge-<?php echo $projet['est_actif'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $projet['est_actif'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Description courte</h5>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($projet['description_courte'])); ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <h5>Description complète</h5>
                                <p><?php echo nl2br(htmlspecialchars($projet['description_complete'])); ?></p>
                            </div>
                            
                            <?php 
                            $technologies = $projet['technologies'] ? json_decode($projet['technologies'], true) : [];
                            $defis = $projet['defis'] ? json_decode($projet['defis'], true) : [];
                            $solutions = $projet['solutions'] ? json_decode($projet['solutions'], true) : [];
                            $resultats = $projet['resultats'] ? json_decode($projet['resultats'], true) : [];
                            ?>
                            
                            <?php if(!empty($technologies)): ?>
                            <div class="mb-3">
                                <h5>Technologies utilisées</h5>
                                <?php foreach($technologies as $tech): ?>
                                <span class="badge badge-light border mr-1"><?php echo htmlspecialchars($tech); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($defis)): ?>
                            <div class="mb-3">
                                <h5>Défis rencontrés</h5>
                                <ul>
                                    <?php foreach($defis as $defi): ?>
                                    <li><?php echo htmlspecialchars($defi); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($solutions)): ?>
                            <div class="mb-3">
                                <h5>Solutions apportées</h5>
                                <ul>
                                    <?php foreach($solutions as $solution): ?>
                                    <li><?php echo htmlspecialchars($solution); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php if(!empty($resultats)): ?>
                            <div class="mb-3">
                                <h5>Résultats obtenus</h5>
                                <ul>
                                    <?php foreach($resultats as $resultat): ?>
                                    <li><?php echo htmlspecialchars($resultat); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?action=edit&id=<?php echo $projet['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="projets.php" class="btn btn-default">Retour à la liste</a>
                </div>
            </div>
            
            <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $action === 'add' ? 'Ajouter un Projet' : 'Modifier le Projet'; ?>
                    </h3>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Titre *</label>
                                    <input type="text" name="titre" class="form-control" 
                                           value="<?php echo htmlspecialchars($projet['titre'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Slug *</label>
                                    <input type="text" name="slug" class="form-control" 
                                           value="<?php echo htmlspecialchars($projet['slug'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description courte *</label>
                            <textarea name="description_courte" class="form-control" rows="3" required><?php echo htmlspecialchars($projet['description_courte'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Description complète</label>
                            <textarea name="description_complete" class="form-control" rows="6"><?php echo htmlspecialchars($projet['description_complete'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nom du client</label>
                                    <input type="text" name="nom_client" class="form-control" 
                                           value="<?php echo htmlspecialchars($projet['nom_client'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date du projet</label>
                                    <input type="date" name="date_projet" class="form-control" 
                                           value="<?php echo $projet['date_projet'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>URL du projet</label>
                                    <input type="url" name="url_projet" class="form-control" 
                                           value="<?php echo htmlspecialchars($projet['url_projet'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>URL GitHub</label>
                                    <input type="url" name="url_github" class="form-control" 
                                           value="<?php echo htmlspecialchars($projet['url_github'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Catégories</label>
                                    <select name="categories[]" class="form-control select2" multiple>
                                        <?php foreach($categories as $categorie): ?>
                                        <option value="<?php echo $categorie['id']; ?>"
                                                <?php echo in_array($categorie['id'], $categories_projet ?? []) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categorie['nom']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Ordre d'affichage</label>
                                    <input type="number" name="ordre_affichage" class="form-control" 
                                           value="<?php echo $projet['ordre_affichage'] ?? 0; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Note (sur 5)</label>
                                    <select name="note" class="form-control">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php echo ($projet['note'] ?? 5) == $i ? 'selected' : ''; ?>>
                                            <?php echo $i; ?> étoile<?php echo $i > 1 ? 's' : ''; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Image du projet</label>
                                    <?php if(isset($projet['url_image']) && $projet['url_image']): ?>
                                    <div class="mb-2">
                                        <img src="../Admin/uploads/<?php echo $projet['url_image']; ?>" 
                                             alt="Image projet" style="max-height: 100px;" class="img-thumbnail">
                                        <input type="hidden" name="image_existante" value="<?php echo $projet['url_image']; ?>">
                                        <br>
                                        <small>Image actuelle</small>
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" name="image" class="form-control-file" accept="image/*">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Logo du client</label>
                                    <?php if(isset($projet['url_logo_client']) && $projet['url_logo_client']): ?>
                                    <div class="mb-2">
                                        <img src="../Admin/uploads/<?php echo $projet['url_logo_client']; ?>" 
                                             alt="Logo client" style="max-height: 100px;" class="img-thumbnail">
                                        <input type="hidden" name="logo_client_existant" value="<?php echo $projet['url_logo_client']; ?>">
                                        <br>
                                        <small>Logo actuel</small>
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" name="logo_client" class="form-control-file" accept="image/*">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Technologies (séparées par des virgules)</label>
                                    <input type="text" name="technologies" class="form-control" 
                                           value="<?php 
                                           if (isset($projet['technologies'])) {
                                               $techs = json_decode($projet['technologies'], true);
                                               echo htmlspecialchars(implode(', ', $techs));
                                           }
                                           ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Défis (séparés par des virgules)</label>
                                    <input type="text" name="defis" class="form-control" 
                                           value="<?php 
                                           if (isset($projet['defis'])) {
                                               $defis = json_decode($projet['defis'], true);
                                               echo htmlspecialchars(implode(', ', $defis));
                                           }
                                           ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Solutions (séparées par des virgules)</label>
                                    <input type="text" name="solutions" class="form-control" 
                                           value="<?php 
                                           if (isset($projet['solutions'])) {
                                               $sols = json_decode($projet['solutions'], true);
                                               echo htmlspecialchars(implode(', ', $sols));
                                           }
                                           ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Résultats (séparés par des virgules)</label>
                                    <input type="text" name="resultats" class="form-control" 
                                           value="<?php 
                                           if (isset($projet['resultats'])) {
                                               $res = json_decode($projet['resultats'], true);
                                               echo htmlspecialchars(implode(', ', $res));
                                           }
                                           ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="est_en_vedette" class="form-check-input" 
                                           id="est_en_vedette" <?php echo ($projet['est_en_vedette'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="est_en_vedette">Projet en vedette</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" name="est_actif" class="form-check-input" 
                                           id="est_actif" <?php echo ($projet['est_actif'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="est_actif">Projet actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="projets.php" class="btn btn-default">Annuler</a>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Initialiser Select2 pour les catégories multiples
    $('.select2').select2({
        placeholder: "Sélectionnez les catégories",
        allowClear: true
    });
});
</script>