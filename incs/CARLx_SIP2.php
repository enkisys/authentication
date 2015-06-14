<?php
/*
	Protocol - CARLx SIP2
*/
	$ilsphp = "CARLx_SIP2.php";
	//include SIP2 class
	require_once ($_CONFIG['libPath'] . "3P/SIP2.php");
	
		print "<h3>" . $ilsphp . "</h3>";
		print "<pre>";		
		print "We made it to " . $ilsphp . "<br>";	

	function CARLx_SIP2 ($data=array()) {
		
		$ilsphp = "CARLx_SIP2.php";		
		print "We made it to the " . $ilsphp . " function" ;	
		print "</pre>";

    // create object
	   $mysip = new sip2;
    // Set host name/IP address
	  	$mysip->hostname = $data['orgConfigs']['authentication_ip'];
	// Set port number
	    $mysip->port = $data['orgConfigs']['authentication_port'];
    // patronNumber
		$mysip->patron = $data['patronNumber'];
    //Password - see if actual password or last name as password
    	if (strtoupper($data['orgConfigs']['authentication_password']) == "LASTNAME") {
    		$mysip->patronpwd = $data['patronLastName'];
    	} else {
			$mysip->patronpwd = $data['patronPin'];
		}
    // connect to SIP server 
	    $result = $mysip->connect();
			print "<h3>" . $ilsphp . " Connection Result</h3>";
			print "<pre>";		
			print($result);	
			print "</pre>";	     
	    if (!isset($result) || $result == '') {  //can't connect to SIP2 server
				$return_data['status'] = "invalid";
				$return_data['reason'] = "We apologize, but we are experiencing technical problems.";
				return ($return_data);
				exit ();
	    }

		if ($data['orgConfigs']['authentication_protocol_username'] != '') { //SIP2 login required
			$loginMsg = $mysip->msgLogin($data['orgConfigs']['authentication_protocol_username'],$data['orgConfigs']['authentication_protocol_password']);
			$response = $mysip->parseLoginResponse( $mysip->get_message($loginMsg) );

			if ($response['fixed']['Ok'] != 1) {
				//unable to login to SIP2 server
				$return_data['status'] = "invalid";
				$return_data['reason'] = "We apologize, but we are experiencing technical problems.";
				return ($return_data);
				exit ();
			}
		}

		//call PatronInformation Request
		$patronInfo = $mysip->msgPatronInformation('none');
    // parse the raw response into an array
	    $response = $mysip->parsePatronInfoResponse( $mysip->get_message($patronInfo) );
	
		if ($_SESSION['debug']) {
			print "<div align='left'><pre>response ";
			print_r ($response);
			print "</pre></div>";
		}
	
		//set up return array
			$return_data = array();
	
		//Patron Barcode - AA
		$return_data['patronNumber'] = trim($response['variable']['AA'][0]);
	
		//Patron Type - XA
			if (isset($response['variable']['XA'][0])) {
				$return_data['patronType'] = trim($response['variable']['XA'][0]);
			} else {
				$return_data['patronType'] = '';
			}
	
		//Zip Code - parse out of BD
			if (isset($response['variable']['BD'][0])) {
				$splitAddress = explode("^",$response['variable']['BD'][0]);
				$zipcode = trim($splitAddress[3]);
				$return_data['zipCode'] = substr($zipcode,0,5);  //only want the first 5 digits
			} else {
				$return_data['zipCode'] = '';
			}
	
		//Age - XB in format YYYYMMDD for Date of Birth
			if (isset($response['variable']['XB'][0])) {
				$today = date('Y-m-d');
				$todayTimestamp = strtotime($today);
					
				$sip2Year = substr($response['variable']['XB'][0],0,4);
				$sip2Month = substr($response['variable']['XB'][0],4,2);
				$sip2Day = substr($response['variable']['XB'][0],6,2);
					
				$sip2DOB = $sip2Year . "-" . $sip2Month . "-" . $sip2Day;
				$sip2DOBTimestamp = strtotime($sip2DOB);
				$todayTimestamp = strtotime($today);
		
		    $diff_secs = abs($todayTimestamp - $sip2DOBTimestamp);
		    $base_year = min(date("Y", $todayTimestamp), date("Y", $sip2DOBTimestamp));
		    $diff = mktime(0, 0, $diff_secs, 1, 1, $base_year);
        $years  = ((date("Y", $diff) - $base_year) * 12 + date("n", $diff) - 1)/12;
		
				$return_data['age'] = floor($years);
			} else {
				//$return_data['age'] = 0; 
				$return_data['age'] = $_SESSION['Age Limit'];
			}
	
		//Email - BE
			if (isset($response['variable']['BE'][0])) {
				$return_data['email'] = trim($response['variable']['BE'][0]);
			} else {
				$return_data['email'] = '';
			}
				
		//Name - AE.  Full name Last name first{space}firstname.  could have spaces separating hyphenated last name.
			$nameArray = explode(" ",$response['variable']['AE'][0]);
			foreach ($nameArray as $key => $name) {
				$nameArray[$key]  = strtoupper(trim($name));
			}
			//will determine actual last name below in Patron Status and Name check
	
		//Soft Block - AF
			if (isset($response['variable']['AF'][0])) {
				$return_data['SoftBlock'] = trim($response['variable']['AF'][0]);
				if ($return_data['SoftBlock'] == "Library Card Expired") {
					$return_data['status'] = "invalid";
					$return_data['reason'] = "We apologize for the inconvenience, but according to our records your library card has expired.";
						return ($return_data);
						exit ();
				}
			}
	
		//Patron Status - BL
			$return_data['patronStatus'] = trim($response['variable']['BL'][0]);
	
		//Zip Code check	
/*
		if ($data['orgConfigs']['authentication_zipcodes'] != '') {
			$zipArray = explode(",",$data['orgConfigs']['authentication_zipcodes']);
			if (!in_array($return_data['zipCode'],$zipArray)) { //bad
				$return_data['status'] = "invalid";
				$return_data['reason'] = "Invalid Zip Code.  You must live with-in . " . $data['orgConfigs']['organization_servicearea'] . " to use the Discover & Go Service.";
				return ($return_data);
				exit ();
			}
		}
*/
	
		//Patron Type check
/*
			$patronTypeArray = explode(",",$data['orgConfigs']['authentication_patronTypes']);
			if (!in_array($return_data['patronType'],$patronTypeArray)) { 
				$return_data['status'] = "invalid";
				$return_data['reason'] = "Invalid Patron Type";
				if ($_SESSION['debug']) {
					print_r ($return_data);
				}
				return ($return_data);
				exit ();
			}
	
			if ($_SESSION['debug']) {
				print "<pre>";
				print_r($patronTypeArray);
				print "</pre>";
				print "org patron types: " . $data['orgConfigs']['authentication_patronTypes'] . "<br>";
			}
		
		//Soft Block check  (Checking for "Soft Block", "No Override", and "Blocked"
			//check to see if PatronStatusType should be checked and if yes, what types are blocked
			if ($data['orgConfigs']['authentication_patronStatusCheck'] == "Y") {
				$patronStatusTypesArray = explode(",",$data['orgConfigs']['authentication_patronStatusTypes']);
				if (in_array($return_data['SoftBlock'],$patronStatusTypesArray)) {
	
	 			//if (isset($response['variable']['AF']) and $response['variable']['AF'][0] == "Soft Block" or $response['variable']['AF'][0] == "No Override" or $response['variable']['AF'][0] == "Blocked") { 
					$return_data['status'] = "invalid";
					$return_data['reason'] = "Account blocked - " . $response['variable']['AF'][0];
					return ($return_data);
					exit ();
				}
			}
*/

		//Patron Status and Name check
			if ($response['variable']['BL'][0] == "Y") {

				$return_data['fullName'] = trim($response['variable']['AE'][0]);
				$nameTest = $nameArray[0] . " " . $nameArray[1];

				//now compare last names entered before returning valid
				if (isset($data['authenticated'])) { //if $data['authenticated'], then patron previously authenticated (through EZProxy).
					//now check for lastName match.....
					if (in_array($data['patronLastName'], $nameArray)) {  //last name entered equals first element of $nameArray
						$return_data['lastName'] = strtoupper($nameArray[0]);
						
						for ($i=1; $i<count($nameArray); $i++) { //get rest of name and put in first name column
							$return_data['firstName'] .= $nameArray[$i] . " " ;
						}
						
						$return_data['firstName'] = trim ($return_data['firstName']);
					}
					elseif (strtoupper($data['patronLastName']) == $nameTest) {  //last name contains space, and matches $nameArray[0] . " " . $nameArray[1]
						$return_data['lastName'] = strtoupper($nameTest);

						for ($i=2; $i<count($nameArray); $i++) { //get rest of name and put in first name column
							$return_data['firstName'] .= $nameArray[$i] . " " ;
						}
						
						$return_data['firstName'] = trim ($return_data['firstName']);
					}

					//list ($return_data['lastName'],$return_data['firstName']) = explode(" ", $response['variable']['AE'][0],2);
					$return_data['status'] = "valid";
					return ($return_data);
					exit ();
				}
				else {	//logging in through vPASS login page
					if (in_array(strtoupper($data['patronLastName']), $nameArray)) { //last name entered equals first element of $nameArray
						$return_data['lastName'] = strtoupper($nameArray[0]);

						for ($i=1; $i<count($nameArray); $i++) { //get rest of name and put in first name column
							$return_data['firstName'] .= $nameArray[$i] . " " ;
						}
						
						$return_data['firstName'] = trim ($return_data['firstName']);
						$return_data['status'] = "valid";
					}
					elseif (strtoupper($data['patronLastName']) == $nameTest) { //last name contains space, and matches $nameArray[0] . " " . $nameArray[1]
						$return_data['lastName'] = strtoupper($nameTest);

						for ($i=2; $i<count($nameArray); $i++) { //get rest of name and put in first name column
							$return_data['firstName'] .= $nameArray[$i] . " " ;
						}
						$return_data['firstName'] = trim ($return_data['firstName']);
						$return_data['status'] = "valid";
					}
					else {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Name mismatch";
						return ($return_data);
						exit ();
					}
				}						
				
				
/*
					if (strtoupper($return_data['lastName']) != stripslashes(strtoupper($data['patronLastName']))) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Name mismatch";
						return ($return_data);
						exit ();
					} else {
						$return_data['status'] = "valid";
						return ($return_data);
						exit ();
					}
*/
			} else { //BL - patron status not equal to "Y"
				$return_data['status'] = "invalid";
				$return_data['reason'] = "No Account";
				return ($return_data);
				exit ();
			}
							
		//Return Data
		return($return_data);
		exit();		
	}
?>