function openTab(evt, tabName) {
    // cacher tous les tabcontent
    var tabcontent = document.getElementsByClassName("tabcontent");
    for (var i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; }

    // retirer la classe active de tous les boutons
    var tablinks = document.getElementsByClassName("tablinks");
    for (var i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }

    // afficher l'onglet courant
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

document.addEventListener("DOMContentLoaded", () => {
    const links = document.querySelectorAll("a[data-link]");
    const main = document.querySelector("main");
    const cache = {};

    // Reusable message/toast helper. type = 'success'|'error'|'info'
    function showMessage(text, type = 'info', timeout = 5000) {
        if (!main) { alert(text); return; }
        let container = document.getElementById('app-message-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'app-message-container';
            container.style.position = 'fixed';
            container.style.top = '10px';
            container.style.left = '50%';
            container.style.transform = 'translateX(-50%)';
            container.style.zIndex = 1060;
            document.body.appendChild(container);
        }
        const el = document.createElement('div');
        el.className = 'app-message app-message-' + type;
        el.style.minWidth = '240px';
        el.style.margin = '6px auto';
        el.style.padding = '10px 14px';
        el.style.borderRadius = '6px';
        el.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
        el.style.color = '#fff';
        el.style.fontSize = '14px';
        if (type === 'success') el.style.background = '#198754';
        else if (type === 'error') el.style.background = '#dc3545';
        else el.style.background = '#0d6efd';
        el.textContent = text;
        container.appendChild(el);
        setTimeout(() => {
            try { el.style.opacity = '0'; el.style.transition = 'opacity 300ms'; } catch (e) {}
            setTimeout(() => { try { el.remove(); } catch (e) {} }, 350);
        }, timeout);
    }
    // expose globally so inline scripts can call it
    window.showMessage = showMessage;

    // Helper: execute inline and external scripts contained inside a root element
    const execScripts = (root) => {
        if (!root) return;
        const scripts = Array.from(root.querySelectorAll('script'));
        scripts.forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            if (oldScript.src) {
                newScript.src = oldScript.src;
                document.head.appendChild(newScript);
            } else {
                newScript.text = oldScript.innerHTML;
                document.body.appendChild(newScript);
                document.body.removeChild(newScript);
            }
        });
    };

    // Google Maps loader - returns a Promise that resolves when google.maps is available
    function ensureGoogleMapsLoaded(key) {
        if (!key) return Promise.reject(new Error('No Google API key'));
        if (window.google && window.google.maps) return Promise.resolve();
        if (window.__gmaps_promise) return window.__gmaps_promise;

        window.__gmaps_promise = new Promise(function(resolve, reject){
            // If a script tag already exists that wasn't added by us, try to wait for it
            var existing = Array.from(document.querySelectorAll('script[src*="maps.googleapis.com"]'))[0];
            // Create a safe callback wrapper to resolve and cleanup timeout
            var settled = false;
            var cleanup = function(ok){
                if (settled) return; settled = true;
                try { if (timeoutId) clearTimeout(timeoutId); } catch (e) {}
                if (ok) resolve(); else reject(new Error('Google Maps failed to load'));
            };

            // Install callback that the Google API will call on successful load
            window.__gmaps_callback = function(){
                try { if (window.google && window.google.maps) { cleanup(true); return; } } catch (e) {}
                // If callback fired but google isn't available, treat as failure
                cleanup(false);
            };

            // If a script already exists, attach error handler and rely on callback/timeout
            if (existing) {
                existing.onerror = function(e){ cleanup(false); };
            } else {
                var s = document.createElement('script');
                s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(key) + '&libraries=places&callback=__gmaps_callback';
                s.async = true;
                s.defer = true;
                s.onerror = function(e){ cleanup(false); };
                document.head.appendChild(s);
            }

            // Some API errors (invalid key, referer error) cause the script to load but never call the callback.
            // Add a timeout to detect that situation and fail fast so fallback UI can be used.
            var timeoutId = setTimeout(function(){
                try {
                    if (window.google && window.google.maps) { cleanup(true); return; }
                } catch (e) {}
                cleanup(false);
            }, 8000);
        });
        return window.__gmaps_promise;
    }

    // Listen for SPA content loaded and initialize maps/autocomplete on location page
    window.addEventListener('spa:contentloaded', function(ev){
        var page = (ev && ev.detail && ev.detail.page) ? ev.detail.page : null;
        if (page === 'location' || location.search.indexOf('page=location') !== -1) {
                try { initLocationMap(); } catch (e) {}
                try { initPlaces(); } catch (e) { try { enableGeoFallback(); } catch (ie) {} }
        }
    });

    // Initialize the map on the Location page (if element #map exists)
    function initLocationMap() {
        var key = window.GOOGLE_PLACES_API_KEY || null;
        if (!key) return; // nothing to do without API key
        ensureGoogleMapsLoaded(key).then(function(){
            try {
                var mapEl = document.getElementById('map');
                if (!mapEl) return;
                // default center (Paris)
                var defaultCenter = { lat: 48.8566, lng: 2.3522 };
                // create map only once
                if (!window.__location_map) {
                    window.__location_map = new google.maps.Map(mapEl, { center: defaultCenter, zoom: 13 });
                    window.__location_marker = new google.maps.Marker({ map: window.__location_map });
                }
            } catch (e) { console.warn('initLocationMap error', e); }
        }).catch(function(err){ console.warn('Google Maps load failed', err); });
    }

    // Robust Places Autocomplete initializer (ensures library loaded)
    function initPlaces() {
        const key = window.GOOGLE_PLACES_API_KEY || null;
        if (!key) return;
        // guard: don't init multiple times for same input
        if (window.__places_initialized) return;
        // Ensure google maps + places library is loaded
        ensureGoogleMapsLoaded(key).then(function(){
            // Ensure Google Autocomplete menu appears above other UI elements
            try {
                var _s = document.createElement('style');
                _s.type = 'text/css';
                _s.appendChild(document.createTextNode('.pac-container{z-index:999999 !important;}'));
                document.head.appendChild(_s);
            } catch (e) { /* non-fatal */ }
            try {
                const input = document.getElementById('r_address');
                if (!input) return;
                // mark initialized so we don't double attach
                window.__places_initialized = true;
                // create autocomplete and request key fields
                const ac = new google.maps.places.Autocomplete(input, { types: ['address'] });
                if (typeof ac.setFields === 'function') ac.setFields(['place_id','formatted_address','geometry']);
                ac.addListener('place_changed', function(){
                    const place = ac.getPlace();
                    if (!place) return;
                    const pid = place.place_id || '';
                    const addr = place.formatted_address || input.value || '';
                    const pidEl = document.getElementById('r_place_id');
                    if (pidEl) pidEl.value = pid;
                    try { input.dataset.placeId = pid; } catch (e) {}
                    input.value = addr;
                    try { if (typeof showMessage === 'function') showMessage('Adresse sélectionnée et validée', 'success', 1400); } catch (e) {}
                    try { console.debug('Google Place selected', place); } catch (e) {}
                    try {
                        if (window.__location_map && place.geometry && place.geometry.location) {
                            window.__location_map.setCenter(place.geometry.location);
                            window.__location_map.setZoom(15);
                            if (!window.__location_marker) window.__location_marker = new google.maps.Marker({ map: window.__location_map });
                            window.__location_marker.setPosition(place.geometry.location);
                        }
                    } catch (e) { console.warn('Failed to update map for selected place', e); }
                });
            } catch (e) { console.warn('initPlaces error', e); }
        }).catch(function(err){ console.warn('Google Places load failed', err); });
    }

    // Fallback: simple suggestion box using server-side geo endpoint if Places is not available
    function enableGeoFallback() {
        const input = document.getElementById('r_address');
        if (!input) return;
        // avoid duplicate listeners
        if (input.__geo_fallback_attached) return;
        input.__geo_fallback_attached = true;

        // create suggestion container
        const list = document.createElement('div');
        list.className = 'geo-suggestions';
        list.style.position = 'absolute';
        list.style.zIndex = 99999;
        list.style.background = '#fff';
        list.style.border = '1px solid #ddd';
        list.style.display = 'none';
        list.style.maxHeight = '220px';
        list.style.overflowY = 'auto';
        list.style.width = (input.offsetWidth - 2) + 'px';
        input.parentNode.style.position = 'relative';
        input.parentNode.appendChild(list);

        let currentTimer = null;
        input.addEventListener('input', function(){
            const v = input.value.trim();
            if (currentTimer) clearTimeout(currentTimer);
            if (v.length < 3) { list.style.display = 'none'; return; }
            // wait briefly to let Google Autocomplete show suggestions if available
            currentTimer = setTimeout(function(){
                // if Google pac-container has visible items, don't show fallback
                const pac = document.querySelectorAll('.pac-container .pac-item');
                if (pac && pac.length > 0) { list.style.display = 'none'; return; }

                fetch('/api.php?page=geo&action=suggest&q=' + encodeURIComponent(v), { method: 'GET' })
                    .then(r => r.json())
                    .then(json => {
                        if (!json || !json.success) { list.style.display = 'none'; return; }
                        list.innerHTML = '';
                        json.suggestions.forEach(s => {
                            const it = document.createElement('div');
                            it.className = 'geo-suggestion-item';
                            it.style.padding = '8px';
                            it.style.cursor = 'pointer';
                            it.style.borderBottom = '1px solid #f2f2f2';
                            it.textContent = s.formatted_address;
                            it.dataset.placeId = s.place_id || '';
                            it.dataset.lat = s.lat || '';
                            it.dataset.lng = s.lng || '';
                            it.addEventListener('click', function(){
                                input.value = s.formatted_address;
                                const pidEl = document.getElementById('r_place_id'); if (pidEl) pidEl.value = s.place_id || '';
                                list.style.display = 'none';
                                try { if (typeof showMessage === 'function') showMessage('Adresse sélectionnée', 'success', 1200); } catch (e) {}
                            });
                            list.appendChild(it);
                        });
                        list.style.display = json.suggestions.length ? 'block' : 'none';
                    }).catch(e => { list.style.display = 'none'; });
            }, 350);
        });

        document.addEventListener('click', function(ev){ if (!list.contains(ev.target) && ev.target !== input) list.style.display = 'none'; });
        window.addEventListener('resize', function(){ list.style.width = (input.offsetWidth - 2) + 'px'; });
    }

    function setActiveLink(page) {
        links.forEach(link => {
            if (link.getAttribute("data-link") === page) {
                link.classList.add("active");
                link.setAttribute("aria-current", "page");
            } else {
                link.classList.remove("active");
                link.removeAttribute("aria-current");
            }
        });
    }

    function navigateTo(page, addToHistory = true) {
        if (!page) page = "home";
        if (addToHistory) {
            history.pushState(null, "", `?page=${page}`);
        }
        
        setActiveLink(page);

        if (cache[page]) {
            main.innerHTML = cache[page];
            // remove any init guards stored in attributes so pages re-run their init
            try {
                const marked = main.querySelectorAll('[data-calendar-init], [data-initialized]');
                marked.forEach(function(el){ el.removeAttribute('data-calendar-init'); el.removeAttribute('data-initialized'); });
            } catch (e) {}
            // execute any scripts that were included in the returned HTML
            execScripts(main);
            // notify pages injected via cache
            try { window.dispatchEvent(new CustomEvent('spa:contentloaded', { detail: { page } })); } catch (e) {}
            return;
        }

        fetch('/api.php?page=' + encodeURIComponent(page))
            .then(res => {
                const ct = (res.headers.get('content-type') || '').toLowerCase();
                if (ct.includes('application/json')) return res.json();
                // if server returned HTML (error page or raw content), use .text()
                return res.text().then(text => ({ success: true, content: text }));
            })
            .then(data => {
                if (!data) throw new Error('Aucune réponse du serveur');
                if (data.success === false) {
                    console.error('Erreur API:', data.error || data);
                    main.innerHTML = `<h2>Erreur API</h2><pre>${data.error || JSON.stringify(data)}</pre>`;
                    return;
                }
                // Sécurité : si content est undefined, afficher message clair
                if (typeof data.content === 'undefined') {
                    console.warn('Réponse API sans content:', data);
                    main.innerHTML = '<h2>Réponse invalide du serveur</h2>';
                    return;
                }
                cache[page] = data.content;
                main.innerHTML = data.content;
                // Execute any scripts that were included in the returned HTML.
                execScripts(main);
                // Notify injected page scripts that content was loaded so they can initialize
                try { window.dispatchEvent(new CustomEvent('spa:contentloaded', { detail: { page } })); } catch (e) {}
            })
            .catch(err => {
                console.error("Erreur API :", err);
                main.innerHTML = "<h2>Erreur de chargement</h2>";
            });
    }

    document.addEventListener("click", e => {
        const link = e.target.closest("a[data-link]");
        if (!link) return;

        e.preventDefault();
        navigateTo(link.getAttribute("data-link"));
    });

    window.addEventListener("popstate", () => {
        const params = new URLSearchParams(window.location.search);
        navigateTo(params.get("page"), false);
    });

    const params = new URLSearchParams(window.location.search);

    navigateTo(params.get("page") || "home");

    // Proactively attempt to load Google Maps if a key exists but the API hasn't been loaded yet.
    try {
        var proactiveKey = window.GOOGLE_PLACES_API_KEY || null;
        if (proactiveKey && !(window.google && window.google.maps) && !window.__gmaps_attempted) {
            window.__gmaps_attempted = true;
            ensureGoogleMapsLoaded(proactiveKey).then(function(){
                console.info('Google Maps/Places loaded proactively');
                try { initPlaces(); } catch (e) {}
            }).catch(function(err){
                console.warn('Proactive Google Maps load failed:', err);
                try { enableGeoFallback(); } catch (e) {}
            });
        }
    } catch (e) {}

    var firstTab = document.getElementsByClassName('tablinks')[0];
    if(firstTab) firstTab.click();

    // Reservation modal handling (shared)
    let reservationModalEl = document.getElementById('reservationModal');
    let reservationModal = null;
    const getReservationModal = () => {
        reservationModalEl = document.getElementById('reservationModal');
        if (!reservationModalEl) return null;
        if (typeof bootstrap !== 'undefined') {
            try {
                return new bootstrap.Modal(reservationModalEl);
            } catch (e) {
                return null;
            }
        }
        return null;
    };

    function openReservationModal(opts) {
    reservationModalEl = document.getElementById('reservationModal');
    if (!reservationModalEl) return;
        // fill fields
        document.getElementById('r_type').value = opts.type || '';
        document.getElementById('r_item_id').value = opts.item_id || '';
        document.getElementById('r_title').value = opts.title || '';
        // if an ISO date is provided, try to set start (datetime-local expects YYYY-MM-DDTHH:MM)
        if (opts.date_event) {
            try {
                const d = new Date(opts.date_event);
                if (!isNaN(d.getTime())) {
                    const iso = d.toISOString();
                    // keep only YYYY-MM-DDTHH:MM
                    document.getElementById('r_start').value = iso.substring(0,16);
                }
            } catch (e) {}
        }
        // try to use bootstrap modal first
        reservationModal = getReservationModal();
        if (reservationModal && typeof reservationModal.show === 'function') {
            reservationModal.show();
            return;
        }

        // fallback: manually show the modal by toggling classes
        reservationModalEl.classList.add('show');
        reservationModalEl.style.display = 'block';
        reservationModalEl.removeAttribute('aria-hidden');
        reservationModalEl.setAttribute('aria-modal', 'true');
        // add a backdrop
        let backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'reservation-backdrop';
        document.body.appendChild(backdrop);

    // initialize places (autocomplete) when opening modal — uses top-level initializer
    try { initPlaces(); } catch (e) { console.warn('initPlaces failed', e); try { enableGeoFallback(); } catch (ie) {} }
    }

    // Delegate clicks for reserve buttons (they are inside dynamic content)
    document.body.addEventListener('click', function(e){
        const btn = e.target.closest('.js-open-reservation');
        if (!btn) return;
        e.preventDefault();
        const type = btn.getAttribute('data-reserve-type') || '';
        const item_id = btn.getAttribute('data-item-id') || '';
        const title = btn.getAttribute('data-title') || '';
        const dateEvent = btn.getAttribute('data-date-event') || '';
        openReservationModal({ type: type, item_id: item_id, title: title, date_event: dateEvent });
    });

    // Submit reservation form
    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(ev){
            ev.preventDefault();
            // If Google Places is enabled, require a selected place_id to ensure validated address
            if (window.GOOGLE_PLACES_API_KEY) {
                const pid = document.getElementById('r_place_id');
                if (!pid || !pid.value) {
                    showMessage('Veuillez sélectionner une adresse valide dans la liste proposée.', 'error');
                    return;
                }
            }
            const fd = new FormData(reservationForm);
            // Add action and payload
            fd.append('action', 'create');
            // send as FormData
            fetch('/api.php?page=reservations', {
                method: 'POST',
                body: fd
            }).then(res => res.text().then(text => ({ status: res.status, text }))).then(({status, text}) => {
                // Try to parse JSON, otherwise show raw text (helps surface PHP errors or HTML)
                let json = null;
                try { json = JSON.parse(text); } catch (e) { json = null; }
                if (json && json.success) {
                    // hide modal (bootstrap or fallback)
                    reservationModal = getReservationModal();
                    if (reservationModal && typeof reservationModal.hide === 'function') {
                        reservationModal.hide();
                    } else if (reservationModalEl) {
                        reservationModalEl.classList.remove('show');
                        reservationModalEl.style.display = 'none';
                        reservationModalEl.setAttribute('aria-hidden', 'true');
                        const bd = document.getElementById('reservation-backdrop'); if (bd) bd.remove();
                    }
                    showMessage('Réservation enregistrée.', 'success');
                    // refresh displayed availability for the reserved item if provided in the form
                    try {
                        const type = document.getElementById('r_type').value;
                        const itemId = document.getElementById('r_item_id').value;
                        if (type && itemId) {
                            // map type to api page
                            const page = (type === 'panier') ? 'paniers' : (type === 'planche' ? 'planches' : (type === 'evenement' ? 'evenements' : null));
                            if (page) {
                                const fd2 = new FormData(); fd2.append('action','get'); fd2.append('id', itemId);
                                fetch('/api.php?page=' + page, { method: 'POST', body: fd2 }).then(r => r.json()).then(data2 => {
                                    if (data2 && data2.success && data2.item) {
                                        const selector = `.item-dispo[data-item-type="${type}"][data-item-id="${itemId}"]`;
                                        const els = document.querySelectorAll(selector);
                                        els.forEach(function(el){
                                            if (page === 'evenements') {
                                                el.textContent = data2.item.nb_places ?? data2.item.nb_places_remaining ?? '0';
                                            } else {
                                                el.textContent = data2.item.disponible ?? '0';
                                            }
                                        });

                                        // Also update SPA cache for this page if present so navigating back shows fresh values
                                        try {
                                            if (typeof cache !== 'undefined' && cache[page]) {
                                                const tmp = document.createElement('div');
                                                tmp.innerHTML = cache[page];
                                                const tmpEls = tmp.querySelectorAll(selector);
                                                tmpEls.forEach(function(el){
                                                    if (page === 'evenements') {
                                                        el.textContent = data2.item.nb_places ?? data2.item.nb_places_remaining ?? '0';
                                                    } else {
                                                        el.textContent = data2.item.disponible ?? '0';
                                                    }
                                                });
                                                cache[page] = tmp.innerHTML;
                                            }
                                        } catch (ee) { /* non-fatal */ }
                                    }
                                }).catch(e => { console.warn('Failed to refresh item after reservation', e); });
                            }
                        }
                    } catch (e) { console.warn('refresh availability error', e); }
                    return;
                }

                // Not success — prefer server-provided error message
                let errMsg = 'Erreur inconnue';
                if (json && json.error) errMsg = json.error;
                else if (text && text.trim()) errMsg = text.trim();

                console.error('Reservation failed', status, errMsg);
                // show a helpful inline message with server response
                const max = 2000;
                showMessage('Erreur lors de la réservation: ' + (errMsg.length > max ? errMsg.substring(0, max) + '\n--- (truncated) ---' : errMsg), 'error');
            }).catch(err => {
                console.error('Reservation network error', err);
                showMessage('Erreur réseau lors de la réservation. Voir console pour détails.', 'error');
            });
        });
    }
    // attach fallback hide to modal close buttons
    document.body.addEventListener('click', function(e){
        const close = e.target.closest('[data-bs-dismiss="modal"]');
        if (!close) return;
        const modal = e.target.closest('.modal');
        if (modal && typeof bootstrap === 'undefined') {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            const bd = document.getElementById('reservation-backdrop'); if (bd) bd.remove();
        }
    });
});