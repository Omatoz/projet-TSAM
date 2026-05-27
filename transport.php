<?php 
include 'header.php'; 
include 'database.php'; 

$briquesBDD = [];
$destination_choisie = null;

if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
    $destination_choisie = (int)$_SESSION['panier'][0]; 
}

if ($conn !== null && $destination_choisie !== null) {
    try {
        $stmt = $conn->prepare("SELECT * FROM briques_voyage WHERE type_brique = 'transport' AND id_destination_parente = :dest ORDER BY id ASC");
        $stmt->execute(['dest' => $destination_choisie]);
        $briquesBDD = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $exception) {
        echo "<div style='color:red; font-weight:bold; padding:1rem;'>Erreur SQL : " . $exception->getMessage() . "</div>";
    }
} else {
    echo "<div style='padding:4rem 2rem; font-weight:bold; color:#4f46e5; text-align:center; font-family:sans-serif;'>
            <p style='font-size:1.5rem; margin-bottom:1rem;'>👑 VoyageVista Élite</p>
            <p style='color:#6b7280; font-weight:normal;'>Veuillez sélectionner votre destination sur notre catalogue d'accueil avant de configurer vos transports.</p>
            <a href='index.php' style='display:inline-block; margin-top:1.5rem; padding:0.75rem 1.5rem; background:#4f46e5; color:white; text-decoration:none; border-radius:8px;'>Voir le catalogue</a>
          </div>";
    include 'footer.php';
    exit;
}
?>

<link rel="stylesheet" href="index.css">

<section class="search-section">
    <div class="search-container">
        <div class="title-bloc">
            <h1>Vos Transferts & Vols Privés</h1>
        </div>

        <form action="transport.php" method="GET" class="search-form">
            <div class="champ-saisie-bloc">
                <label>Recherche</label>
                <input type="text" id="search-destination" placeholder="Filtrer les transports..." />
            </div>
            <div class="champ-saisie-bloc">
                <label>Gamme Élite</label>
                <select id="search-transport">
                    <option value="tous">Toutes les options</option>
                    <option value="vol">Vols / Jets</option>
                    <option value="privé">Privé</option>
                </select>
            </div>
            <div class="champ-saisie-bloc">
                <label>Date de départ</label>
                <input type="date" id="search-date" value="2026-06-15" />
            </div>
            <div>
                <button type="button" id="btn-filtrer-recherche" class="btn-submit-recherche">Rechercher</button>
            </div>
        </form>
    </div>
</section>

<section class="main-content-section">
    <div class="main-grid">
        
        <div class="col-catalogue">
            <div class="bloc-title">
                <h2>Options de Transport VIP</h2>
            </div>
            <div class="cards-grid" id="catalogue-voyages"></div>
        </div>

        <div>
            <div class="bloc-title">
                <h2>Mon itinéraire</h2>
            </div>
            <div class="panier-container">
                <p class="panier-sub" id="panier-statut">0 transport configuré</p>
                <div class="panier-items-list" id="panier-contenu"></div>
                <div class="panier-total-row">
                    <span class="total-label">Prix total estimé</span>
                    <span class="total-price" id="panier-total">0 €</span>
                </div>
                <button class="btn-panier-main" id="btn-valider-panier" disabled>Continuer vers les hébergements ➔</button>
            </div>
        </div>

    </div>
</section>

<script>
const voyagesData = <?php echo json_encode($briquesBDD); ?>;
let panier = [];

const catalogueContainer = document.getElementById('catalogue-voyages');
const panierContenu = document.getElementById('panier-contenu');
const panierTotal = document.getElementById('panier-total');
const panierStatut = document.getElementById('panier-statut');
const btnValiderPanier = document.getElementById('btn-valider-panier');

function afficherCatalogue() {
    catalogueContainer.innerHTML = ""; 
    const texteRecherche = document.getElementById('search-destination').value.toLowerCase();
    const filtreTransport = document.getElementById('search-transport').value;

    const voyagesFiltrés = voyagesData.filter(v => {
        const matchTexte = v.titre.toLowerCase().includes(texteRecherche) || v.description.toLowerCase().includes(texteRecherche);
        let matchTransport = true;
        if (filtreTransport !== "tous") {
            matchTransport = v.titre.toLowerCase().includes(filtreTransport) || v.description.toLowerCase().includes(filtreTransport);
        }
        return matchTexte && matchTransport;
    });

    if (voyagesFiltrés.length === 0) {
        catalogueContainer.innerHTML = "<p style='font-size: 0.875rem; color:#6b7280; font-weight:700; text-transform:uppercase;'>Aucune option disponible.</p>";
        return;
    }

    voyagesFiltrés.forEach(voyage => {
        const estDansLePanier = panier.some(item => item.id === voyage.id);
        const prixEntier = Math.round(voyage.prix);
        
        let visuelHtml = voyage.image_url 
            ? `<div class="placeholder-image-bloc" style="background-image: url('${voyage.image_url}'); background-size: cover; background-position: center;"><span class="price-badge">${prixEntier} €</span></div>`
            : `<div class="placeholder-image-bloc ${voyage.couleur_css || 'bg-transport'}"><span class="price-badge">${prixEntier} €</span></div>`;

        const cardHtml = `
            <div class="bloc-card">
                ${visuelHtml}
                <div class="card-header">
                    <h3 class="card-title">${voyage.titre}</h3>
                </div>
                <p class="card-description">${voyage.description}</p>
                <button class="btn-action-bloc" onclick="ajouterAuPanier(${voyage.id})" ${estDansLePanier ? 'style="background-color:#4f46e5;" disabled' : ''}>
                    ${estDansLePanier ? 'Sélectionné ✓' : 'Sélectionner ce transport'}
                </button>
            </div>
        `;
        catalogueContainer.insertAdjacentHTML('beforeend', cardHtml);
    });
}

window.ajouterAuPanier = function(id) {
    const voyageSelectionne = voyagesData.find(v => v.id == id);
    if (voyageSelectionne && !panier.some(item => item.id == id)) {
        panier.push(voyageSelectionne);
        mettreAJourPanier();
        afficherCatalogue();
    }
}

window.retirerDuPanier = function(id) {
    panier = panier.filter(item => item.id != id);
    mettreAJourPanier();
    afficherCatalogue();
}

function mettreAJourPanier() {
    panierContenu.innerHTML = ""; 
    if (panier.length === 0) {
        panierContenu.innerHTML = `<div style="text-align:center; padding:2rem 0; color:#9ca3af; font-size:0.75rem; font-weight:700; text-transform:uppercase;">Aucun transport sélectionné.</div>`;
        panierTotal.textContent = "0 €";
        panierStatut.textContent = "0 transport configuré";
        btnValiderPanier.disabled = true;
        return;
    }

    let total = 0;
    panier.forEach(item => {
        const prixItem = Math.round(item.prix);
        total += prixItem;
        const itemHtml = `
            <div class="panier-item-row">
                <div>
                    <p class="panier-item-name">${item.titre}</p>
                    <span class="panier-item-type">TRANSPORT</span>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span class="panier-item-price">${prixItem} €</span>
                    <button onclick="retirerDuPanier(${item.id})" style="background:none; border:none; color:#ef4444; font-weight:800; cursor:pointer; font-size:11px;">✕</button>
                </div>
            </div>
        `;
        panierContenu.insertAdjacentHTML('beforeend', itemHtml);
    });

    panierTotal.textContent = total + " €";
    panierStatut.textContent = `${panier.length} transport(s) configuré(s)`;
    btnValiderPanier.disabled = false;
}

// Remplace l'ancien code du bouton de validation par celui-ci :
btnValiderPanier.addEventListener('click', async () => {
    if (panier.length === 0) return;
    
    btnValiderPanier.textContent = "Sauvegarde en cours...";
    btnValiderPanier.disabled = true;

    try {
        // Boucle "for...of" : Elle attend sagement la fin de chaque fetch avant de passer au suivant !
        for (const item of panier) {
            const reponse = await fetch(`stockage.php?action=ajouter&id=${item.id}`);
            await reponse.json(); // On attend la réponse positive du serveur
        }
        
        // Une fois que TOUTES les briques ont été enregistrées l'une après l'autre : on change de page
        window.location.href = "hebergement.php"; // (À adapter en "activite.php" ou "panier.php" selon la page)
        
    } catch (err) {
        console.error("Erreur d'enregistrement :", err);
        alert("Une brique n'a pas pu être sauvegardée. Veuillez réessayer.");
        btnValiderPanier.textContent = "Continuer ➔";
        btnValiderPanier.disabled = false;
    }
});

document.getElementById('btn-filtrer-recherche').addEventListener('click', afficherCatalogue);
afficherCatalogue();
mettreAJourPanier();
</script>

<?php include 'footer.php'; ?>