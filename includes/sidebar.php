<!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="tableau_bord.php" class="brand-link">
      <i class="fas fa-laptop-code brand-icon"></i>
      <span class="brand-text font-weight-light">PLMB Admin</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <i class="fas fa-user-circle img-circle elevation-2"></i>
        </div>
        <div class="info">
          <a href="#" class="d-block"><?php echo $_SESSION['admin_prenom'] . ' ' . $_SESSION['admin_nom']; ?></a>
          <small class="text-warning"><?php echo ucfirst($_SESSION['admin_role']); ?></small>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->
          <li class="nav-item">
            <a href="tableau_bord.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tableau_bord.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Tableau de bord</p>
            </a>
          </li>
          
          <li class="nav-header">GESTION DU CONTENU</li>
          
          <li class="nav-item">
            <a href="services.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-cogs"></i>
              <p>Services</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="projets.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'projets.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-briefcase"></i>
              <p>Projets</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="articles_blog.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'articles_blog.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-blog"></i>
              <p>Articles Blog</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="temoignages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'temoignages.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-comments"></i>
              <p>Témoignages</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="equipe.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'equipe.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>Équipe</p>
            </a>
          </li>
          
          <li class="nav-header">COMMUNICATION</li>
          
          <li class="nav-item">
            <a href="messages.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-envelope"></i>
              <p>Messages
                <span class="badge badge-info right">
                  <?php
                  // Compter les nouveaux messages
                  try {
                    require_once 'config.php';
                    $sql = "SELECT COUNT(*) FROM demandes_contact WHERE statut = 'nouveau'";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $nb_messages = $stmt->fetchColumn();
                    echo $nb_messages;
                  } catch(PDOException $e) {
                    echo '0';
                  }
                  ?>
                </span>
              </p>
            </a>
          </li>
          
          <li class="nav-header">ADMINISTRATION</li>
          
          <li class="nav-item">
            <a href="utilisateurs.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'utilisateurs.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-users"></i>
              <p>Utilisateurs</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="medias.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'medias.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-images"></i>
              <p>Médias</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="parametres.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'parametres.php' ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-cog"></i>
              <p>Paramètres</p>
            </a>
          </li>
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>