<?php
session_start();
include 'database.php';

// Vérification de sécurité absolue : Seuls les admins et organisateurs y ont accès
$stmtUser = $conn->prepare("SELECT role FROM utilisateurs WHERE id = ?");
$stmtUser->execute([$_SESSION['user_id'] ?? 0]);
$userRole = $stmtUser->fetchColumn();

if ($userRole !== 'admin' && $userRole !== 'organisateur') {
    die("Accès refusé. Vous n'avez pas les droits d'administration.");
}

// 1. GESTION DES DESTINATIONS (AJOUT)
if (isset($_POST['ajouter_destination'])) {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $prix = $_POST['prix'];
    $categorie = $_POST['categorie'];
    
    $stmt = $conn->prepare("INSERT INTO destinations (titre, description, prix, categorie) VALUES (?, ?, ?, ?)");
    $stmt->execute([$titre, $description, $prix, $categorie]);
    header("Location: admin.php?msg=dest_added");
    exit;
}

// 2. GESTION DES DESTINATIONS (SUPPRESSION)
if (isset($_GET['supprimer_dest'])) {
    $id_dest = (int)$_GET['supprimer_dest'];
    $stmt = $conn->prepare("DELETE FROM destinations WHERE id = ?");
    $stmt->execute([$id_dest]);
    header("Location: admin.php?msg=dest_deleted");
    exit;
}

// 3. GESTION DES RÔLES UTILISATEURS
if (isset($_POST['changer_role'])) {
    $id_user = (int)$_POST['id_user'];
    $nouveau_role = $_POST['nouveau_role']; // 'client', 'organisateur', ou 'admin'
    
    // On ne permet pas à un organisateur de nommer un admin (Sécurité)
    if ($userRole === 'organisateur' && $nouveau_role === 'admin') {
        die("Droits insuffisants.");
    }
    
    $stmt = $conn->prepare("UPDATE utilisateurs SET role = ? WHERE id = ?");
    $stmt->execute([$nouveau_role, $id_user]);
    header("Location: admin.php?msg=role_updated");
    exit;
}

// Récupération des données pour affichage
$destinations = $conn->query("SELECT * FROM destinations ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$utilisateurs = $conn->query("SELECT id, nom, prenom, email, role FROM utilisateurs ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<link rel="stylesheet" href="auth.css">
<div class="auth-page-wrapper">
    <div class="auth-card-strict" style="max-width: 1000px;">
        <h1 style="color: #4f46e5;">Tableau de bord Administration</h1>
        <p>Connecté en tant que : <strong><?= strtoupper($userRole) ?></strong></p>
        
        <?php if(isset($_GET['msg'])): ?>
            <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                Opération réalisée avec succès !
            </div>
        <?php endif; ?>

        <hr style="margin: 2rem 0;">

        <h2>Gestion du Catalogue (Destinations)</h2>
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <form method="POST" style="background: #f9fafb; padding: 1rem; border: 1px solid #e5e7eb;">
                <h3>Ajouter une destination</h3>
                <div class="champ-saisie-auth">
                    <input type="text" name="titre" placeholder="Titre (ex: Séjour Miami)" required style="width: 100%; border:none; background:transparent;">
                </div>
                <div class="champ-saisie-auth">
                    <input type="number" name="prix" placeholder="Prix de base (€)" required style="width: 100%; border:none; background:transparent;">
                </div>
                <div class="champ-saisie-auth">
                    <input type="text" name="categorie" placeholder="Catégorie (ex: plages)" style="width: 100%; border:none; background:transparent;">
                </div>
                <div class="champ-saisie-auth">
                    <textarea name="description" placeholder="Description de la destination..." required style="width: 100%; border:none; background:transparent;"></textarea>
                </div>
                <button type="submit" name="ajouter_destination" class="btn-auth-submit" style="width: 100%;">Ajouter</button>
            </form>

            <div>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <tr style="background: #f3f4f6; text-align: left;">
                        <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Titre</th>
                        <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Prix</th>
                        <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Action</th>
                    </tr>
                    <?php foreach ($destinations as $d): ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><?= htmlspecialchars($d['titre']) ?></td>
                        <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><?= $d['prix'] ?> €</td>
                        <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">
                            <a href="admin.php?supprimer_dest=<?= $d['id'] ?>" onclick="return confirm('Supprimer cette offre ?');" style="color: red; font-weight: bold; text-decoration: none;">✕ Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <hr style="margin: 2rem 0;">

        <?php if ($userRole === 'admin'): ?>
        <h2>Gestion des Rôles Utilisateurs</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
            <tr style="background: #f3f4f6; text-align: left;">
                <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Nom / Prénom</th>
                <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Email</th>
                <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Rôle Actuel</th>
                <th style="padding: 10px; border-bottom: 2px solid #e5e7eb;">Changer de Rôle</th>
            </tr>
            <?php foreach ($utilisateurs as $u): ?>
            <tr>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><?= htmlspecialchars($u['email']) ?></td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;"><strong><?= strtoupper($u['role']) ?></strong></td>
                <td style="padding: 10px; border-bottom: 1px solid #e5e7eb;">
                    <form method="POST" style="margin: 0; display: flex; gap: 10px;">
                        <input type="hidden" name="id_user" value="<?= $u['id'] ?>">
                        <select name="nouveau_role" style="padding: 5px; border-radius: 4px;">
                            <option value="client" <?= $u['role'] === 'client' ? 'selected' : '' ?>>Client</option>
                            <option value="organisateur" <?= $u['role'] === 'organisateur' ? 'selected' : '' ?>>Organisateur</option>
                            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <button type="submit" name="changer_role" style="background: #10b981; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px;">Valider</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

    </div>
</div>
<?php include 'footer.php'; ?>