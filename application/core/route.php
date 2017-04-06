<?php

class Route {

	public static function start() {

		// контроллер и действие по умолчанию
		$controller_name = 'Main';
		$action_name = 'index';

		self::checkBackUri($_SERVER['REQUEST_URI']);
		$routes = explode('/', $_SERVER['REQUEST_URI']);

		if (!empty($routes[1])) {	
			$action_name = $routes[1];
		}

		// // получаем имя контроллера
		// if (!empty($routes[1])) {	
		// 	$controller_name = $routes[1];
		// }
		
		// // получаем имя экшена
		// if (!empty($routes[2])) {
		// 	$action_name = $routes[2];
		// }

		// добавляем префиксы
		// $model_name = 'Model_'.$controller_name;
		$controller_name = 'Controller_'.$controller_name;
		$action_name = 'action_'.$action_name;

		// подцепляем файл с классом модели (файла модели может и не быть)

		// $model_file = strtolower($model_name).'.php';
		// $model_path = "application/models/".$model_file;
		// if(file_exists($model_path))
		// {
		// 	include "application/models/".$model_file;
		// }

		// подцепляем файл с классом контроллера
		$controller_file = strtolower($controller_name).'.php';
		$controller_path = "application/controllers/".$controller_file;
		try {
			if(file_exists($controller_path)) {
				include $controller_path;
			}else{
				throw new HttpException(404);
			}
			
			// создаем контроллер
			$controller = new $controller_name;
			$action = $action_name;
			
			if(method_exists($controller, $action)){
				// вызываем действие контроллера
				$controller->$action();
			}else{
				throw new HttpException(404);
			}
		} catch(HttpException $e) {
			header($e->getMessage());
		} catch (PDOException $e) {
			print "Error!: " . $e->getMessage() . "<br />";
			die();
		}
	
	}

	public static function checkBackUri($requestUri) {

		Model::startSession();

		if (
			isset($_SESSION['backToUri']) &&
			isset($_SESSION['backFromUri']) &&
			$_SESSION['backFromUri'] !== $requestUri
		) {
			unset($_SESSION['backToUri']);
			unset($_SESSION['backFromUri']);
		}
		
	}

	public static function setBackUri($backToUri, $backFromUri) {

		Model::startSession();
		$_SESSION['backToUri'] = $backToUri;
		$_SESSION['backFromUri'] = $backFromUri;

	}

	public static function getBackUri() {

		Model::startSession();
		return !empty($_SESSION['backToUri']) ? $_SESSION['backToUri'] : false;

	}

	
}

?>