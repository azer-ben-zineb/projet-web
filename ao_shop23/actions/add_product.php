<?php


session_start();
require_once __DIR__ . '/../db.php';

// Vérification admin
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../client/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/produits.php');
    exit;
}

$isEdit = ($_POST['is_edit'] ?? '') === '1';
$reference = trim($_POST['reference'] ?? '');
$designation = trim($_POST['designation'] ?? '');
$designationEn = trim($_POST['designation_en'] ?? '');
$description = trim($_POST['description'] ?? '');
$descriptionEn = trim($_POST['description_en'] ?? '');
$marque = trim($_POST['marque'] ?? '');
$prix = floatval($_POST['prix'] ?? 0);
$quantite = intval($_POST['quantite'] ?? 0);
$idCategorie = intval($_POST['id_categorie'] ?? 0);

// Validation
if (empty($reference) || empty($designation) || empty($marque) || $prix <= 0 || $idCategorie <= 0) {
    $_SESSION['toast'] = 'Veuillez remplir tous les champs obligatoires.';
    header('Location: ../admin/produits.php');
    exit;
}

// Gestion de la photo
$photoName = null;
if (!empty($_FILES['photo']['name'])) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $photoName = $reference . '_' . time() . '.' . $ext;
        $uploadPath = __DIR__ . '/../uploads/' . $photoName;
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath);
    }
}

try {
    if ($isEdit) {
        // Mode édition
        if ($photoName) {
            $stmt = $pdo->prepare("
                UPDATE produits
                SET designation = ?, designation_en = ?, description = ?, description_en = ?,
                    marque = ?, prix = ?, quantite = ?, photo = ?, id_categorie = ?
                WHERE reference = ?
            ");
            $stmt->execute([$designation, $designationEn, $description, $descriptionEn,
                           $marque, $prix, $quantite, $photoName, $idCategorie, $reference]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE produits
                SET designation = ?, designation_en = ?, description = ?, description_en = ?,
                    marque = ?, prix = ?, quantite = ?, id_categorie = ?
                WHERE reference = ?
            ");
            $stmt->execute([$designation, $designationEn, $description, $descriptionEn,
                           $marque, $prix, $quantite, $idCategorie, $reference]);
        }
        $_SESSION['toast'] = 'Produit modifié avec succès.';
    } else {
        // Mode création - vérifier que la référence n'existe pas
        $stmt = $pdo->prepare("SELECT reference FROM produits WHERE reference = ?");
        $stmt->execute([$reference]);
        if ($stmt->fetch()) {
            $_SESSION['toast'] = 'Cette référence existe déjà.';
            header('Location: ../admin/produits.php');
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO produits (reference, designation, designation_en, description, description_en,
                                 marque, prix, quantite, photo, id_categorie, nombre_ventes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$reference, $designation, $designationEn, $description, $descriptionEn,
                       $marque, $prix, $quantite, $photoName, $idCategorie]);

        // Envoyer un email aux abonnés actifs
        $stmt = $pdo->query("
            SELECT u.email FROM users u
            JOIN abonnements a ON u.id_user = a.id_user
            WHERE a.actif = 1 AND a.date_fin > NOW()
            GROUP BY u.id_user
        ");
        $subscribers = $stmt->fetchAll();
        foreach ($subscribers as $sub) {
            @mail($sub['email'], 'Nouveau produit chez AO Shop',
                "Un nouveau produit est disponible : $designation ($marque) — " . number_format($prix, 2) . " DT\n\nDécouvrez-le sur AO Shop!");
        }

        $_SESSION['toast'] = 'Produit ajouté avec succès.';
    }
} catch (PDOException $e) {
    $_SESSION['toast'] = 'Erreur: ' . $e->getMessage();
}

header('Location: ../admin/produits.php');
exit;
