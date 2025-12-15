<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

// Titre de la page
$page_title = "Tableau de Bord";

// Récupérer les statistiques
try {
    // Statistiques générales
    $stats = [];
    
    // Requêtes optimisées pour les statistiques
    $requetes_stats = [
        'total_demandes' => "SELECT COUNT(*) FROM demandes_contact",
        'demandes_mois' => "SELECT COUNT(*) FROM demandes_contact WHERE MONTH(date_creation) = MONTH(CURRENT_DATE()) AND YEAR(date_creation) = YEAR(CURRENT_DATE())",
        'demandes_nouvelles' => "SELECT COUNT(*) FROM demandes_contact WHERE statut = 'nouveau'",
        'projets_actifs' => "SELECT COUNT(*) FROM projets WHERE est_actif = 1",
        'services_actifs' => "SELECT COUNT(*) FROM services WHERE est_actif = 1",
        'articles_publies' => "SELECT COUNT(*) FROM articles_blog WHERE statut = 'publie'",
        'temoignages_approuves' => "SELECT COUNT(*) FROM temoignages WHERE est_approuve = 1",
        'membres_equipe' => "SELECT COUNT(*) FROM membres_equipe WHERE est_actif = 1"
    ];
    
    foreach ($requetes_stats as $key => $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $stats[$key] = $stmt->fetchColumn();
    }
    
    // Dernières demandes de contact
    $sql = "SELECT dc.*, s.titre as service_nom 
            FROM demandes_contact dc 
            LEFT JOIN services s ON dc.service_id = s.id 
            ORDER BY dc.date_creation DESC 
            LIMIT 6";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dernieres_demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Évolution des demandes sur les 6 derniers mois
    $sql = "SELECT 
                DATE_FORMAT(date_creation, '%Y-%m') as periode,
                MONTHNAME(date_creation) as mois,
                COUNT(*) as nombre
            FROM demandes_contact 
            WHERE date_creation >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(date_creation), MONTH(date_creation)
            ORDER BY YEAR(date_creation), MONTH(date_creation)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $evolution_demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Projets récents
    $sql = "SELECT titre, date_creation, est_en_vedette 
            FROM projets 
            WHERE est_actif = 1 
            ORDER BY date_creation DESC 
            LIMIT 4";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $projets_recents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $erreur = "Erreur lors du chargement des données : " . $e->getMessage();
}

include '../Includes/header.php';
include '../Includes/sidebar.php';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Tableau de Bord</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Tableau de bord</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Alertes -->
            <?php if(isset($erreur)): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Erreur</h5>
                <?php echo $erreur; ?>
            </div>
            <?php endif; ?>
            
            <!-- Cartes de statistiques -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info elevation-1">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Demandes ce mois</span>
                            <span class="info-box-number"><?php echo $stats['demandes_mois'] ?? 0; ?></span>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: 70%"></div>
                            </div>
                            <small><?php echo $stats['demandes_nouvelles'] ?? 0; ?> nouvelles</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-success elevation-1">
                            <i class="fas fa-briefcase"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Projets actifs</span>
                            <span class="info-box-number"><?php echo $stats['projets_actifs'] ?? 0; ?></span>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: 70%"></div>
                            </div>
                            <small>En production</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1">
                            <i class="fas fa-blog"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Articles publiés</span>
                            <span class="info-box-number"><?php echo $stats['articles_publies'] ?? 0; ?></span>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 50%"></div>
                            </div>
                            <small>Contenu actif</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-purple elevation-1">
                            <i class="fas fa-users"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">Membres équipe</span>
                            <span class="info-box-number"><?php echo $stats['membres_equipe'] ?? 0; ?></span>
                            <div class="progress">
                                <div class="progress-bar bg-purple" style="width: 70%"></div>
                            </div>
                            <small>Équipe active</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Colonne de gauche -->
                <div class="col-md-8">
                    <!-- Dernières demandes -->
                    <div class="card">
                        <div class="card-header border-transparent">
                            <h3 class="card-title">Dernières demandes de contact</h3>
                            <div class="card-tools">
                                <span class="badge badge-info"><?php echo $stats['total_demandes'] ?? 0; ?> total</span>
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table m-0">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Service</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($dernieres_demandes)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4">
                                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                <p class="text-muted">Aucune demande de contact</p>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach($dernieres_demandes as $demande): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                                <span class="text-white font-weight-bold">
                                                                    <?php echo strtoupper(substr($demande['prenom'], 0, 1) . substr($demande['nom'], 0, 1)); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <div class="font-weight-bold"><?php echo htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']); ?></div>
                                                            <small class="text-muted"><?php echo htmlspecialchars($demande['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light"><?php echo htmlspecialchars($demande['service_nom'] ?? 'Non spécifié'); ?></span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d/m/Y', strtotime($demande['date_creation'])); ?></small>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($demande['date_creation'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $badge_class = [
                                                        'nouveau' => 'badge-info',
                                                        'en_cours' => 'badge-warning',
                                                        'contacte' => 'badge-primary',
                                                        'converti' => 'badge-success',
                                                        'rejete' => 'badge-secondary'
                                                    ];
                                                    $statut_text = [
                                                        'nouveau' => 'Nouveau',
                                                        'en_cours' => 'En cours',
                                                        'contacte' => 'Contacté',
                                                        'converti' => 'Converti',
                                                        'rejete' => 'Rejeté'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $badge_class[$demande['statut']] ?? 'badge-secondary'; ?>">
                                                        <?php echo $statut_text[$demande['statut']] ?? $demande['statut']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="messages.php?action=view&id=<?php echo $demande['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       title="Voir les détails">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer clearfix">
                            <a href="messages.php" class="btn btn-sm btn-secondary float-right">
                                <i class="fas fa-list"></i> Voir toutes les demandes
                            </a>
                        </div>
                    </div>

                    <!-- Projets récents -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Projets Récents</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php if(empty($projets_recents)): ?>
                                <div class="col-12 text-center py-4">
                                    <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">Aucun projet récent</p>
                                </div>
                                <?php else: ?>
                                    <?php foreach($projets_recents as $projet): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="font-weight-bold mb-0"><?php echo htmlspecialchars($projet['titre']); ?></h6>
                                                <?php if($projet['est_en_vedette']): ?>
                                                <span class="badge badge-warning">Vedette</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="far fa-calendar mr-1"></i>
                                                <?php echo date('d/m/Y', strtotime($projet['date_creation'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne de droite -->
                <div class="col-md-4">
                    <!-- Évolution des demandes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Activité des 6 derniers mois</h3>
                        </div>
                        <div class="card-body">
                            <?php if(!empty($evolution_demandes)): ?>
                                <?php 
                                $max_demandes = max(array_column($evolution_demandes, 'nombre'));
                                foreach($evolution_demandes as $mois): 
                                    $pourcentage = $max_demandes > 0 ? ($mois['nombre'] / $max_demandes) * 100 : 0;
                                ?>
                                <div class="progress-group">
                                    <div class="progress-group-header">
                                        <i class="far fa-calendar progress-group-icon"></i>
                                        <span><?php echo substr($mois['mois'], 0, 3); ?></span>
                                        <span class="ml-auto"><?php echo $mois['nombre']; ?></span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $pourcentage; ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted">Aucune donnée disponible</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions rapides -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Actions Rapides</h3>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <a href="projets.php?action=add" class="btn btn-app bg-gradient-primary">
                                        <i class="fas fa-plus fa-2x"></i>
                                        <span>Nouveau<br>Projet</span>
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="articles_blog.php?action=add" class="btn btn-app bg-gradient-success">
                                        <i class="fas fa-edit fa-2x"></i>
                                        <span>Nouvel<br>Article</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="services.php" class="btn btn-app bg-gradient-info">
                                        <i class="fas fa-cogs fa-2x"></i>
                                        <span>Gérer<br>Services</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="parametres.php" class="btn btn-app bg-gradient-secondary">
                                        <i class="fas fa-cog fa-2x"></i>
                                        <span>Paramètres<br>Site</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques supplémentaires -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Aperçu du Site</h3>
                        </div>
                        <div class="card-body">
                            <div class="small-box bg-gradient-teal mb-3">
                                <div class="inner">
                                    <h3><?php echo $stats['services_actifs'] ?? 0; ?></h3>
                                    <p>Services en ligne</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <a href="services.php" class="small-box-footer">
                                    Gérer <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                            
                            <div class="small-box bg-gradient-orange">
                                <div class="inner">
                                    <h3><?php echo $stats['temoignages_approuves'] ?? 0; ?></h3>
                                    <p>Témoignages publiés</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <a href="temoignages.php" class="small-box-footer">
                                    Voir <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>