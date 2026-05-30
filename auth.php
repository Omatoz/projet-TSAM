<?php
// 1. On démarre la mémoire tampon pour éviter tout bug d'envoi d'en-tête
ob_start(); 
session_start();
include 'database.php'; 

// Si déjà connecté, on dégage vers l'espace membre
if (isset($_SESSION['user_id'])) {
    header('Location: utilisateur.php');
    exit;
}

$message_erreur = "";

// 2. Traitement du formulaire (logique uniquement)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if ($conn !== null) {
        $stmt = $conn->prepare("SELECT id, mot_de_passe FROM utilisateurs WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];

            // Redirection (Si un redirect est présent dans l'URL, on l'utilise)
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'utilisateur.php';
            header("Location: $redirect");
            exit;
        } else {
            $message_erreur = "Identifiant ou mot de passe incorrect.";
        }
    }
}

// 3. Maintenant on inclut le header (car on a fini nos redirections)
include 'header.php';
?>

<link rel="stylesheet" href="auth.css">

<div class="auth-page-wrapper">
    <div class="auth-card-strict">

        <?php if (isset($_GET['message']) && $_GET['message'] == 'deleted'): ?>
            <p style="color: green; text-align: center; font-size: 0.8rem; margin-bottom: 10px;">
                Votre compte a bien été supprimé.
            </p>
        <?php endif; ?>

        <?php if (!empty($message_erreur)): ?>
            <p style="color: red; text-align: center; font-size: 0.9rem; margin-top: 10px;">
                <?php echo $message_erreur; ?>
            </p>
        <?php endif; ?>

        <div>
            <h2 class="auth-header-title">Connexion</h2>
            <p class="auth-header-sub">Veuillez entrer vos identifiants d'accès</p>
        </div>

        <form action="auth.php?redirect=<?php echo urlencode($_GET['redirect'] ?? 'utilisateur.php'); ?>" method="POST" class="auth-form-vertical">

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