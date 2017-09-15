<?php
//
// Arquivo "apagar_conta.php"
//


// Functions 
include 'functions.php';


// Variables
$file_name_01 = 'log/apagar_conta_01-solicitacao_do_cliente.log';
$file_name_02 = 'log/apagar_conta_02-resposta_servidor.log';
//$server_fido = 'http://idpfido.cafeexpresso.rnp.br/fidouaf/v1/public/deregRequest';
$server_fido = 'http://localhost/fidouaf/v1/public/deregRequest';


// Capture the "challenge" sent by Android cliente
$json = file_get_contents('php://input');
$json_string = $json;
gravar_arquivo($file_name_01, $json_string);


// cURL PHP extention to POST request
$ch = curl_init("$server_fido");
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
?>
