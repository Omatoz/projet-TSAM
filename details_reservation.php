<?php
session_start();
include 'database.php';
include 'header.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: auth.php');
    exit;
}

$id_res = (int)$_GET['id'];

// Récupération de la réservation AVEC le nombre de voyageurs
$stmt = $conn->prepare("SELECT r.*, d.titre as destination_nom 
    FROM reservations r 
    LEFT JOIN destinations d ON r.id_destination = d.id 
    WHERE r.id = ? AND r.id_utilisateur = ?");
$stmt->execute([$id_res, $_SESSION['user_id']]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$res) die("Réservation introuvable.");

// Récupération des items liés
$stmtDetails = $conn->prepare("SELECT * FROM details_reservation WHERE id_reservation = ?");
$stmtDetails->execute([$id_res]);
$details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

function getDetailItem($conn, $type, $id) {
    $tables = [
        'transport'   => ['table' => 'transports', 'prix' => 'prix'],
        'hebergement' => ['table' => 'hebergements', 'prix' => 'prix_nuit'],
        'activite'    => ['table' => 'activites', 'prix' => 'prix_ticket'],
        'destination' => ['table' => 'destinations', 'prix' => 'prix'],
        'package'     => ['table' => 'sejours', 'prix' => 'prix']
    ];
    if (!isset($tables[$type])) return null;
    $config = $tables[$type];
    $sql = "SELECT titre, description, " . $config['prix'] . " as prix FROM " . $config['table'] . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<link rel="stylesheet" href="auth.css"> 
<style>
    .reservation-header { background: #f3f4f6; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 5px solid #4f46e5; }
    .grid-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
    .card-item { border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.5rem; background: #fff; transition: 0.3s; }
    .badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .badge-transport { background: #dbeafe; color: #1e40af; }
    .badge-hebergement { background: #d1fae5; color: #065f46; }
    .badge-activite { background: #fef3c7; color: #92400e; }
    .badge-destination, .badge-package { background: #fee2e2; color: #991b1b; }
    .price-txt { font-size: 1.25rem; font-weight: 700; color: #4f46e5; margin-top: 1rem; }
</style>

<div class="auth-page-wrapper">
    <div class="auth-card-strict" style="max-width: 900px;">
        <a href="utilisateur.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">← Retour au tableau de bord</a>

        <div class="reservation-header" style="margin-top: 1rem;">
            <h1 style="margin: 0; font-size: 1.5rem;">Itinéraire : <?= htmlspecialchars($res['destination_nom'] ?? 'Séjour personnalisé') ?></h1>
            <p style="margin: 5px 0 0; color: #4b5563;">
                Réservation #<?= $id_res ?> • Statut : <strong><?= ucfirst($res['statut']) ?></strong><br>
                <span style="color: #10b981; font-weight: bold;">👤 Prévu pour <?= $res['nb_voyageurs'] ?> voyageur(s)</span>
            </p>
            <div class="price-txt"><?= number_format($res['total_prix'], 2) ?> €</div>
        </div>

        <div class="grid-container">
            <?php foreach ($details as $item): 
                $data = getDetailItem($conn, $item['type_item'], $item['id_item']);
                if ($data): 
                    $type = htmlspecialchars($item['type_item']);
                    ?>
                    <div class="card-item">
                        <span class="badge badge-<?= $type ?>"><?= $type ?></span>
                        <h3 style="margin: 0.75rem 0;"><?= htmlspecialchars($data['titre'] ?? 'Service') ?></h3>
                        
                        <?php if (!empty($item['date_debut'])): ?>
                            <p style="font-size: 0.8rem; color: #4f46e5; font-weight: bold;">
                                📅 Du <?= htmlspecialchars($item['date_debut']) ?> au <?= htmlspecialchars($item['date_fin'] ?? $item['date_debut']) ?>
                            </p>
                        <?php endif; ?>

                        <p style="font-size: 0.9rem; color: #6b7280; line-height: 1.5;">
                            <?= htmlspecialchars(substr($data['description'] ?? 'Pas de description.', 0, 100)) ?>...
                        </p>
                    </div>
                <?php endif; endforeach; ?>
        </div>

        <div style="margin-top: 2rem; text-align: center;">
            <button onclick="window.print()" class="btn-auth-submit" style="background-color: #10b981; border: none; cursor: pointer;">
                Imprimer l'itinéraire
            </button>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>