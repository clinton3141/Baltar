<?php

namespace Seer\Checker;

use Doctrine\DBAL\Connection;

class CheckerProvider
{
	private $conn;

	public function __construct (Connection $conn) {
		$this->conn = $conn;
	}

	public function getCheckersByUserName($username) {
		$query = $this->conn->executeQuery('SELECT * FROM checkers WHERE user_id = (SELECT id FROM users WHERE username = ?)', array ($username));
		$checkers = array ();
		if (!$_checkers = $query->fetchAll()) {
			throw new CheckerNotFoundException(sprintf('No checkers found for username: %s', $username));
		}
		foreach ($_checkers as $checker) {
			$checkers[] = new Checker($checker['id'], $checker['user_id'], $checker['name'], $checker['url'], $checker['text'], $checker['invert'], $checker['is_active']);
		}
		return $checkers;
	}

	public function createCheckerForUserName ($username) {
		// TODO: this doesn't quite feel right...
		$query = $this->conn->executeQuery('SELECT id FROM users WHERE username = ?', array($username));
		if (!$user = $query->fetch()) {
			throw new \Exception ();
		}
		return new Checker(false, $user['id']);
	}

	public function getCheckerByIdAndUserName($id, $username) {
		$query = $this->conn->executeQuery('SELECT * FROM checkers WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)', array ($id, $username));
		if (!$checker = $query->fetch()) {
			throw new CheckerNotFoundException(sprintf('You do not own that checker'));
		}
		return new Checker($checker['id'], $checker['user_id'], $checker['name'], $checker['url'], $checker['text'], $checker['invert'], $checker['is_active']);
	}

	public function getCheckerById($id) {
		$query = $this->conn->executeQuery('SELECT * FROM checkers WHERE id = ?', array ($id));
		if (!$checker = $query->fetch()) {
			throw new CheckerNotFoundException(sprintf('Checker with id: %i does not exist', $id));
		}
		return new Checker($checker['id'], $checker['user_id'], $checker['name'], $checker['url'], $checker['text'], $checker['invert'], $checker['is_active']);
	}
}
