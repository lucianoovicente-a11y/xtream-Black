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


if (isset($_POST)) {
	
	$app_id = intval($_POST["app_id"]);

	// Configurações
	$app_nome           = $_POST["nome_app"];
	$app_logo           = trim($_POST["logo_app"]);
	$app_side           = trim($_POST["sidebar_app"]);
	$app_backg          = trim($_POST["background_app"]);
	$app_favor          = trim($_POST["favorito_app"]);
	$app_whats          = $_POST["whatsapp_app"];

	$user 				= new Usuarios();
	$info_user 			= $user->info_user_logado();

	if ($info_user["user_info"]["nivel"] == 1) {
		// Conexão
		$app_psp            = trim($_POST["psp_app"]);
		$app_email          = trim($_POST["email_app"]);
		$app_auth           = trim($_POST["auth_app"]);
		$app_register       = trim($_POST["register_app"]);
	}



	// Banner 1
	$app_banner1_nome   = $_POST["nome1_banner"];
	$app_banner1_url    = $_POST["url1_banner"];
	$app_banner1_tipo   = intval($_POST["tipo1_banner"]);
	$app_banner1_status = isset($_POST["status1_banner"]) ? 1 : 0;

	// Banner 2
	$app_banner2_nome   = $_POST["nome2_banner"];
	$app_banner2_url    = $_POST["url2_banner"];
	$app_banner2_tipo   = $_POST["tipo2_banner"];
	$app_banner2_status = isset($_POST["status2_banner"]) ? 1 : 0;

	// Banner 3
	$app_banner3_nome   = $_POST["nome3_banner"];
	$app_banner3_url    = $_POST["url3_banner"];
	$app_banner3_tipo   = $_POST["tipo3_banner"];
	$app_banner3_status = isset($_POST["status3_banner"]) ? 1 : 0;

	// Banner 4
	$app_banner4_nome   = $_POST["nome4_banner"];
	$app_banner4_url    = $_POST["url4_banner"];
	$app_banner4_tipo   = $_POST["tipo4_banner"];
	$app_banner4_status = isset($_POST["status4_banner"]) ? 1 : 0;


	// Avisos
	$app_aviso          = $_POST["aviso_app"];

	// Cores
	$app_cor_esquerda   = trim($_POST["cor_esquerda"]);
	$app_cor_centro     = trim($_POST["cor_centro"]);
	$app_cor_direita    = trim($_POST["cor_direita"]);


	$configuracao["configuracao"] = [$app_nome, $app_logo, $app_side, $app_backg, $app_favor, $app_whats];
	if ($info_user["user_info"]["nivel"] == 1) {
		$configuracao["conexao"] 	  = [$app_psp, $app_email, $app_auth, $app_register];
	}
	$configuracao["aviso"] 	  	  = $app_aviso;
	$configuracao["cores"] 	  	  = ["esquerdo" => $app_cor_esquerda, "centro" => $app_cor_centro, "direita" => $app_cor_direita];
	$configuracao["banners"] 	  = [
		"banner1" => [$app_banner1_nome, $app_banner1_url, $app_banner1_tipo, $app_banner1_status], 
		"banner2" => [$app_banner2_nome, $app_banner2_url, $app_banner2_tipo, $app_banner2_status], 
		"banner3" => [$app_banner3_nome, $app_banner3_url, $app_banner3_tipo, $app_banner3_status], 
		"banner4" => [$app_banner4_nome, $app_banner4_url, $app_banner4_tipo, $app_banner4_status]
	];

	$apps = new Aplicativos();

	$result_edit = $apps->editar_aplicativo($configuracao, $app_id);

	echo json_encode($result_edit);

}else{
	echo json_encode(["status" => false, "msg" => "Acesso não autorizado"]); die();
}