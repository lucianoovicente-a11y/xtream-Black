<?php
/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */
session_start();

require("../../configuracoes/functions.php");
require("../../autoload.php");

if (isset($_POST['user_nome']) && isset($_POST['user_email']) && isset($_POST['user_senha']) && isset($_POST['nivel_acesso'])) {
	$user = new Usuarios();

	$info = $user->info_user_logado();

	if ($info["user_info"]["nivel"] != 1) {
		echo json_encode(["status" => false, "msg" => "Você não está autorizado a fazer essa ação"]);
	}

	$user_nome    = $_POST['user_nome'];
	$user_email   = trim($_POST['user_email']);
	$user_senha   = $_POST['user_senha'];
	$nivel_acesso = intval($_POST['nivel_acesso']);


	if (empty($user_nome)) {
		echo json_encode(["status" => false, "msg" => "O campo nome não pode está em branco!"]); die();
	}

	if (empty($user_email)) {
		echo json_encode(["status" => false, "msg" => "O campo email não pode está em branco!"]); die();
	}

	if (empty($user_senha)) {
		echo json_encode(["status" => false, "msg" => "O campo senha não pode está em branco!"]); die();
	}


	$response = $user->adicionar_usuario($user_nome, $user_email, $user_senha, $nivel_acesso);
	echo json_encode($response);
}



?>