<?php

namespace Seer\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\DBAL\Connection;

class UserProvider implements UserProviderInterface
{
	private $conn;

	public function __construct (Connection $conn) {
		$this->conn = $conn;
	}

	public function loadUserByUserName($username) {
		$query = $this->conn->executeQuery('SELECT * FROM users WHERE username = LOWER(?)', array ($username));
		if (!$user = $query->fetch()) {
			throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
		}
		return new User($user['username'], $user['password'], explode(',', $user['roles']), true, true, true, true);
	}

	public function refreshUser (UserInterface $user) {
		if (!$user instanceof User) {
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
		}
		return $this->loadUserByUserName($user->getUsername());
	}

	public function supportsClass($class) {
		return $class === 'Symfony\Component\Security\Core\User\User';
	}
}
