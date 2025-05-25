<?php include 'sidebar.php'; ?>
<div style="margin-left: 220px; padding: 20px;">
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
        .manipulation-item {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .manipulation-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            background-color: white;
        }
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
                    <option value="C001">C001 - 10/01/2024 - Rex (Problème digestif) - 15.00€</option>
                    <option value="C002">C002 - 15/12/2024 - Eclair (Boiterie arrière gauche) - 70.00€</option>
                    <option value="C522">C522 - 12/04/2025 - Rex (Accepté par administrateur) - 15.00€</option>
                </select>
            </div>
            <button type="button" onclick="loadConsultation()" id="load-btn" disabled>Charger la consultation</button>
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
                            <option value="A001">Rex (Chien Labrador) - Dupont Marie</option>
                            <option value="A002">Misty (Chat Siamois) - Martin Sophie</option>
                            <option value="A003">Eclair (Cheval Pur-Sang) - Durand Jean</option>
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
                    <div class="checkbox-item">
                        <input type="checkbox" id="M001" name="manipulations" value="M001">
                        <label for="M001">
                            <strong>M001 - Manipulation lombaire</strong><br>
                            <small>Durée: 20 min - Tarif: 25.00€</small>
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="M002" name="manipulations" value="M002">
                        <label for="M002">
                            <strong>M002 - Manipulation cervicale</strong><br>
                            <small>Durée: 15 min - Tarif: 20.00€</small>
                        </label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="M003" name="manipulations" value="M003">
                        <label for="M003">
                            <strong>M003 - Manipulation thoracique</strong><br>
                            <small>Durée: 30 min - Tarif: 30.00€</small>
                        </label>
                    </div>
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
                        <option value="C001">C001 - 10/01/2024 - Problème digestif (Rex)</option>
                        <option value="C002">C002 - 15/12/2024 - Boiterie arrière gauche (Eclair)</option>
                        <option value="C522">C522 - 12/04/2025 - Accepté par administrateur (Rex)</option>
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

    <script>
        // Données des consultations existantes avec toutes leurs informations
        const consultationsData = {
            'C001': {
                id: 'C001',
                typec: 'Basique',
                datec: '2024-01-10',
                heurec: '10:30',
                dureec: 30,
                diagnostic: 'Problème digestif',
                motif: 'Consultation de routine',
                lieuc: 'Cabinet',
                tarif: 15.00,
                animal: 'A001',
                manipulations: [],
                precedente: null
            },
            'C002': {
                id: 'C002',
                typec: 'Osteopathique',
                datec: '2024-12-15',
                heurec: '15:00',
                dureec: 60,
                diagnostic: 'Boiterie arrière gauche',
                motif: 'Chute recente',
                lieuc: 'Hors Cabinet',
                tarif: 70.00,
                animal: 'A003',
                manipulations: ['M001', 'M003'],
                precedente: 'C001'
            },
            'C522': {
                id: 'C522',
                typec: 'Basique',
                datec: '2025-04-12',
                heurec: '11:20',
                dureec: 30,
                diagnostic: 'Accepté par administrateur',
                motif: 'fish',
                lieuc: 'Cabinet',
                tarif: 15.00,
                animal: 'A001',
                manipulations: [],
                precedente: null
            }
        };

        const manipulationsData = {
            'M001': { name: 'Manipulation lombaire', duration: 20, cost: 25.00 },
            'M002': { name: 'Manipulation cervicale', duration: 15, cost: 20.00 },
            'M003': { name: 'Manipulation thoracique', duration: 30, cost: 30.00 }
        };

        function showMessage(message, type = 'success') {
            const container = document.getElementById('message-container');
            container.innerHTML = `<div class="message ${type}">${message}</div>`;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        function updateSelectedManipulations() {
            const selectedCheckboxes = document.querySelectorAll('input[name="manipulations"]:checked');
            const selectedContainer = document.getElementById('selected-manipulations');
            const selectedList = document.getElementById('selected-list');
            
            if (selectedCheckboxes.length === 0) {
                selectedContainer.style.display = 'none';
                return;
            }

            selectedContainer.style.display = 'block';
            let totalDuration = 0;
            let totalCost = 0;
            let listHTML = '';

            selectedCheckboxes.forEach(checkbox => {
                const manipulation = manipulationsData[checkbox.value];
                totalDuration += manipulation.duration;
                totalCost += manipulation.cost;
                listHTML += `<div>• ${manipulation.name} (${manipulation.duration} min - ${manipulation.cost.toFixed(2)}€)</div>`;
            });

            selectedList.innerHTML = listHTML;
            document.getElementById('total-duration').textContent = totalDuration;
            document.getElementById('total-manipulation-cost').textContent = totalCost.toFixed(2);

            updateTotalCost();
        }

        function updateTotalCost() {
            const baseTarif = parseFloat(document.getElementById('tarif').value) || 0;
            const manipulationCost = parseFloat(document.getElementById('total-manipulation-cost')?.textContent) || 0;
            const totalCost = baseTarif + manipulationCost;
            
            document.getElementById('total-cost').textContent = totalCost.toFixed(2) + '€';
            
            const baseDuration = parseInt(document.getElementById('dureec').value) || 30;
            const manipulationDuration = parseInt(document.getElementById('total-duration')?.textContent) || 0;
            const totalTime = baseDuration + manipulationDuration;
            
            document.getElementById('total-time').textContent = totalTime + ' minutes';
        }

        function previewConsultation() {
            const preview = document.getElementById('consultation-preview');
            preview.style.display = 'block';
            updateTotalCost();
            preview.scrollIntoView({ behavior: 'smooth' });
        }

        function loadConsultation() {
            const selectedId = document.getElementById('select-consultation').value;
            if (!selectedId) return;

            const consultation = consultationsData[selectedId];
            if (!consultation) return;

            // Afficher le formulaire
            document.getElementById('consultation-form-container').style.display = 'block';
            
            // Remplir les champs
            document.getElementById('idconsultation').value = consultation.id;
            document.getElementById('animal').value = consultation.animal;
            document.getElementById('typec').value = consultation.typec;
            document.getElementById('lieuc').value = consultation.lieuc;
            document.getElementById('datec').value = consultation.datec;
            document.getElementById('heurec').value = consultation.heurec;
            document.getElementById('dureec').value = consultation.dureec;
            document.getElementById('tarif').value = consultation.tarif;
            document.getElementById('motif').value = consultation.motif;
            document.getElementById('diagnostic').value = consultation.diagnostic;
            
            // Cocher les manipulations existantes
            document.querySelectorAll('input[name="manipulations"]').forEach(checkbox => {
                checkbox.checked = consultation.manipulations.includes(checkbox.value);
            });

            // Sélectionner la consultation précédente
            if (consultation.precedente) {
                document.getElementById('consultation-precedente').value = consultation.precedente;
            }

            // Mettre à jour les affichages
            updateSelectedManipulations();
            
            showMessage(`Consultation ${consultation.id} chargée avec succès. Vous pouvez maintenant la modifier.`, 'success');
            
            // Scroll vers le formulaire
            document.getElementById('consultation-form-container').scrollIntoView({ behavior: 'smooth' });
        }

        function validateForm() {
            const requiredFields = ['animal', 'typec', 'lieuc', 'datec', 'heurec', 'tarif', 'motif'];
            
            for (let field of requiredFields) {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    showMessage(`Le champ "${element.previousElementSibling.textContent}" est obligatoire.`, 'error');
                    element.focus();
                    return false;
                }
            }

            return true;
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Enable/disable load button based on selection
            document.getElementById('select-consultation').addEventListener('change', function() {
                const loadBtn = document.getElementById('load-btn');
                loadBtn.disabled = !this.value;
            });

            // Add listeners for manipulations
            document.querySelectorAll('input[name="manipulations"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedManipulations);
            });

            // Add listener for tarif changes
            document.getElementById('tarif').addEventListener('input', updateTotalCost);
            document.getElementById('dureec').addEventListener('input', updateTotalCost);

            // Form submission
            document.getElementById('consultation-form').addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!validateForm()) {
                    return;
                }

                // Simulate form update
                const formData = new FormData(this);
                const selectedManipulations = Array.from(document.querySelectorAll('input[name="manipulations"]:checked'))
                    .map(cb => cb.value);

                const consultationId = document.getElementById('idconsultation').value;

                console.log('Modification de la consultation:', {
                    id: consultationId,
                    consultation: Object.fromEntries(formData.entries()),
                    manipulations: selectedManipulations
                });

                showMessage(`Consultation ${consultationId} modifiée avec succès ! Les changements ont été sauvegardés.`, 'success');
                
                // Reset form after successful submission
                setTimeout(() => {
                    resetForm();
                }, 2000);
            });

            // Auto-fill duration based on consultation type
            document.getElementById('typec').addEventListener('change', function() {
                updateTotalCost();
            });

            // Auto-fill tarif based on lieu
            document.getElementById('lieuc').addEventListener('change', function() {
                updateTotalCost();
            });
        });
    </script>
</body>
</html>