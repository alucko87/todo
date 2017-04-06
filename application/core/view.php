<?php

class View {

	const VIEWS_PATH = "application/views/";
	
	// public function generate($content_view, $template_view, $models = null) {
		
	// 	if(is_array($data)) {
	// 		// преобразуем элементы массива в переменные
	// 		extract($data);
	// 	}
		
		
	// 	include self::VIEWS_PATH.$template_view;
	// }

	public function generate($arrayViews, $models = null) {
		/*
		if(is_array($data)) {
			// преобразуем элементы массива в переменные
			extract($data);
		}
		*/

		if (!is_array($arrayViews)) {
			throw new HttpException();
		}
		
		$i = 0;

		include self::VIEWS_PATH.'views_provider.php';

	}

	public function generate_partial($content_view, $models = null) {
		
		include self::VIEWS_PATH.$content_view;

	}

	public function returnView($content_view, $models = null) {

		ob_start();
		include self::VIEWS_PATH.$content_view;
		return ob_get_clean();

	}




	// public function generate($views = array(), $models = array()) {
	// 	/*
	// 	if(is_array($data)) {
	// 		// преобразуем элементы массива в переменные
	// 		extract($data);
	// 	}
	// 	*/
	// 	if (!is_array($views)) {
	// 		throw new HttpException();
	// 	}
	// 	$template_view = array_pop($views);
	// 	if (count($views) > 1) {
	// 		$this->generate($views = array(), $models = array())
	// 	}
	// 		include self::VIEWS_PATH.$template_view;
	// }







}

?>