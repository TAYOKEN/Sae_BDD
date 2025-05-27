<?php include 'sidebar.php'; ?>
<div style="margin-left: 220px; padding: 20px;">
<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'saebdd';
$username = 'postgres';
$password = '2606';

$message = '';
$messageType = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des champs requis
        if (empty($_POST['nomp']) || empty($_POST['prenomp']) || empty($_POST['type'])) {
            throw new Exception('Les champs nom, prénom et type sont obligatoires');
        }
        
        // Validation du type
        if (!in_array($_POST['type'], ['Particulier', 'Professionnel'])) {
            throw new Exception('Type de propriétaire invalide');
        }
        
        // Connexion à la base de données
        $dsn = "pgsql:host=$host;dbname=$dbname";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Générer un ID unique
        do {
            $idproprietaire = 'P' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM proprietaire WHERE idproprietaire = ?');
            $stmt->execute([$idproprietaire]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);
        
        // Préparer les données
        $nomp = trim($_POST['nomp']);
        $prenomp = trim($_POST['prenomp']);
        $adressep = !empty($_POST['adressep']) ? trim($_POST['adressep']) : null;
        $telp = !empty($_POST['telp']) ? trim($_POST['telp']) : null;
        $type = $_POST['type'];
        $iban = null;
        $site_web = null;
        
        if ($type === 'Professionnel') {
            $iban = !empty($_POST['iban']) ? trim($_POST['iban']) : null;
            $site_web = !empty($_POST['site_web']) ? trim($_POST['site_web']) : null;
        }
        
        // Insérer en base
        $sql = 'INSERT INTO proprietaire (idproprietaire, nomp, prenomp, adressep, telp, iban, site_web, type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $idproprietaire,
            $nomp,
            $prenomp,
            $adressep,
            $telp,
            $iban,
            $site_web,
            $type
        ]);
        
        if ($result) {
            $message = 'Propriétaire créé avec succès! ID: ' . $idproprietaire;
            $messageType = 'success';
            // Vider les variables pour reset le formulaire
            $_POST = array();
        } else {
            throw new Exception('Erreur lors de la création du propriétaire');
        }
        
    } catch (Exception $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de propriétaire</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .dashboard-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .required {
            color: #ff4d4d;
        }
        .two-column {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Création d'un nouveau propriétaire</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="proprietaireForm">
            <div class="form-section">
                <h2>Informations personnelles</h2>
                <div class="two-column">
                    <div class="form-group">
                        <label for="nomp">Nom <span class="required">*</span></label>
                        <input type="text" id="nomp" name="nomp" value="<?php echo isset($_POST['nomp']) ? htmlspecialchars($_POST['nomp']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="prenomp">Prénom <span class="required">*</span></label>
                        <input type="text" id="prenomp" name="prenomp" value="<?php echo isset($_POST['prenomp']) ? htmlspecialchars($_POST['prenomp']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="type">Type de propriétaire <span class="required">*</span></label>
                    <select id="type" name="type" required>
                        <option value="">Sélectionnez un type</option>
                        <option value="Particulier" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Particulier') ? 'selected' : ''; ?>>Particulier</option>
                        <option value="Professionnel" <?php echo (isset($_POST['type']) && $_POST['type'] === 'Professionnel') ? 'selected' : ''; ?>>Professionnel</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <h2>Coordonnées</h2>
                <div class="form-group">
                    <label for="adressep">Adresse</label>
                    <input type="text" id="adressep" name="adressep" value="<?php echo isset($_POST['adressep']) ? htmlspecialchars($_POST['adressep']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="telp">Téléphone</label>
                    <input type="tel" id="telp" name="telp" value="<?php echo isset($_POST['telp']) ? htmlspecialchars($_POST['telp']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-section" id="professionnelSection" style="display: <?php echo (isset($_POST['type']) && $_POST['type'] === 'Professionnel') ? 'block' : 'none'; ?>;">
                <h2>Informations professionnelles</h2>
                <div class="form-group">
                    <label for="iban">IBAN</label>
                    <input type="text" id="iban" name="iban" maxlength="34" value="<?php echo isset($_POST['iban']) ? htmlspecialchars($_POST['iban']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="site_web">Site web</label>
                    <input type="text" id="site_web" name="site_web" value="<?php echo isset($_POST['site_web']) ? htmlspecialchars($_POST['site_web']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit">Créer le propriétaire</button>
            </div>
        </form>
    </div>

    <script>
        // Afficher/masquer les champs professionnels selon le type sélectionné
        document.getElementById('type').addEventListener('change', function() {
            const professionnelSection = document.getElementById('professionnelSection');
            if (this.value === 'Professionnel') {
                professionnelSection.style.display = 'block';
            } else {
                professionnelSection.style.display = 'none';
            }
        });
    </script>
</body>
</html>