<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Paramètres du Site";

// Vérifier les permissions
if (!a_la_permission('administrateur')) {
    $_SESSION['error'] = "Accès non autorisé";
    header('Location: tableau_bord.php');
    exit();
}

// Catégories de paramètres
$categories = [
    'general' => 'Général',
    'contact' => 'Coordonnées',
    'social' => 'Réseaux Sociaux',
    'seo' => 'SEO',
    'maintenance' => 'Maintenance'
];

// Paramètres par défaut
$parametres_par_defaut = [
    // Général
    'nom_entreprise' => ['value' => 'PLMB Technologie', 'type' => 'text', 'categorie' => 'general'],
    'slogan_entreprise' => ['value' => 'Votre partenaire de confiance pour toutes vos solutions numériques et digitales', 'type' => 'text', 'categorie' => 'general'],
    'description_entreprise' => ['value' => 'PLMB Technologie est une entreprise spécialisée dans le développement de solutions digitales innovantes', 'type' => 'textarea', 'categorie' => 'general'],
    'logo_entreprise' => ['value' => '', 'type' => 'image', 'categorie' => 'general'],
    'favicon_entreprise' => ['value' => '', 'type' => 'image', 'categorie' => 'general'],
    
    // Contact
    'email_entreprise' => ['value' => 'contact@plmb-technologie.fr', 'type' => 'email', 'categorie' => 'contact'],
    'telephone_entreprise' => ['value' => '+33 1 23 45 67 89', 'type' => 'text', 'categorie' => 'contact'],
    'adresse_entreprise' => ['value' => '123 Avenue de la Technologie, 75000 Paris', 'type' => 'textarea', 'categorie' => 'contact'],
    'horaires_entreprise' => ['value' => 'Lundi - Vendredi: 9h00 - 18h00', 'type' => 'textarea', 'categorie' => 'contact'],
    
    // Réseaux sociaux
    'facebook_url' => ['value' => '', 'type' => 'url', 'categorie' => 'social'],
    'twitter_url' => ['value' => '', 'type' => 'url', 'categorie' => 'social'],
    'linkedin_url' => ['value' => '', 'type' => 'url', 'categorie' => 'social'],
    'instagram_url' => ['value' => '', 'type' => 'url', 'categorie' => 'social'],
    'youtube_url' => ['value' => '', 'type' => 'url', 'categorie' => 'social'],
    'github_url' => ['value' => '', 'type' => 'url', 'categorie' => 'social'],
    
    // SEO
    'meta_titre_default' => ['value' => 'PLMB Technologie - Solutions Numériques Innovantes', 'type' => 'text', 'categorie' => 'seo'],
    'meta_description_default' => ['value' => 'Découvrez nos services de développement web, applications mobiles, solutions cloud et cybersécurité', 'type' => 'textarea', 'categorie' => 'seo'],
    'meta_keywords' => ['value' => 'développement web, applications mobiles, solutions cloud, cybersécurité, transformation digitale', 'type' => 'text', 'categorie' => 'seo'],
    'google_analytics_id' => ['value' => '', 'type' => 'text', 'categorie' => 'seo'],
    'google_site_verification' => ['value' => '', 'type' => 'text', 'categorie' => 'seo'],
    
    // Maintenance
    'mode_maintenance' => ['value' => '0', 'type' => 'boolean', 'categorie' => 'maintenance'],
    'message_maintenance' => ['value' => 'Site en maintenance - Nous serons de retour rapidement', 'type' => 'textarea', 'categorie' => 'maintenance'],
    'acces_maintenance' => ['value' => '', 'type' => 'text', 'categorie' => 'maintenance']
];

// Initialiser la variable $parametres
$parametres = [];

try {
    // Récupérer les paramètres existants
    $sql = "SELECT cle_parametre, valeur_parametre, type_parametre FROM parametres_site";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $parametres_existants = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Fusionner avec les valeurs par défaut
    foreach ($parametres_par_defaut as $cle => $config) {
        $parametres[$cle] = [
            'value' => $parametres_existants[$cle] ?? $config['value'],
            'type' => $config['type'],
            'categorie' => $config['categorie']
        ];
    }
    
    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $updates = [];
        $uploads = [];
        
        // Traiter chaque paramètre
        foreach ($parametres as $cle => $config) {
            $valeur = '';
            
            switch ($config['type']) {
                case 'boolean':
                    $valeur = isset($_POST[$cle]) ? '1' : '0';
                    break;
                    
                case 'image':
                    // Gestion de l'upload d'image
                    if (isset($_FILES[$cle]) && $_FILES[$cle]['error'] === UPLOAD_ERR_OK) {
                        require_once '../Includes/upload.php';
                        
                        $dossier = $cle === 'favicon_entreprise' ? 'favicon' : 'generale';
                        $upload = uploader_fichier($_FILES[$cle], $dossier);
                        
                        if ($upload['success']) {
                            // Supprimer l'ancienne image si elle existe
                            if (!empty($parametres[$cle]['value'])) {
                                supprimer_fichier($parametres[$cle]['value']);
                            }
                            $valeur = $upload['chemin'];
                            $uploads[$cle] = $valeur;
                        }
                    } else {
                        $valeur = $parametres[$cle]['value'];
                    }
                    break;
                    
                case 'textarea':
                    $valeur = trim($_POST[$cle] ?? '');
                    break;
                    
                default:
                    $valeur = trim($_POST[$cle] ?? '');
                    break;
            }
            
            $updates[$cle] = $valeur;
        }
        
        // Mettre à jour la base de données
        foreach ($updates as $cle => $valeur) {
            // Vérifier si le paramètre existe déjà
            $sql_check = "SELECT COUNT(*) FROM parametres_site WHERE cle_parametre = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$cle]);
            $exists = $stmt_check->fetchColumn();
            
            if ($exists) {
                // Mettre à jour
                $sql = "UPDATE parametres_site SET valeur_parametre = ?, type_parametre = ? WHERE cle_parametre = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$valeur, $parametres[$cle]['type'], $cle]);
            } else {
                // Insérer
                $sql = "INSERT INTO parametres_site (cle_parametre, valeur_parametre, type_parametre) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$cle, $valeur, $parametres[$cle]['type']]);
            }
        }
        
        $_SESSION['success'] = "Paramètres mis à jour avec succès";
        header('Location: parametres.php');
        exit();
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur base de données : " . $e->getMessage();
}

include '../Includes/header.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Paramètres du Site</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Paramètres</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <div class="row">
                <div class="col-md-3">
                    <!-- Navigation des catégories -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Catégories</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav nav-pills flex-column">
                                <?php foreach($categories as $key => $nom): ?>
                                <li class="nav-item">
                                    <a href="#<?php echo $key; ?>" class="nav-link category-link" data-toggle="tab">
                                        <i class="fas fa-<?php echo get_icon_for_category($key); ?> mr-2"></i>
                                        <?php echo $nom; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <form method="post" enctype="multipart/form-data">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Configuration du Site</h3>
                                <div class="card-tools">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    
                                    <!-- Catégorie Général -->
                                    <div class="tab-pane active" id="general">
                                        <h4><i class="fas fa-cog mr-2"></i>Paramètres Généraux</h4>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Nom de l'entreprise *</label>
                                                    <input type="text" name="nom_entreprise" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['nom_entreprise']['value'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Slogan</label>
                                                    <input type="text" name="slogan_entreprise" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['slogan_entreprise']['value'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Description de l'entreprise</label>
                                            <textarea name="description_entreprise" class="form-control" rows="3"><?php echo htmlspecialchars($parametres['description_entreprise']['value'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Description utilisée pour le référencement et les présentations</small>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Logo de l'entreprise</label>
                                                    <?php if(!empty($parametres['logo_entreprise']['value'])): ?>
                                                    <div class="mb-2">
                                                        <img src="../Admin/uploads/<?php echo $parametres['logo_entreprise']['value']; ?>" 
                                                             alt="Logo entreprise" style="max-height: 100px;" class="img-thumbnail">
                                                        <br>
                                                        <small>Logo actuel</small>
                                                    </div>
                                                    <?php endif; ?>
                                                    <input type="file" name="logo_entreprise" class="form-control-file" accept="image/*">
                                                    <small class="form-text text-muted">Format recommandé : PNG transparent, min 200x200px</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Favicon</label>
                                                    <?php if(!empty($parametres['favicon_entreprise']['value'])): ?>
                                                    <div class="mb-2">
                                                        <img src="../Admin/uploads/<?php echo $parametres['favicon_entreprise']['value']; ?>" 
                                                             alt="Favicon" style="max-height: 32px;" class="img-thumbnail">
                                                        <br>
                                                        <small>Favicon actuel</small>
                                                    </div>
                                                    <?php endif; ?>
                                                    <input type="file" name="favicon_entreprise" class="form-control-file" accept="image/*">
                                                    <small class="form-text text-muted">Format : ICO ou PNG 32x32px</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Catégorie Contact -->
                                    <div class="tab-pane" id="contact">
                                        <h4><i class="fas fa-address-book mr-2"></i>Coordonnées</h4>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email de contact *</label>
                                                    <input type="email" name="email_entreprise" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['email_entreprise']['value'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Téléphone</label>
                                                    <input type="text" name="telephone_entreprise" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['telephone_entreprise']['value'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Adresse</label>
                                            <textarea name="adresse_entreprise" class="form-control" rows="2"><?php echo htmlspecialchars($parametres['adresse_entreprise']['value'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Horaires d'ouverture</label>
                                            <textarea name="horaires_entreprise" class="form-control" rows="3"><?php echo htmlspecialchars($parametres['horaires_entreprise']['value'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Ex: Lundi - Vendredi: 9h00 - 18h00</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Catégorie Réseaux Sociaux -->
                                    <div class="tab-pane" id="social">
                                        <h4><i class="fas fa-share-alt mr-2"></i>Réseaux Sociaux</h4>
                                        <p class="text-muted">Renseignez les URLs de vos réseaux sociaux. Laissez vide pour masquer.</p>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fab fa-facebook text-primary mr-2"></i>Facebook</label>
                                                    <input type="url" name="facebook_url" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['facebook_url']['value'] ?? ''); ?>" 
                                                           placeholder="https://facebook.com/votre-page">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fab fa-twitter text-info mr-2"></i>Twitter</label>
                                                    <input type="url" name="twitter_url" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['twitter_url']['value'] ?? ''); ?>" 
                                                           placeholder="https://twitter.com/votre-compte">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fab fa-linkedin text-primary mr-2"></i>LinkedIn</label>
                                                    <input type="url" name="linkedin_url" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['linkedin_url']['value'] ?? ''); ?>" 
                                                           placeholder="https://linkedin.com/company/votre-entreprise">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fab fa-instagram text-danger mr-2"></i>Instagram</label>
                                                    <input type="url" name="instagram_url" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['instagram_url']['value'] ?? ''); ?>" 
                                                           placeholder="https://instagram.com/votre-compte">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fab fa-youtube text-danger mr-2"></i>YouTube</label>
                                                    <input type="url" name="youtube_url" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['youtube_url']['value'] ?? ''); ?>" 
                                                           placeholder="https://youtube.com/c/votre-chaine">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label><i class="fab fa-github mr-2"></i>GitHub</label>
                                                    <input type="url" name="github_url" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['github_url']['value'] ?? ''); ?>" 
                                                           placeholder="https://github.com/votre-organisation">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Catégorie SEO -->
                                    <div class="tab-pane" id="seo">
                                        <h4><i class="fas fa-search mr-2"></i>Paramètres SEO</h4>
                                        
                                        <div class="form-group">
                                            <label>Meta titre par défaut</label>
                                            <input type="text" name="meta_titre_default" class="form-control" 
                                                   value="<?php echo htmlspecialchars($parametres['meta_titre_default']['value'] ?? ''); ?>">
                                            <small class="form-text text-muted">Titre par défaut pour les pages sans meta titre spécifique</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Meta description par défaut</label>
                                            <textarea name="meta_description_default" class="form-control" rows="3"><?php echo htmlspecialchars($parametres['meta_description_default']['value'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Description par défaut pour les pages sans meta description spécifique</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Mots-clés</label>
                                            <input type="text" name="meta_keywords" class="form-control" 
                                                   value="<?php echo htmlspecialchars($parametres['meta_keywords']['value'] ?? ''); ?>">
                                            <small class="form-text text-muted">Mots-clés séparés par des virgules</small>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Google Analytics ID</label>
                                                    <input type="text" name="google_analytics_id" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['google_analytics_id']['value'] ?? ''); ?>" 
                                                           placeholder="UA-XXXXXXXXX-X ou G-XXXXXXXXXX">
                                                    <small class="form-text text-muted">Identifiant de suivi Google Analytics</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Google Site Verification</label>
                                                    <input type="text" name="google_site_verification" class="form-control" 
                                                           value="<?php echo htmlspecialchars($parametres['google_site_verification']['value'] ?? ''); ?>" 
                                                           placeholder="Code de vérification Google Search Console">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Catégorie Maintenance -->
                                    <div class="tab-pane" id="maintenance">
                                        <h4><i class="fas fa-tools mr-2"></i>Maintenance</h4>
                                        
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            <strong>Attention :</strong> Le mode maintenance empêche l'accès au site public pour tous les visiteurs excepté les administrateurs.
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" name="mode_maintenance" class="custom-control-input" 
                                                       id="mode_maintenance" <?php echo ($parametres['mode_maintenance']['value'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                                <label class="custom-control-label" for="mode_maintenance">
                                                    <strong>Activer le mode maintenance</strong>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Message de maintenance</label>
                                            <textarea name="message_maintenance" class="form-control" rows="4"><?php echo htmlspecialchars($parametres['message_maintenance']['value'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">Message affiché aux visiteurs lorsque le site est en maintenance</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Accès administrateur</label>
                                            <input type="text" name="acces_maintenance" class="form-control" 
                                                   value="<?php echo htmlspecialchars($parametres['acces_maintenance']['value'] ?? ''); ?>" 
                                                   placeholder="Mot de passe temporaire pour accéder au site">
                                            <small class="form-text text-muted">Optionnel : mot de passe pour permettre l'accès à certains utilisateurs</small>
                                        </div>
                                    </div>
                                    
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Enregistrer tous les paramètres
                                </button>
                                <button type="reset" class="btn btn-default">Réinitialiser les modifications</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Navigation par onglets
    $('.category-link').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Masquer tous les onglets
        $('.tab-pane').removeClass('active');
        
        // Afficher l'onglet cible
        $(target).addClass('active');
        
        // Mettre à jour la navigation
        $('.nav-link').removeClass('active');
        $(this).addClass('active');
    });
    
    // Prévisualisation des images
    $('input[type="file"]').change(function() {
        var input = $(this);
        var preview = input.siblings('img');
        
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                preview.attr('src', e.target.result);
            }
            
            reader.readAsDataURL(this.files[0]);
        }
    });
    
    // Confirmation pour le mode maintenance
    $('#mode_maintenance').change(function() {
        if ($(this).is(':checked')) {
            if (!confirm('Êtes-vous sûr de vouloir activer le mode maintenance ? Le site sera inaccessible aux visiteurs.')) {
                $(this).prop('checked', false);
            }
        }
    });
});
</script>

<?php
// Fonction helper pour les icônes des catégories
function get_icon_for_category($categorie) {
    $icons = [
        'general' => 'cog',
        'contact' => 'address-book',
        'social' => 'share-alt',
        'seo' => 'search',
        'maintenance' => 'tools'
    ];
    return $icons[$categorie] ?? 'cog';
}
?>