<?php

namespace Seer\User;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
	private $username;
	private $email;
	private $salt;
	private $password;
	private $roles;
	private $id;

	public function __construct ($id, $username, $email, $password, $salt, array $roles)
	{
		$this->id = $id;
		$this->email = $email;
		$this->username = $username;
		$this->password = $password;
		$this->salt = $salt;
		$this->roles = $roles;
	}

	public function getEmail ()
	{
		return $this->email;
	}

	public function getRoles ()
	{
		return $this->roles;
	}

	public function getPassword ()
	{
		return $this->password;
	}

	public function getUsername ()
	{
		return $this->username;
	}

	public function getSalt ()
	{
		return $this->salt;
	}

	public function eraseCredentials ()
	{

	}

	public function getId ()
	{
		return $this->id;
	}

	public function equals (UserInterface $user)
	{
		if (!$user instanceof User) {
			return false;
		}
		if ($this->password !== $user->getPassword()) {
			return false;
		}
		if ($this->salt !== $user->getSalt()) {
			return false;
		}
		if ($this->username !== $user->getUsername()) {
			return false;
		}
		return true;
	}
}
