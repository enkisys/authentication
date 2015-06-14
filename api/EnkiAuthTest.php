<?php

error_reporting(E_ERROR | E_PARSE);
ini_set ('display_errors', 'On');
$_SESSION['debug'] = 'Y';

include_once ('../conf/config.php');


require_once '../incs/lib_data_filters.php';
require_once '../incs/lib_enkitools.php';

untaint_input();


if ($_SAFE['lid'] == 0) {
	// you must pick a library
	
	
	$msg = "You must select a library to login";
}
else {
		
		$userId = $_SAFE['un'];
		$userPass = $_SAFE['up'];
		$passType = $_SAFE['ptype'];
		$auth_protocol = $_SAFE['protocol'];
		$auth_vendor = $_SAFE['vendor'];
		$auth_https = $_SAFE['https'];
		$ip = $_SAFE['ipaddress'];
		$port = $_SAFE['port'];
		

		$authVendorProtocol = $auth_vendor . "_" . $auth_protocol;
		$authFile = $authVendorProtocol . ".php";
		
		print $_SESSION['debug'];		
		print "<h3>Authfile</h3>";
		print "<pre>";		
		print($_CONFIG['libPath'] . $authFile);	
		print "</pre>";
		
		$refurl = $_SERVER['HTTP_REFERER'];
		print "<h3>Referring URL</h3>";
		print "<pre>";
		print $refurl; 
		print "</pre>";
		
		print "<h3>Library Data Lookup</h3>";
		print "<pre>";		
		print "</pre>";

		// create array for passing data to ILS
		$dataArray['orgConfigs']['authentication_ip'] = $ip;		
		$dataArray['orgConfigs']['authentication_port'] = $port;
		$dataArray['orgConfigs']['auth_https'] = $auth_https;
		$dataArray['patronNumber'] = $_SAFE['un'];
		$dataArray['orgConfigs']['authentication_password'] = $passType;
		$dataArray['patronLastName'] = $_SAFE['up'];
		$dataArray['patronPIN'] = $_SAFE['up'];
		$dataArray['orgConfigs']['authentication_patronTypes'] = $auth_ptypes;
		$dataArray['orgConfigs']['authentication_protocol_username'] = $_SAFE['sipuser'];
		$dataArray['orgConfigs']['authentication_protocol_password'] = $_SAFE['sippwd'];

		print "<h3>Resulting Data Array</h3>";
		print "<pre>";		
		print_r($dataArray);
		print "</pre>";
		print "<h3>Resulting Data Array</h3>";

		include ($_CONFIG['libPath'] . $authFile);
		
		eval ("\$result = \$authVendorProtocol(\$dataArray);"); // this is what came back from the patron authentication request
		
		if (isset($_SAFE['test'])) {
			print "<h3>The Result</h3>";
			print "<pre>";
			print_r($result);
			print "</pre>";
		}

		if (!$result) { // did not get a result from ILS
			print "Did not get a result from the ILS";
			$sendBack = "FAIL";
		}


		
		print $sendBack;
		exit();
		
}
?>
<h3>Test Authentication Form</H3><br>
<div id="thisForm">

<form name="EnkiLogin" action="" method="post">


<b>Authentication Server Information</b><br><br>
Vendor: 
<select name="vendor" id="vendor">
	<option value="">  </option>
	<option value="CARLx"> CARLx </option>
	<option value="Evergreen"> Evergreen </option>
	<option value="Horizon"> Horizon </option>
	<option value="III"> III </option>
	<option value="Koha"> Koha </option>
	<option value="Polaris"> Polaris </option>
	<option value="TLC"> TLC </option>
	<option value="Kansas"> Kansas </option>
	<option value="Generic"> Generic </option>
	<option value="Any"> Any </option>
</select><br><br>
Protocol:
<select name="protocol" id="protocol">
	<option value="">  </option>
	<option value="SIP2"> SIP2 </option>
	<option value="PAPI"> PAPI (III) </option>
	<option value="BMatch"> BMatch </option>
	<option value="XML"> XML </option>
</select><br><br>

IP Address: <input class="loginFormInput" type="text" name="ipaddress" value="" size="15"/> (IP address of authentication server)<br><br>
Port: <input class="loginFormInput" type="text" name="port" value="" size="6"/> (SIP or PAPI port)<br><br>
Https:
<select name="https" id="https">
	<option value="N"> No </option>
	<option value="Y"> Yes </option>
</select><br><br>
SIP User: <input class="loginFormInput" type="text" name="sipuser" value="" size="12"/> (leave blank if not used)<br><br>
SIP Password: <input class="loginFormInput" type="text" name="sippwd" value="" size="12"/> (leave blank if not used)<br><br>
<HR>
<b>User Information</b><br><br>
User Barcode: <input class="loginFormInput" type="text" name="un" value="" size="15"/><br><br>
User Password: <input class="loginFormInput" type="text" name="up" size="15" id="password"/><br><br>
Password Type:
<select name="ptype" id="ptype">
	<option value="">  </option>
	<option value="LastName"> Last Name </option>
	<option value="PIN"> PIN </option>
	<option value="None"> None </option>
	<option value="DOB"> DOB </option>
</select><br><br>
<input id="loginButtonHome" type="submit" name="submit" value="Test Authentication" alt='Test Authentication' />
<input type="hidden" name="test" value="test" />
<input type="hidden" name="lid" value="1" />
</form>

</div>
