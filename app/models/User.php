<?php
class User {
	private $db;
	private $table = 'users';

	public function __construct(PDO $db) {
		$this->db = $db;
	}

	// Trouver un utilisateur par email
	public function findByEmail($email) {
		$sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':email' => $email]);
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}
?>
