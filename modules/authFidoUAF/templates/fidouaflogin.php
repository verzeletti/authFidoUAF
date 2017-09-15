<?php
//$this->data['header'] = $this->t('{authFidoUAF:fidouaf:header}');
$this->data['autofocus'] = 'otp';

$this->includeAtTemplateBase('includes/header_midbr.php');

?>

<?php
if ($this->data['errorcode'] !== NULL) {
?>
	<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/experience/gtk-dialog-error.48x48.png" class="float-l" style="margin: 15px" alt="" />
		<h2><?php echo $this->t('{login:error_header}'); ?></h2>
		<p><b><?php echo $this->t('{errors:title_' . $this->data['errorcode'] . '}'); ?></b></p>
		<!-- <p><?php //echo $this->t('{errors:descr_' . $this->data['errorcode'] . '}'); ?></p> -->
	</div>
<?php
}
?>

 	<img style="float: right" src="<?php  echo(SimpleSAML_Module::getModuleURL('authFidoUAF/resources/logo_fido.jpg')); ?>" alt="" />
	<img style="clear: right; float: right" src="<?php  echo(SimpleSAML_Module::getModuleURL('authFidoUAF/resources/fidouaf.jpg')); ?>" alt="" /> 


	<h2 style=""><?php echo $this->t('{authFidoUAF:fidouaf:header}'); ?></h2>

	<form action="?" method="post" name="f">
		<!-- <p><?php //echo $this->t('{consent:consent:consent_accept}', array( 'SPNAME' => $dstName, 'IDPNAME' => $srcName) ); ?></p> -->
		
		<p><?php echo $this->t('{authFidoUAF:fidouaf:intro}'); ?></p>

		<p><?php 
			include_once("fidouaf/functions.php");
			//$SP_Id = $_SERVER["HTTP_REFERER"];
			$SP_Id = $_REQUEST["AuthState"];
			$SP_Id =  trim(rawurldecode(before("&cookieTime", after("spentityid=", rawurldecode($SP_Id)))), '/');
			if (!preg_match('#^http(s)?://#', $SP_Id)) {
			    $SP_Id = 'http://' . $SP_Id;
			}
			$SP_Id = parse_url($SP_Id);
			$SP_Id = preg_replace('/^www\./', '', $SP_Id['host']);

			echo "SP.: " . $SP_Id;
		?></p>
	
		<p><input id="otp" style="border: 1px solid #ccc; background: #eee; padding: .5em; font-size: medium; width: 70%; color: #aaa" type="text" tabindex="2" name="otp" /></p>




<?php
foreach ($this->data['stateparams'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>

	</form>

<?php

$this->includeAtTemplateBase('includes/footer.php');
