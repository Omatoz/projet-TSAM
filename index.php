<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'header.php'; 
include 'database.php';

$destinations = [];
$sejours = [];
$notifications = [];

if ($conn !== null) {
    try {
        $stmt = $conn->query("SELECT id, titre, description, prix, categorie, couleur_css, image_url FROM destinations ORDER BY id ASC");
        $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtPack = $conn->query("SELECT id, titre, description, prix, categorie, image_url FROM sejours ORDER BY id ASC");
        $sejours = $stmtPack->fetchAll(PDO::FETCH_ASSOC);
        if (isset($_SESSION['user_id'])) {
            $stmtNotifs = $conn->prepare("SELECT * FROM notifications WHERE id_utilisateur = ? AND lu = 0 ORDER BY date_creation DESC");
            $stmtNotifs->execute([$_SESSION['user_id']]);
            $notifications = $stmtNotifs->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch(PDOException $e) { echo "Erreur : " . $e->getMessage(); }
}
?>

<link rel="stylesheet" href="index.css">

<style>
    .toggle-container { display: flex; justify-content: center; gap: 10px; margin-bottom: 2rem; background: #f3f4f6; padding: 5px; border-radius: 30px; width: fit-content; margin-left: auto; margin-right: auto; }
    .btn-toggle { padding: 10px 25px; border-radius: 25px; font-weight: 700; font-size: 0.9rem; border: none; cursor: pointer; transition: 0.3s; background: transparent; color: #6b7280; }
    .btn-toggle.active { background: #4f46e5; color: white; box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2); }
</style>

<section class="search-section">
    <div class="search-container">
        <div class="title-bloc"><h1>Planifiez. Explorez. Vivez.</h1></div>
        <form action="index.php" method="GET" class="search-form">
            <div class="champ-saisie-bloc">
                <label>Destination</label>
                <input type="text" id="search-destination" placeholder="Où allez-vous ?" oninput="afficherCatalogue()" />
            </div>
            <div class="champ-saisie-bloc">
                <label>Voyageurs</label>
                <input type="number" id="search-voyageurs" min="1" max="20" value="1" onchange="afficherCatalogue()" />
            </div>
            <div class="champ-saisie-bloc" style="border:none;">
                <button type="button" id="btn-filtrer-recherche" class="btn-submit-recherche">Rechercher</button>
            </div>
        </form>
    </div>
</section>

<section class="filter-section">
    <div class="toggle-container">
        <button id="btn-alacarte" class="btn-toggle active">À la carte</button>
        <button id="btn-packages" class="btn-toggle">Packages Complets</button>
    </div>

    <div class="filter-container">
        <span class="filter-label">Catégories :</span>
        <div class="filter-item"><button class="btn-categorie-rond actif" data-categorie="tous">TO</button><span class="filter-text">Tous</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="plages">PL</button><span class="filter-text">Plages</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="montagnes">MO</button><span class="filter-text">Montagnes</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="urbain">UR</button><span class="filter-text">Urbain</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="atypiques">AT</button><span class="filter-text">Atypiques</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="aventures">AV</button><span class="filter-text">Aventures</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="detente">DE</button><span class="filter-text">Détente</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="culture">CU</button><span class="filter-text">Culture</span></div>
        <div class="filter-item"><button class="btn-categorie-rond inactif" data-categorie="gastronomie">GA</button><span class="filter-text">Gastronomie</span> </div>
    </div>
</section>

<section class="main-content-section">
    <div class="main-grid">
        <div class="col-catalogue">
            <div class="bloc-title"><h2 id="titre-catalogue">Destinations</h2></div>
            <div class="cards-grid" id="catalogue-voyages"></div>
        </div>
        <div>
            <div class="bloc-title"><h2>Mon itinéraire</h2></div>
            <div class="panier-container">
                <p class="panier-sub" id="panier-statut">0 destination sélectionnée</p>
                <div class="panier-items-list" id="panier-contenu"></div>
                <div class="panier-total-row">
                    <span class="total-label">Prix total estimé</span>
                    <span class="total-price" id="panier-total">0 €</span>
                </div>
                <button class="btn-panier-main" id="btn-valider-panier" disabled>Continuer vers les transports ➔</button>
            </div>
        </div>
    </div>
</section>

<script>
    const voyagesData = <?php echo json_encode($destinations); ?>;
    const packagesData = <?php echo json_encode($sejours); ?>;
    let panier = [];
    let categorieActuelle = "tous";
    let modeAffichage = "alacarte";

    const catalogueContainer = document.getElementById('catalogue-voyages');
    const panierContenu = document.getElementById('panier-contenu');
    const panierTotal = document.getElementById('panier-total');
    const panierStatut = document.getElementById('panier-statut');
    const btnValiderPanier = document.getElementById('btn-valider-panier');

    document.getElementById('btn-alacarte').addEventListener('click', function() {
        modeAffichage = "alacarte";
        this.style.backgroundColor = "#4f46e5"; this.style.color = "white";
        document.getElementById('btn-packages').style.backgroundColor = "#f3f4f6";
        document.getElementById('btn-packages').style.color = "#374151";
        document.getElementById('titre-catalogue').innerText = "Destinations";
        afficherCatalogue();
    });

    document.getElementById('btn-packages').addEventListener('click', function() {
        modeAffichage = "packages";
        this.style.backgroundColor = "#4f46e5"; this.style.color = "white";
        document.getElementById('btn-alacarte').style.backgroundColor = "#f3f4f6";
        document.getElementById('btn-alacarte').style.color = "#374151";
        document.getElementById('titre-catalogue').innerText = "Packages Séjours";
        afficherCatalogue();
    });



    function afficherCatalogue() {
        catalogueContainer.innerHTML = ""; 
        const texteRecherche = document.getElementById('search-destination').value.toLowerCase();
        
        const voyagesFiltrés = voyagesData.filter(v => {
            const matchCategorie = (categorieActuelle === "tous" || (v.categorie && v.categorie.split(',').includes(categorieActuelle)));
            // NOUVEAU : On filtre UNIQUEMENT sur le titre
            const matchTexte = v.titre.toLowerCase().includes(texteRecherche);
            

            // Faux filtre pour simuler l'onglet "Packages" (à relier à la BDD plus tard)
            if (modeAffichage === "packages") return false; // En attendant une vraie table packages

            return matchCategorie && matchTexte;
        });

// Remplacez le bloc actuel par celui-ci
        if (modeAffichage === "packages") {
            packagesData.forEach(pack => {
        // Filtrage simple par texte
                const texteRecherche = document.getElementById('search-destination').value.toLowerCase();
                if (pack.titre.toLowerCase().includes(texteRecherche)) {
                    const cardHtml = `
            <div class="bloc-card">
                <div class="placeholder-image-bloc" style="background-image: url('${pack.image_url}'); background-size: cover;"></div>
                <div class="card-header"><h3 class="card-title">${pack.titre}</h3></div>
                <p class="card-description">${pack.description}</p>
                <p style="font-weight:bold; color:#4f46e5;">${pack.prix} €</p>
                <button class="btn-action-bloc" onclick="alert('Réservation package non implémentée')">Voir Package</button>
                    </div>`;
                    catalogueContainer.insertAdjacentHTML('beforeend', cardHtml);
                }
            });
    return; // On arrête la fonction ici si mode packages
}

if (voyagesFiltrés.length === 0) {
    catalogueContainer.innerHTML = "<p style='font-size: 0.875rem; color:#6b7280; font-weight:700;'>Aucune destination trouvée.</p>";
    return;
}

voyagesFiltrés.forEach(voyage => {
    const estDansLePanier = panier.some(item => item.id === voyage.id);

    let visuelHtml = voyage.image_url 
    ? `<div class="placeholder-image-bloc" style="background-image: url('${voyage.image_url}'); background-size: cover; background-position: center;"></div>`
    : `<div class="placeholder-image-bloc ${voyage.couleur_css || 'bg-bali'}"></div>`;

    const cardHtml = `
            <div class="bloc-card">
                ${visuelHtml}
                <div class="card-header"><h3 class="card-title">${voyage.titre}</h3></div>
                <p class="card-description">${voyage.description}</p>
                <button class="btn-action-bloc" onclick="ajouterAuPanier(${voyage.id})" ${estDansLePanier ? 'style="background-color:#4f46e5;" disabled' : ''}>
                    ${estDansLePanier ? 'Sélectionné ✓' : 'Sélectionner'}
                </button>
            </div>
    `;
    catalogueContainer.insertAdjacentHTML('beforeend', cardHtml);
});
}

window.ajouterAuPanier = function(id) {
    const voyageSelectionne = voyagesData.find(v => v.id == id);
    if (voyageSelectionne) { panier = [voyageSelectionne]; mettreAJourPanier(); afficherCatalogue(); }
}
window.retirerDuPanier = function() { panier = []; mettreAJourPanier(); afficherCatalogue(); }

function mettreAJourPanier() {
    panierContenu.innerHTML = ""; 
    if (panier.length === 0) {
        panierContenu.innerHTML = `<div style="text-align:center; padding:2rem 0; color:#9ca3af; font-size:0.75rem; font-weight:700;">Votre itinéraire est vide.</div>`;
        panierTotal.textContent = "0 €"; btnValiderPanier.disabled = true; return;
    }

        // NOUVEAU : La destination vaut 0€ dans le total
    panier.forEach(item => {
        panierContenu.insertAdjacentHTML('beforeend', `
            <div class="panier-item-row">
                <div><p class="panier-item-name">${item.titre}</p><span class="panier-item-type">Destination</span></div>
                <button onclick="retirerDuPanier()" style="background:none; border:none; color:#ef4444; font-weight:800; cursor:pointer;">✕</button></div>
        </div>`);
    });
        panierTotal.textContent = "0 €"; // Destination = 0
        btnValiderPanier.disabled = false;
    }

    btnValiderPanier.addEventListener('click', async () => {
        if (panier.length === 0) return;
        btnValiderPanier.textContent = "Initialisation..."; btnValiderPanier.disabled = true;

        try {
            const nbVoyageurs = document.getElementById('search-voyageurs').value;
            await fetch(`stockage.php?action=set_voyageurs&nb=${nbVoyageurs}`);
            await fetch('stockage.php?action=retirer&id=all');
            const reponse = await fetch(`stockage.php?action=ajouter&id=${panier[0].id}&type=destination`);
            const data = await reponse.json();
            
            if (data.status === 'success') window.location.href = "transport.php"; 
            else { alert("Erreur."); btnValiderPanier.disabled = false; }
        } catch (err) { alert("Erreur réseau."); btnValiderPanier.disabled = false; }
    });

    document.querySelectorAll('.btn-categorie-rond').forEach(bouton => {
        bouton.addEventListener('click', () => {
            document.querySelectorAll('.btn-categorie-rond').forEach(b => { b.classList.remove('actif'); b.classList.add('inactif'); });
            bouton.classList.remove('inactif'); bouton.classList.add('actif');
            categorieActuelle = bouton.getAttribute('data-categorie'); afficherCatalogue();
        });
    });

    afficherCatalogue(); mettreAJourPanier();
</script>
<?php include 'footer.php'; ?>