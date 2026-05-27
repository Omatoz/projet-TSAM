<?php
// Connexion à la base de données et démarrage de la session via ton fichier central
include 'database.php';

$erreur = "";
$succes = "";

// Traitement du formulaire d'inscription quand l'utilisateur clique sur Soumettre
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des données reçues pour éviter les failles ou injections
    $nom = strip_tags(trim($_POST['nom']));
    $prenom = strip_tags(trim($_POST['prenom']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Vérification de base que tous les champs sont remplis
    if (empty($nom) || empty($prenom) || empty($email) || empty('password')) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } else {
        if ($conn !== null) {
            try {
                // Vérifier si l'email existe déjà dans la base
                $checkEmail = $conn->prepare("SELECT id FROM utilisateurs WHERE email = :email");
                $checkEmail->execute(['email' => $email]);
                
                if ($checkEmail->rowCount() > 0) {
                    $erreur = "Cette adresse email est déjà associée à un compte.";
                } else {
                    // Sécurité : Hachage du mot de passe (ne JAMAIS stocker un mot de passe en texte clair !)
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertion du nouvel utilisateur
                    $statutParDefaut = 'client'; // Rôle de base
                    $insert = $conn->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, date_creation) VALUES (:nom, :prenom, :email, :mdp, :role, NOW())");
                    
                    $insert->execute([
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'email' => $email,
                        'mdp' => $passwordHash,
                        'role' => $statutParDefaut
                    ]);
                    
                    $succes = "Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.";
                }
            } catch (PDOException $e) {
                $erreur = "Erreur lors de l'inscription : " . $e->getMessage();
            }
        }
    }
}

include 'header.php'; 
?>

<section class="auth-section" style="padding: 4rem 1rem; min-height: 80vh; display: flex; align-items: center; justify-content: center; background-color: #f3f4f6;">
    <div class="auth-container" style="background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; max-width: 450px;">
        
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 style="font-size: 1.75rem; color: #1f2937; font-weight: 800; margin-bottom: 0.5rem;">Rejoindre VoyageVista</h2>
            <p style="color: #6b7280; font-size: 0.875rem;">Créez votre compte pour configurer et sauvegarder vos itinéraires.</p>
        </div>

        <?php if (!empty($erreur)): ?>
            <div style="background-color: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 6px; font-size: 0.875rem; margin-bottom: 1.5rem; border: 1px solid #fee2e2;">
                <?= $erreur; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div style="background-color: #f0fdf4; color: #166534; padding: 1rem; border-radius: 6px; font-size: 0.875rem; margin-bottom: 1.5rem; border: 1px solid #dcfce7;">
                <?= $succes; ?>
                <div style="margin-top: 0.5rem;"><a href="auth.php" style="color: #4f46e5; font-weight: 700; text-decoration: underline;">Se connecter maintenant ➔</a></div>
            </div>
        <?php endif; ?>

        <form action="inscription.php" method="POST" style="display: flex; flex-direction: column; gap: 1.25rem;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #374151;">Nom</label>
                    <input type="text" name="nom" required placeholder="NOM" style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;" value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
                </div>
                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                    <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #374151;">Prénom</label>
                    <input type="text" name="prenom" required placeholder="prenom" style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;" value="<?= isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>">
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #374151;">Adresse Email</label>
                <input type="email" name="email" required placeholder="nom@domaine.com" style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                <label style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #374151;">Mot de passe</label>
                <input type="password" name="password" required placeholder="••••••••" style="padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem;">
            </div>

            <button type="submit" style="background-color: #4f46e5; color: white; padding: 0.75rem; border: none; border-radius: 6px; font-weight: 700; font-size: 0.875rem; cursor: pointer; transition: background-color 0.2s; margin-top: 0.5rem;">
                Créer mon compte
            </button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; border-top: 1px solid #e5e7eb; padding-top: 1.25rem;">
            <p style="font-size: 0.875rem; color: #6b7280;">
                Déjà inscrit ? <a href="auth.php" style="color: #4f46e5; font-weight: 700; text-decoration: none;">Identifiez-vous ici</a>
            </p>
        </div>

    </div>
</section>

<?php include 'footer.php'; ?>