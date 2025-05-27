<?php include 'sidebar.php'; ?>
<div style="margin-left: 220px; padding: 20px;">
<?php
session_start(); // TODO: Changer toutes les connection a la bdd par require_once 'connexion.php';

$host = 'localhost';
$dbname = 'saebdd';
$user = 'postgres';
$password = '2606';

// Vérification de la connexion de l'utilisateur
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Récupération des informations utilisateur depuis la session
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';
$user_role = $_SESSION['user_role'] ?? 'Non spécifié';
$user_id = $_SESSION['user_id'] ?? 0;

// Vérifier si l'ID de consultation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$idconsultation = $_GET['id'];

// Connexion à la base de données
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Récupération des informations sur la consultation
try {
    // Informations de base de la consultation
    $stmt = $pdo->prepare("
        SELECT * FROM public.consultation 
        WHERE idconsultation = :idconsultation
    ");
    $stmt->bindParam(':idconsultation', $idconsultation);
    $stmt->execute();
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consultation) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Informations sur l'animal et le propriétaire
    $stmt = $pdo->prepare("
        SELECT 
            a.*, 
            p.*, 
            con.tarif as tarif_consultation, 
            con.lieuc as lieu_consultation
        FROM public.consulter con
        JOIN public.animal a ON con.idanimal = a.idanimal
        JOIN public.\"proprietaire\" p ON a.\"idproprietaire\" = p.\"idproprietaire\"
        WHERE con.idconsultation = :idconsultation
    ");
    $stmt->bindParam(':idconsultation', $idconsultation);
    $stmt->execute();
    $animal_proprietaire = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Manipulations associées à la consultation
    $stmt = $pdo->prepare("
        SELECT m.*
        FROM public.soigner s
        JOIN public.manipulation m ON s.codemanipulation = m.codemanipulation
        WHERE s.idconsultation = :idconsultation
    ");
    $stmt->bindParam(':idconsultation', $idconsultation);
    $stmt->execute();
    $manipulations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Historique des consultations
    $stmt = $pdo->prepare("
        SELECT c.*
        FROM public.historique h
        JOIN public.consultation c ON h.idancienneconsultation = c.idconsultation
        WHERE h.idconsultation = :idconsultation
    ");
    $stmt->bindParam(':idconsultation', $idconsultation);
    $stmt->execute();
    $anciennes_consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Consultations qui font référence à cette consultation dans leur historique
    $stmt = $pdo->prepare("
        SELECT c.*
        FROM public.historique h
        JOIN public.consultation c ON h.idconsultation = c.idconsultation
        WHERE h.idancienneconsultation = :idconsultation
    ");
    $stmt->bindParam(':idconsultation', $idconsultation);
    $stmt->execute();
    $consultations_futures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur lors de la récupération des données: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la consultation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #333;
        }
        .section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
        }
        .info-label {
            font-weight: bold;
        }
        .manipulations-list, .historique-list {
            margin-top: 15px;
        }
        .manipulation-item, .historique-item {
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .back-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Détails de la consultation #<?= htmlspecialchars($idconsultation) ?></h1>
        
        <!-- Informations sur la consultation -->
        <div class="section">
            <h2>Informations générales</h2>
            <div class="info-grid">
                <div class="info-label">ID Consultation:</div>
                <div><?= htmlspecialchars($consultation['idconsultation']) ?></div>
                
                <div class="info-label">Type:</div>
                <div><?= htmlspecialchars($consultation['typec']) ?></div>
                
                <div class="info-label">Date:</div>
                <div><?= htmlspecialchars($consultation['datec']) ?></div>
                
                <div class="info-label">Heure:</div>
                <div><?= htmlspecialchars($consultation['heurec']) ?></div>
                
                <div class="info-label">Durée:</div>
                <div><?= htmlspecialchars($consultation['dureec'] ?? 'Non spécifiée') ?> minutes</div>
                
                <div class="info-label">Lieu:</div>
                <div><?= htmlspecialchars($consultation['lieuc']) ?></div>
                
                <div class="info-label">Tarif:</div>
                <div><?= htmlspecialchars($consultation['tarif']) ?> €</div>
                
                <div class="info-label">Motif:</div>
                <div><?= nl2br(htmlspecialchars($consultation['motif'] ?? 'Non spécifié')) ?></div>
                
                <div class="info-label">Diagnostic:</div>
                <div><?= nl2br(htmlspecialchars($consultation['diagnostic'] ?? 'Non spécifié')) ?></div>
            </div>
        </div>
        
        <!-- Informations sur l'animal -->
        <?php if ($animal_proprietaire): ?>
        <div class="section">
            <h2>Informations sur l'animal</h2>
            <div class="info-grid">
                <div class="info-label">ID Animal:</div>
                <div><?= htmlspecialchars($animal_proprietaire['idanimal']) ?></div>
                
                <div class="info-label">Nom:</div>
                <div><?= htmlspecialchars($animal_proprietaire['noma']) ?></div>
                
                <div class="info-label">Espèce:</div>
                <div><?= htmlspecialchars($animal_proprietaire['espÃ¨cea']) ?></div>
                
                <div class="info-label">Race:</div>
                <div><?= htmlspecialchars($animal_proprietaire['racea'] ?? 'Non spécifiée') ?></div>
                
                <div class="info-label">Taille:</div>
                <div><?= htmlspecialchars($animal_proprietaire['taille'] ?? 'Non spécifiée') ?> cm</div>
                
                <div class="info-label">Genre:</div>
                <div><?= htmlspecialchars($animal_proprietaire['genre'] ?? 'Non spécifié') ?></div>
                
                <div class="info-label">Poids:</div>
                <div><?= htmlspecialchars($animal_proprietaire['poids'] ?? 'Non spécifié') ?> kg</div>
                
                <div class="info-label">Castration:</div>
                <div><?= $animal_proprietaire['castration'] ? 'Oui' : 'Non' ?></div>
                
                <div class="info-label">Date de naissance:</div>
                <div><?= htmlspecialchars($animal_proprietaire['date_de_naissance'] ?? 'Non spécifiée') ?></div>
            </div>
        </div>
        
        <!-- Informations sur le propriétaire -->
        <div class="section">
            <h2>Informations sur le propriétaire</h2>
            <div class="info-grid">
                <div class="info-label">ID Propriétaire:</div>
                <div><?= htmlspecialchars($animal_proprietaire['idproprietaire']) ?></div>
                
                <div class="info-label">Nom:</div>
                <div><?= htmlspecialchars($animal_proprietaire['nomp']) ?></div>
                
                <div class="info-label">Prénom:</div>
                <div><?= htmlspecialchars($animal_proprietaire['prenomp']) ?></div>
                
                <div class="info-label">Adresse:</div>
                <div><?= htmlspecialchars($animal_proprietaire['adressep'] ?? 'Non spécifiée') ?></div>
                
                <div class="info-label">Téléphone:</div>
                <div><?= htmlspecialchars($animal_proprietaire['telp'] ?? 'Non spécifié') ?></div>
                
                <div class="info-label">Type:</div>
                <div><?= htmlspecialchars($animal_proprietaire['type']) ?></div>
                
                <?php if (!empty($animal_proprietaire['iban'])): ?>
                <div class="info-label">IBAN:</div>
                <div><?= htmlspecialchars($animal_proprietaire['iban']) ?></div>
                <?php endif; ?>
                
                <?php if (!empty($animal_proprietaire['site_web'])): ?>
                <div class="info-label">Site web:</div>
                <div><a href="<?= htmlspecialchars($animal_proprietaire['site_web']) ?>" target="_blank"><?= htmlspecialchars($animal_proprietaire['site_web']) ?></a></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Manipulations -->
        <?php if (!empty($manipulations)): ?>
        <div class="section">
            <h2>Manipulations effectuées</h2>
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Durée estimée</th>
                        <th>Tarif de base</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manipulations as $manipulation): ?>
                    <tr>
                        <td><?= htmlspecialchars($manipulation['codemanipulation']) ?></td>
                        <td><?= htmlspecialchars($manipulation['description']) ?></td>
                        <td><?= htmlspecialchars($manipulation['duree_estimee']) ?> minutes</td>
                        <td><?= htmlspecialchars($manipulation['tarif_base']) ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Historique des consultations -->
        <?php if (!empty($anciennes_consultations)): ?>
        <div class="section">
            <h2>Consultations précédentes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Lieu</th>
                        <th>Tarif</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($anciennes_consultations as $ancienne): ?>
                    <tr>
                        <td><?= htmlspecialchars($ancienne['idconsultation']) ?></td>
                        <td><?= htmlspecialchars($ancienne['typec']) ?></td>
                        <td><?= htmlspecialchars($ancienne['datec']) ?></td>
                        <td><?= htmlspecialchars($ancienne['heurec']) ?></td>
                        <td><?= htmlspecialchars($ancienne['lieuc']) ?></td>
                        <td><?= htmlspecialchars($ancienne['tarif']) ?> €</td>
                        <td><a href="consultation_details.php?id=<?= htmlspecialchars($ancienne['idconsultation']) ?>">Voir détails</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Consultations futures -->
        <?php if (!empty($consultations_futures)): ?>
        <div class="section">
            <h2>Consultations suivantes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Lieu</th>
                        <th>Tarif</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($consultations_futures as $future): ?>
                    <tr>
                        <td><?= htmlspecialchars($future['idconsultation']) ?></td>
                        <td><?= htmlspecialchars($future['typec']) ?></td>
                        <td><?= htmlspecialchars($future['datec']) ?></td>
                        <td><?= htmlspecialchars($future['heurec']) ?></td>
                        <td><?= htmlspecialchars($future['lieuc']) ?></td>
                        <td><?= htmlspecialchars($future['tarif']) ?> €</td>
                        <td><a href="consultation_details.php?id=<?= htmlspecialchars($future['idconsultation']) ?>">Voir détails</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="back-btn">Retour au tableau de bord</a>
    </div>
</body>