<?php
require_once __DIR__ . "/../models/User.php";

class UserController {
	private $model;

	public function __construct(PDO $db) {
		$this->model = new User($db);
		if (session_status() === PHP_SESSION_NONE) session_start();
	}

	// Authentifier l'utilisateur
	public function authenticate($email, $password) {
		$user = $this->model->findByEmail($email);
		if (!$user) return ['success' => false, 'error' => 'Utilisateur introuvable'];

		if (password_verify($password, $user['password'])) {
			// Auth OK, enregistrer l'utilisateur en session
			$_SESSION['user'] = [
				'id' => $user['id'],
				'email' => $user['email'],
				'role' => $user['role'] ?? 'user'
			];
			// User requested: redirect to http://tfe/?page=home after successful login
			return ['success' => true, 'redirect' => 'http://tfe/?page=home'];
		}

		return ['success' => false, 'error' => 'Mot de passe incorrect'];
	}

	// Déconnexion
	public function logout() {
		if (session_status() === PHP_SESSION_NONE) session_start();
		session_unset();
		session_destroy();
	}
}
?>
