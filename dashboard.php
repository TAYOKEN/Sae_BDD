<?php include 'sidebar.php'; ?>
<div style="margin-left: 220px; padding: 20px;">
<?php
session_start();
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

// Connexion à la base de données
try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données (DASHBOARD): " . $e->getMessage());
}

// Traitement des actions selon le rôle
if ($user_role === 'client') {
    // Traitement de la demande de consultation pour un client
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_consultation'])) {
        $typec = $_POST['typec'] ?? '';
        $datec = $_POST['datec'] ?? '';
        $heurec = $_POST['heurec'] ?? '';
        $dureec = $_POST['dureec'] ?? '';
        $motif = $_POST['motif'] ?? '';
        $lieuc = $_POST['lieuc'] ?? '';
        $idanimal = $_POST['idanimal'] ?? '';
        
        // Génération d'un ID pour la consultation (simple pour l'exemple)
        $idconsultation = 'C' . sprintf('%03d', rand(100, 999));
        
        try {
            // On commence une transaction
            $pdo->beginTransaction();
            
            // Insérer dans la table consultation
            $stmt = $pdo->prepare("
                INSERT INTO public.consultation 
                (idconsultation, typec, datec, heurec, \"dureec\", diagnostic, motif, lieuc, tarif) 
                VALUES (:idconsultation, :typec, :datec, :heurec, :dureec, NULL, :motif, :lieuc, 0.00)
            ");
            
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->bindParam(':typec', $typec);
            $stmt->bindParam(':datec', $datec);
            $stmt->bindParam(':heurec', $heurec);
            $stmt->bindParam(':dureec', $dureec);
            $stmt->bindParam(':motif', $motif);
            $stmt->bindParam(':lieuc', $lieuc);
            $stmt->execute();
            
            // Lier l'animal à la consultation
            if (!empty($idanimal)) {
                $stmt = $pdo->prepare("
                    INSERT INTO public.consulter 
                    (idanimal, idconsultation, tarif, lieuc) 
                    VALUES (:idanimal, :idconsultation, 0.00, :lieuc)
                ");
                
                $stmt->bindParam(':idanimal', $idanimal);
                $stmt->bindParam(':idconsultation', $idconsultation);
                $stmt->bindParam(':lieuc', $lieuc);
                $stmt->execute();
            }
            
            $pdo->commit();
            $success_message = "Demande de consultation envoyée avec succès !";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Erreur lors de la création de la consultation: " . $e->getMessage();
        }
    }
    
    // Récupération des animaux pour le formulaire
    try {
        $stmt = $pdo->prepare("
            SELECT a.idanimal, a.noma, a.\"especea\", a.racea
            FROM public.animal a
            JOIN public.\"proprietaire\" p ON a.\"idproprietaire\" = p.\"idproprietaire\"
            JOIN public.utilisateurs u ON LOWER(p.nomp) = LOWER(u.nom)
            WHERE u.id = :user_id
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la récupération des animaux: " . $e->getMessage();
        $animals = [];
    }
    
} elseif ($user_role === 'admin') {
    // Traitement des actions d'administration
    
    // Accepter une consultation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_consultation'])) {
        $idconsultation = $_POST['idconsultation'] ?? '';
        $tarif = $_POST['tarif'] ?? 0;
        $diagnostic = $_POST['diagnostic'] ?? '';
        
        try {
            $stmt = $pdo->prepare("
                UPDATE public.consultation 
                SET diagnostic = :diagnostic, tarif = :tarif
                WHERE idconsultation = :idconsultation
            ");
            
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->bindParam(':diagnostic', $diagnostic);
            $stmt->bindParam(':tarif', $tarif);
            $stmt->execute();
            
            // Mettre à jour le tarif dans la table consulter
            $stmt = $pdo->prepare("
                UPDATE public.consulter 
                SET tarif = :tarif
                WHERE idconsultation = :idconsultation
            ");
            
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->bindParam(':tarif', $tarif);
            $stmt->execute();
            
            $success_message = "Consultation acceptée avec succès !";
            
        } catch (PDOException $e) {
            $error_message = "Erreur lors de l'acceptation de la consultation: " . $e->getMessage();
        }
    }
    
    // Refuser/supprimer une consultation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_consultation'])) {
        $idconsultation = $_POST['idconsultation'] ?? '';
        
        try {
            // Supprimer d'abord les références dans les tables liées
            $pdo->beginTransaction();
            
            // Supprimer de la table consulter
            $stmt = $pdo->prepare("DELETE FROM public.consulter WHERE idconsultation = :idconsultation");
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->execute();
            
            // Supprimer de la table soigner
            $stmt = $pdo->prepare("DELETE FROM public.soigner WHERE idconsultation = :idconsultation");
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->execute();
            
            // Supprimer de la table historique (comme idconsultation)
            $stmt = $pdo->prepare("DELETE FROM public.historique WHERE idconsultation = :idconsultation");
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->execute();
            
            // Supprimer de la table historique (comme idancienneconsultation)
            $stmt = $pdo->prepare("DELETE FROM public.historique WHERE idancienneconsultation = :idconsultation");
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->execute();
            
            // Enfin, supprimer de la table consultation
            $stmt = $pdo->prepare("DELETE FROM public.consultation WHERE idconsultation = :idconsultation");
            $stmt->bindParam(':idconsultation', $idconsultation);
            $stmt->execute();
            
            $pdo->commit();
            $success_message = "Consultation supprimée avec succès !";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = "Erreur lors de la suppression de la consultation: " . $e->getMessage();
        }
    }
    
    // Récupération des consultations pour l'admin
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, a.noma, a.\"especea\", p.nomp as proprietaire
            FROM public.consultation c
            LEFT JOIN public.consulter con ON c.idconsultation = con.idconsultation
            LEFT JOIN public.animal a ON con.idanimal = a.idanimal
            LEFT JOIN public.\"proprietaire\" p ON a.\"idproprietaire\" = p.\"idproprietaire\"
            ORDER BY c.datec DESC, c.heurec DESC
        ");
        $stmt->execute();
        $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la récupération des consultations: " . $e->getMessage();
        $consultations = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
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
            max-width: 1000px;
            margin: 0 auto;
        }
        h1, h2, h3 {
            color: #333;
        }
        .user-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .logout-btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #ff4d4d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        .logout-btn:hover {
            background-color: #ff3333;
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
        input[type="date"],
        input[type="time"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .action-buttons form {
            margin: 0;
        }
        .button-accept {
            background-color: #4CAF50;
        }
        .button-delete {
            background-color: #ff4d4d;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <h1>Tableau de bord</h1>
        
        <div class="user-info">
            <h2>Bienvenue, <?= htmlspecialchars($user_name) ?> !</h2>
            <p>Rôle: <?= htmlspecialchars($user_role) ?></p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if ($user_role === 'client'): ?>
            <!-- Interface client -->
            <h3>Demander une consultation</h3>
            
            <?php if (empty($animals)): ?>
                <div class="message error">Aucun animal trouvé pour votre compte. Veuillez contacter l'administrateur.</div>
            <?php else: ?>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="idanimal">Animal</label>
                        <select id="idanimal" name="idanimal" required>
                            <option value="">Sélectionnez un animal</option>
                            <?php foreach ($animals as $animal): ?>
                                <option value="<?= htmlspecialchars($animal['idanimal']) ?>">
                                    <?= htmlspecialchars($animal['noma']) ?> 
                                    (<?= htmlspecialchars($animal['especea']) ?> - 
                                    <?= htmlspecialchars($animal['racea'] ?? 'Non spécifié') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="typec">Type de consultation</label>
                        <select id="typec" name="typec" required>
                            <option value="Basique">Basique</option>
                            <option value="Osteopathique">Ostéopathique</option>
                            <option value="Homeopathique">Homéopathique</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="datec">Date</label>
                        <input type="date" id="datec" name="datec" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="heurec">Heure</label>
                        <input type="time" id="heurec" name="heurec" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dureec">Durée estimée (minutes)</label>
                        <input type="number" id="dureec" name="dureec" min="15" max="120" step="15" value="30">
                    </div>
                    
                    <div class="form-group">
                        <label for="lieuc">Lieu</label>
                        <select id="lieuc" name="lieuc" required>
                            <option value="Cabinet">Cabinet</option>
                            <option value="Hors Cabinet">Hors Cabinet</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="motif">Motif de la consultation</label>
                        <textarea id="motif" name="motif" required></textarea>
                    </div>
                    
                    <button type="submit" name="create_consultation">Demander la consultation</button>
                </form>
            <?php endif; ?>
            
        <?php elseif ($user_role === 'admin'): ?>
            <!-- Interface admin -->
            <h3>Gestion des consultations</h3>
            
            <?php if (empty($consultations)): ?>
                <div class="message info">Aucune consultation à afficher.</div>
            <?php else: ?>
                <table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Animal</th>
            <th>Propriétaire</th>
            <th>Type</th>
            <th>Date</th>
            <th>Heure</th>
            <th>Lieu</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($consultations as $consultation): ?>
            <tr>
                <td>
                    <a href="consultation_details.php?id=<?= htmlspecialchars($consultation['idconsultation']) ?>">
                        <?= htmlspecialchars($consultation['idconsultation']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($consultation['noma'] ?? 'Non spécifié') ?></td>
                <td><?= htmlspecialchars($consultation['proprietaire'] ?? 'Non spécifié') ?></td>
                <td><?= htmlspecialchars($consultation['typec']) ?></td>
                <td><?= htmlspecialchars($consultation['datec']) ?></td>
                <td><?= htmlspecialchars($consultation['heurec']) ?></td>
                <td><?= htmlspecialchars($consultation['lieuc']) ?></td>
                <td>
                    <?php if (empty($consultation['diagnostic']) && $consultation['tarif'] <= 0): ?>
                        <span style="color: orange;">En attente</span>
                    <?php else: ?>
                        <span style="color: green;">Acceptée</span>
                    <?php endif; ?>
                </td>
                <td class="action-buttons">
                    <a href="consultation_details.php?id=<?= htmlspecialchars($consultation['idconsultation']) ?>" class="button-view">
                        Détails
                    </a>
                    
                    <?php if (empty($consultation['diagnostic']) && $consultation['tarif'] <= 0): ?>
                        <!-- Formulaire pour accepter une consultation -->
                        <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir accepter cette consultation?');">
                            <input type="hidden" name="idconsultation" value="<?= htmlspecialchars($consultation['idconsultation']) ?>">
                            <input type="hidden" name="diagnostic" value="Accepté par administrateur">
                            <input type="hidden" name="tarif" value="<?= $consultation['typec'] === 'Basique' ? '15.00' : ($consultation['typec'] === 'Osteopathique' ? '70.00' : '40.00') ?>">
                            <button type="submit" name="accept_consultation" class="button-accept">Accepter</button>
                        </form>
                    <?php endif; ?>
                    
                    <!-- Formulaire pour supprimer une consultation -->
                    <form method="post" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette consultation? Cette action est irréversible.');">
                        <input type="hidden" name="idconsultation" value="<?= htmlspecialchars($consultation['idconsultation']) ?>">
                        <button type="submit" name="delete_consultation" class="button-delete">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="message error">
                Vous n'avez pas les permissions nécessaires pour accéder à cette page.
            </div>
        <?php endif; ?>
        
        <a href="deconnexion.php" class="logout-btn">Se déconnecter</a>
    </div>
</body>
</html>