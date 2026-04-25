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


if (isset($_POST['dominio'])) {
	$dominio = trim($_POST['dominio']);
	$code = new Encode();

	$result_encode = $code->criptografar_dominio($dominio);
	echo json_encode(["status" => true, "msg" => "Domínio criptografado com sucesso!", "crypt" => $result_encode]);
}else{
	echo json_encode(["status" => false, "msg" => "Acesso não autorizado"]); die();
}