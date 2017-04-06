<?php
/**
 * Model is the base class, which has access to data in the database.
 * 
 * @author Artem Lucko
 */
abstract class Model
{

	private $errors = array();
	protected $regime;
	protected $sqlParams;
	protected static $db = null;
	protected static $pathsToDbConf = array(
		"application/core/db_conf1.php",
		"application/core/db_conf2.php",
		"application/core/db_conf3.php"
	);
	protected $cols  =  array();
	protected $arrayConditions = array();
	protected $bindParams = array();
	protected $colsNames = array();//свойство следует переопределить в наследуемом классе: ...
	//TODO пересмотреть область видимости
	public function __construct($_regime = null, $_cols = array())
	{

		if (self::$db === null) {
			if (reset(self::$pathsToDbConf) === false) {
				throw new HttpException();
			}

			self::connectDb(self::$pathsToDbConf);
		}

		if (empty(static::$colsNames)) {
			throw new HttpException();
		}
		$this->cols = array_fill_keys(static::$colsNames, null);

		if (!empty($_cols)) {
			foreach ((array)$_cols as $key => $val) {
				if (isset($this->cols[$key])) {
					$this->cols[$key] = $val;
				} //TODO дописать else, которое укажет в логе, что ошибка в имени поля
			}
		}

		if ($_regime !== null) {
			$this->regime = $_regime;
		}

	}
	//TODO пересмотреть область видимости
	public function __get($name) {
		
		foreach ((array)static::$cols as $col => $val) {
			if ($name === $col) {
				return $val;
			}
		}
		throw new HttpException();
		
	}
	//TODO пересмотреть область видимости
	public function __set($name, $settingVal) {
		
		foreach ((array)static::$cols as $col => &$val) {
			if ($name === $col) {
				$val = $settingVal;
				return true;
			}
		}
		throw new HttpException();
		
	}

	private static function connectDb(&$arrDbPaths) {

		try{
			$dbPath = pos($arrDbPaths);
			$dbConf = require $dbPath;
			self::$db = new PDO($dbConf['DSN'], $dbConf['dbUser'], $dbConf['dbPass']);
		} catch (PDOException $e) {
			if (next($arrDbPaths) === false) {
				print "Error!: " . $e->getMessage() . "<br />";
				die();
			}
			self::connectDb($arrDbPaths);
		}

	}

	public static function model($regime = null, $cols = array()) {

		$className = get_called_class();
		return new $className($regime, $cols);

	}
	
	public function getErrors($col = null) {
	
		if ($col === null) {
			return $this->errors;
		}
		return $this->errors[$col];
	
	}

	protected function input($arrayInput) {

		$inputMap = $this->chooseByRegime(static::getInputMap());
		$arrayPreparedData = array();

		//antihacking code:
		if (isset($inputMap['counter'])) {
			$cnt = count($inputMap['counter']);
			foreach ($inputMap as $record) {
				$field = $record[1];
				if (is_array($arrayInput[$field]) &&
					key(end($arrayInput[$field])) > $cnt) {
					throw new HttpException();
				}
				unset($field);
			}
		}

		//working code:
		foreach ($inputMap as $record) {
			if (!is_array($record)) {
				continue;
			}

			$colName = $record[0];
			$field = $record[1];

			$arrayRecord['property'] = ['cols', $colName];
			$arrayRecord['value'] = $arrayInput[$field];

			$arrayPreparedData[] = $arrayRecord;
			unset($arrayRecord, $colName, $field);
		}

		return $models = $this->recordDataInModels($arrayPreparedData);

	}


	//$arrayData - array such as:
	//array(
	//	array(
	//		'property' => array('propName', key1, key2, ...),
	//		'value' => val
	//		),
	//  ...
	//)
	private function recordDataInModels($arrayData, $className = null) {

		if ($className === null) {
			$className = get_called_class();
		}

		if (!array($arrayData)) {
			throw new HttpException();
		}

		//definition the required quantity of models and creating:
		foreach ($arrayData as $record) {

			if (!is_array($record['value'])) {
				continue;
			}

			$maxIndex = key(end($record['value']));
			if (empty($cnt) || $maxIndex > $cnt) {
				$cnt = $maxIndex;
			}

		}

		$cnt = (empty($cnt)) ? 1 : $cnt;
		for ($i = 0; $i <= $cnt; $i++) {
			$models[$i] = new $className($this->regime);
		}

		//filling the created models:
		foreach ($arrayData as $record) {

			for ($i = 0; $i < $cnt; $i++) {
				foreach ($record['property'] as $propName) {
					if (!$objProp) {
						$objProp = &$models[$i]->$propName;
					} else {
						$objProp = &$objProp[$propName];
					}
				}

				if (isset($record['value'][$i])) {
					$objProp = $record['value'][$i];
				} elseif (!is_array($record['value'])) {
					$objProp = $record['value'];
				}
				unset($objProp);
			}

		}

		return $models;

	}
//???пока не нужно<
	private function getRules() {

		return [];

	}
//>???
	//Hadle (validate, save or smth else) all passed models.
	//Can work in two modes.
	//First mode - calling this method from self class Model.
	//	In this case it gets array of arrays of models to $arrayModels parameter.
	//	It uses when there is necessity to handle a few models of different child classes.
	//Second mode - calling this method from child class model.
	//	In this case it gets array of this class models to $arrayModels parameter.
	//	It is used to handle models of one child class.
	public static function handleAll(&$arrayModels, $handler, ...$params) {

		if (!is_array($arrayModels)) {
			throw new HttpException();
		}

		//the first mode:
		if (get_called_class() === __CLASS__) {
			foreach ($arrayModels as $models) {
				if (!is_array($models)) {
					throw new HttpException();
				}
			}
			$isDone = true;
			foreach ($arrayModels as $models) {
				foreach ($models as $model) {
					$isDone = $model->$handler(...$params) ? $isDone : false;
				}
			}
		//the second mode:
		} else {
			if (!self::compareWithCalledClass($arrayModels)) {
				throw new HttpException();
			}
			$isDone = true;
			foreach ($arrayModels as $model) {
				$isDone = $model->$handler(...$params) ? $isDone : false;
			}
		}
		return $isDone;

	}
//??? вроде бы этот метод заменен новым универсальным (handleAll) и его можно удалить <
	public static function validateAll(&$arrayModels) {

		return self::handleAll($arrayModels, 'validate');

	}
//>???
	public function validate() {

		$arrayRules = static::getRules();
		$isValid = true;

		foreach ((array)$arrayRules as $rule) {

			if (isset($rule['regime'])) {
				$ruleApplicability = false;
				$regimes = explode(',', $rule['regime']);
				foreach ((array)$regimes as $regime) {
					if ($this->regime == trim($regime)) {
						$ruleApplicability = true;
						break;
					}
				}
				if (!$ruleApplicability) {
					continue;
				}
			}

			$validator = $rule[1] . 'Validate';
			if (!method_exists($this, $validator)) {
				continue;
			}

			$needValidateCols = explode(',', (string)$rule[0]);
			foreach ((array)$needValidateCols as $colName) {
				$colName = trim($colName);
				$params = !empty($rule['params']) ? $rule['params'] : null;
				$isValid = $this->$validator($cols[$colName], $params) ? $isValid : false;
			}

		}
		return $isValid;

	}
	//TODO продумать валидатор 'ownership'
	private function ownershipValidate($col, $params) {

		$this->arrayConditions = array(
			1 => array($params['ownerCol'], 'val' => $params['ownerId']),
		);

		$arrayDbRows = $model->findAll($params['table'], $col);

		foreach ($arrayDbRows as $dbRow) {
			if ($this->$col == $dbRow[$col]) {
				return true;
			}
		}

		throw new HttpException();

	}

	private function requireValidate($col, $params) {

		if (empty($prop)) {
			$this->errors[$col] = СООБЩЕНИЕ; //TODO!!!
			return false;
		}
		
		return true;

	}
	
	public static function startSession() {

		if (session_id()) {
			return true;
		}else{
			return session_start();
		}

	}

	public static function destroySession() {

		self::startSession();
		$params = session_get_cookie_params();
	    setcookie(session_name(), '', time() - 60*60*24,
	    	$params['path'], $params['domain'],
	    	$params['secure'], $params['httponly']);
	    session_destroy();

	}

	private function executeSql($sql) {

		$findRequest = self::$db->prepare($sql);

		return $findRequest->execute($this->bindParams);

	}

	// private function findDataObj($table, $attributes) {

	// 	$sql = "SELECT $attributes FROM $table";

	// 	if (!empty($this->arrayConditions)) {
	// 		$condition = $this->makeSqlCondition();
	// 		$sql .= " WHERE $condition";
	// 	}

	// 	return executeSql($sql);
		
	// }

	private function findDataObj($table, $attributes) {

		$sql = "SELECT $attributes FROM $table";

		if (!empty($this->arrayConditions)) {
			$condition = $this->makeSqlCondition();
			$sql .= " WHERE $condition";
		}
		$dataObj = executeSql($sql);

		return $dataObj->setFetchMode(PDO::FETCH_CLASS, get_called_class());
		
	}

	protected function find($table, $attributes) {

		return $this->findDataObj($table, $attributes)->fetch();

	}

	protected function findAll($table, $attributes) {

		return $this->findDataObj($table, $attributes)->fetchAll();
		
	}

	protected function insert($table) {

		$cols = '';
		$values = '';
		$bindParams = array();
		foreach ($this->cols as $key => $value) {
			if (!empty($value)) {
				$cols .= $key . ', ';
				$values .= ':?, ';
				$bindParams[] = $value;
			}
		}
		$cols = trim($cols, ', ');
		$values = trim($values, ', ');

		$sql = "INSERT INTO $table ($cols) VALUES ($values)";
		$insertRequest = self::$db->prepare($sql);

		return $insertRequest->execute($this->bindParams);
		
	}

	protected function update($table) {

		$expression = '';
		$condition = $this->makeSqlCondition();

		foreach ($this->cols as $key => $value) {
			if (empty($value) === false) {
				$expression .= $key . ' = :' . $key . ', ';
				$this->bindParams[':' . $key] = $value;
			}
		}
		$expression = trim($expression, ', ');

		$sql = "UPDATE $table SET $expression";

		if (!empty($condition)) {
			$sql .= " WHERE $condition";
		}

		return executeSql($sql);
		
	}

	protected function delete($table) {

		$condition = $this->makeSqlCondition();
		$sql = "DELETE FROM $table";

		if (!empty($condition)) {
			$sql .= " WHERE $condition";
		}
		
		return executeSql($sql);
		
	}

	// $mode can take the values:
	// - "r&nr" - regime and no regime:
	//			choose all data-arrays vith required regime-element
	//			and all data-arrays without regime-element (common data-arrays);
	// - "r||nr" - regime or no regime:
	//			choose all data-arrays vith required regime-element
	//			or data-arrays without regime-element (common data-arrays)
	//			if there aren't any data-arrays with regime-element;
 	private function chooseByRegime($arrayInput, $mode = "r&nr") {

		$arrayOutput = [];
		$arrayCommon = [];

		$foundedRegime = false;
		foreach ($arrayInput as $data) {
			if (isset($data['regime'])) {
				$regimes = explode(',', $data['regime']);
				foreach ($regimes as $regime) {
					if ($this->regime === trim($regime)) {
						$arrayOutput[] = $data;
						$foundedRegime = true;
						break;
					}
				}
			} else {
				$arrayCommon[] = $data;
			}
		}

		switch ($mode) {
			case "r&nr":
				array_merge($arrayOutput, $arrayCommon);
				break;
			case "r||nr":
				if (empty($arrayOutput)) {
					$arrayOutput = $arrayCommon;
				}
				break;
			default:
				throw new HttpException();
		}
		
		return $arrayOutput;

	}

	// protected static function compareWithCalledClass($models) {

	// 	$className = get_called_class();
	// 	if (!is_array($models)) {
	// 		return is_a($models, $className);
	// 	}
	// 	foreach ($models as $model) {
	// 		if (!is_a($model, $className)) {
	// 			return false;
	// 		}
	// 	}
	// 	return true;

	// }

	private static function compareWithCalledClass($models) {

		$className = get_called_class();

		if (!is_array($models)) {
			return is_a($models, $className);
		}
		foreach ($models as $model) {
			if (!is_a($model, $className)) {
				return false;
			}
		}
		return true;

	}

	public static function saveAll(&$arrayModels, $needValidate = true) {

		return self::handleAll($arrayModels, 'save', $needValidate);

	}

	protected function getCondOfSave() {

		return array();

	}

	private function setCondOfSave() {

		$arrayConditions = $this->chooseByRegime(static::getCondOfSave(), "r||nr");
		if (count($arrayConditions) > 1) {
			throw new HttpException();
		}//почему нельзя принимать более одного условия? проверить и если что - удалить
		$this->arrayConditions = isset($arrayConditions[0]) ?
			$arrayConditions[0] : [];

	}

	/**
	 * Creates the SQL expression part after the operator "WHERE".
	 * Uses the array returned by the method 
	 *
	 *
	 * @return string the SQL expression part
	 */
	protected function makeSqlCondition() {

		if (!$this->arrayConditions) {
			return '';
		}

		$conditions = [];
		$arrayConditions = $this->arrayConditions;

		foreach ($arrayConditions as $key => $value) {
			if (!is_int($key)) {
				continue;
			}
			$colName = $value[0];
			if (!empty($value['val'])) {
				$conditions[$key] = "$colName = $value[val]";
			} elseif (!empty($value['bind']) or $value['bind'] = $this->cols[$colName]) {
				$bindParams[] = $value['bind'];
				$lastParamNum = count($bindParams) - 1;
				$conditions[$key] = "$colName = :$lastParamNum";
			}
		}

		if (!empty($arrayConditions['map'])) {
			foreach ($conditions as $key => $value) {
				$patternArray[] = '~(?<![0-9])' . $key . '(?![0-9])~';
				$replaceArray[] = $value;
			}

			$sql = preg_replace($patternArray, $replaceArray, $arrayConditions['map']);
		} else {
			$i = 0;
			foreach ($conditions as $key => $value) {
				$sql .= $i++ > 0 ? ' AND ' : '';
				$sql .= $value;
			}
		}

		return $sql;

	}

	protected function checkDataType($var, $dataType = 'string') {
		switch ($dataType) {
			case 'string':
				if (!is_string($var)) {
					throw new HttpException();
				}
				break;
			case 'bool':
				if (!is_bool($var)) {
					throw new HttpException();
				}
				break;
			default:
				throw new HttpException();
		}
	}

	public function save(...$args) {

		checkDataType($args[0], 'bool');
		$needValidate = $args[0] === false ? false : true;

		if ($needValidate && $this->validate() === false) {
			return false;
		}

		$table = static::$tableName;
		$this->setCondOfSave();

		switch ($this->regime) {
			case 'insert':
				$this->insert($table);
				$break;
			case 'update':
				$this->update($table);
				$break;
			case 'delete':
				$this->delete($table);
				$break;
			default:
				throw new HttpException();
		}

		return true;

	}

}

?>