<?php
// Page d'administration - version robuste
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Calculer le chemin de base du projet
$basePath = dirname(__DIR__, 3); // points to project root (TFECode)

// Inclure la configuration de la base de données
require_once $basePath . '/config/database.php';

// Inclure les modèles et contrôleurs si présents
$controllers = [
    'PlancheAperoController' => $basePath . '/app/controllers/PlancheAperoController.php',
    'PanierController' => $basePath . '/app/controllers/PanierController.php',
    'EvenementController' => $basePath . '/app/controllers/EvenementController.php',
    'ReservationController' => $basePath . '/app/controllers/ReservationController.php'
];

foreach ($controllers as $class => $path) {
    if (file_exists($path)) {
        require_once $path;
    }
}

// Connexion à la base
$db = null;
try {
    $db = (new Database())->getConnection();
} catch (Throwable $e) {
    echo "<div class='alert alert-danger'>Erreur de connexion à la base : " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Instancier les contrôleurs disponibles
$plancheController = class_exists('PlancheAperoController') && $db ? new PlancheAperoController($db) : null;
$panierController = class_exists('PanierController') && $db ? new PanierController($db) : null;
$evenementController = class_exists('EvenementController') && $db ? new EvenementController($db) : null;
$reservationController = class_exists('ReservationController') && $db ? new ReservationController($db) : null;
?>

<main class="admin-container container my-5">
  <h1 class="text-center">Administration Traiteur</h1>

  <div class="tabs">
    <button class="tablinks" onclick="openTab(event, 'Planches')">Planches Apéro</button>
    <button class="tablinks" onclick="openTab(event, 'Paniers')">Paniers Repas</button>
    <button class="tablinks" onclick="openTab(event, 'Evenements')">Événements</button>
    <button class="tablinks" onclick="openTab(event, 'Reservations')">Réservations</button>
  </div>

    <div id="Planches" class="tabcontent">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3>Planches Apéro</h3>
            <button class="btn" onclick="openModal('planches')">➕ Ajouter une planche</button>
        </div>

    <?php
    if ($plancheController) {
        try {
            $plancheController->afficherPlanches();
        } catch (Throwable $e) {
            echo "<div class='alert alert-warning'>Erreur affichage planches: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Contrôleur PlancheApero non disponible</div>";
    }
    ?>
  </div>

    <div id="Evenements" class="tabcontent">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3>Événements</h3>
            <button class="btn" onclick="openModal('evenements')">➕ Ajouter un événement</button>
        </div>

    <?php
    if ($evenementController) {
        try {
            $evenementController->afficherEvenements();
        } catch (Throwable $e) {
            echo "<div class='alert alert-warning'>Erreur affichage événements: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Section événements à implémenter</div>";
    }
    ?>
  </div>

    <div id="Paniers" class="tabcontent">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <h3>Paniers Repas</h3>
            <button class="btn" onclick="openModal('paniers')">➕ Ajouter un panier</button>
        </div>

    <?php
    if ($panierController) {
        try {
            $panierController->afficherPaniers();
        } catch (Throwable $e) {
            echo "<div class='alert alert-warning'>Erreur affichage paniers: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Contrôleur Panier non disponible</div>";
    }
    ?>
  </div>

  <div id="Reservations" class="tabcontent">
    <?php
    if ($reservationController) {
        try {
            $reservationController->afficherReservations();
        } catch (Throwable $e) {
            echo "<div class='alert alert-warning'>Erreur affichage réservations: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Section réservations à implémenter</div>";
    }
    ?>
  </div>
</main>

<script>
// Initialiser l'onglet par défaut
document.addEventListener('DOMContentLoaded', function() {
    const firstBtn = document.querySelector('.tablinks');
    const firstContent = document.querySelector('.tabcontent');
    if (firstBtn && firstContent) {
        firstBtn.classList.add('active');
        firstContent.classList.add('active');
        firstContent.style.display = 'block';
    }
});

function openTab(evt, tabName) {
    var tabcontent = document.getElementsByClassName("tabcontent");
    for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
        tabcontent[i].classList.remove('active');
    }
    var tablinks = document.getElementsByClassName("tablinks");
    for (var i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove('active');
    }
    var el = document.getElementById(tabName);
    if (el) {
        el.style.display = 'block';
        el.classList.add('active');
    }
    if (evt && evt.currentTarget) evt.currentTarget.classList.add('active');
}
</script>

<script>
// Helpers pour appeler l'API
async function apiPost(resource, action, data = {}) {
    data.action = action;
    const form = new FormData();
    for (const k in data) {
        const v = data[k];
        if (Array.isArray(v)) {
            // envoyer les tableaux comme JSON
            form.append(k, JSON.stringify(v));
        } else {
            form.append(k, v);
        }
    }
    const res = await fetch('/api.php?page=' + resource, {
        method: 'POST',
        body: form
    });
    const ct = (res.headers.get('content-type') || '').toLowerCase();
    if (ct.includes('application/json')) {
        return res.json();
    }
    // if server returned HTML (error page), read and return as error
    const text = await res.text();
    return { success: false, error: 'Server returned non-JSON response', raw: text };
}

// Planches: handled via modal. Use openModal('planches', id) to edit or openModal('planches') to create.
async function supprimerPlanche(id) {
    if (!confirm('Supprimer la planche #' + id + ' ?')) return;
    const r = await apiPost('planches', 'delete', {id});
    if (r.success) {
        // remove the element from the DOM if present
        const el = document.querySelector(`[data-resource-id='planches-${id}']`);
        if (el && el.parentNode) el.parentNode.removeChild(el);
        if (window.showMessage) window.showMessage('Planche supprimée.', 'success'); else alert('Planche supprimée.');
    } else {
        if (window.showMessage) window.showMessage('Erreur suppression: ' + (r.error || ''), 'error'); else alert('Erreur suppression');
    }
}

// Événements
async function createEvenement() {
    const data = {
        nom: document.getElementById('e_titre').value,
        description: document.getElementById('e_description').value,
        date_event: document.getElementById('e_date_debut').value,
        nb_places: document.getElementById('e_nb_places') ? document.getElementById('e_nb_places').value : null,
        prix_place: document.getElementById('e_prix') ? document.getElementById('e_prix').value : null,
        infos_complementaires: document.getElementById('e_infos') ? document.getElementById('e_infos').value : null,
        duree: document.getElementById('e_duree') ? document.getElementById('e_duree').value : null,
        date_fin_inscription: document.getElementById('e_date_fin_inscription') ? document.getElementById('e_date_fin_inscription').value : null
    };
    const r = await apiPost('evenements', 'create', data);
    if (r.success) { if (window.showMessage) window.showMessage('Événement créé.', 'success'); else alert('Événement créé.'); } else { if (window.showMessage) window.showMessage('Erreur: ' + (r.error || 'création impossible'), 'error'); else alert('Erreur: ' + (r.error || 'création impossible')); }
}

async function supprimerEvenement(id) {
    if (!confirm('Supprimer l\'événement #' + id + ' ?')) return;
    const r = await apiPost('evenements', 'delete', {id});
    if (r.success) {
        const el = document.querySelector(`[data-resource-id='evenements-${id}']`);
        if (el && el.parentNode) el.parentNode.removeChild(el);
        if (window.showMessage) window.showMessage('Événement supprimé.', 'success'); else alert('Événement supprimé.');
    } else {
        if (window.showMessage) window.showMessage('Erreur suppression: ' + (r.error || ''), 'error'); else alert('Erreur suppression');
    }
}

async function supprimerReservation(id) {
    if (!confirm('Supprimer la réservation #' + id + ' ?')) return;
    const r = await apiPost('reservations', 'delete', {id});
    if (r.success) {
        const el = document.querySelector(`[data-resource-id='reservations-${id}']`);
        if (el && el.parentNode) el.parentNode.removeChild(el);
        if (window.showMessage) window.showMessage('Réservation supprimée.', 'success'); else alert('Réservation supprimée.');
    } else {
        if (window.showMessage) window.showMessage('Erreur suppression: ' + (r.error || ''), 'error'); else alert('Erreur suppression');
    }
}

async function modifierEvenement(id) {
    const titre = prompt('Titre:');
    if (titre === null) return;
    const description = prompt('Description:');
    const date_debut = prompt('Date début (YYYY-MM-DD HH:MM):');
    const date_fin = prompt('Date fin (YYYY-MM-DD HH:MM):');
    const lieu = prompt('Lieu:');
    const prix = prompt('Prix:');
    const data = { id, titre, description, date_debut, date_fin, lieu, prix };
    const r = await apiPost('evenements', 'update', data);
    if (r.success) { if (window.showMessage) window.showMessage('Événement mis à jour.', 'success'); else alert('Événement mis à jour.'); } else { if (window.showMessage) window.showMessage('Erreur modification', 'error'); else alert('Erreur modification'); }
}

// Paniers
async function createPanier() {
    const data = {
        nom: document.getElementById('pa_nom').value,
        ingredients: document.getElementById('pa_ingredients').value,
        allergies: document.getElementById('pa_allergies').value,
        nb_personnes: document.getElementById('pa_nb').value,
        prix: document.getElementById('pa_prix').value,
        disponible: document.getElementById('pa_dispo').value
    };
    const r = await apiPost('paniers', 'create', data);
    if (r.success) { if (window.showMessage) window.showMessage('Panier créé.', 'success'); else alert('Panier créé.'); } else { if (window.showMessage) window.showMessage('Erreur: ' + (r.error || 'création impossible'), 'error'); else alert('Erreur: ' + (r.error || 'création impossible')); }
}

async function supprimerPanier(id) {
    if (!confirm('Supprimer le panier #' + id + ' ?')) return;
    const r = await apiPost('paniers', 'delete', {id});
    if (r.success) {
        const el = document.querySelector(`[data-resource-id='paniers-${id}']`);
        if (el && el.parentNode) el.parentNode.removeChild(el);
        if (window.showMessage) window.showMessage('Panier supprimé.', 'success'); else alert('Panier supprimé.');
    } else {
        if (window.showMessage) window.showMessage('Erreur suppression: ' + (r.error || ''), 'error'); else alert('Erreur suppression');
    }
}

async function modifierPanier(id) {
        openModal('paniers', id);
}
</script>

<!-- Modals génériques -->
<div id="admin-modal" class="admin-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:9999;">
    <div class="admin-modal-dialog" style="background:#fff; padding:18px; border-radius:10px; width:90%; max-width:720px; box-shadow:0 8px 30px rgba(0,0,0,0.2);">
        <h4 id="admin-modal-title">Modifier</h4>
        <form id="admin-modal-form" onsubmit="event.preventDefault(); submitModal();">
            <div id="admin-modal-fields"></div>
            <div style="text-align:right; margin-top:12px;">
                <button type="button" class="btn" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn" style="margin-left:8px;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Templates pour modals par ressource -->
<template id="tpl-planches">
    <div class="admin-create-form">
        <div>
            <label>Nom</label>
            <input name="nom" class="form-control" type="text" required />
        </div>
        <div>
            <label>Nombre de personnes</label>
            <input name="nb_personnes" class="form-control" type="number" min="1" />
        </div>
        <div>
            <label>Prix</label>
            <input name="prix" class="form-control" type="number" step="0.01" />
        </div>
        <div>
            <label>Nombre disponibles</label>
            <input name="disponible" class="form-control" type="number" min="0" />
        </div>
        <div style="grid-column:1/-1;">
            <label>Ingrédients</label>
            <input name="ingredients" class="form-control" type="text" />
        </div>
        <div style="grid-column:1/-1;">
            <label>Allergènes</label>
            <div class="multi-checkbox-allergenes" data-name="allergenes" style="max-height:160px; overflow:auto; padding:6px; border:1px solid #e6e6e6; border-radius:4px;"></div>
        </div>
    </div>
</template>

<template id="tpl-evenements">
    <div class="admin-create-form">
        <div>
            <label>Nom</label>
            <input id="e_titre" name="nom" class="form-control" type="text" required />
        </div>
        <div style="grid-column:1/-1;">
            <label>Description</label>
            <input id="e_description" name="description" class="form-control" type="text" />
        </div>
        <div>
            <label>Date début</label>
            <input id="e_date_debut" name="date_event" class="form-control" type="datetime-local" />
        </div>
        <div>
            <label>Nombre de places</label>
            <input id="e_nb_places" name="nb_places" class="form-control" type="number" />
        </div>
        <div>
            <label>Prix par place</label>
            <input id="e_prix" name="prix_place" class="form-control" type="number" step="0.01" />
        </div>
        <div style="grid-column:1/-1;">
            <label>Infos complémentaires</label>
            <input id="e_infos" name="infos_complementaires" class="form-control" type="text" />
        </div>
        <div>
            <label>Durée</label>
            <input id="e_duree" name="duree" class="form-control" type="text" placeholder="ex: 2h" />
        </div>
        <div>
            <label>Date fin d'inscription</label>
            <input id="e_date_fin_inscription" name="date_fin_inscription" class="form-control" type="datetime-local" />
        </div>
    </div>
</template>

<template id="tpl-paniers">
    <div class="admin-create-form">
        <div>
            <label>Nom</label>
            <input name="nom" class="form-control" type="text" required />
        </div>
        <div>
            <label>Nombre de personnes</label>
            <input name="nb_personnes" class="form-control" type="number" min="1" />
        </div>
        <div>
            <label>Prix</label>
            <input name="prix" class="form-control" type="number" step="0.01" />
        </div>
        <div>
            <label>Nombre disponibles</label>
            <input name="disponible" class="form-control" type="number" min="0" />
        </div>
        <div style="grid-column:1/-1;">
            <label>Ingrédients</label>
            <input name="ingredients" class="form-control" type="text" />
        </div>
        <div style="grid-column:1/-1;">
            <label>Allergènes</label>
            <div class="multi-checkbox-allergenes" data-name="allergenes" style="max-height:160px; overflow:auto; padding:6px; border:1px solid #e6e6e6; border-radius:4px;"></div>
        </div>
    </div>
</template>

<script>
// Modal logic: openModal(resource, id) builds fields based on resource and populates if possible
function closeModal(){ document.getElementById('admin-modal').style.display='none'; }

function getRowDataPrefix(prefix, id){
    // Read the element with data-resource-id and parse its 'data-item' JSON payload
    const el = document.querySelector(`[data-resource-id='${prefix}-${id}']`);
    if (!el) return null;
    const raw = el.getAttribute('data-item');
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

async function openModal(resource, id = null){
    const modal = document.getElementById('admin-modal');
    const title = document.getElementById('admin-modal-title');
    const fields = document.getElementById('admin-modal-fields');
    fields.innerHTML = '';
    let schema = [];
    if (resource === 'planches'){
        // Set title depending on action: create (no id) or edit (has id)
        if (id) title.textContent = 'Modifier la planche #' + id; else title.textContent = 'Ajouter une nouvelle planche';
        schema = [
            {name:'nom', label:'Nom', type:'text'},
            {name:'nb_personnes', label:'Nb personnes', type:'number'},
            {name:'prix', label:'Prix', type:'number', step:'0.01'},
            {name:'disponible', label:'Disponible', type:'number'},
            {name:'ingredients', label:'Ingrédients', type:'text'},
            {name:'allergenes', label:'Allergènes (json)', type:'text'}
        ];
    } else if (resource === 'evenements'){
        if (id) title.textContent = 'Modifier l\'événement #' + id; else title.textContent = 'Ajouter un événement';
        schema = [
            {name:'titre', label:'Titre', type:'text'},
            {name:'description', label:'Description', type:'text'},
            {name:'date_debut', label:'Date début', type:'datetime-local'},
            {name:'prix', label:'Prix', type:'number', step:'0.01'}
        ];
    } else if (resource === 'paniers'){
        if (id) title.textContent = 'Modifier le panier #' + id; else title.textContent = 'Ajouter un panier';
        schema = [
            {name:'nom', label:'Nom', type:'text'},
            {name:'nb_personnes', label:'Nb personnes', type:'number'},
            {name:'prix', label:'Prix', type:'number', step:'0.01'},
            {name:'disponible', label:'Disponible', type:'number'},
            {name:'ingredients', label:'Ingrédients', type:'text'},
            {name:'allergenes', label:'Allergènes (json)', type:'text'}
        ];
    }

    // Try to collect existing data from rendered DOM if controllers added data- attributes
    const rowData = getRowDataPrefix(resource, id) || {};

    // Use template if available
    let tplId = null;
    if (resource === 'planches') tplId = 'tpl-planches';
    if (resource === 'evenements') tplId = 'tpl-evenements';
    if (resource === 'paniers') tplId = 'tpl-paniers';

    if (tplId) {
        const tpl = document.getElementById(tplId);
        const clone = tpl.content.cloneNode(true);
        fields.appendChild(clone);

        // prefill inputs if rowData present
        if (rowData) {
            const inputs = fields.querySelectorAll('input');
            inputs.forEach(inp => {
                const name = inp.name;
                if (rowData[name] !== undefined) {
                    // if array, join with comma
                    if (Array.isArray(rowData[name])) inp.value = rowData[name].join(', ');
                    else inp.value = rowData[name];
                }
            });

            // If planches or paniers, populate allergenes/allergies checkbox list from API and set selected options
            if (resource === 'planches' || resource === 'paniers') {
                const checkboxContainer = fields.querySelector('.multi-checkbox-allergenes');
                if (checkboxContainer) {
                    // fetch allergenes list
                    fetch('/api.php?page=allergenes')
                        .then(r=>r.json())
                        .then(data=>{
                            if (!data || !data.allergenes) return;
                            // determine selected ids/names
                            let selectedIds = [];
                            if (resource === 'planches') selectedIds = rowData.allergene_ids ? rowData.allergene_ids.map(String) : [];
                            if (resource === 'paniers') {
                                if (Array.isArray(rowData.allergies)) selectedIds = rowData.allergies.map(String);
                                else if (typeof rowData.allergies === 'string' && rowData.allergies.trim() !== '') selectedIds = rowData.allergies.split(',').map(s=>s.trim());
                                else selectedIds = [];
                            }
                            // build checkboxes
                            data.allergenes.forEach(a=>{
                                const id = String(a.id);
                                const wrapper = document.createElement('div');
                                wrapper.style.marginBottom = '6px';
                                const cb = document.createElement('input');
                                cb.type = 'checkbox';
                                cb.value = id;
                                cb.id = 'allergene_'+id + '_' + Math.random().toString(36).slice(2,7);
                                cb.name = checkboxContainer.dataset.name || 'allergenes';
                                if (selectedIds.includes(id) || selectedIds.includes(a.nom)) cb.checked = true;
                                const lbl = document.createElement('label');
                                lbl.htmlFor = cb.id;
                                lbl.style.marginLeft = '6px';
                                lbl.textContent = a.nom;
                                wrapper.appendChild(cb);
                                wrapper.appendChild(lbl);
                                checkboxContainer.appendChild(wrapper);
                            });
                        }).catch(()=>{});
                }
            }
            // If we have a planche name, prefer showing it in the title instead of the numeric id
            if (resource === 'planches' && id && rowData && rowData.nom) {
                // use a friendly title showing the planche name
                title.textContent = 'Modifier la planche — ' + rowData.nom;
            }
            // If we have an event name, show it in the modal title
            if (resource === 'evenements' && id && rowData && (rowData.nom || rowData.titre)) {
                title.textContent = 'Modifier l\'événement — ' + (rowData.nom || rowData.titre);
            }
            // If we have a panier name, show it in the modal title
            if (resource === 'paniers' && id && rowData && rowData.nom) {
                title.textContent = 'Modifier le panier — ' + rowData.nom;
            }
        }
        // If create mode (no rowData) and planches or paniers, still populate allergenes select
        if (!rowData && (resource === 'planches' || resource === 'paniers')) {
            const checkboxContainer = fields.querySelector('.multi-checkbox-allergenes');
            if (checkboxContainer) {
                fetch('/api.php?page=allergenes')
                    .then(r=>r.json())
                    .then(data=>{
                        if (!data || !data.allergenes) return;
                        data.allergenes.forEach(a=>{
                            const id = String(a.id);
                            const wrapper = document.createElement('div');
                            wrapper.style.marginBottom = '6px';
                            const cb = document.createElement('input');
                            cb.type = 'checkbox';
                            cb.value = id;
                            cb.id = 'allergene_'+id + '_' + Math.random().toString(36).slice(2,7);
                            cb.name = checkboxContainer.dataset.name || 'allergenes';
                            const lbl = document.createElement('label');
                            lbl.htmlFor = cb.id;
                            lbl.style.marginLeft = '6px';
                            lbl.textContent = a.nom;
                            wrapper.appendChild(cb);
                            wrapper.appendChild(lbl);
                            checkboxContainer.appendChild(wrapper);
                        });
                    }).catch(()=>{});
            }
        }
    } else {
        // fallback: build simple inputs from schema
        for (const f of schema){
            const wrapper = document.createElement('div');
            wrapper.style.marginBottom='8px';
            const label = document.createElement('label');
            label.textContent = f.label;
            label.style.display='block';

            // If this field represents allergenes/allergies, create a checkbox list and populate from API
            if (f.name === 'allergenes' || f.name === 'allergies') {
                const checkboxContainer = document.createElement('div');
                checkboxContainer.className = 'multi-checkbox-allergenes';
                checkboxContainer.dataset.name = f.name;
                checkboxContainer.style.maxHeight = '160px';
                checkboxContainer.style.overflow = 'auto';
                checkboxContainer.style.padding = '6px';
                checkboxContainer.style.border = '1px solid #e6e6e6';
                checkboxContainer.style.borderRadius = '4px';

                // Append label and container now; populate options async
                wrapper.appendChild(label);
                wrapper.appendChild(checkboxContainer);
                fields.appendChild(wrapper);

                // Determine which values should be selected (if any)
                let selectedIds = [];
                if (rowData && rowData[f.name] !== undefined) {
                    const raw = rowData[f.name];
                    if (Array.isArray(raw)) selectedIds = raw.map(String);
                    else if (typeof raw === 'string' && raw.trim() !== '') selectedIds = raw.split(',').map(s=>s.trim());
                    else selectedIds = [];
                }

                // Populate options from backend allergenes list
                fetch('/api.php?page=allergenes')
                    .then(r=>r.json())
                    .then(data=>{
                        if (!data || !data.allergenes) return;
                        data.allergenes.forEach(a=>{
                            const id = String(a.id);
                            const w = document.createElement('div');
                            w.style.marginBottom = '6px';
                            const cb = document.createElement('input');
                            cb.type = 'checkbox';
                            cb.value = id;
                            cb.id = 'allergene_'+id + '_' + Math.random().toString(36).slice(2,7);
                            cb.name = checkboxContainer.dataset.name || f.name;
                            if (selectedIds.includes(id) || selectedIds.includes(a.nom)) cb.checked = true;
                            const lbl = document.createElement('label');
                            lbl.htmlFor = cb.id;
                            lbl.style.marginLeft = '6px';
                            lbl.textContent = a.nom;
                            w.appendChild(cb);
                            w.appendChild(lbl);
                            checkboxContainer.appendChild(w);
                        });
                    }).catch(()=>{});
            } else {
                const input = document.createElement('input');
                input.name = f.name;
                input.type = f.type || 'text';
                if (f.step) input.step = f.step;
                input.className = 'form-control';
                input.style.width='100%';
                if (rowData[f.name] !== undefined) input.value = rowData[f.name];
                wrapper.appendChild(label);
                wrapper.appendChild(input);
                fields.appendChild(wrapper);
            }
        }
    }

    // store resource/id/action on form
    const form = document.getElementById('admin-modal-form');
    form.dataset.resource = resource;
    form.dataset.id = id || '';
    form.dataset.action = id ? 'update' : 'create';
    modal.style.display='flex';
}

async function submitModal(){
    const form = document.getElementById('admin-modal-form');
        const resource = form.dataset.resource;
        const id = form.dataset.id;
        const action = form.dataset.action || (id ? 'update' : 'create');
    const inputs = Array.from(form.querySelectorAll('input'));
    const selects = Array.from(form.querySelectorAll('select'));
    const data = { id };
    for (const inp of inputs){
        let v = inp.value;
        if (inp.name === 'allergenes' || inp.name === 'allergies'){
            // accept JSON array or comma-separated string
            v = v.trim();
            if (!v) { v = []; }
            else if (v.startsWith('[')) {
                try{ v = JSON.parse(v); } catch(e){ v = v; }
            } else {
                v = v.split(',').map(s=>s.trim()).filter(Boolean);
            }
        }
        if (inp.type === 'number' && v === '') v = null;
        data[inp.name] = v;
    }
    // handle selects (multiple)
    for (const sel of selects){
        const name = sel.name;
        if (sel.multiple) {
            const vals = Array.from(sel.selectedOptions).map(o => o.value);
            data[name] = vals;
        } else {
            data[name] = sel.value;
        }
    }
        // handle checkbox lists for allergenes/allergies
        const checkboxLists = Array.from(form.querySelectorAll('.multi-checkbox-allergenes'));
        for (const list of checkboxLists) {
            const name = list.dataset.name || list.getAttribute('data-name') || 'allergenes';
            const checked = Array.from(list.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
            data[name] = checked;
        }
        const r = await apiPost(resource, action, data);
    if (r.success){
        try { closeModal(); } catch (e) {}
        // prefer inline message via global helper if available
        try { if (window.showMessage) window.showMessage('Enregistré avec succès.', 'success'); else alert('Enregistré avec succès.'); } catch (e) { alert('Enregistré avec succès.'); }
    } else {
        try { if (window.showMessage) window.showMessage('Erreur: ' + (r.error || 'update failed'), 'error'); else alert('Erreur: ' + (r.error || 'update failed')); } catch (e) { alert('Erreur: ' + (r.error || 'update failed')); }
    }
}
</script>