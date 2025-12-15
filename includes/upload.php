<?php
// Fonction pour uploader des fichiers
function uploader_fichier($fichier, $dossier) {
    $chemin_upload = UPLOAD_PATH . $dossier . '/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($chemin_upload)) {
        mkdir($chemin_upload, 0755, true);
    }
    
    // Vérifier les erreurs
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erreur lors de l\'upload'];
    }
    
    // Vérifier le type de fichier
    $types_autorises = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($fichier['type'], $types_autorises)) {
        return ['success' => false, 'message' => 'Type de fichier non autorisé'];
    }
    
    // Vérifier la taille (max 5MB)
    if ($fichier['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Fichier trop volumineux (max 5MB)'];
    }
    
    // Générer un nom unique
    $extension = pathinfo($fichier['name'], PATHINFO_EXTENSION);
    $nom_fichier = uniqid() . '.' . $extension;
    $chemin_complet = $chemin_upload . $nom_fichier;
    
    // Déplacer le fichier
    if (move_uploaded_file($fichier['tmp_name'], $chemin_complet)) {
        return [
            'success' => true, 
            'nom_fichier' => $nom_fichier,
            'chemin' => $dossier . '/' . $nom_fichier
        ];
    }
    
    return ['success' => false, 'message' => 'Erreur lors du déplacement du fichier'];
}

// Fonction pour supprimer un fichier
function supprimer_fichier($chemin) {
    $chemin_complet = UPLOAD_PATH . $chemin;
    if (file_exists($chemin_complet)) {
        return unlink($chemin_complet);
    }
    return false;
}
?>