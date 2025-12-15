<?php
require_once 'includes/config.php';

// Données de l'admin à enregistrer
$prenom = "Administrateur";
$nom = "Système";
$email = "admin@gmail.com";
$mot_de_passe = "12345"; // mot de passe clair

try {
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);

    if($stmt->rowCount() > 0){
        exit("Erreur : cet email est déjà utilisé.");
    }

    // Hash du mot de passe
    $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    // Insertion dans la table utilisateurs
    $insert = $pdo->prepare("
        INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe_hash, role, est_actif, date_creation)
        VALUES (:prenom, :nom, :email, :mot_de_passe_hash, 'administrateur', 1, NOW())
    ");

    $insert->execute([
        ':prenom' => $prenom,
        ':nom' => $nom,
        ':email' => $email,
        ':mot_de_passe_hash' => $mot_de_passe_hash
    ]);

    echo "Administrateur enregistré avec succès !";

} catch (PDOException $e) {
    echo "Erreur BDD : " . $e->getMessage();
}
