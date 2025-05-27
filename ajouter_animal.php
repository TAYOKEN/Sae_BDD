<?php
session_start();
require_once 'connexion.php';

// Verifier si l'utilisateur est connecte et est client
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = null;
$error = null;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noma = trim($_POST['noma'] ?? '');
    $especea = trim($_POST['especea'] ?? '');
    $racea = trim($_POST['racea'] ?? '');
    $taille = $_POST['taille'] ?? null;
    $genre = $_POST['genre'] ?? '';
    $poids = $_POST['poids'] ?? null;
    $castration = $_POST['castration'] ?? 'f';
    $date_de_naissance = $_POST['date_de_naissance'] ?? null;

    if (empty($noma) || empty($especea) || empty($genre)) {
        $error = "Les champs Nom, Espece et Genre sont obligatoires.";
    } else {
        try {
            $pdo = getDBConnection();

            // Recuperer l'idproprietaire lie à l'utilisateur
            $stmt = $pdo->prepare('SELECT p."idproprietaire" FROM public."proprietaire" p JOIN public.utilisateurs u ON LOWER(p.nomp) = LOWER(u.nom) WHERE u.id = :user_id');
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $proprietaire = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($proprietaire) {
                $idproprietaire = $proprietaire['idproprietaire'];
                // Generer un nouvel ID animal unique (ex: A123)
                $idanimal = 'A' . rand(100, 999);

                $stmt = $pdo->prepare('INSERT INTO public.animal (idanimal, "especea", noma, racea, taille, genre, poids, castration, date_de_naissance, "idproprietaire") VALUES (:idanimal, :especea, :noma, :racea, :taille, :genre, :poids, :castration, :date_de_naissance, :idproprietaire)');
                $stmt->bindParam(':idanimal', $idanimal);
                $stmt->bindParam(':especea', $especea);
                $stmt->bindParam(':noma', $noma);
                $stmt->bindParam(':racea', $racea);
                $stmt->bindParam(':taille', $taille);
                $stmt->bindParam(':genre', $genre);
                $stmt->bindParam(':poids', $poids);
                $stmt->bindParam(':castration', $castration);
                $stmt->bindParam(':date_de_naissance', $date_de_naissance);
                $stmt->bindParam(':idproprietaire', $idproprietaire);
                $stmt->execute();

                $success = "Animal ajoute avec succes !";
            } else {
                $error = "Impossible de trouver votre profil proprietaire.";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de l'animal : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un animal</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .container { background: white; padding: 25px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); width: 350px; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], input[type="date"], select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #45a049; }
        .error { color: red; margin-bottom: 15px; text-align: center; }
        .success { color: green; margin-bottom: 15px; text-align: center; }
        .back-link { display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ajouter un animal</h2>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="noma">Nom de l'animal *</label>
                <input type="text" id="noma" name="noma" required>
            </div>
            <div class="form-group">
                <label for="especea">Espece *</label>
                <input type="text" id="especea" name="especea" required>
            </div>
            <div class="form-group">
                <label for="racea">Race</label>
                <input type="text" id="racea" name="racea">
            </div>
            <div class="form-group">
                <label for="taille">Taille (cm)</label>
                <input type="number" id="taille" name="taille" step="0.01">
            </div>
            <div class="form-group">
                <label for="genre">Genre *</label>
                <select id="genre" name="genre" required>
                    <option value="">Selectionnez</option>
                    <option value="Mâle">Mâle</option>
                    <option value="Femelle">Femelle</option>
                </select>
            </div>
            <div class="form-group">
                <label for="poids">Poids (kg)</label>
                <input type="number" id="poids" name="poids" step="0.01">
            </div>
            <div class="form-group">
                <label for="castration">Castre ?</label>
                <select id="castration" name="castration">
                    <option value="t">Oui</option>
                    <option value="f">Non</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date_de_naissance">Date de naissance</label>
                <input type="date" id="date_de_naissance" name="date_de_naissance">
            </div>
            <button type="submit">Ajouter l'animal</button>
        </form>
        <a href="dashboard.php" class="back-link">← Retour au tableau de bord</a>
    </div>
</body>
</html>