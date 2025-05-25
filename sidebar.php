<?php
$utilisateur = $_SESSION['utilisateur'] ?? null;
$role = $utilisateur['role'] ?? 'guest';
?>

<div class="sidebar" style="width: 200px; background-color: #f4f4f4; height: 100vh; position: fixed; padding: 20px;">
    <h3>Menu</h3>
    <ul style="list-style: none; padding: 0;">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="ajouter_animal.php">Ajouter Animal</a></li>
        <li><a href="consultation_details.php">Détails Consultation</a></li>

        <?php if ($role === 'admin'): ?>
            <li><a href="creeconsultation.php">Créer Consultation</a></li>
            <li><a href="creeproprietaires.php">Créer Propriétaire</a></li>
            <li><a href="creeutilisateur.php">Créer Utilisateur</a></li>
        <?php endif; ?>

        <li><a href="deconnexion.php">Déconnexion</a></li>
    </ul>
</div>
