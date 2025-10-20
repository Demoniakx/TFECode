<section id="location-page" class="location py-5">
    <style>
        /* Mini calendar availability coloring */
        .cal-day.available { background: linear-gradient(180deg, #e9f7ef, #f6fff7); border: 1px solid #c6f0d0; }
        .cal-day.unavailable { background: linear-gradient(180deg, #fdecea, #fff6f6); border: 1px solid #f5c2c7; }
        .cal-day.unavailable .date-num { color: #b30000; font-weight:600; }
        .cal-day.available .date-num { color: #1a7f2e; }
    </style>
    <div class="container">
        <h2 class="text-center mb-5">Location de Salle pour vos Événements</h2>
        <p class="lead text-center mb-4">Organisez vos événements gourmands dans notre espace chaleureux, entièrement équipé.</p>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card p-3">
                    <div class="mini-calendar">
                        <div class="cal-header">
                            <div>
                                <button id="cal-prev" class="nav-btn">◀</button>
                                <button id="cal-today" class="nav-btn">Aujourd'hui</button>
                                <button id="cal-next" class="nav-btn">▶</button>
                            </div>
                            <div class="title" id="cal-title"></div>
                            <div></div>
                        </div>
                    <div class="cal-grid" id="cal-weekdays"></div>
                <div class="cal-grid" id="cal-days"></div>
            </div>
        </div>
            </div>
            <div class="col-lg-5">
                <div class="card p-3">
                    <h4 class="mb-3">Demande de réservation</h4>
                    <form id="reservation-form">
                        <div class="mb-2">
                            <label for="r_title">Objet</label>
                            <input id="r_title" name="title" class="form-control" placeholder="Ex: Atelier cuisine" required />
                        </div>
                        <div class="mb-2">
                            <label for="r_name">Nom</label>
                            <input id="r_name" name="name" class="form-control" placeholder="Votre nom" required />
                        </div>
                        <div class="mb-2">
                            <label for="r_email">Email</label>
                            <input id="r_email" name="email" type="email" class="form-control" placeholder="email@exemple.com" required />
                        </div>
                        <div class="mb-2">
                            <label for="r_phone">Téléphone</label>
                            <input id="r_phone" name="phone" class="form-control" placeholder="0600000000" />
                        </div>
                        <div class="mb-2">
                            <label for="r_start">Date et heure</label>
                            <input id="r_start" name="start" type="datetime-local" class="form-control" required />
                        </div>
                        <div class="mb-2">
                            <label for="r_quantity">Nombre de personnes</label>
                            <input id="r_quantity" name="quantity" type="number" min="1" class="form-control" placeholder="Ex: 10" />
                        </div>
                        <div class="mb-2">
                            <label for="r_notes">Notes</label>
                            <textarea id="r_notes" name="notes" class="form-control" rows="3" placeholder="Besoin particulier, configuration, nombre d'invités"></textarea>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" value="1" id="r_traiteur" name="want_traiteur">
                            <label class="form-check-label" for="r_traiteur">Je souhaite le service traiteur</label>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                        </div>
                        <div id="res-message" class="mt-2" aria-live="polite"></div>
                    </form>
                </div>

                <div class="card p-3 mt-3">
                    <h5>Où nous trouver</h5>
                    <div id="map" style="width:100%;height:240px;background:#e6e9ee;display:flex;align-items:center;justify-content:center;">Google Maps Placeholder</div>
                </div>
            </div>
        </div>
    </div>





    <script>
        function initLocationCalendar(){
        (function(){
            // utility
            function pad(n){return n<10? '0'+n: ''+n}
            function toYMD(d){ return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
            function monthTitle(year, month){ return new Date(year, month, 1).toLocaleString(undefined, { month: 'long', year: 'numeric' }); }

            var state = { viewYear: (new Date()).getFullYear(), viewMonth: (new Date()).getMonth(), eventsByDay: {} };

            // prevent double initialization when page is injected multiple times
            var rootEl = document.getElementById('location-page');
            if (rootEl && rootEl.dataset.calendarInit) return;

            var weekdays = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
            var wdContainer = document.getElementById('cal-weekdays');
            // only create weekdays header if not already populated
            if (wdContainer && wdContainer.children.length === 0) {
                weekdays.forEach(function(w){ var el = document.createElement('div'); el.className='cal-weekday'; el.textContent = w; wdContainer.appendChild(el); });
            }

            function fetchEvents(){
                return fetch('/api.php?page=reservations', { method: 'POST', body: new URLSearchParams({ action: 'list', type: 'salle' }) })
                    .then(function(r){ return r.text(); })
                    .then(function(t){ try{ return JSON.parse(t); }catch(e){ console.error('Invalid JSON from reservations API', t); return null; } })
                    .then(function(data){
                        state.eventsByDay = {};
                        if (data && data.events) {
                            data.events.forEach(function(ev){
                                    // try multiple possible date fields returned by the API
                                    var rawDate = ev.start || ev.date || ev.start_dt || ev.date_event || ev.start;
                                    var start = null;
                                    if (rawDate) {
                                        try {
                                            if (typeof rawDate === 'string') {
                                                if (rawDate.indexOf('T') !== -1) start = rawDate.split('T')[0];
                                                else if (rawDate.indexOf(' ') !== -1) start = rawDate.split(' ')[0];
                                                else start = rawDate;
                                            } else {
                                                start = (new Date(rawDate)).toISOString().slice(0,10);
                                            }
                                        } catch (e) {
                                            start = null;
                                        }
                                    }
                                    if (!start && ev.start) {
                                        try { start = (new Date(ev.start)).toISOString().slice(0,10); } catch (e) { start = null; }
                                    }
                                    if (!start) return;
                                if (!state.eventsByDay[start]) state.eventsByDay[start]=[];
                                state.eventsByDay[start].push(ev);
                            });
                        }
                        renderCalendar();
                    }).catch(function(err){ console.error('Error fetching events', err); });
            }

            function isPrivEvent(e){
                if (!e) return false;
                var t = (e.title || '').toString().toLowerCase();
                var notes = (e.extendedProps && (e.extendedProps.notes || e.extendedProps.info)) ? (e.extendedProps.notes || e.extendedProps.info).toString().toLowerCase() : '';
                var name = (e.extendedProps && e.extendedProps.name) ? e.extendedProps.name.toString().toLowerCase() : '';
                // look for 'privat' substring to catch privatisation/privatisé/etc.
                if (t.indexOf('privat') !== -1) return true;
                if (notes.indexOf('privat') !== -1) return true;
                if (name.indexOf('privat') !== -1) return true;
                // boolean-like flags that might be present
                if (e.privatisation === 1 || e.privatisation === '1' || e.is_private === 1 || e.is_private === '1' || e.private === 1 || e.private === '1') return true;
                if (e.extendedProps && (e.extendedProps.privatisation === 1 || e.extendedProps.privatisation === '1' || e.extendedProps.is_private === 1 || e.extendedProps.is_private === '1')) return true;
                return false;
            }

            function renderCalendar(){
                var daysContainer = document.getElementById('cal-days');
                daysContainer.innerHTML='';
                document.getElementById('cal-title').textContent = monthTitle(state.viewYear, state.viewMonth);

                var firstDay = new Date(state.viewYear, state.viewMonth, 1);
                // we want Monday-first; getDay: 0=Sun..6=Sat
                var shift = (firstDay.getDay() + 6) % 7; // days to show from prev month
                var startDate = new Date(firstDay); startDate.setDate(1 - shift);

                for (var i=0;i<42;i++){
                    var d = new Date(startDate); d.setDate(startDate.getDate()+i);
                    var cell = document.createElement('div'); cell.className='cal-day';
                    if (d.getMonth() !== state.viewMonth) cell.classList.add('other');
                    var num = document.createElement('div'); num.className='date-num'; num.textContent = d.getDate(); cell.appendChild(num);
                    var ymd = toYMD(d);
                    var evs = state.eventsByDay[ymd] || [];
                    // If any reservation/event exists on that day, mark as unavailable (red).
                    // Also detect privatisation separately for metadata.
                    var privCount = 0;
                    for (var j=0;j<evs.length;j++){ if (isPrivEvent(evs[j])) privCount++; }
                    if (evs.length > 0) {
                        cell.classList.add('unavailable');
                        cell.dataset.eventCount = evs.length;
                        if (privCount > 0) cell.dataset.privatisation = '1';
                        else cell.dataset.privatisation = '0';
                    } else {
                        cell.classList.add('available');
                    }
                    // debug: log YMD and count
                    if (evs.length > 0) console.debug('cal day', ymd, 'events=', evs.length, 'priv=', privCount);
                    cell.dataset.ymd = ymd;
                    cell.addEventListener('click', function(){ onDayClick(this.dataset.ymd); });
                    daysContainer.appendChild(cell);
                }
            }

            function onDayClick(ymd){
                // show events in the message area and prefill form start
                var events = state.eventsByDay[ymd] || [];
                var msg = document.getElementById('res-message');
                if (events.length===0) {
                    msg.innerHTML = '<div class="alert alert-info">Aucun évènement ce jour. Vous pouvez créer une réservation.</div>';
                } else {
                    // Only show a simple unavailable message (do not list reservation details)
                    msg.innerHTML = '<div class="alert alert-danger">Cette date est indisponible — une réservation existe déjà pour ce jour.</div>';
                }
                // prefill form start with date at 09:00 by default
                var startInput = document.getElementById('r_start');
                if (startInput) {
                    startInput.value = ymd + 'T09:00';
                }
            }

            // attach navigation listeners only once
            var btnPrev = document.getElementById('cal-prev');
            if (btnPrev && !btnPrev.dataset.listener) {
                btnPrev.addEventListener('click', function(){ var m = state.viewMonth-1; if (m<0){ state.viewYear--; state.viewMonth=11;} else state.viewMonth=m; renderCalendar(); });
                btnPrev.dataset.listener = '1';
            }
            var btnNext = document.getElementById('cal-next');
            if (btnNext && !btnNext.dataset.listener) {
                btnNext.addEventListener('click', function(){ var m = state.viewMonth+1; if (m>11){ state.viewYear++; state.viewMonth=0;} else state.viewMonth=m; renderCalendar(); });
                btnNext.dataset.listener = '1';
            }
            var btnToday = document.getElementById('cal-today');
            if (btnToday && !btnToday.dataset.listener) {
                btnToday.addEventListener('click', function(){ var now=new Date(); state.viewYear=now.getFullYear(); state.viewMonth=now.getMonth(); renderCalendar(); });
                btnToday.dataset.listener = '1';
            }

            // initial load
            fetchEvents();

            // mark initialized on root to prevent double-init
            if (rootEl) rootEl.dataset.calendarInit = '1';

            // handle form submit via AJAX and refresh calendar
            var form = document.getElementById('reservation-form');
            form.addEventListener('submit', function(ev){
                ev.preventDefault();
                // client-side: prevent submission if the selected day already has a reservation
                var startInput = document.getElementById('r_start');
                var selectedYmd = null;
                if (startInput && startInput.value) {
                    var s = startInput.value;
                    if (s.indexOf('T')!==-1) selectedYmd = s.split('T')[0];
                    else if (s.indexOf(' ')!==-1) selectedYmd = s.split(' ')[0];
                    else selectedYmd = s;
                }
                if (selectedYmd && (state.eventsByDay[selectedYmd] || []).length > 0) {
                    var msg = document.getElementById('res-message');
                    msg.innerHTML = '<div class="alert alert-danger">La réservation pour cette date n\'est pas disponible car il y a déjà une réservation à cette date.</div>';
                    return;
                }
                var data = new FormData(form);
                data.append('action','create');
                data.append('type','salle');
                fetch('/api.php?page=reservations', { method: 'POST', body: data })
                    .then(function(r){ return r.text(); })
                    .then(function(t){ try{ return JSON.parse(t); }catch(e){ console.error('Invalid JSON from create reservation', t); return null; } })
                    .then(function(json){
                        var msg = document.getElementById('res-message');
                        console.log('create reservation response', json);
                        if (json && json.success) {
                            msg.innerHTML = '<div class="alert alert-success">Demande envoyée. Nous vous contacterons bientôt.</div>';
                            form.reset();
                            fetchEvents();
                        } else {
                            msg.innerHTML = '<div class="alert alert-danger">Erreur lors de la création de la réservation.</div>';
                        }
                    }).catch(function(e){
                        var msg = document.getElementById('res-message');
                        console.error('Network error', e);
                        msg.innerHTML = '<div class="alert alert-danger">Erreur réseau.</div>';
                    });
            });
        })();
        }

        // initialize on full page load
        try { initLocationCalendar(); } catch (e) {}

        // initialize when SPA injects this page
        window.addEventListener('spa:contentloaded', function(ev){
            if (ev && ev.detail && ev.detail.page === 'location') {
                try { initLocationCalendar(); } catch (e) {}
            }
        });
    </script>
</section>
