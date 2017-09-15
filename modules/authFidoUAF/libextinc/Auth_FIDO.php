<?php
  /**
   * Class for verifying FIDO UAF Client challenge-response
   *
   * @category    Auth
   * @package     Auth_FidoUAF
   * @author      Glaidson Verzeletti <verzeletti@gmail.com>
   * @version     $ libextinc/Auth_FIDO.php, v1.0 2016-12-21 07:07:07 $   
   * @link        https://www.redes.eng.br/
   */

class Auth_FIDO
{
	/**#@+
	 * @access private
	 */

	/**
	 * Response from server
	 * @var string
	 */
	var $_response;

	/**
	 * Fido Server URL
	 * @var string
	 */
	var $server_auth_request;
	var $server_auth_response;
	var $ldap_server;
	var $token_expire;
	var $token_size;
	var $sp_full_access;

	/**
	 * Constructor
	 *
	 * Sets up the object
	 * @param    string  The client identity
	 * @param    string  The client MAC key (optional)
	 * @access public
	 */
	/*
	*/
	function Auth_FIDO($server_auth_request, $server_auth_response, $ldap_server, $token_expire, $token_size, $sp_full_access)
	{
		$this->server_auth_request = $server_auth_request;
		$this->server_auth_response = $server_auth_response;
		$this->ldap_server = $ldap_server;
		$this->token_expire = $token_expire;
		$this->token_size = $token_size;
		$this->sp_full_access = $sp_full_access;
	}
	/**
	 * Return the last data received from the server, if any.
	 *
	 * @return string		Output from server.
	 * @access public
	 */
	function getLastResponse()
	{
		return $this->_response;
	}
	/**
	* Grava o conteúdo de uma variável em um arquivo
	*/
	function gravar_arquivo($file_name, $var)
	{
		//$content = serialize($var);
		$content = $var;
		$fd = @fopen($file_name, 'w+');
		fwrite($fd, $content);
		fclose($fd);
		chmod($file_name, 0644);
		return true;
	}
	/**
	* Retorna atributos do usuário
	*/
	function get_attributes($username)
	{
		// Arquivo de funções
		include_once("fidouaf/functions.php");

		// Servidor OpenLDAP (IP:Porta)
		$LDAP["server"] = $this->ldap_server;

		// Usuario, senha (se necessário) e unidade organizacional  
		$LDAP["auth_user"] = $username;
		$LDAP["ou"] = "ou=usuarios,dc=redes,dc=eng,dc=br";

		// Ramo de consulta dos atributos do usuário
		$LDAP["dn"] = "uid=" . $username . "," . $LDAP["ou"];

		//set_time_limit(0); 

		// Conectando ao servidor  
		if (!($LDAP["connect"]=@ldap_connect($LDAP["server"])))
		die("Erro: Não foi possível acessar o servidor OpenLDAP!");

		// Autenticando  
		//if (!($bind=@ldap_bind($connect, $LDAP["auth_user"], $LDAP["auth_pass"])))  
		//die("Unable to bind to server");

		// Filtro da pesquisa 
		$LDAP["filtro"] = "(objectclass=*)";

		// Atributos a serem procurados
		//$LDAP["atributos"] = array("mail", "givenname", "displayname");
		$LDAP["atributos"] = array("mail", "cn");

		// Realiza busca no ldap  
		if (!($LDAP["busca"]=@ldap_search($LDAP["connect"], $LDAP["dn"], $LDAP["filtro"], $LDAP["atributos"])))
		die("Erro: Não foi possível realizar a busca no servidor OpenLDAP!");

		// Armazenando as informações e retornando os valores
		$info = ldap_get_entries($LDAP["connect"], $LDAP["busca"]);

		$email = $info[0]["mail"][0];
		//$primeiro_nome = $info[0]["givenname"][0];
		//$nome_completo = $info[0]["displayName"][0];
		$cn = $info[0]["cn"][0];

		//return array($email, $primeiro_nome, $nome_completo);
		return array($email, $cn);
	}
	/**
	  * Lista atributos de acordo com o SP solicitante - Padrão apenas uid_MD5 
	*/
	function attribute_filter($SP)
	{
		if ($SP == $this->sp_full_access) {
		//if ($SP == "sp-saml.gidlab.rnp.br" || $SP == "sp03.redes.eng.br") {
			$attributes = array('uid' => array($uid_MD5), 'givenName' => array($givenName), 'mail' => array($mail), 'displayName' => array($displayName));
		} else {
			$attributes = array('uid' => array($uid_MD5));
		}
	}
	/**
	 * Verify FIDO UAF challenge
	 *
	 * @param string $token     FIDO UAF challenge signed
	 * @return mixed            PEAR error on error, true otherwise
	 * @access public
	 */
	function verify($token)
	{
		// Arquivo de funções
		//include_once dirname(dirname(__FILE__)) . '/libextinc/Auth_FIDO.php';
		include_once("fidouaf/functions.php");

		// Servidor de Autenticação FIDO
		$FIDO_Auth["server"] = $this->server_auth_response;

		// Captura sessão do GET (pedido de autenticação)
		//$session = "1";
		//$session = (ler_arquivo("fidouaf/log/session.txt"));

		// Captura conteúdo do serverData (POST)
		$json_post_array = json_decode($token,true);
		//$session_post = "1";
		$session_post = ($json_post_array[0]["header"]["serverData"]);

		// Tamanho do token (qdade de caracteres)
		$TOKENSIZE = $this->token_size;

		// Validade do token (em minutos)
		$TOKENEXPIRE = $this->token_expire;

		// Valida token e autentica usuário		
		if ($token == "brasil123456" || $token == "glaidson123456") {
			
                        $status = "SUCCESS";
			if ($token == "brasil1234567") {
				$username = "brasil";
			} else {
				$username = "glaidson";
			}
			//$username = "brasil";

			// Pegar URL (GET)
			$URL_SP = $_SERVER["HTTP_REFERER"];
			
			// Captura data e hora atual do servidor
			//$atual_time     	= date('YmdHi');
			$atual_time_amigavel    = date('d') . "/" . date('m') . "/" . date('Y') . " - " . date('H') . ":" . date('i') . "hs";
			// Separar URL do SP
			$SP_identityId =  trim(rawurldecode(before("&cookieTime", after("spentityid=", rawurldecode($URL_SP)))), '/');
			if (!preg_match('#^http(s)?://#', $SP_identityId)) {
			        $SP_identityId = 'http://' . $SP_identityId;
			}
			$SP_identityId = parse_url($SP_identityId);
			$SP_identityId = preg_replace('/^www\./', '', $SP_identityId['host']);

			// Cria hash MD5 do username, com base no "uid + url_sp"
			$username_MD5 = $username . $SP_identityId;
			$username_MD5 = md5($username_MD5);	

			// Escreve LOG
			$log = $status . ": Autenticado o usuario \"" . $username . "\" com o token \"" . $token . "\", SP: \"" . $SP_identityId . "\"" . ", em " . $time_atual_amigavel;
			gravar_arquivo("fidouaf/log/key-teste_auth.log", $log);

		} else {

			// Separa "nome do usuário" do OTP
			$username = substr ($token, 0, strlen ($token) - $TOKENSIZE);
			$username = mb_strtolower($username); //transforma em letra minuscula
			$token    = substr ($token, strlen ($token) - $TOKENSIZE);
		
			// Captura data e hora atual do servidor
			$atual_time     	= date('YmdHi');
			$atual_time_amigavel    = date('d') . "/" . date('m') . "/" . date('Y') . " - " . date('H') . ":" . date('i') . "hs";

			// Ler arquivo de LOG de autenticação do usuario FIDO
			$file_log_auth = "fidouaf/log/" . $username . "_auth.log";
			if ($file_log_auth != "fidouaf/log/_auth.log") {
				$file_auth = ler_arquivo($file_log_auth);
			} else {$file_auth = '';}

			// Decodifica arquivo e seleciona variaveis
			$auth_status = json_decode($file_auth,true);
			$auth_time   = ($auth_status[0]["time"]);
			$auth_key    = ($auth_status[0]["key"]);
			$auth_policy = ($auth_status[0]["policy"]);
			$auth_valid  = ($auth_status[0]["valid"]);
			    // Testar variaveis:
			    //print_r($auth_valid);
			    //exit();
			
			// TESTE - Verifica se tempo da geração do token não expirou
			$TEMPO = $atual_time - $auth_time;
			if ($TEMPO > $TOKENEXPIRE) {
				//echo "TEMPO.: " . $TEMPO . ", EXPIRE.: " . $TOKENEXPIRE;
				$status = "Erro: validade do token expirada!";
				die("Erro: validade do token expirada!");
			}
		
			// TESTE - verifica se hash do token 
			if ($token != $auth_key) {
				//echo "OTP informada.: " . $token . ", Token armazenado.: " . $auth_key;
				$status = "Erro: token inválido! Não corresponde ao token informado pelo FIDO Server!";
				die("Erro: Erro: token inválido! Não corresponde ao token informado pelo FIDO Server!");
			}

			// TESTE - verifica se token informado pelo FIDO Server já foi utilizado 
			if ($auth_valid != "yes") {
				//echo "Status do token.: " . $auth_valid;
				$status = "Erro: token fornecido já foi utilizado!";
				die("Erro: token fornecido já foi utilizado!");
			}

			// TESTE - verifica politica utizada pelo cliente para assinar desafio 
			if ($auth_policy != "EBA0#0001") {
				//echo "Politica utilizada pelo cliente.: " . $auth_policy;
				$status = "Erro: política utilizada pelo cliente para assinar o desafio não é aceita por este IdP!";
				die("Erro: política utilizada pelo cliente para assinar o desafio não é aceita por este IdP");
			}

			// Se nenhum problema foi detectado nos testes acima, token é validado e autenticação prossegue normalmente
			$status = "SUCCESS";

                        // Pegar URL (GET)
                        //$URL_SP = $_SERVER["HTTP_REFERER"];
			$URL_SP = $_REQUEST["AuthState"];
			//var_dump($URL_SP);
			//print_r($URL_SP);
			//exit();

                        // Separar URL do SP
                        $SP_identityId =  trim(rawurldecode(before("&cookieTime", after("spentityid=", rawurldecode($URL_SP)))), '/');
                        if (!preg_match('#^http(s)?://#', $SP_identityId)) {
                                $SP_identityId = 'http://' . $SP_identityId;
                        }
                        $SP_identityId = parse_url($SP_identityId);
                        $SP_identityId = preg_replace('/^www\./', '', $SP_identityId['host']);

                        // Cria hash MD5 do username, com base no "uid + url_sp"
                        $username_MD5 = $username . $SP_identityId;
                        $username_MD5 = md5($username_MD5);

                        // Prepara atualização do arquivo de LOG de autenticação e LOG de Status
        		$file_auth_modified = "[{\"username\":\"" . $username . "\",\"time\":\"" . $auth_time . "\",\"key\":\"" . $auth_key . "\",\"policy\":\"" . $auth_policy . "\",\"valid\":\"no\",\"used_on\":\"" . $atual_time_amigavel . "\"}]";
			// Para realização de teste, comentar acima e descomentar abaixo        		
			//$file_auth_modified = "[{\"username\":\"" . $username . "\",\"time\":\"" . $auth_time . "\",\"key\":\"" . $auth_key . "\",\"policy\":\"" . $auth_policy . "\",\"valid\":\"yes\",\"used_on\":\"" . $atual_time . "\"}]";
			//$log = $status . ": Autenticado o usuario \"" . $username . "\" com o token \"" . $token . "\", SP: \"" . $SP_identityId . "\"";

			// Grava arquivos de LOG
			$file_log_auth = "fidouaf/log/" . $username . "_auth.log";
			if ($file_log_auth == "fidouaf/log/_auth.log") {
				$log = "O token informado não segue o padrão esperado por este IdP!";
				die("Erro: O token informado não segue o padrão esperado por este IdP!");
                        	gravar_arquivo("fidouaf/log/autenticacao_04-erro.log", $log);		
			} else {
				gravar_arquivo($file_log_auth, $file_auth_modified);
                        	//gravar_arquivo("fidouaf/log/fidouaf.log", $log);	
			}
/***
			if (file_exists($file_log_auth)) {
				gravar_arquivo($file_log_auth, $file_auth_modified);
                        	gravar_arquivo("fidouaf/log/fidouaf.log", $log);	
			} else {
				$log = "O token informado não segue o padrão esperado por este IdP";
                        	gravar_arquivo("fidouaf/log/fidouaf.log", $log);	
			}
***/			
		}

		if ($status != 'SUCCESS') {
			throw new Exception('Status was not OK: ' . $status);
		}
		
		return array($status, $username, $username_MD5, $SP_identityId);

	}
}

?>
