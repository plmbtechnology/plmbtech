<?php
require_once '../Includes/config.php';
require_once '../Includes/auth.php';
verifier_connexion();

$page_title = "Gestion des Messages";

// Actions CRUD
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$filter = $_GET['filter'] ?? 'all';

try {
    switch($action) {
        case 'view':
            if ($id) {
                // Récupérer le message
                $sql = "SELECT dc.*, s.titre as service_nom 
                        FROM demandes_contact dc 
                        LEFT JOIN services s ON dc.service_id = s.id 
                        WHERE dc.id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $message = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$message) {
                    $_SESSION['error'] = "Message non trouvé";
                    header('Location: messages.php');
                    exit();
                }
                
                // Marquer comme lu si c'est un nouveau message
                if ($message['statut'] === 'nouveau') {
                    $sql = "UPDATE demandes_contact SET statut = 'en_cours' WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id]);
                }
            }
            break;
            
        case 'update_status':
            if ($id && isset($_POST['statut'])) {
                $statut = $_POST['statut'];
                $notes = $_POST['notes'] ?? '';
                $assigne_a = $_POST['assigne_a'] ?? null;
                
                $sql = "UPDATE demandes_contact 
                        SET statut = ?, notes = ?, assigne_a = ?, date_modification = NOW() 
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$statut, $notes, $assigne_a, $id]);
                
                $_SESSION['success'] = "Statut mis à jour avec succès";
                header('Location: messages.php?action=view&id=' . $id);
                exit();
            }
            break;
            
        case 'delete':
            if ($id) {
                $sql = "DELETE FROM demandes_contact WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $_SESSION['success'] = "Message supprimé avec succès";
                header('Location: messages.php');
                exit();
            }
            break;
            
        case 'list':
        default:
            // Construire la requête avec filtre
            $where = "1=1";
            $params = [];
            
            if ($filter === 'nouveau') {
                $where .= " AND statut = 'nouveau'";
            } elseif ($filter === 'en_cours') {
                $where .= " AND statut = 'en_cours'";
            } elseif ($filter === 'contacte') {
                $where .= " AND statut = 'contacte'";
            } elseif ($filter === 'converti') {
                $where .= " AND statut = 'converti'";
            } elseif ($filter === 'rejete') {
                $where .= " AND statut = 'rejete'";
            }
            
            $sql = "SELECT dc.*, s.titre as service_nom, u.prenom as assigne_prenom, u.nom as assigne_nom 
                    FROM demandes_contact dc 
                    LEFT JOIN services s ON dc.service_id = s.id 
                    LEFT JOIN utilisateurs u ON dc.assigne_a = u.id 
                    WHERE $where 
                    ORDER BY dc.date_creation DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupérer les utilisateurs pour l'assignation
            $sql_users = "SELECT id, prenom, nom FROM utilisateurs WHERE est_actif = 1";
            $stmt_users = $pdo->prepare($sql_users);
            $stmt_users->execute();
            $utilisateurs = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1>Gestion des Messages</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="tableau_bord.php">Accueil</a></li>
                        <li class="breadcrumb-item active">Messages</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <?php if($action === 'list'): ?>
            
            <div class="row mb-3">
                <div class="col-12">
                    <div class="btn-group">
                        <a href="?filter=all" class="btn btn-<?php echo $filter === 'all' ? 'primary' : 'secondary'; ?>">Tous</a>
                        <a href="?filter=nouveau" class="btn btn-<?php echo $filter === 'nouveau' ? 'primary' : 'secondary'; ?>">
                            Nouveaux <span class="badge badge-light"><?php echo count(array_filter($messages, fn($m) => $m['statut'] === 'nouveau')); ?></span>
                        </a>
                        <a href="?filter=en_cours" class="btn btn-<?php echo $filter === 'en_cours' ? 'primary' : 'secondary'; ?>">En cours</a>
                        <a href="?filter=contacte" class="btn btn-<?php echo $filter === 'contacte' ? 'primary' : 'secondary'; ?>">Contactés</a>
                        <a href="?filter=converti" class="btn btn-<?php echo $filter === 'converti' ? 'primary' : 'secondary'; ?>">Convertis</a>
                        <a href="?filter=rejete" class="btn btn-<?php echo $filter === 'rejete' ? 'primary' : 'secondary'; ?>">Rejetés</a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Liste des Messages</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Service</th>
                                <th>Budget</th>
                                <th>Statut</th>
                                <th>Assigné à</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($messages as $message): ?>
                            <tr class="<?php echo $message['statut'] === 'nouveau' ? 'table-warning' : ''; ?>">
                                <td>
                                    <small><?php echo date('d/m/Y', strtotime($message['date_creation'])); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($message['date_creation'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($message['prenom'] . ' ' . $message['nom']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($message['email']); ?></small>
                                    <?php if($message['entreprise']): ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($message['entreprise']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($message['service_nom'] ?? 'Non spécifié'); ?></td>
                                <td>
                                    <?php 
                                    $budgets = [
                                        '1-5k' => '1-5k €',
                                        '5-15k' => '5-15k €',
                                        '15-30k' => '15-30k €',
                                        '30-50k' => '30-50k €',
                                        '50k+' => '50k+ €',
                                        'non-defini' => 'À définir'
                                    ];
                                    echo $budgets[$message['budget']] ?? $message['budget'];
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = [
                                        'nouveau' => 'badge-warning',
                                        'en_cours' => 'badge-info',
                                        'contacte' => 'badge-primary',
                                        'converti' => 'badge-success',
                                        'rejete' => 'badge-danger'
                                    ];
                                    $statut_text = [
                                        'nouveau' => 'Nouveau',
                                        'en_cours' => 'En cours',
                                        'contacte' => 'Contacté',
                                        'converti' => 'Converti',
                                        'rejete' => 'Rejeté'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $badge_class[$message['statut']]; ?>">
                                        <?php echo $statut_text[$message['statut']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($message['assigne_prenom']): ?>
                                    <?php echo htmlspecialchars($message['assigne_prenom'] . ' ' . $message['assigne_nom']); ?>
                                    <?php else: ?>
                                    <span class="text-muted">Non assigné</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?action=view&id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?action=delete&id=<?php echo $message['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
                    <h3 class="card-title">Détails du Message</h3>
                    <div class="card-tools">
                        <a href="messages.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-primary">
                                <div class="card-header">
                                    <h3 class="card-title">Informations du Client</h3>
                                </div>
                                <div class="card-body">
                                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($message['prenom'] . ' ' . $message['nom']); ?></p>
                                    <p><strong>Email :</strong> <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>"><?php echo htmlspecialchars($message['email']); ?></a></p>
                                    <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($message['telephone'] ?: 'Non renseigné'); ?></p>
                                    <p><strong>Entreprise :</strong> <?php echo htmlspecialchars($message['entreprise'] ?: 'Non renseignée'); ?></p>
                                    <p><strong>Service demandé :</strong> <?php echo htmlspecialchars($message['service_nom'] ?? 'Non spécifié'); ?></p>
                                    <p><strong>Budget :</strong> 
                                        <?php 
                                        $budgets = [
                                            '1-5k' => '1 000 € - 5 000 €',
                                            '5-15k' => '5 000 € - 15 000 €',
                                            '15-30k' => '15 000 € - 30 000 €',
                                            '30-50k' => '30 000 € - 50 000 €',
                                            '50k+' => '50 000 € et plus',
                                            'non-defini' => 'À définir'
                                        ];
                                        echo $budgets[$message['budget']] ?? $message['budget'];
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card card-info">
                                <div class="card-header">
                                    <h3 class="card-title">Gestion du Message</h3>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="?action=update_status&id=<?php echo $message['id']; ?>">
                                        <div class="form-group">
                                            <label>Statut</label>
                                            <select name="statut" class="form-control" required>
                                                <option value="nouveau" <?php echo $message['statut'] === 'nouveau' ? 'selected' : ''; ?>>Nouveau</option>
                                                <option value="en_cours" <?php echo $message['statut'] === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                                <option value="contacte" <?php echo $message['statut'] === 'contacte' ? 'selected' : ''; ?>>Contacté</option>
                                                <option value="converti" <?php echo $message['statut'] === 'converti' ? 'selected' : ''; ?>>Converti</option>
                                                <option value="rejete" <?php echo $message['statut'] === 'rejete' ? 'selected' : ''; ?>>Rejeté</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Assigner à</label>
                                            <select name="assigne_a" class="form-control">
                                                <option value="">Non assigné</option>
                                                <?php foreach($utilisateurs as $user): ?>
                                                <option value="<?php echo $user['id']; ?>" 
                                                        <?php echo $message['assigne_a'] == $user['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Notes internes</label>
                                            <textarea name="notes" class="form-control" rows="4" placeholder="Notes sur le suivi de ce message..."><?php echo htmlspecialchars($message['notes'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Mettre à jour
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card card-success mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Description du Projet</h3>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($message['description_projet'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="card card-secondary mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Informations Techniques</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Date de réception :</strong> <?php echo date('d/m/Y à H:i', strtotime($message['date_creation'])); ?></p>
                            <p><strong>Dernière modification :</strong> <?php echo $message['date_modification'] ? date('d/m/Y à H:i', strtotime($message['date_modification'])) : 'Jamais'; ?></p>
                            <p><strong>Adresse IP :</strong> <?php echo htmlspecialchars($message['adresse_ip']); ?></p>
                            <p><strong>Source :</strong> 
                                <?php 
                                $sources = [
                                    'site_web' => 'Site web',
                                    'recommandation' => 'Recommandation',
                                    'reseau_social' => 'Réseau social',
                                    'autre' => 'Autre'
                                ];
                                echo $sources[$message['source']] ?? $message['source'];
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            
        </div>
    </section>
</div>

<?php include '../Includes/footer.php'; ?>