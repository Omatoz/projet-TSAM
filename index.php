<?php 
include 'header.php'; 
?>

    <link rel="stylesheet" href="index.css">

<section class="search-section">
    <div class="search-container">
        <div class="title-bloc">
            <h1>Planifiez. Explorez. Vivez.</h1>
            <p>Agence de voyages — Configuration d'itinéraires</p>
        </div>

        <form action="index.php" method="GET" class="search-form">
            <div class="champ-saisie-bloc">
                <label>Destination</label>
                <input type="text" id="search-destination" placeholder="Où allez-vous ?" />
            </div>
            <div class="champ-saisie-bloc">
                <label>Transport</label>
                <select id="search-transport">
                    <option value="tous">Tous</option>
                    <option value="avion">Avion</option>
                    <option value="train">Train</option>
                    <option value="bus">Bus</option>
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

<section class="filter-section">
    <div class="filter-container">
        <span class="filter-label">Catégories :</span>
        <div class="filter-item">
            <button class="btn-categorie-rond actif" data-categorie="tous">TO</button>
            <span class="filter-text">Tous</span>
        </div>
        <div class="filter-item">
            <button class="btn-categorie-rond inactif" data-categorie="plages">PL</button>
            <span class="filter-text">Plages</span>
        </div>
        <div class="filter-item">
            <button class="btn-categorie-rond inactif" data-categorie="montagnes">MO</button>
            <span class="filter-text">Montagnes</span>
        </div>
        <div class="filter-item">
            <button class="btn-categorie-rond inactif" data-categorie="aventures">AV</button>
            <span class="filter-text">Aventures</span>
        </div>
        <div class="filter-item">
            <button class="btn-categorie-rond inactif" data-categorie="detente">DE</button>
            <span class="filter-text">Détente</span>
        </div>
    </div>
</section>

<section class="main-content-section">
    <div class="main-grid">
        
        <div class="col-catalogue">
            <div class="bloc-title">
                <h2>Destinations & Briques disponibles</h2>
            </div>
            
            <div class="cards-grid" id="catalogue-voyages"></div>
        </div>

        <div>
            <div class="bloc-title">
                <h2>Mon itinéraire composé</h2>
            </div>
            <div class="panier-container">
                <p class="panier-sub" id="panier-statut">0 brique sélectionnée</p>
                
                <div class="panier-items-list" id="panier-contenu">
                    </div>

                <div class="panier-total-row">
                    <span class="total-label">Prix total estimé</span>
                    <span class="total-price" id="panier-total">0 €</span>
                </div>
                <button class="btn-panier-main" id="btn-valider-panier" disabled>Voir tout mon itinéraire</button>
            </div>
        </div>

    </div>
</section>

<script>
// 1. Base de données locale de voyages (Briques de l'agence)
const voyagesData = [
    { id: 1, titre: "Bali, Indonésie", description: "Vol régulier inclus au départ de Paris, hébergement de rêve en bord de mer.", prix: 789, categorie: "plages", transport: "avion", couleur: "bg-bali" },
    { id: 2, titre: "Ligne de Bus — Europe", description: "Brique de transport terrestre reliant de manière fluide les grandes capitales.", prix: 159, categorie: "detente", transport: "bus", couleur: "bg-transport" },
    { id: 3, titre: "Aventure en Suisse", description: "Randonnées extrêmes guidées au cœur des Alpes avec nuits en refuge de haute montagne.", prix: 1249, categorie: "aventures", transport: "train", couleur: "bg-bali" },
    { id: 4, titre: "Chamonix Mont-Blanc", description: "Séjour ski ou vtt selon saison, forfait remontées mécaniques et hôtel douillet.", prix: 450, categorie: "montagnes", transport: "train", couleur: "bg-transport" },
    { id: 5, titre: "Maldives — farniente", description: "Bungalow sur pilotis, formule tout inclus pour une coupure géométrique totale.", prix: 1899, categorie: "plages", transport: "avion", couleur: "bg-bali" },
    { id: 6, titre: "Circuit Train Interrail", description: "Pass ferroviaire complet pour explorer les plus hauts sommets d'Europe.", prix: 299, categorie: "aventures", transport: "train", couleur: "bg-transport" }
];

// 2. État de l'application (Le panier commence VIDE de base)
let panier = [];
let categorieActuelle = "tous";

// 3. Éléments du DOM (HTML)
const catalogueContainer = document.getElementById('catalogue-voyages');
const panierContenu = document.getElementById('panier-contenu');
const panierTotal = document.getElementById('panier-total');
const panierStatut = document.getElementById('panier-statut');
const btnValiderPanier = document.getElementById('btn-valider-panier');

// 4. Fonction pour afficher les cartes du catalogue selon les filtres
function afficherCatalogue() {
    catalogueContainer.innerHTML = ""; // On nettoie le catalogue visuel
    
    // Récupération des valeurs de recherche textuelle et transport
    const texteRecherche = document.getElementById('search-destination').value.toLowerCase();
    const filtreTransport = document.getElementById('search-transport').value;

    // Filtrage des données
    const voyagesFiltrés = voyagesData.filter(v => {
        const matchCategorie = (categorieActuelle === "tous" || v.categorie === categorieActuelle);
        const matchTexte = v.titre.toLowerCase().includes(texteRecherche) || v.description.toLowerCase().includes(texteRecherche);
        const matchTransport = (filtreTransport === "tous" || v.transport === filtreTransport);
        return matchCategorie && matchTexte && matchTransport;
    });

    if (voyagesFiltrés.length === 0) {
        catalogueContainer.innerHTML = "<p style='font-size: 0.875rem; color:#6b7280; font-weight:700; text-transform:uppercase;'>Aucune brique ne correspond à vos critères.</p>";
        return;
    }

    // Génération du code HTML pour chaque carte
    voyagesFiltrés.forEach(voyage => {
        const estDansLePanier = panier.some(item => item.id === voyage.id);
        
        const cardHtml = `
            <div class="bloc-card">
                <div class="placeholder-image-bloc ${voyage.couleur}">
                    <span class="price-badge">${voyage.prix} €</span>
                </div>
                <div class="card-header">
                    <h3 class="card-title">${voyage.titre}</h3>
                </div>
                <p class="card-description">${voyage.description}</p>
                <button class="btn-action-bloc" onclick="ajouterAuPanier(${voyage.id})" ${estDansLePanier ? 'style="background-color:#4f46e5;" disabled' : ''}>
                    ${estDansLePanier ? 'Sélectionné ✓' : 'Sélectionner cette brique'}
                </button>
            </div>
        `;
        catalogueContainer.insertAdjacentHTML('beforeend', cardHtml);
    });
}

// 5. Fonction pour ajouter un voyage au panier
window.ajouterAuPanier = function(id) {
    const voyageSelectionne = voyagesData.find(v => v.id === id);
    
    // On vérifie s'il n'est pas déjà dans le panier
    if (!panier.some(item => item.id === id)) {
        panier.push(voyageSelectionne);
        mettreAJourPanier();
        afficherCatalogue(); // Rafraîchit les boutons pour afficher "Sélectionné"
    }
}

// 6. Fonction pour retirer un élément du panier
window.retirerDuPanier = function(id) {
    panier = panier.filter(item => item.id !== id);
    mettreAJourPanier();
    afficherCatalogue(); // Rétablit le bouton initial dans le catalogue
}

// 7. Fonction de mise à jour de l'affichage du panier
function mettreAJourPanier() {
    panierContenu.innerHTML = ""; // On nettoie l'ancien rendu visuel du panier
    
    if (panier.length === 0) {
        // Rendu si le panier est vide de base
        panierContenu.innerHTML = `
            <div style="text-align:center; padding:2rem 0; color:#9ca3af; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">
                Votre itinéraire est vide.<br>Sélectionnez des briques à gauche.
            </div>
        `;
        panierTotal.textContent = "0 €";
        panierStatut.textContent = "0 brique sélectionnée";
        btnValiderPanier.disabled = true;
        btnValiderPanier.style.opacity = "0.5";
        btnValiderPanier.style.cursor = "not-allowed";
        return;
    }

    // Si le panier contient des briques, on les affiche et calcule le cumul
    let total = 0;
    panier.forEach(item => {
        total += item.prix;
        const itemHtml = `
            <div class="panier-item-row">
                <div>
                    <p class="panier-item-name">${item.titre}</p>
                    <span class="panier-item-type" style="text-transform:uppercase;">Brique ${item.transport}</span>
                </div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span class="panier-item-price">${item.prix} €</span>
                    <button onclick="retirerDuPanier(${item.id})" style="background:none; border:none; color:#ef4444; font-weight:800; cursor:pointer; font-size:11px;">✕</button>
                </div>
            </div>
        `;
        panierContenu.insertAdjacentHTML('beforeend', itemHtml);
    });

    // Affichage des calculs mis à jour
    panierTotal.textContent = total + " €";
    panierStatut.textContent = `${panier.length} brique${panier.length > 1 ? 's' : ''} configurée${panier.length > 1 ? 's' : ''}`;
    btnValiderPanier.disabled = false;
    btnValiderPanier.style.opacity = "1";
    btnValiderPanier.style.cursor = "pointer";
}

// 8. Gestionnaires d'Événements pour la barre des catégories (Boutons ronds)
document.querySelectorAll('.btn-categorie-rond').forEach(bouton => {
    bouton.addEventListener('click', (e) => {
        // On gère l'état actif graphique des boutons circulaires
        document.querySelectorAll('.btn-categorie-rond').forEach(b => {
            b.classList.remove('actif');
            b.classList.add('inactif');
        });
        bouton.classList.remove('inactif');
        bouton.classList.add('actif');

        // On applique le filtre de données
        categorieActuelle = bouton.getAttribute('data-categorie');
        afficherCatalogue();
    });
});

// Événement sur le bouton Rechercher du formulaire
document.getElementById('btn-filtrer-recherche').addEventListener('click', afficherCatalogue);

// 9. Initialisation au premier chargement de la page
afficherCatalogue();
mettreAJourPanier();
</script>

<?php include 'footer.php'; ?>