<?php
/**
 * DESENVOLVIDO POR Newtec - WhatsApp: https://wa.me/5545991498688
 */
session_start();

require("../../../configuracoes/functions.php");
require("../../../autoload.php");


$app  = new Aplicativos();
$code = new Encode();


$info_app = $app->buscar_apps();

$hash_crypt = [
	[
		[
			$info_app["app_info"][0]["binstream_register"], 
			$info_app["app_info"][0]["binstream_auth"], 
			$info_app["app_info"][0]["binstream_email"], 
			$info_app["app_info"][0]["binstream_psp"]
		]
	], 
	[
		[
			$info_app["app_info"][0]["app_img_logotipo"], 
			$info_app["app_info"][0]["app_img_sidebar"], 
			$info_app["app_info"][0]["app_img_background"], 
			$info_app["app_info"][0]["app_img_favoritos"] 
		] 
	], 
	[
		[
			"Nome App", 
			"Nome provider", 
			$info_app["app_info"][0]["app_msg_suporte"], 
			$info_app["app_info"][0]["app_cor_esquerdo"], 
			$info_app["app_info"][0]["app_cor_centro"], 
			$info_app["app_info"][0]["app_cor_direito"]
		] 
	], 
	[
		[
			"0" 
		] 
	] 
];


echo $code->criptografar($hash_crypt);