<?php
/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */
session_start();

require("../../configuracoes/functions.php");
require("../../autoload.php");

$session = new Sessao();
if (!$session->verificar_sessao()) {
	echo json_encode(["status" => false, "msg" => "Acesso não autorizado"]); die();
}


if (isset($_POST['id'])) {
	
	$app_id = intval($_POST['id']);
	$apps = new Aplicativos();

	$result_info = $apps->buscar_apps();
	echo json_encode($result_info);
}else{
	echo json_encode(["status" => false, "msg" => "Acesso não autorizado"]); die();
}