<div class="login-container container my-5">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card">
				<div class="card-body">
					<h2 class="card-title text-center">Connexion</h2>
					<div id="login-error" class="alert alert-danger" style="display:none;"></div>
					<form id="login-form" method="post" action="/api.php?page=login">
						<div class="form-group">
							<label for="email">Email</label>
							<input type="email" id="email" name="email" class="form-control" placeholder="votre@email.com" required>
						</div>
						<div class="form-group">
							<label for="password">Mot de passe</label>
							<input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
						</div>
						<div class="form-group mt-3 text-center">
							<button type="submit" id="login-submit" class="btn btn-reserver">Se connecter</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const form = document.getElementById('login-form');
	const submit = document.getElementById('login-submit');
	const errorBox = document.getElementById('login-error');

	form.addEventListener('submit', function(e) {
		e.preventDefault();
		errorBox.style.display = 'none';
		submit.disabled = true;

		const data = new FormData(form);

		fetch(form.action, {
			method: 'POST',
			body: data
		})
		.then(res => res.json())
		.then(json => {
			if (!json) throw new Error('Aucune réponse du serveur');
			if (json.success) {
				window.location.href = json.redirect || '/TFECode/public/index.php';
			} else {
				errorBox.innerText = json.error || 'Identifiants invalides';
				errorBox.style.display = 'block';
			}
		})
		.catch(err => {
			errorBox.innerText = err.message || 'Erreur réseau';
			errorBox.style.display = 'block';
		})
		.finally(() => submit.disabled = false);
	});
});
</script>
