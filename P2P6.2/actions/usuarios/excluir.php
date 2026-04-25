<?php
/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */
session_start();

require("../../configuracoes/functions.php");
require("../../autoload.php");

if (isset($_POST['user_id'])) {
	$user = new Usuarios();

	$info = $user->info_user_logado();

	if ($info["user_info"]["nivel"] != 1) {
		echo json_encode(["status" => false, "msg" => "Você não está autorizado a fazer essa ação"]);
	}


	$user_id      = intval($_POST['user_id']);


	$response = $user->excluir_usuario($user_id);
	echo json_encode($response);
}



?>