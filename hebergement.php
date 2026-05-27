<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'header.php'; 
include 'database.php'; 

$briquesBDD = [];
$destination_choisie = null;

if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
    $destination_choisie = (int)$_SESSION['panier'][0]; 
}

if ($conn !== null && $destination_choisie !== null) {
    try {
        $stmt = $conn->prepare("SELECT * FROM briques_voyage WHERE type_brique = 'hebergement' AND id_destination_parente = :dest ORDER BY id ASC");
        $stmt->execute(['dest' => $destination_choisie]);
        $briquesBDD = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $exception) {
        echo "<div style='color:red; font-weight:bold; padding:1rem;'>Erreur SQL : " . $exception->getMessage() . "</div>";
    }
} else {
    echo "<div style='padding:4rem 2rem; text-align:center; font-family:sans-serif;'>
            <p style='font-size:1.25rem; font-weight:bold; color:#4f46e5;'>⚠️ Aucune destination choisie</p>
            <p style='color:#6b7280;'>Veuillez d'abord sélectionner une destination sur l'accueil.</p>
            <a href='index.php' style='display:inline-block; margin-top:1rem; padding:0.5rem 1rem; background:#4f46e5; color:white; text-decoration:none; border-radius:6px;'>Retour à l'accueil</a>
          </div>";
    include 'footer.php';
    exit;
}
?>

<link rel="stylesheet" href="index.css">

<section class="search-section">
    <div class="search-container">
        <div class="title-bloc">
            <h1>Vos Hébergements & Hôtels d'Exception</h1>
        </div>

        <form action="hebergement.php" method="GET" class="search-form">
            <div class="champ-saisie-bloc">
                <label>Filtre</label>
                <input type="text" id="search-destination" placeholder="Rechercher un établissement..." />
            </div>
            <div class="champ-saisie-bloc">
                <label>Gamme</label>
                <select id="search-transport">
                    <option value="tous">Tous les hébergements</option>
                    <option value="villa">Villas privées</option>
                    <option value="suite">Suites / Palaces</option>
                </select>
            </div>
            <div class="champ-saisie-bloc">
                <label>Date</label>
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
                <h2>Sélection d'Hôtels & Demeures</h2>
            </div>
            <div class="cards-grid" id="catalogue-voyages"></div>
        </div>

        <div>
            <div class="bloc-title">
                <h2>Mon itinéraire</h2>
            </div>
            <div class="panier-container">
                <p class="panier-sub" id="panier-statut">0 hébergement configuré</p>
                <div class="panier-items-list" id="panier-contenu"></div>
                <div class="panier-total-row">
                    <span class="total-label">Prix total estimé</span>
                    <span class="total-price" id="panier-total">0 €</span>
                </div>
                <button class="btn-panier-main" id="btn-valider-panier" disabled>Continuer vers les activités ➔</button>
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
        catalogueContainer.innerHTML = "<p style='font-size: 0.875rem; color:#6b7280; font-weight:700; text-transform:uppercase;'>Aucun hébergement disponible.</p>";
        return;
    }

    voyagesFiltrés.forEach(voyage => {
        const estDansLePanier = panier.some(item => item.id === voyage.id);
        const prixEntier = Math.round(voyage.prix);
        
        let visuelHtml = voyage.image_url 
            ? `<div class="placeholder-image-bloc" style="background-image: url('${voyage.image_url}'); background-size: cover; background-position: center;"><span class="price-badge">${prixEntier} €</span></div>`
            : `<div class="placeholder-image-bloc ${voyage.couleur_css || 'bg-bali'}"><span class="price-badge">${prixEntier} €</span></div>`;

        const cardHtml = `
            <div class="bloc-card">
                ${visuelHtml}
                <div class="card-header">
                    <h3 class="card-title">${voyage.titre}</h3>
                </div>
                <p class="card-description">${voyage.description}</p>
                <button class="btn-action-bloc" onclick="ajouterAuPanier(${voyage.id})" ${estDansLePanier ? 'style="background-color:#4f46e5;" disabled' : ''}>
                    ${estDansLePanier ? 'Sélectionné ✓' : 'Sélectionner cet hôtel'}
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
        panierContenu.innerHTML = `<div style="text-align:center; padding:2rem 0; color:#9ca3af; font-size:0.75rem; font-weight:700; text-transform:uppercase;">Aucun hôtel sélectionné.</div>`;
        panierTotal.textContent = "0 €";
        panierStatut.textContent = "0 hébergement configuré";
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
                    <span class="panier-item-type">HÉBERGEMENT</span>
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
    panierStatut.textContent = `${panier.length} hébergement(s) configuré(s)`;
    btnValiderPanier.disabled = false;
}

btnValiderPanier.addEventListener('click', async () => {
    if (panier.length === 0) return;
    btnValiderPanier.textContent = "Sauvegarde...";
    btnValiderPanier.disabled = true;

    try {
        for (const item of panier) {
            await fetch(`stockage.php?action=ajouter&id=${item.id}`).then(r => r.json());
        }
        // CORRECTION : On redirige bien vers activite.php au lieu d'hebergement.php
        window.location.href = "activite.php"; 
    } catch (err) {
        console.error(err);
        alert("Une erreur est survenue lors de l'enregistrement.");
        btnValiderPanier.textContent = "Continuer vers les activités ➔";
        btnValiderPanier.disabled = false;
    }
});

document.getElementById('btn-filtrer-recherche').addEventListener('click', afficherCatalogue);
afficherCatalogue();
mettreAJourPanier();
</script>

<?php include 'footer.php'; ?>