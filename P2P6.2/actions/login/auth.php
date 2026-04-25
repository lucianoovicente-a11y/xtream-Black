<?php
/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */
session_start();

require("../../configuracoes/functions.php");
require("../../autoload.php");

if (isset($_POST['email']) && isset($_POST['password'])) {
	$login = new Usuarios();

	$email = $_POST['email'];
	$senha = $_POST['password'];

	if (empty($email)) {
		echo json_encode(["status" => false, "msg" => "O campo email não pode está em branco!"]); die();
	}

	if (empty($senha)) {
		echo json_encode(["status" => false, "msg" => "O campo senha não pode está em branco!"]); die();
	}

	$response = $login->auth($email, $senha);

	if ($response['status']) {

		$_SESSION['admin_id'] = $response['admin_id'];
        $_SESSION['admin_logged'] = true;

		echo json_encode(["status" => true, "msg" => $response['msg']]);
	}else{
		echo json_encode(["status" => false, "msg" => $response['msg']]);
	}
}



?>