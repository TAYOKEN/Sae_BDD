<?php include 'sidebar.php'; ?>
<div style="margin-left: 220px; padding: 20px;">
<?php
// Configuration de la base de données
class DatabaseConfig {
    private $host = 'localhost';
    private $dbname = 'saebdd';
    private $username = 'postgres';
    private $password = '1307';
    private $pdo;

    public function __construct() {
        try {
            $dsn = "pgsql:host={$this->host};dbname={$this->dbname}";
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Classe pour gérer les utilisateurs
class UserManager {
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    public function createUser($nom, $email, $mot_de_passe, $role, $proprietaire_id = null) {
        try {
            // Vérifier si l'email existe déjà
            $checkEmail = $this->db->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $checkEmail->execute([$email]);
            
            if ($checkEmail->rowCount() > 0) {
                return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
            }

            // Hacher le mot de passe
            $hashedPassword = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            // Insérer le nouvel utilisateur
            $stmt = $this->db->prepare("
                INSERT INTO utilisateurs (nom, email, mot_de_passe, role) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([$nom, $email, $hashedPassword, $role]);
            
            return ['success' => true, 'message' => 'Utilisateur créé avec succès !'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la création : ' . $e->getMessage()];
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->db->query("SELECT id, nom, email, role FROM utilisateurs ORDER BY id");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function fixSequence() {
    try {
        $stmt = $this->db->query("SELECT setval('utilisateurs_id_seq', COALESCE((SELECT MAX(id) FROM utilisateurs), 1), true)");
        return ['success' => true, 'message' => 'Séquence corrigée avec succès.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur lors de la correction : ' . $e->getMessage()];
    }
}

    public function deleteUser($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Utilisateur supprimé avec succès.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la suppression.'];
        }
    }

    public function getUserById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function updateUser($id, $nom, $email, $role) {
        try {
            $stmt = $this->db->prepare("
                UPDATE utilisateurs 
                SET nom = ?, email = ?, role = ? 
                WHERE id = ?
            ");
            $stmt->execute([$nom, $email, $role, $id]);
            return ['success' => true, 'message' => 'Utilisateur modifié avec succès.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erreur lors de la modification.'];
        }
    }
}

// Classe pour gérer les propriétaires
class ProprietaireManager {
    private $db;

    public function __construct($database) {
        $this->db = $database->getConnection();
    }

    public function getAllProprietaires() {
        try {
            $stmt = $this->db->query("
                SELECT idproprietaire, nomp, prenomp, adressep, telp, iban, site_web, type 
                FROM proprietaire 
                ORDER BY nomp
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getProprietaireById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM proprietaire 
                WHERE idproprietaire = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}

// Initialisation
$database = new DatabaseConfig();
$userManager = new UserManager($database);
$proprietaireManager = new ProprietaireManager($database);

// Traitement des actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $result = $userManager->createUser(
                    $_POST['nom'],
                    $_POST['email'],
                    $_POST['mot_de_passe'],
                    $_POST['role']
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'delete':
                $result = $userManager->deleteUser($_POST['user_id']);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'update':
                $result = $userManager->updateUser(
                    $_POST['user_id'],
                    $_POST['nom'],
                    $_POST['email'],
                    $_POST['role']
                );
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

// Récupération des données
$users = $userManager->getAllUsers();
$proprietaires = $proprietaireManager->getAllProprietaires();

// Gestion de l'édition
$editUser = null;
if (isset($_GET['edit'])) {
    $editUser = $userManager->getUserById($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
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
            max-width: 1200px;
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
        input[type="password"],
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
        .button-delete {
            background-color: #ff4d4d;
        }
        .button-delete:hover {
            background-color: #ff3333;
        }
        .button-edit {
            background-color: #2196F3;
        }
        .button-edit:hover {
            background-color: #1976D2;
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
            gap: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .proprietaire-details {
            display: none;
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .two-column {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
    <script>
        function showProprietaireDetails() {
            const select = document.getElementById('proprietaire');
            const details = document.getElementById('proprietaire-details');
            const selectedValue = select.value;
            
            if (selectedValue) {
                // Simulation des détails - dans une vraie application, vous feriez un appel AJAX
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }

        function confirmDelete(userId, userName) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${userName}" ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editUser(userId) {
            window.location.href = `?edit=${userId}`;
        }
    </script>
</head>
<body>
    <div class="dashboard-container">
        <h1><?= $editUser ? 'Modifier' : 'Créer' ?> un Utilisateur</h1>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
            <?php endif; ?>

            <div class="two-column">
                <!-- Section Utilisateur -->
                <div class="form-section">
                    <h3>Informations Utilisateur</h3>
                    
                    <div class="form-group">
                        <label for="nom">Nom <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" value="<?= $editUser ? htmlspecialchars($editUser['nom']) : '' ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?= $editUser ? htmlspecialchars($editUser['email']) : '' ?>" required>
                    </div>

                    <?php if (!$editUser): ?>
                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe <span class="required">*</span></label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="role">Rôle <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="">Sélectionnez un rôle</option>
                            <option value="admin" <?= ($editUser && $editUser['role'] === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                            <option value="client" <?= ($editUser && $editUser['role'] === 'client') ? 'selected' : '' ?>>Client</option>
                            <option value="gestionnaire" <?= ($editUser && $editUser['role'] === 'gestionnaire') ? 'selected' : '' ?>>Gestionnaire</option>
                        </select>
                    </div>
                </div>

                <!-- Section Propriétaire -->
                <div class="form-section">
                    <h3>Liaison avec Propriétaire (Optionnel)</h3>
                    
                    <div class="form-group">
                        <label for="proprietaire">Propriétaire</label>
                        <select id="proprietaire" name="proprietaire" onchange="showProprietaireDetails()">
                            <option value="">Sélectionnez un propriétaire</option>
                            <?php foreach ($proprietaires as $prop): ?>
                                <option value="<?= htmlspecialchars($prop['idproprietaire']) ?>">
                                    <?= htmlspecialchars($prop['prenomp'] . ' ' . $prop['nomp']) ?> - 
                                    <?= htmlspecialchars($prop['type']) ?> 
                                    (<?= htmlspecialchars($prop['adressep']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="proprietaire-details" class="proprietaire-details">
                        <h4>Détails du propriétaire sélectionné :</h4>
                        <div id="proprietaire-info">
                            <!-- Les détails seront affichés ici via JavaScript -->
                        </div>
                    </div>

                    <div class="user-info">
                        <h4>Propriétaires disponibles :</h4>
                        <?php foreach ($proprietaires as $prop): ?>
                            <p><strong><?= htmlspecialchars($prop['prenomp'] . ' ' . $prop['nomp']) ?></strong> - <?= htmlspecialchars($prop['type']) ?></p>
                            <p>📍 <?= htmlspecialchars($prop['adressep']) ?></p>
                            <p>📞 <?= htmlspecialchars($prop['telp']) ?></p>
                            <?php if ($prop['iban']): ?>
                                <p>💳 <?= htmlspecialchars($prop['iban']) ?></p>
                            <?php endif; ?>
                            <?php if ($prop['site_web']): ?>
                                <p>🌐 <a href="<?= htmlspecialchars($prop['site_web']) ?>" target="_blank"><?= htmlspecialchars($prop['site_web']) ?></a></p>
                            <?php endif; ?>
                            <hr>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <button type="submit"><?= $editUser ? 'Modifier' : 'Créer' ?> Utilisateur</button>
            <?php if ($editUser): ?>
                <button type="button" onclick="window.location.href='?'">Annuler</button>
            <?php endif; ?>
        </form>

        <!-- Liste des utilisateurs existants -->
        <h2>Utilisateurs Existants</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>
                    <td><?= htmlspecialchars($user['nom']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="button-edit" onclick="editUser(<?= $user['id'] ?>)">Modifier</button>
                            <button class="button-delete" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nom']) ?>')">Supprimer</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>