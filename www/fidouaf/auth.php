<?php
//
// Arquivo "auth.php"
//

// Carrega funções 
include 'functions.php';


// Variáveis gerais
$file_session = '/tmp/session.txt';
$file_name_01 = 'log/autenticacao_01-desafio.log';
$file_name_02 = 'log/autenticacao_02-assinatura_do_client.log';
$file_name_03 = 'log/autenticacao_03-resposta_do_servidor.log';
$file_name_error = 'log/autenticacao_04-erro.log';


// Variáveis GLOBAIS
GLOBAL $auth_status;
GLOBAL $auth_username;
//session_start();    


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // cURL PHP extention to GET request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://idpfido.cafeexpresso.rnp.br/fidouaf/v1/public/authRequest");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ret_get = curl_exec($ch);
    curl_close($ch);

    // Captura conteúdo do serverData
    $json_get_array = json_decode($ret_get,true);
    $serverData_get = ($json_get_array[0]["header"]["serverData"]);
  
    // Captura conteúdo do desafio
    $challenge = ($json_get_array[0]["challenge"]);
  
    // Imprime resultado na tela
    print_r($ret_get);
    
    // Escreve um arquivo de LOG
    gravar_arquivo($file_name_01, $ret_get);

    // Criar sessão
    //if (isset($serverData_get)) {
    //    $_SESSION['serverData'] = "session_1";
    //}
    gravar_arquivo($file_session, $serverData_get);

} else {
    
    // Captura a assinatura do desafio gerada pelo Cliente Android
    $json_post = file_get_contents('php://input');
    gravar_arquivo($file_name_02, $json_post);

    // Captura conteúdo do serverData
    $json_post_array = json_decode($json_post,true);
    $serverData_post = ($json_post_array[0]["header"]["serverData"]);
    
    // Captura sessão do GET (pedido de autenticação)
    $session = (ler_arquivo($file_session));

    // Compara se o pedido de autenticação corresponde à sessão criada pelo GET
    if ( $session == $serverData_post ) {

        // cURL PHP extention to POST request
        $ch = curl_init('http://idpfido.cafeexpresso.rnp.br/fidouaf/v1/public/authResponse');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json_post))
        );
        $ret_post = curl_exec($ch);

        // Imprime resultado na tela
        //print_r($ret_post);

        // Verifica se autenticação ocorreu com sucesso, captura dados e cria hash
        $server_status = json_decode($ret_post,true);
        $auth_policy   = ($server_status[0]["AAID"]);
        $auth_key      = ($server_status[0]["KeyID"]);
        $auth_deviceId = ($server_status[0]["deviceId"]);
        $auth_username = ($server_status[0]["username"]);
        $auth_username = mb_strtolower($auth_username); // converte para caixa baixa
        $auth_status   = ($server_status[0]["status"]);
	$auth_time     = date('YmdHi');
	$time_crypt    = date('YmdHisu');	
	$auth_key_hash = $auth_key . $time_crypt;
	$auth_key_hash = md5($auth_key_hash);

	// Prepara LOGs e devolve resultado para cliente
        $auth_ret      = "[{\"username\":\"" . $auth_username . "\",\"time\":\"" . $auth_time . "\",\"key\":\"" . $auth_key_hash . "\",\"policy\":\"" . $auth_policy . "\",\"valid\":\"yes\"}]";
	$auth_ret_cliente = "[{\"AAID\":\"" . $auth_policy . "\",\"KeyID\":\"" . $auth_key_hash . "\",\"deviceId\":\"" . $auth_deviceId . "\",\"username\":\"" . $auth_username . "\",\"status\":\"" . $auth_status . "\"}]";
	//$auth_ret_cliente = json_encode($auth_ret_cliente,true);
        print_r($auth_ret_cliente);


        // Escreve um arquivo de LOG
        gravar_arquivo($file_name_03, $auth_ret_cliente);
	$file_log_auth = "log/" . $auth_username . "_auth.log";
        gravar_arquivo($file_log_auth, $auth_ret);

    } else {  
        $auth_ret = "Seções diferentes!!!";
        gravar_arquivo($file_name_error, $auth_ret);
        exit();
    }

}

?>
