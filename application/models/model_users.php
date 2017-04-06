<?php

class Model_Users extends Model
{

	protected $tableName = 'users';

	protected $colsNames = array(
		'id',
		'name',
		'pwd',
		'create_date',
		'token'
		);

	public function __construct($regime = 'search', $cols = array())
	{

		parent::__construct($regime, $cols);

	}

	protected function getInputMap()
	{

		return array(
			array('name', 'name'),
			array('password', 'password'),
			'counter' => 'usersCnt',
		);

		// return array(
		// 	array(
		// 		'values' => array(
		// 			'id' => isset($this->cols['id']) ? $this->cols['id'] : null,
		// 			'name' => isset($_POST['name']) ? $_POST['name'] : null,
		// 			'firstname' => isset($_POST['firstname']) ? $_POST['firstname'] : null,
		// 			'lastname' => isset($_POST['lastname']) ? $_POST['lastname'] : null,
		// 			'password' => isset($_POST['password']) ? $_POST['password'] : null,
		// 			'address' => isset($_POST['address']) ? $_POST['address'] : null,
		// 			'zip_city' => isset($_POST['zip_city']) ? $_POST['zip_city'] : null,
		// 			'country_id' => isset($_POST['country_id']) ? $_POST['country_id'] : null,
		// 			'publish' => isset($_POST['publishEmail']) ? $_POST['publishEmail'] : null,
		// 		),
		// 		'quantity' => isset($this->cols['id']) ? $this->cols['id'] : 0,
		// 		'regime' => 'update'
		// 	),
		// );

	}

	protected function getRules()
	{

		return array(
			array('name, password', 'require', 'regime' => 'login'),
			array('firstname, lastname, address, zip_city, country_id',
				'require',
				'regime' => 'update'),
			);

	}

	protected function getCondOfSave()
	{

		return array(
			array(
				1 => array('id'),
				'regime' => 'update, delete',
				),
		);

	}

	public function getUserId() {

		parent::startSession();
		return !empty($_SESSION['user']) ? $_SESSION['user'] : false;
		
	}

	public function findByID($user_id) {

		$this->arrayConditions = array(
			1 => array('id', 'val' => $user_id),
		);

		return $model = $this->find('users', '*');

	}

	public function findPublished() {

		$this->arrayConditions = array(
			1 => array('publish', 'val' => 1),
		);

		return $model = $this->findAll('users', '*');

	}
	//TODO check code below:
	public function login() {

		if (!isset($_POST['name'], $_POST['password'])) {
			throw new HttpException();
		}
		//валидация пользовательского ввода:
		foreach ($_POST as $key => $value) {
			if (empty($value)) {
				$this->errors[$key] = "This field is required";
			}
			$this->$key = $value;
		}

		if (count($this->errors) == 0) {
			$user = $this->find('users', 'id, password', 'name=?', [$this->name]);
			if (!$user) {
				$this->errors['name'] = "There is no such user in the database";
				return false;
			}
			if ($user['password'] && password_verify($this->password, $user['password'])) {
				parent::startSession();
				$_SESSION['user'] = $user['id'];
				return true;
			}
			$this->errors['password'] = "Incorrect password";
		}
		return false;

	}

}

?>