<?php
require_once 'auth.php';
verifier_connexion();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin PLMB | <?php echo $page_title ?? 'Tableau de bord'; ?></title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

  <!-- Font Awesome Icons (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-pap1Ed2s0+0L8cDkzYH4vU1y0m8RvfCq5L9xjXZH4MQ9D4C2R3e2O6dPb3BsyFqSx+7M1X1T4p5R70rRZ3l/0A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Bootstrap 4 CSS (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <!-- AdminLTE CSS (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

  <!-- Styles personnalisés -->
  <!-- Dans ton <head> -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-pap1Ed2s0+0L8cDkzYH4vU1y0m8RvfCq5L9xjXZH4MQ9D4C2R3e2O6dPb3BsyFqSx+7M1X1T4p5R70rRZ3l/0A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Font Awesome CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-pap1Ed2s0+0L8cDkzYH4vU1y0m8RvfCq5L9xjXZH4MQ9D4C2R3e2O6dPb3BsyFqSx+7M1X1T4p5R70rRZ3l/0A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <link rel="stylesheet" href="../Assets/css/admin.css">
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="../index.php" class="nav-link" target="_blank">Voir le site</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-comments"></i>
          <span class="badge badge-danger navbar-badge">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="messages.php" class="dropdown-item">
            Voir tous les messages
          </a>
        </div>
      </li>
      
      <li class="nav-item">
        <a class="nav-link" href="../deconnexion.php">
          <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  