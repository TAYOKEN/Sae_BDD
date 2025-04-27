<?php
session_start();
session_destroy();
header("Location: login.php");
exit;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déconnexion</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 50px; }
        .message { background: white; padding: 20px; border-radius: 10px; max-width: 400px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="message">
        <h2>Déconnexion réussie</h2>
        <p>Vous avez été déconnecté avec succès.</p>
        <p><a href="login.php">Se reconnecter</a></p>
    </div>
</body>
</html>
<?php
// session_start();
// session_destroy();
// header("Location: login.php");
// exit;
// ?>