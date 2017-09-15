<?php
//
// Arquivo "registro.php"
//


// Functions 
include 'functions.php';


// Variables
$file_name = 'log/registro_01-resposta_do_servidor_para_a_solicitacao.log';
$file_name_01 = 'log/registro_02-resposta_do_cliente.log';
$file_name_02 = 'log/registro_03-resposta_do_servidor_ao_pedido.log';
$server_fido_get = "http://idpfido.cafeexpresso.rnp.br/fidouaf/v1/public/regRequest";
$server_fido_post = "http://localhost/fidouaf/v1/public/regResponse";


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	// Pegar username e montar URL de destino
	$url_origem = $_SERVER['REQUEST_URI'];
	$username = username('registro.php/', $url_origem);
	$url_destino = $server_fido_get."/".$username;

	// cURL PHP extention to GET request
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, "$url_destino" );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
	$ret = curl_exec($ch);
	curl_close($ch);

	// Print result
	echo $ret;

	// Write the LOG
	gravar_arquivo($file_name, $ret);

} else {
	// Capture the "challenge" sent by Android cliente
	$json = file_get_contents('php://input');
	$json_string = $json;
	gravar_arquivo($file_name_01, $json_string);

	// cURL PHP extention to POST request
	$ch = curl_init("$server_fido_post");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	    'Content-Type: application/json',
	    'Content-Length: ' . strlen($json_string))
	);
	$result = curl_exec($ch);

	// Print result
	echo $result;

	// Write the LOG
	gravar_arquivo($file_name_02, $result);
}

?>
