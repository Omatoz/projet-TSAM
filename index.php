<?php 
include 'header.php'; 

include 'database.php';

$briquesBDD = [];

// récupération des briques de voyage depuis MySQL via $conn
if ($conn !== null) {
    try {
        $requete = $conn->query("SELECT * FROM briques_voyage ORDER BY id ASC");
        $briquesBDD = $requete->fetchAll(PDO::FETCH_ASSOC);
    } catch(PDOException $exception) {
        echo "<div style='color:red; font-weight:bold; padding:1rem;'>Erreur lors de la récupération des briques : " . $exception->getMessage() . "</div>";
    }
} 
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
// 1. Récupération sécurisée du catalogue PHP issu de MySQL
const voyagesData = <?php echo json_encode($briquesBDD); ?>;

// 2. État de l'application (Le panier commence VIDE au chargement)
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
    catalogueContainer.innerHTML = ""; 
    
    // 1. Récupération des valeurs des filtres de saisie
    const texteRecherche = document.getElementById('search-destination').value.toLowerCase();
    const filtreTransport = document.getElementById('search-transport').value;

    // 2. Filtrage croisé des données reçues de MySQL
    const voyagesFiltrés = voyagesData.filter(v => {
        // Vérification si la catégorie sélectionnée est dans la liste de la brique (séparée par des virgules)
        const matchCategorie = (categorieActuelle === "tous" || v.categorie.split(',').includes(categorieActuelle));
        
        // Filtrage textuel (titre ou description)
        const matchTexte = v.titre.toLowerCase().includes(texteRecherche) || 
                             v.description.toLowerCase().includes(texteRecherche);
        
        // Filtrage par transport basé sur le contenu textuel de la brique
        let matchTransport = true;
        if (filtreTransport !== "tous") {
            matchTransport = v.titre.toLowerCase().includes(filtreTransport) || 
                             v.description.toLowerCase().includes(filtreTransport);
        }
                             
        return matchCategorie && matchTexte && matchTransport;
    });

    // 3. Gestion du cas où aucun voyage ne correspond aux critères
    if (voyagesFiltrés.length === 0) {
        catalogueContainer.innerHTML = "<p style='font-size: 0.875rem; color:#6b7280; font-weight:700; text-transform:uppercase;'>Aucune brique ne correspond à vos critères.</p>";
        return;
    }

    // 4. BOUCLE DE GÉNÉRATION DES CARTES
    voyagesFiltrés.forEach(voyage => {
        const estDansLePanier = panier.some(item => item.id === voyage.id);
        const prixEntier = Math.round(voyage.prix);
        
        // Gestion de l'affichage de l'image Pinterest ou de la couleur de secours
        let visuelHtml = '';
        if (voyage.image_url) {
            // Si une image est présente en Base de Données
            visuelHtml = `<div class="placeholder-image-bloc" style="background-image: url('${voyage.image_url}'); background-size: cover; background-position: center;">
                            <span class="price-badge">${prixEntier} €</span>
                          </div>`;
        } else {
            // Fallback : si pas d'image, on applique la couleur unie CSS par défaut
            visuelHtml = `<div class="placeholder-image-bloc ${voyage.couleur_css || 'bg-bali'}">
                            <span class="price-badge">${prixEntier} €</span>
                          </div>`;
        }

        // Structure HTML finale de la carte de voyage
        const cardHtml = `
            <div class="bloc-card">
                ${visuelHtml}
                <div class="card-header">
                    <h3 class="card-title">${voyage.titre}</h3>
                </div>
                <p class="card-description">${voyage.description}</p>
                <button class="btn-action-bloc" onclick="ajouterAuPanier(${voyage.id})" ${estDansLePanier ? 'style="background-color:#4f46e5;" disabled' : ''}>
                    ${estDansLePanier ? 'Sélectionné ✓' : 'Sélectionner cette brique'}
                </button>
            </div>
        `;
        
        // Injection de la carte dans le container HTML
        catalogueContainer.insertAdjacentHTML('beforeend', cardHtml);
    });
}

// 5. Ajouter un élément au panier
window.ajouterAuPanier = function(id) {
    const voyageSelectionne = voyagesData.find(v => v.id == id);
    if (voyageSelectionne && !panier.some(item => item.id == id)) {
        panier.push(voyageSelectionne);
        mettreAJourPanier();
        afficherCatalogue();
    }
}

// 6. Retirer un élément du panier
window.retirerDuPanier = function(id) {
    panier = panier.filter(item => item.id != id);
    mettreAJourPanier();
    afficherCatalogue();
}

// 7. Mise à jour visuelle du panier
function mettreAJourPanier() {
    panierContenu.innerHTML = ""; 
    
    if (panier.length === 0) {
        panierContenu.innerHTML = `
            <div style="text-align:center; padding:2rem 0; color:#9ca3af; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em;">
                Votre itinéraire est vide.<br>Sélectionnez des briques à gauche.
            </div>
        `;
        panierTotal.textContent = "0 €";
        panierStatut.textContent = "0 brique sélectionnée";
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
                    <span class="panier-item-type" style="text-transform:uppercase;">Brique ${item.type_brique || 'Option'}</span>
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
    panierStatut.textContent = `${panier.length} brique${panier.length > 1 ? 's' : ''} configurée${panier.length > 1 ? 's' : ''}`;
    btnValiderPanier.disabled = false;
}

// 8. Événements sur les boutons de catégories (cercles)
document.querySelectorAll('.btn-categorie-rond').forEach(bouton => {
    bouton.addEventListener('click', () => {
        document.querySelectorAll('.btn-categorie-rond').forEach(b => {
            b.classList.remove('actif');
            b.classList.add('inactif');
        });
        bouton.classList.remove('inactif');
        bouton.classList.add('actif');

        categorieActuelle = bouton.getAttribute('data-categorie');
        afficherCatalogue();
    });
});

document.getElementById('btn-filtrer-recherche').addEventListener('click', afficherCatalogue);

// 9. Chargement Initial
afficherCatalogue();
mettreAJourPanier();
</script>

<?php include 'includes/footer.php'; ?>