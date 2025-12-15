<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Gestion des Articles Blog";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

try {
    // Récupérer les catégories pour les formulaires
    $sql_categories = "SELECT * FROM categories_article WHERE est_actif = 1 ORDER BY nom";
    $stmt_categories = $pdo->prepare($sql_categories);
    $stmt_categories->execute();
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    switch($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $titre = trim($_POST['titre']);
                $slug = trim($_POST['slug']);
                $extrait = trim($_POST['extrait']);
                $contenu = trim($_POST['contenu']);
                $auteur_id = $_SESSION['admin_id'];
                $statut = $_POST['statut'];
                $date_publication = $statut === 'publie' ? date('Y-m-d H:i:s') : null;
                $meta_titre = trim($_POST['meta_titre']);
                $meta_description = trim($_POST['meta_description']);
                
                // Calcul du temps de lecture
                $word_count = str_word_count(strip_tags($contenu));
                $reading_time = ceil($word_count / 200); // 200 mots par minute
                
                // Gestion de l'upload d'image
                $featured_image_url = null;
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                    require_once '../Includes/upload.php';
                    $upload = uploader_fichier($_FILES['featured_image'], 'articles');
                    if ($upload['success']) {
                        $featured_image_url = $upload['chemin'];
                    }
                }
                
                $sql = "INSERT INTO articles_blog (titre, slug, extrait, contenu, auteur_id, 
                        featured_image_url, statut, date_publication, temps_lecture, 
                        meta_titre, meta_description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$titre, $slug, $extrait, $contenu, $auteur_id, 
                              $featured_image_url, $statut, $date_publication, $reading_time,
                              $meta_titre, $meta_description]);
                
                $article_id = $pdo->lastInsertId();
                
                // Gestion des catégories
                if (isset($_POST['categories']) && is_array($_POST['categories'])) {
                    foreach ($_POST['categories'] as $categorie_id) {
                        $sql = "INSERT INTO relations_article_categorie (article_id, categorie_id) VALUES (?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$article_id, $categorie_id]);
                    }
                }
                
                $_SESSION['success'] = "Article créé avec succès";
                header('Location: articles_blog.php');
                exit();
            }
            break;
            
        case 'edit':
            if ($id) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $titre = trim($_POST['titre']);
                    $slug = trim($_POST['slug']);
                    $extrait = trim($_POST['extrait']);
                    $contenu = trim($_POST['contenu']);
                    $statut = $_POST['statut'];
                    $meta_titre = trim($_POST['meta_titre']);
                    $meta_description = trim($_POST['meta_description']);
                    
                    // Gérer la date de publication
                    $date_publication = $_POST['date_publication_existante'];
                    if ($statut === 'publie' && !$date_publication) {
                        $date_publication = date('Y-m-d H:i:s');
                    } elseif ($statut !== 'publie') {
                        $date_publication = null;
                    }
                    
                    // Calcul du temps de lecture
                    $word_count = str_word_count(strip_tags($contenu));
                    $reading_time = ceil($word_count / 200);
                    
                    // Gestion de l'upload d'image
                    $featured_image_url = $_POST['image_existante'];
                    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                        require_once '../Includes/upload.php';
                        $upload = uploader_fichier($_FILES['featured_image'], 'articles');
                        if ($upload['success']) {
                            if ($featured_image_url) {
                                supprimer_fichier($featured_image_url);
                            }
                            $featured_image_url = $upload['chemin'];
                        }
                    }
                    
                    $sql = "UPDATE articles_blog 
                            SET titre = ?, slug = ?, extrait = ?, contenu = ?, 
                                featured_image_url = ?, statut = ?, date_publication = ?, 
                                temps_lecture = ?, meta_titre = ?, meta_description = ?,
                                date_modification = NOW()
                            WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$titre, $slug, $extrait, $contenu, $featured_image_url, 
                                  $statut, $date_publication, $reading_time, $meta_titre, 
                                  $meta_description, $id]);
                    
                    // Mise à jour des catégories
                    $sql = "DELETE FROM relations_article_categorie WHERE article_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id]);
                    
                    if (isset($_POST['categories']) && is_array($_POST['categories'])) {
                        foreach ($_POST['categories'] as $categorie_id) {
                            $sql = "INSERT INTO relations_article_categorie (article_id, categorie_id) VALUES (?, ?)";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$id, $categorie_id]);
                        }
                    }
                    
                    $_SESSION['success'] = "Article modifié avec succès";
                    header('Location: articles_blog.php');
                    exit();
                }
                
                // Récupérer l'article pour édition
                $sql = "SELECT a.*, u.prenom as auteur_prenom, u.nom as auteur_nom 
                        FROM articles_blog a 
                        LEFT JOIN utilisateurs u ON a.auteur_id = u.id 
                        WHERE a.id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $article = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$article) {
                    $_SESSION['error'] = "Article non trouvé";
                    header('Location: articles_blog.php');
                    exit();
                }
                
                // Récupérer les catégories de l'article
                $sql = "SELECT categorie_id FROM relations_article_categorie WHERE article_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $categories_article = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
            break;
            
        case 'view':
            if ($id) {
                // Récupérer l'article avec ses catégories et auteur
                $sql = "SELECT a.*, u.prenom as auteur_prenom, u.nom as auteur_nom,
                        GROUP_CONCAT(ca.nom) as categories_noms
                        FROM articles_blog a
                        LEFT JOIN utilisateurs u ON a.auteur_id = u.id
                        LEFT JOIN relations_article_categorie rac ON a.id = rac.article_id
                        LEFT JOIN categories_article ca ON rac.categorie_id = ca.id
                        WHERE a.id = ?
                        GROUP BY a.id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $article = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$article) {
                    $_SESSION['error'] = "Article non trouvé";
                    header('Location: articles_blog.php');
                    exit();
                }
            }
            break;
            
        case 'delete':
            if ($id) {
                // Récupérer l'image pour la supprimer
                $sql = "SELECT featured_image_url FROM articles_blog WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $article = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($article && $article['featured_image_url']) {
                    require_once '../Includes/upload.php';
                    supprimer_fichier($article['featured_image_url']);
                }
                
                // Supprimer les relations de catégories
                $sql = "DELETE FROM relations_article_categorie WHERE article_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                // Supprimer l'article
                $sql = "DELETE FROM articles_blog WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Article supprimé avec succès";
                header('Location: articles_blog.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Récupérer tous les articles avec auteur et catégories
            $sql = "SELECT a.*, u.prenom as auteur_prenom, u.nom as auteur_nom,
                    GROUP_CONCAT(ca.nom) as categories_noms
                    FROM articles_blog a
                    LEFT JOIN utilisateurs u ON a.auteur_id = u.id
                    LEFT JOIN relations_article_categorie rac ON a.id = rac.article_id
                    LEFT JOIN categories_article ca ON rac.categorie_id = ca.id
                    GROUP BY a.id
                    ORDER BY a.date_creation DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Gestion des Articles Blog</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Articles Blog</li>
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
                    <h3 class="card-title">Liste des Articles</h3>
                    <div class="card-tools">
                        <a href="?action=add" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouvel Article
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Catégories</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Vues</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($articles as $article): ?>
                            <tr>
                                <td>
                                    <?php if($article['featured_image_url']): ?>
                                    <img src="../Admin/uploads/<?php echo $article['featured_image_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($article['titre']); ?>" 
                                         style="width: 60px; height: 40px; object-fit: cover;" class="img-thumbnail">
                                    <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 40px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($article['titre']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($article['extrait']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($article['auteur_prenom'] . ' ' . $article['auteur_nom']); ?></td>
                                <td>
                                    <?php if($article['categories_noms']): ?>
                                    <?php 
                                    $cats = explode(',', $article['categories_noms']);
                                    foreach($cats as $cat): ?>
                                    <span class="badge badge-info mr-1"><?php echo htmlspecialchars(trim($cat)); ?></span>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <span class="text-muted">Aucune</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $statut_class = [
                                        'publie' => 'success',
                                        'brouillon' => 'warning',
                                        'archive' => 'secondary'
                                    ];
                                    $statut_text = [
                                        'publie' => 'Publié',
                                        'brouillon' => 'Brouillon',
                                        'archive' => 'Archivé'
                                    ];
                                    ?>
                                    <span class="badge badge-<?php echo $statut_class[$article['statut']]; ?>">
                                        <?php echo $statut_text[$article['statut']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo $article['date_publication'] ? date('d/m/Y', strtotime($article['date_publication'])) : 'Non publié'; ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo $article['temps_lecture']; ?> min</small>
                                </td>
                                <td><?php echo $article['nombre_vues']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="?action=view&id=<?php echo $article['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?action=edit&id=<?php echo $article['id']; ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?php echo $article['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet article ?')">
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
                    <h3 class="card-title">Détails de l'Article</h3>
                    <div class="card-tools">
                        <a href="articles_blog.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h2><?php echo htmlspecialchars($article['titre']); ?></h2>
                            
                            <div class="mb-4">
                                <span class="badge badge-<?php echo $article['statut'] === 'publie' ? 'success' : ($article['statut'] === 'brouillon' ? 'warning' : 'secondary'); ?> mr-2">
                                    <?php echo $article['statut'] === 'publie' ? 'Publié' : ($article['statut'] === 'brouillon' ? 'Brouillon' : 'Archivé'); ?>
                                </span>
                                <span class="text-muted">
                                    Par <?php echo htmlspecialchars($article['auteur_prenom'] . ' ' . $article['auteur_nom']); ?> 
                                    • <?php echo $article['temps_lecture']; ?> min de lecture
                                    • <?php echo $article['nombre_vues']; ?> vues
                                </span>
                            </div>
                            
                            <?php if($article['categories_noms']): ?>
                            <div class="mb-3">
                                <?php 
                                $cats = explode(',', $article['categories_noms']);
                                foreach($cats as $cat): ?>
                                <span class="badge badge-primary mr-1"><?php echo htmlspecialchars(trim($cat)); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-4">
                                <h4>Extrait</h4>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($article['extrait'])); ?></p>
                            </div>
                            
                            <div class="mb-4">
                                <h4>Contenu</h4>
                                <div class="border rounded p-3 bg-light">
                                    <?php echo nl2br(htmlspecialchars($article['contenu'])); ?>
                                </div>
                            </div>
                            
                            <?php if($article['meta_titre'] || $article['meta_description']): ?>
                            <div class="mb-4">
                                <h4>SEO</h4>
                                <p><strong>Meta titre :</strong> <?php echo htmlspecialchars($article['meta_titre']); ?></p>
                                <p><strong>Meta description :</strong> <?php echo htmlspecialchars($article['meta_description']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4">
                            <?php if($article['featured_image_url']): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h5>Image principale</h5>
                                </div>
                                <div class="card-body text-center">
                                    <img src="../Admin/uploads/<?php echo $article['featured_image_url']; ?>" 
                                         alt="<?php echo htmlspecialchars($article['titre']); ?>" 
                                         class="img-fluid rounded">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card mt-3">
                                <div class="card-header">
                                    <h5>Informations</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>Date de création :</strong><br>
                                    <?php echo date('d/m/Y à H:i', strtotime($article['date_creation'])); ?></p>
                                    
                                    <?php if($article['date_publication']): ?>
                                    <p><strong>Date de publication :</strong><br>
                                    <?php echo date('d/m/Y à H:i', strtotime($article['date_publication'])); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if($article['date_modification']): ?>
                                    <p><strong>Dernière modification :</strong><br>
                                    <?php echo date('d/m/Y à H:i', strtotime($article['date_modification'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="?action=edit&id=<?php echo $article['id']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="articles_blog.php" class="btn btn-default">Retour à la liste</a>
                </div>
            </div>
            
            <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php echo $action === 'add' ? 'Ajouter un Article' : 'Modifier l\'Article'; ?>
                    </h3>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Titre *</label>
                                    <input type="text" name="titre" class="form-control" 
                                           value="<?php echo htmlspecialchars($article['titre'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Slug *</label>
                                    <input type="text" name="slug" class="form-control" 
                                           value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Extrait *</label>
                            <textarea name="extrait" class="form-control" rows="3" required><?php echo htmlspecialchars($article['extrait'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Contenu *</label>
                            <textarea name="contenu" class="form-control" rows="12" required><?php echo htmlspecialchars($article['contenu'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Statut *</label>
                                    <select name="statut" class="form-control" required>
                                        <option value="brouillon" <?php echo ($article['statut'] ?? 'brouillon') === 'brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                                        <option value="publie" <?php echo ($article['statut'] ?? '') === 'publie' ? 'selected' : ''; ?>>Publié</option>
                                        <option value="archive" <?php echo ($article['statut'] ?? '') === 'archive' ? 'selected' : ''; ?>>Archivé</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Catégories</label>
                                    <select name="categories[]" class="form-control select2" multiple>
                                        <?php foreach($categories as $categorie): ?>
                                        <option value="<?php echo $categorie['id']; ?>"
                                                <?php echo in_array($categorie['id'], $categories_article ?? []) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categorie['nom']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                        <a href="articles_blog.php" class="btn btn-default">Annuler</a>
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
    $('.select2').select2({
        placeholder: "Sélectionnez les catégories",
        allowClear: true
    });
});
</script>