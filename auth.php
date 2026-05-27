<?php 
include 'header.php'; 
?>

    <link rel="stylesheet" href="auth.css">

<div class="auth-page-wrapper">
    <div class="auth-card-strict">
        
        <div>
            <h2 class="auth-header-title">Connexion VoyageVista</h2>
            <p class="auth-header-sub">Veuillez entrer vos identifiants d'accès</p>
        </div>

        <form action="" method="POST" class="auth-form-vertical">
            
            <div class="champ-saisie-auth">
                <label>Identifiant / Email</label>
                <input type="email" name="email" required placeholder="nom@domaine.com" />
            </div>

            <div class="champ-saisie-auth">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="••••••••" />
            </div>

            <button type="submit" class="btn-auth-submit">Valider la connexion</button>
            
        </form>

        <div class="auth-footer-box">
            <p class="auth-meta-text">
                Pas encore enregistré ? <a href="inscription.php" class="auth-link-action">Créer un compte</a>
            </p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>