<?php

namespace Seer\Checker;

use Seer\User\User;
use Doctrine\DBAL\Connection;

class CheckerProvider
{
	private $conn;

	public function __construct (Connection $conn) {
		$this->conn = $conn;
	}

	public function getCheckersByUser (User $user) {
		$user_id = $user->getId();
		$query = $this->conn->executeQuery('SELECT * FROM checkers WHERE user_id = ?', array ($user_id));
		$checkers = array ();
		if (!$_checkers = $query->fetchAll()) {
			throw new CheckerNotFoundException(sprintf('No checkers found for user id: %s', $user_id));
		}
		foreach ($_checkers as $checker) {
			$checkers[] = new Checker($checker['id'], $checker['user_id'], $checker['name'], $checker['url'], $checker['text'], $checker['invert'], $checker['is_active']);
		}
		return $checkers;
	}

	public function createCheckerForUser (User $user) {
		return new Checker(false, $user->getId());
	}

	public function getCheckerByIdAndUser ($id, User $user) {
		$query = $this->conn->executeQuery('SELECT * FROM checkers WHERE id = ? AND user_id = ?', array ($id, $user->getId()));
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
