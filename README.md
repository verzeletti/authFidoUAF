# authFidoUAF

Baseado no módulo authYubiKey do simpleSAMLphp, o authFidoUAF (modules/authFidoUAF) foi escrito para auxiliar na autenticação do agente do usuário (https://github.com/verzeletti/mID-BR), desenvolvido como um protótipo.


----------------------------
Ao configurar o simpleSAMLphp, incluir no arquivo "config/authsources.php" as seguintes linhas:


  'fidouaf' => array(
  
      'authFidoUAF:FidoUAF',      
      // Servidor para requisitar o desafio (GET)
        'server_auth_request' => 'http://idpfido.cafeexpresso.rnp.br/fidouaf/v1/public/authRequest',
      // Servidor para verificar o desafio assinado
        'server_auth_response' => 'http://idpfido.cafeexpresso.rnp.br/fidouaf/v1/public/authResponse',
      // Endereço do Servidor OpenLDAP
        'ldap_server' => 'ldap://digitar_aqui_o-IP_do_Servidor:389',
      // Tempo para expirar o token (em minutos)
        'token_expire' => '5',
      // Define tamanho do token
        'token_size' => '32',
      // SPs com acesso a todos os atributos.: Separar por OR os endereços dos SPs
        'sp_full_access' => ' $SP == "sp-saml.gidlab.rnp.br" || $SP == "sp03.redes.eng.br" ',
        
  ),


----------------------------
Incluir no diretório "www" do simpleSAMLphp as interfaces REST (www/fidouaf) escritas em PHP, as quais permitirão a comunicação entre o mID-BR <-> simpleSAMLphp <-> FidoUAF_DockerServer.
