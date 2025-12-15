<?php
// Vérification si l'utilisateur est connecté
if (!function_exists('est_connecte')) {
    function est_connecte() {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }
}

// Redirection si non connecté
if (!function_exists('verifier_connexion')) {
    function verifier_connexion() {
        if (!est_connecte()) {
            header('Location: ../connexion.php');
            exit();
        }
    }
}

// Vérification des permissions
if (!function_exists('a_la_permission')) {
    function a_la_permission($role_requis) {
        if (!isset($_SESSION['admin_role'])) {
            return false;
        }
        
        $hierarchie = ['lecteur' => 1, 'editeur' => 2, 'administrateur' => 3];
        return $hierarchie[$_SESSION['admin_role']] >= $hierarchie[$role_requis];
    }
}
?>