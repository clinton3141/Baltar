<?php


namespace Seer\Checker;

class CheckerNotFoundException extends \Exception {
	public function __construct ($error) {
		parent::__construct($error);
	}
}
