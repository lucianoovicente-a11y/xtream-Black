<?php
session_start();

require("../../configuracoes/functions.php");
require("../../autoload.php");

$session = new Sessao();
if (!$session->verificar_sessao()) {
	echo json_encode(["status" => false, "msg" => "Acesso não autorizado"]); die();
}


$user    = new Usuarios();
$user_dados = $user->info_user_logado();

$user_id_logado = intval($user_dados["user_info"]["id"]);



$clientes = $user->listar_usuarios();



$result["data"] = [];
if ($clientes["status"]) {

	foreach ($clientes["users"] as $key => $cliente) {

		if ($cliente["id"] == 1) {
			if ($user_id_logado == 1) {
				$acoes = '<div class="action"> 

					<a href="javascript:void(0)" data-toggle="tooltip" class="editar_usuario" data-id="'.$cliente["id"].'" data-nome="'.$cliente["nome"].'" data-email="'.$cliente["email"].'" data-nivel="'.$cliente["nivel"].'" data-original-title="Editar usuário"><span class="badge badge-primary"><i class="fa fa-pencil-square-o text-outline-primary mr-1"></i></span></a>

					</div>';
			}else{
			  $acoes = '<div class="action">-----------------</div>';   
			}
		}else{
			$acoes = '<div class="action"> 

			<a href="javascript:void(0)" data-toggle="tooltip" class="editar_usuario" data-id="'.$cliente["id"].'" data-nome="'.$cliente["nome"].'" data-email="'.$cliente["email"].'" data-nivel="'.$cliente["nivel"].'" data-original-title="Editar usuário"><span class="badge badge-primary"><i class="fa fa-pencil-square-o text-outline-primary mr-1"></i></span></a>
			

			<a href="javascript:void(0)" data-toggle="tooltip" class="ativarDesativar_usuario" data-id="'.$cliente["id"].'" data-nome="'.$cliente["nome"].'" data-original-title="Ativar/Desativar plano"><span class="badge badge-primary"><i class="fa fa-eye text-outline-primary mr-1"></i></span></a>
			

			<a href="javascript:void(0)" data-toggle="tooltip" class="deletar_usuario" data-id="'.$cliente["id"].'" data-nome="'.$cliente["nome"].'"data-original-title="Excluir plano"><span class="badge badge-danger"><i class="fa fa-trash text-outline-danger mr-1"></i></span></a>



			</div>';
		}


		if ($cliente["id"] == 1) {
			if ($cliente['nivel'] == 1) {                
				$nivel = 'Super Administrador';
			}else{
				$nivel = 'Gerente';
			}
		}else{
			if ($cliente['nivel'] == 1) {                
				$nivel = 'Administrador';
			}else{
				$nivel = 'Gerente';
			}
		}

		if ($cliente["id"] == 1) {
			$status = '-----------------';
		}else{
			if ($cliente['status'] == 1) {                
				$status = '<span class="badge badge-outline-primary"><i class="fa fa-circle text-primary mr-1"></i>Usuário Ativo</span>';
			}else{
				$status = '<span class="badge badge-outline-danger"><i class="fa fa-circle text-danger mr-1"></i>Usuário Inativo</span>';
			}
		}

		if ($cliente["id"] == 1) {
			$email = '-----------------';
		}else{          
			$email = $cliente['email'];
		}


		$result["data"][] = [
			'id' => $cliente["id"],
			'nome' => $cliente["nome"],
			'email' => $email,
			'status' => $status,
			'nivel' => $nivel,
			'action' => $acoes
		];
	}

	echo json_encode($result);
}