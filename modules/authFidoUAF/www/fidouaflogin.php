<?php

/**
 * This page shows a token (OTP) login form, and passes information from it
 * to the sspmod_core_Auth_UserPassBase class, which is a generic class for
 * username/password authentication.
 *
 * @author Glaidson Verzeletti <verzeletti@gmail.com>
 * @package SimpleSAMLphp
 */

// Faz verificação se existe o AuthState
if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];


//   Verifica se Token (OTP) foi enviado juntamente com a requisição de autenticação,
// caso contrário, será solicitado ao usuário o informe manual.
/*** Parte do código original deste módulo de autenticação
if (array_key_exists('otp', $_REQUEST)) {
	$otp = $_REQUEST['otp'];
	$metodo = "request";
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
***/
/***
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	//$otp = $_GET['otp'];
	if (!empty($_GET['otp'])) { 
		$otp = $_GET['otp'];
	} else {$otp = '';}
	$metodo = "GET";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
***/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	//$otp = $_POST['otp'];
        if (!empty($_POST['otp'])) { 
                $otp = $_POST['otp'];
        } else {$otp = '';}
	$metodo = "POST";
} else {
	$otp = '';
	$metodo = "não detectado";
}

// Grava um LOG para acompanhar (testar) OTP recebido
include_once("fidouaf/functions.php");
$time	= date('d') . "/" . date('m') . "/" . date('Y') . " - " . date('H') . ":" . date('i') . "hs";
$log 	= "Conteúdo do token (OTP), capturado pelo método \"" . $metodo . "\" em " . $time . "..: " . $otp;
gravar_arquivo("fidouaf/log/metodo_autenticacao_usado_no_IdP.log", $log);


/**** TESTE
if (array_key_exists('RelayState', $_REQUEST)) {
	$SP_Referer = $_REQUEST['RelayState'];
} else {
	$SP_Referer = $SP_URL;
}
****/

if (!empty($otp)) {
	// attempt to log in
	$errorCode = sspmod_authFidoUAF_Auth_Source_FidoUAF::handleLogin($authStateId, $otp);
} else {
	$errorCode = NULL;
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'authFidoUAF:fidouaflogin.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['errorcode'] = $errorCode;
$t->show();
exit();
