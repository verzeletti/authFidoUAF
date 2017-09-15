<?php

/*
 * Class for verifying FIDO UAF Token and User Attributes
   *
   * @category    Auth
   * @package     Auth_FidoUAF
   * @author      Glaidson Verzeletti <verzeletti@gmail.com>
   * @version     $ lib/Auth/Source/FidoUAF.php, v1.0 2016-12-21 07:07:07 $   
   * @link        https://www.redes.eng.br/
   */

/*
 * Configure it by adding an entry to config/authsources.php such as this:
   *
   *	'fidouaf' => array(
   *		  'ldap_server' => ldap_url,
   *		  'token_expire' => 'time_in_minutes',
   *		  ),
   *
   *
   * @package SimpleSAMLphp
   */


/* **** Begin **** */
class sspmod_authFidoUAF_Auth_Source_FidoUAF extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGEID = 'sspmod_authFidoUAF_Auth_Source_FidoUAF.state';

	/**
	 * The number of characters of the OTP that is the secure token.
	 * The rest is the user id.
	 */
	const TOKENSIZE = 32;

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'sspmod_authFidoUAF_Auth_Source_FidoUAF.AuthId';

	/**
	 * The client id/key for use with the Auth_FIDO PHP module.
	 */
	private $server_auth_request;
	private $server_auth_response;
	private $ldap_server;
	private $token_expire;
	private $token_size;
	private $sp_full_access;

	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		// Call the parent constructor first, as required by the interface
		parent::__construct($info, $config);

		if (array_key_exists('server_auth_request', $config)) {
			$this->server_auth_request = $config['server_auth_request'];
		}

		if (array_key_exists('server_auth_response', $config)) {
			$this->server_auth_response = $config['server_auth_response'];
		}

		if (array_key_exists('ldap_server', $config)) {
			$this->ldap_server = $config['ldap_server'];
		}

		if (array_key_exists('token_expire', $config)) {
			$this->token_expire = $config['token_expire'];
		}

		if (array_key_exists('token_size', $config)) {
			$this->token_size = $config['token_size'];
		}

		if (array_key_exists('sp_full_access', $config)) {
			$this->sp_full_access = $config['sp_full_access'];
		}

	}


	/**
	 * Initialize login.
	 *
	 * This function saves the information about the login, and redirects to a
	 * login page.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		// We are going to need the authId in order to retrieve this authentication source later
		$state[self::AUTHID] = $this->authId;

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);

		$url = SimpleSAML_Module::getModuleURL('authFidoUAF/fidouaflogin.php');
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('AuthState' => $id));
	}
	
	
	/**
	 * Handle login request.
	 *
	 * This function is used by the login form (core/www/loginuserpass.php) when the user
	 * enters a username and password. On success, it will not return. On wrong
	 * username/password failure, it will return the error code. Other failures will throw an
	 * exception.
	 *
	 * @param string $authStateId  The identifier of the authentication state.
	 * @param string $otp  The one time password entered-
	 * @return string  Error code in the case of an error.
	 */
	public static function handleLogin($authStateId, $otp) {
		assert('is_string($authStateId)');
		assert('is_string($otp)');

		/* Retrieve the authentication state. */
		$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);

		/* Find authentication source. */
		assert('array_key_exists(self::AUTHID, $state)');
		$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}


		try {
			/* Attempt to log in. */
			$attributes = $source->login($otp);
		} catch (SimpleSAML_Error_Error $e) {
			/* An error occurred during login. Check if it is because of the wrong
			 * username/password - if it is, we pass that error up to the login form,
			 * if not, we let the generic error handler deal with it.
			 */
			if ($e->getErrorCode() === 'WRONGUSERPASS') {
				return 'WRONGUSERPASS';
			}

			/* Some other error occurred. Rethrow exception and let the generic error
			 * handler deal with it.
			 */
			throw $e;
		}

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);
	}
	
	/**
	 * Return the user id part of a one time passord
	 */
	public static function getFIDOKeyPrefix($otp) {
		$uid = substr ($otp, 0, strlen ($otp) - self::TOKENSIZE);
		return $uid;
	}

	/**
	 * Attempt to log in using the given username and password.
	 *
	 * On a successful login, this function should return the users attributes. On failure,
	 * it should throw an exception. If the error was caused by the user entering the wrong
	 * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
	 *
	 * Note that both the username and the password are UTF-8 encoded.
	 *
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @return array  Associative array with the users attributes.
	 */
	protected function login($otp) {
		assert('is_string($otp)');

		require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/libextinc/Auth_FIDO.php';

		$attributes = array();

		try {
			// Encaminha solicitações para as funções em "libextinc/Auth_FIDO.php"
			$fido = new Auth_FIDO($this->server_auth_request, $this->server_auth_response, $this->ldap_server, $this->token_expire, $this->token_size, $this->sp_full_access);
			//$uid = self::getFIDOKeyPrefix($otp);
			list($auth, $uid, $uid_MD5, $SP) = $fido->verify($otp);
			//list($mail, $givenName, $displayName) = $fido->get_attributes($uid);
			list($mail, $cn) = $fido->get_attributes($uid);
			// Determina lista de atributos que serão entregues para cada SP
			//list($attributes) = $fido->attribute_filter($SP);
			//if ($SP == "sp-saml.gidlab.rnp.br" || $SP == "sp04.redes.eng.br") {
			if ($SP == "sp-saml.gidlab.rnp.br") {
				//$attributes = array('uid' => array($uid_MD5), 'givenName' => array($givenName), 'mail' => array($mail), 'displayName' => array($displayName));
				$attributes = array('uid' => array($uid_MD5), 'cn' => array($cn), 'mail' => array($mail));
			} else {
				$attributes = array('uid' => array($uid_MD5));
			}
		} catch (Exception $e) {
		  	SimpleSAML_Logger::info('FidoUAF_Key:' . $this->authId . ': Validation error (otp ' . $otp . '), debug output: ' . $yubi->getLastResponse());

			throw new SimpleSAML_Error_Error('WRONGUSERPASS', $e);
		}

		SimpleSAML_Logger::info('FidoUAF_Key:' . $this->authId . ': FidoUAF_Key otp ' . $otp . ' validated successfully: ' . $fido->getLastResponse());

		return $attributes;
	}

}
