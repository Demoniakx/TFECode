    <footer class="footer mt-5">
        <div class="container text-center">
            <div class="row mb-4">
                <!-- Coordonnées -->
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">Contact</h5>
                    <p><i class="bi bi-telephone-fill"></i> <a href="tel:+330123456789">01 23 45 67 89</a></p>
                    <p><i class="bi bi-envelope-fill"></i> <a href="mailto:contact@montraiteur.fr">contact@montraiteur.fr</a></p>
                    <p><i class="bi bi-geo-alt-fill"></i> 12 Rue des Saveurs, 75000 Paris</p>
                </div>

                <!-- Réseaux sociaux -->
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">Suivez-nous</h5>
                    <a href="#" class="social-link me-2"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-link me-2"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-link me-2"><i class="bi bi-youtube"></i></a>
                </div>

                <!-- Horaires -->
                <div class="col-md-4 mb-3">
                    <h5 class="footer-title">Horaires</h5>
                    <p>Lundi - Vendredi : 9h - 18h</p>
                    <p>Samedi : 10h - 14h</p>
                    <p>Dimanche : Fermé</p>
                </div>
            </div>

            <div class="row border-top pt-3">
                <div class="col">
                    <p class="small">&copy; <?= date('Y'); ?> Thomas Cooking. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>
<!-- Reservation modal (shared) -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservationModalLabel">Réserver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reservationForm">
                <div class="modal-body">
                    <input type="hidden" name="type" id="r_type" value="">
                    <input type="hidden" name="item_id" id="r_item_id" value="">
                    <div class="mb-3">
                        <label for="r_title" class="form-label">Objet</label>
                        <input type="text" class="form-control" id="r_title" name="title" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="r_start" class="form-label">Date de retrait</label>
                        <input type="date" class="form-control" id="r_start" name="start">
                    </div>
                    <div class="mb-3">
                        <label for="r_address" class="form-label">Adresse</label>
                        <input type="search" class="form-control" id="r_address" name="adresse_client" placeholder="Saisissez une adresse" autocomplete="off">
                        <input type="hidden" id="r_place_id" name="place_id" value="">
                        <div class="form-text">L'adresse sera validée via Google Places.</div>
                    </div>
                    <div class="mb-3">
                        <label for="r_name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="r_name" name="name" required>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-6">
                            <label for="r_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="r_email" name="email" required>
                        </div>
                        <div class="mb-3 col-6">
                            <label for="r_phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="r_phone" name="phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="r_notes" class="form-label">Remarques</label>
                        <textarea class="form-control" id="r_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="r_submit">Envoyer la réservation</button>
                </div>
            </form>
        </div>
    </div>
</div>

        <script src="/assets/js/app.js"></script>
</body>

</html>