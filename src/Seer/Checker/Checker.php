<?php

namespace Seer\Checker;

class Checker
{
	protected $id;

	protected $user_id;

	protected $name;

	protected $url;

	protected $text;

	protected $invert;

	protected $is_active;

	public function __construct ($id, $user_id, $name, $url, $text, $invert, $is_active) {
		$this->id = $id;
		$this->user_id = $user_id;
		$this->name = $name;
		$this->url = $url;
		$this->text = $text;
		$this->invert = $invert;
		$this->is_active = $is_active;
	}

	public function id () {
		return $this->id;
	}

	public function is_active () {
		return $this->is_active;
	}

	public function user () {
		return $this->user_id;
	}

	public function name () {
		return $this->name;
	}

	public function url () {
		return $this->url;
	}

	public function text () {
		return $this->text;
	}

	public function invert () {
		return $this->invert;
	}

	public function belongsTo ($user_id) {
		return $this->user_id == $user_id;
	}

	public function save ($conn) {
		if ($this->id) {
			$conn->update('checkers', array(
					'name' => $this->name,
					'url' => $this->url,
					'text' => $this->text,
					'invert' => $this->invert,
					'is_active' => $this->is_active
				), array (
					'id' => $this->id
				)
			);
		} else {
			$conn->insert('checkers', array(
				'user_id' => $this->user_id,
				'name' => $this->name,
				'url' => $this->url,
				'text' => $this->text,
				'invert' => $this->invert
			));
			$this->id = $conn->lastInsertId();
		}
	}

	public function __set ($key, $val) {
		$this->$key = $val;
	}
}
