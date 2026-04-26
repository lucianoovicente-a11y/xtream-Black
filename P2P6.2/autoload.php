<?php  

// Define o diretório base como o diretório onde este arquivo está localizado
define('BASE_DIR', __DIR__);

spl_autoload_register(

	function($class)
	{
		$file = BASE_DIR . "/classes/" . $class . ".class.php";
		if (file_exists($file)) {
			require $file;
		}
	}

);