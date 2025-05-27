<?php
// Configuration de la base de données
$host = 'localhost';
$dbname = 'saebdd';
$username = 'postgres';
$password = '2606';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
//traitement requete AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'load_consultation':
                loadConsultation($_POST['id'], $pdo);
                break;
            case 'update_consultation':
                // Correction pour gérer les manipulations en tant qu'array
                if (isset($_POST['manipulations'])) {
                    $_POST['manipulations'] = $_POST['manipulations'];
                } else {
                    $_POST['manipulations'] = [];
                }
                updateConsultation($_POST, $pdo);
                break;
        }
    }
    exit;
}

function loadConsultation($id, $pdo) {
    try {
        // Récupérer les données de la consultation
        $stmt = $pdo->prepare("
            SELECT c.*, cons.idanimal, a.noma, a.especea, p.nomp, p.prenomp
            FROM consultation c
            JOIN consulter cons ON c.idconsultation = cons.idconsultation
            JOIN animal a ON cons.idanimal = a.idanimal
            JOIN proprietaire p ON a.idproprietaire = p.idproprietaire
            WHERE c.idconsultation = ?
        ");
        $stmt->execute([$id]);
        $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$consultation) {
            echo json_encode(['success' => false, 'message' => 'Consultation non trouvée']);
            return;
        }
        
        // Récupérer les manipulations associées
        $stmt = $pdo->prepare("
            SELECT m.codemanipulation, m.description, m.duree_estimee, m.tarif_base
            FROM soigner s
            JOIN manipulation m ON s.codemanipulation = m.codemanipulation
            WHERE s.idconsultation = ?
        ");
        $stmt->execute([$id]);
        $manipulations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Récupérer la consultation précédente si elle existe
        $stmt = $pdo->prepare("SELECT idancienneconsultation FROM historique WHERE idconsultation = ?");
        $stmt->execute([$id]);
        $precedente = $stmt->fetchColumn();
        
        $consultation['manipulations'] = array_column($manipulations, 'codemanipulation');
        $consultation['precedente'] = $precedente ?: null;
        
        echo json_encode(['success' => true, 'data' => $consultation]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
}

function updateConsultation($data, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Mettre à jour la consultation
        $stmt = $pdo->prepare("
            UPDATE consultation 
            SET typec = ?, datec = ?, heurec = ?, dureec = ?, diagnostic = ?, motif = ?, lieuc = ?, tarif = ?
            WHERE idconsultation = ?
        ");
        $stmt->execute([
            $data['typec'],
            $data['datec'],
            $data['heurec'],
            $data['dureec'] ?: null,
            $data['diagnostic'],
            $data['motif'],
            $data['lieuc'],
            $data['tarif'],
            $data['idconsultation']
        ]);
        
        // Mettre à jour la table consulter
        $stmt = $pdo->prepare("
            UPDATE consulter 
            SET idanimal = ?, tarif = ?, lieuc = ?
            WHERE idconsultation = ?
        ");
        $stmt->execute([
            $data['animal'],
            $data['tarif'],
            $data['lieuc'],
            $data['idconsultation']
        ]);
        
        // Supprimer les anciennes manipulations
        $stmt = $pdo->prepare("DELETE FROM soigner WHERE idconsultation = ?");
        $stmt->execute([$data['idconsultation']]);
        
        // Ajouter les nouvelles manipulations - CORRECTION ICI
        if (isset($data['manipulations']) && is_array($data['manipulations'])) {
            $stmt = $pdo->prepare("INSERT INTO soigner (idconsultation, codemanipulation) VALUES (?, ?)");
            foreach ($data['manipulations'] as $manipulation) {
                $stmt->execute([$data['idconsultation'], $manipulation]);
            }
        }
        
        // Gérer l'historique
        $stmt = $pdo->prepare("DELETE FROM historique WHERE idconsultation = ?");
        $stmt->execute([$data['idconsultation']]);
        
        if (!empty($data['consultation-precedente'])) {
            $stmt = $pdo->prepare("INSERT INTO historique (idconsultation, idancienneconsultation) VALUES (?, ?)");
            $stmt->execute([$data['idconsultation'], $data['consultation-precedente']]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Consultation mise à jour avec succès']);
        
    } catch(PDOException $e) {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
}




// Récupérer les données pour les listes déroulantes
function getConsultations($pdo) {
    $stmt = $pdo->query("
        SELECT c.idconsultation, c.datec, c.diagnostic, c.tarif, a.noma
        FROM consultation c
        JOIN consulter cons ON c.idconsultation = cons.idconsultation
        JOIN animal a ON cons.idanimal = a.idanimal
        ORDER BY c.datec DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAnimals($pdo) {
    $stmt = $pdo->query("
        SELECT a.idanimal, a.noma, a.especea, a.racea, p.nomp, p.prenomp
        FROM animal a
        JOIN proprietaire p ON a.idproprietaire = p.idproprietaire
        ORDER BY a.noma
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getManipulations($pdo) {
    $stmt = $pdo->query("SELECT * FROM manipulation ORDER BY codemanipulation");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$consultations = getConsultations($pdo);
$animals = getAnimals($pdo);
$manipulations = getManipulations($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Gestion des Consultations</title>
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
        .form-section {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
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
        .button-secondary {
            background-color: #6c757d;
        }
        .button-secondary:hover {
            background-color: #5a6268;
        }
        .button-danger {
            background-color: #dc3545;
        }
        .button-danger:hover {
            background-color: #c82333;
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
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .checkbox-item input[type="checkbox"] {
            width: auto;
        }
        .selected-manipulations {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .consultation-preview {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            margin-top: 15px;
        }
        .preview-section {
            margin-bottom: 10px;
        }
        .preview-label {
            font-weight: bold;
            color: #495057;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="user-info">
            <h1>🏥 Administration Vétérinaire</h1>
            <p><strong>Utilisateur:</strong> Admin (tayoken)</p>
            <p><strong>Rôle:</strong> Administrateur</p>
            <a href="#" class="logout-btn">Déconnexion</a>
        </div>

        <div id="message-container"></div>

        <div class="form-section">
            <h2>🔍 Sélection de la Consultation à Modifier</h2>
            <div class="form-group">
                <label for="select-consultation">Consultation à modifier *</label>
                <select id="select-consultation" required>
                    <option value="">-- Sélectionner une consultation --</option>
                    <?php foreach($consultations as $consultation): ?>
                        <option value="<?= htmlspecialchars($consultation['idconsultation']) ?>">
                            <?= htmlspecialchars($consultation['idconsultation']) ?> - 
                            <?= date('d/m/Y', strtotime($consultation['datec'])) ?> - 
                            <?= htmlspecialchars($consultation['noma']) ?> 
                            (<?= htmlspecialchars($consultation['diagnostic']) ?>) - 
                            <?= number_format($consultation['tarif'], 2) ?>€
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" onclick="loadConsultation()" id="load-btn" disabled>Charger la consultation</button>
            <div class="loading" id="loading">Chargement en cours...</div>
        </div>

        <div id="consultation-form-container" style="display: none;">
            <h2>📝 Modification de la Consultation</h2>

            <form id="consultation-form">
                <!-- Informations de base -->
                <div class="form-section">
                    <h3>🔍 Informations Générales</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="idconsultation">ID Consultation *</label>
                            <input type="text" id="idconsultation" name="idconsultation" required readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="form-group">
                            <label for="animal">Animal concerné *</label>
                            <select id="animal" name="animal" required>
                                <option value="">-- Sélectionner un animal --</option>
                                <?php foreach($animals as $animal): ?>
                                    <option value="<?= htmlspecialchars($animal['idanimal']) ?>">
                                        <?= htmlspecialchars($animal['noma']) ?> 
                                        (<?= htmlspecialchars($animal['especea']) ?> <?= htmlspecialchars($animal['racea']) ?>) - 
                                        <?= htmlspecialchars($animal['nomp']) ?> <?= htmlspecialchars($animal['prenomp']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="typec">Type de consultation *</label>
                            <select id="typec" name="typec" required>
                                <option value="">-- Sélectionner le type --</option>
                                <option value="Basique">Basique</option>
                                <option value="Osteopathique">Ostéopathique</option>
                                <option value="Homeopathique">Homéopathique</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lieuc">Lieu de consultation *</label>
                            <select id="lieuc" name="lieuc" required>
                                <option value="">-- Sélectionner le lieu --</option>
                                <option value="Cabinet">Cabinet</option>
                                <option value="Hors Cabinet">Hors Cabinet</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="datec">Date de consultation *</label>
                            <input type="date" id="datec" name="datec" required>
                        </div>
                        <div class="form-group">
                            <label for="heurec">Heure de consultation *</label>
                            <input type="time" id="heurec" name="heurec" required>
                        </div>
                        <div class="form-group">
                            <label for="dureec">Durée estimée (minutes)</label>
                            <input type="number" id="dureec" name="dureec" min="15" max="180" placeholder="30">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tarif">Tarif de base (€) *</label>
                        <input type="number" id="tarif" name="tarif" step="0.01" min="0" required placeholder="15.00">
                    </div>
                </div>

                <!-- Détails médicaux -->
                <div class="form-section">
                    <h3>🩺 Détails Médicaux</h3>
                    <div class="form-group">
                        <label for="motif">Motif de la consultation *</label>
                        <textarea id="motif" name="motif" required placeholder="Décrivez le motif de la consultation..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="diagnostic">Diagnostic</label>
                        <textarea id="diagnostic" name="diagnostic" placeholder="Diagnostic établi lors de la consultation..."></textarea>
                    </div>
                </div>

                <!-- Manipulations -->
                <div class="form-section">
                    <h3>🔧 Manipulations et Traitements</h3>
                    <p>Sélectionnez les manipulations à effectuer lors de cette consultation :</p>
                    
                    <div class="checkbox-group" id="manipulations-list">
                        <?php foreach($manipulations as $manipulation): ?>
                            <div class="checkbox-item">
                                <input type="checkbox" id="<?= $manipulation['codemanipulation'] ?>" 
                                       name="manipulations" value="<?= $manipulation['codemanipulation'] ?>">
                                <label for="<?= $manipulation['codemanipulation'] ?>">
                                    <strong><?= $manipulation['codemanipulation'] ?> - <?= htmlspecialchars($manipulation['description']) ?></strong><br>
                                    <small>Durée: <?= $manipulation['duree_estimee'] ?> min - Tarif: <?= number_format($manipulation['tarif_base'], 2) ?>€</small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="selected-manipulations" id="selected-manipulations" style="display: none;">
                        <h4>📋 Manipulations sélectionnées:</h4>
                        <div id="selected-list"></div>
                        <p><strong>Durée totale estimée:</strong> <span id="total-duration">0</span> minutes</p>
                        <p><strong>Coût total des manipulations:</strong> <span id="total-manipulation-cost">0.00</span>€</p>
                    </div>
                </div>

                <!-- Historique -->
                <div class="form-section">
                    <h3>📚 Lien avec consultation précédente (optionnel)</h3>
                    <div class="form-group">
                        <label for="consultation-precedente">Consultation précédente liée</label>
                        <select id="consultation-precedente" name="consultation-precedente">
                            <option value="">-- Aucune consultation précédente --</option>
                            <?php foreach($consultations as $consultation): ?>
                                <option value="<?= htmlspecialchars($consultation['idconsultation']) ?>">
                                    <?= htmlspecialchars($consultation['idconsultation']) ?> - 
                                    <?= date('d/m/Y', strtotime($consultation['datec'])) ?> - 
                                    <?= htmlspecialchars($consultation['diagnostic']) ?> (<?= htmlspecialchars($consultation['noma']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="warning" style="margin-top: 10px;">
                        <strong>Info:</strong> Si vous sélectionnez une consultation précédente, un lien sera créé dans l'historique pour faciliter le suivi médical.
                    </div>
                </div>

                <!-- Récapitulatif -->
                <div class="consultation-preview" id="consultation-preview" style="display: none;">
                    <h3>📊 Récapitulatif de la consultation</h3>
                    <div class="preview-section">
                        <span class="preview-label">Coût total estimé:</span> 
                        <span id="total-cost" style="font-size: 1.2em; font-weight: bold; color: #28a745;">0.00€</span>
                    </div>
                    <div class="preview-section">
                        <span class="preview-label">Durée totale:</span> 
                        <span id="total-time">0 minutes</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="button" class="button-secondary" onclick="resetForm()">Annuler</button>
                    <button type="button" onclick="previewConsultation()">Aperçu</button>
                    <button type="submit">Sauvegarder les modifications</button>
                </div>
            </form>
        </div>
    </div>
</html>
<script>
// Variables globales
let selectedManipulations = [];
let manipulationsData = <?= json_encode($manipulations) ?>;

// Activer le bouton de chargement quand une consultation est sélectionnée
document.getElementById('select-consultation').addEventListener('change', function() {
    const loadBtn = document.getElementById('load-btn');
    loadBtn.disabled = this.value === '';
});

// Fonction pour charger une consultation
function loadConsultation() {
    const consultationId = document.getElementById('select-consultation').value;
    if (!consultationId) return;

    showLoading(true);
    showMessage('', ''); // Clear messages

    const formData = new FormData();
    formData.append('action', 'load_consultation');
    formData.append('id', consultationId);

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        showLoading(false);
        if (data.success) {
            populateForm(data.data);
            document.getElementById('consultation-form-container').style.display = 'block';
            showMessage('Consultation chargée avec succès', 'success');
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        showLoading(false);
        showMessage('Erreur de connexion: ' + error.message, 'error');
    });
}

// Fonction pour peupler le formulaire avec les données
function populateForm(data) {
    // Informations de base
    document.getElementById('idconsultation').value = data.idconsultation;
    document.getElementById('animal').value = data.idanimal;
    document.getElementById('typec').value = data.typec;
    document.getElementById('lieuc').value = data.lieuc;
    document.getElementById('datec').value = data.datec;
    document.getElementById('heurec').value = data.heurec;
    document.getElementById('dureec').value = data.dureec || '';
    document.getElementById('tarif').value = data.tarif;
    document.getElementById('motif').value = data.motif || '';
    document.getElementById('diagnostic').value = data.diagnostic || '';
    
    // Consultation précédente
    if (data.precedente) {
        document.getElementById('consultation-precedente').value = data.precedente;
    }

    // Manipulations
    selectedManipulations = data.manipulations || [];
    updateManipulationCheckboxes();
    updateManipulationSummary();
}

// Fonction pour mettre à jour les cases à cocher des manipulations
function updateManipulationCheckboxes() {
    const checkboxes = document.querySelectorAll('input[name="manipulations"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectedManipulations.includes(checkbox.value);
    });
}

// Gestionnaire d'événements pour les manipulations
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des manipulations
    const manipulationCheckboxes = document.querySelectorAll('input[name="manipulations"]');
    manipulationCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                if (!selectedManipulations.includes(this.value)) {
                    selectedManipulations.push(this.value);
                }
            } else {
                selectedManipulations = selectedManipulations.filter(m => m !== this.value);
            }
            updateManipulationSummary();
        });
    });

    // Soumission du formulaire
    document.getElementById('consultation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        updateConsultation();
    });
});

// Fonction pour mettre à jour le résumé des manipulations
function updateManipulationSummary() {
    const selectedContainer = document.getElementById('selected-manipulations');
    const selectedList = document.getElementById('selected-list');
    const totalDuration = document.getElementById('total-duration');
    const totalManipulationCost = document.getElementById('total-manipulation-cost');

    if (selectedManipulations.length === 0) {
        selectedContainer.style.display = 'none';
        return;
    }

    selectedContainer.style.display = 'block';
    
    let html = '';
    let totalDurationValue = 0;
    let totalCost = 0;

    selectedManipulations.forEach(code => {
        const manipulation = manipulationsData.find(m => m.codemanipulation === code);
        if (manipulation) {
            html += `<div style="margin-bottom: 5px;">
                        <strong>${manipulation.codemanipulation}</strong> - ${manipulation.description}
                        <small>(${manipulation.duree_estimee} min - ${parseFloat(manipulation.tarif_base).toFixed(2)}€)</small>
                     </div>`;
            totalDurationValue += parseInt(manipulation.duree_estimee);
            totalCost += parseFloat(manipulation.tarif_base);
        }
    });

    selectedList.innerHTML = html;
    totalDuration.textContent = totalDurationValue;
    totalManipulationCost.textContent = totalCost.toFixed(2);

    updatePreview();
}

// Fonction pour mettre à jour l'aperçu
function updatePreview() {
    const baseTarif = parseFloat(document.getElementById('tarif').value) || 0;
    const manipulationCost = parseFloat(document.getElementById('total-manipulation-cost').textContent) || 0;
    const totalCost = baseTarif + manipulationCost;
    
    document.getElementById('total-cost').textContent = totalCost.toFixed(2) + '€';
    document.getElementById('total-time').textContent = document.getElementById('total-duration').textContent + ' minutes';
}

// Fonction pour afficher l'aperçu
function previewConsultation() {
    updatePreview();
    document.getElementById('consultation-preview').style.display = 'block';
}

// Fonction pour mettre à jour la consultation
function updateConsultation() {
    const form = document.getElementById('consultation-form');
    const formData = new FormData(form);
    
    // Ajouter l'action
    formData.append('action', 'update_consultation');
    
    // Ajouter les manipulations sélectionnées
    selectedManipulations.forEach(manipulation => {
        formData.append('manipulations[]', manipulation);
    });

    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            // Recharger la liste des consultations
            location.reload();
        } else {
            showMessage(data.message, 'error');
        }
    })
    .catch(error => {
        showMessage('Erreur de connexion: ' + error.message, 'error');
    });
}

// Fonction pour réinitialiser le formulaire
function resetForm() {
    document.getElementById('consultation-form').reset();
    document.getElementById('consultation-form-container').style.display = 'none';
    document.getElementById('select-consultation').value = '';
    document.getElementById('load-btn').disabled = true;
    selectedManipulations = [];
    updateManipulationCheckboxes();
    updateManipulationSummary();
    showMessage('', '');
}

// Fonction pour afficher/masquer le chargement
function showLoading(show) {
    document.getElementById('loading').style.display = show ? 'block' : 'none';
}

// Fonction pour afficher les messages
function showMessage(message, type) {
    const container = document.getElementById('message-container');
    if (!message) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = `<div class="message ${type}">${message}</div>`;
    
    // Auto-hide success messages
    if (type === 'success') {
        setTimeout(() => {
            container.innerHTML = '';
        }, 5000);
    }
}

// Mise à jour du coût total quand le tarif de base change
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('tarif').addEventListener('input', updatePreview);
});
</script>