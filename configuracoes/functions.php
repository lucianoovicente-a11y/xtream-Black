<?php
/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */
 
setlocale( LC_ALL, 'pt_BR', 'pt_BR.iso-8859-1', 'pt_BR.utf-8', 'portuguese' );
date_default_timezone_set('America/Sao_Paulo');


/**
 * Classe Conexão PDO
 */
class DB
{
	private $connection = NULL;
	private static $_instance = NULL;
	public static function getInstance()
	{
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	private function __clone()
	{
	}
	// public function getConnection($db_host, $db_port, $db_name, $db_user, $db_pass)
	// {


	// 	$con_name = $db_host . "_" . $db_name;
	// 	try {
	// 		if (!isset($this->connection)) {
	// 			$this->connection = new PDO("mysql:host=" . $db_host . ";port=" . $db_port . ";dbname=" . $db_name . ";charset=utf8", $db_user, $db_pass, array(PDO::ATTR_PERSISTENT => true, PDO::ATTR_TIMEOUT => 5));
	// 			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	// 		}
	// 	} catch (PDOException $e) {
	// 		if (debugEnabled()) {
	// 			exit("Failed to connect to DB: " . $e->getMessage());
	// 		}
	// 		return NULL;
	// 	} catch (Exception $d) {
	// 		if (debugEnabled()) {
	// 			exit("Failed to connect to DB: " . $d->getMessage());
	// 		}
	// 		return NULL;
	// 	}
	// 	return $this->connection;
	// }
	public function getConnection()
	{

		$db = new PDO("sqlite:".__DIR__."/database.sql");
		
		try {
			if (!isset($this->connection)) {
				$this->connection = new PDO("sqlite:".__DIR__."/data.sqlite");
				$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		} catch (PDOException $e) {
			if (debugEnabled()) {
				exit("Failed to connect to DB: " . $e->getMessage());
			}
			return NULL;
		} catch (Exception $d) {
			if (debugEnabled()) {
				exit("Failed to connect to DB: " . $d->getMessage());
			}
			return NULL;
		}
		return $this->connection;
	}
}



/**
 * Conecta com o MySQL usando PDO
 */
function conexaoPDOlocal()
{
	return DB::getInstance()->getConnection();
}