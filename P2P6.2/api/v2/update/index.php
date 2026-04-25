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

$arr = [];
if ($info_app["app_info"][0]["status1"] == 1) {
	array_push($arr, [urlencode($info_app["app_info"][0]["nome1"]), $info_app["app_info"][0]["url1"], intval($info_app["app_info"][0]["tipo1"])]);
}


if ($info_app["app_info"][0]["status2"] == 1) {
	array_push($arr, [urlencode($info_app["app_info"][0]["nome2"]), $info_app["app_info"][0]["url2"], intval($info_app["app_info"][0]["tipo2"])]);
}


if ($info_app["app_info"][0]["status3"] == 1) {
	array_push($arr, [urlencode($info_app["app_info"][0]["nome3"]), $info_app["app_info"][0]["url3"], intval($info_app["app_info"][0]["tipo3"])]);
}


if ($info_app["app_info"][0]["status4"] == 1) {
	array_push($arr, [urlencode($info_app["app_info"][0]["nome4"]), $info_app["app_info"][0]["url4"], intval($info_app["app_info"][0]["tipo4"])]);
}



$hash_crypt = [
	2 => [
		["AVISOS SERVIDOR"],
		[urlencode($info_app["app_info"][0]["aviso"])]
	],
	3 => [
		[""],$arr
	]
];

echo $code->criptografar($hash_crypt);