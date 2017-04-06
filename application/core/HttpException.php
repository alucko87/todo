<?php

class HttpException extends Exception {

	const HTTP_404 = 404;
	public $statusMsg;

	public function __construct($code = 500) {
		switch($code) {

			case self::HTTP_404:
				$this->statusMsg = 'HTTP/1.1 404 Not Found';
				break;

			default:
				// $this->statusMsg = 'HTTP/1.1 404 Not Found';
				$this->statusMsg = 'HTTP/1.1 500 Internal Server Error';
		}

		parent::__construct($this->statusMsg);
	}
	
}

?>