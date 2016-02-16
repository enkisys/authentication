<?php
/**
* TLC User Authentication 
*
* This library provides a method of communicating with a library 
* authentication system for authenticating users to an external system.
*
* PHP version 5
*
* License:
*
* Copyright (c) 2015 Califa Library Group
*
* Permission is hereby granted, free of charge, to any person 
* obtaining a copy of this software and associated documentation 
* files (the "Software"), to deal in the Software without 
* restriction, including without limitation the rights to use, 
* copy, modify, merge, publish, distribute, sublicense, and/or 
* sell copies of the Software, and to permit persons to whom the 
* Software is furnished to do so, subject to the following conditions:

* The above copyright notice and this permission notice shall be 
* included in all copies or substantial portions of the Software.

* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES 
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR 
* OTHER DEALINGS IN THE SOFTWARE.
*
* @package
* @author Laura McKeegan <enkisys@gmail.com>
* @license http://opensource.org/licenses/MIT MIT
* @copyright Califa Library Group 2015
* @version Version: 0.1.0
* @link https://github.com/enkisys/authentication/blob/master/incs/TLC_SIP2.php
*/
/*
	Protocol - TLC SIP2
*/

	//include SIP2 class
	require_once ($_CONFIG['libPath'] . "3P/SIP2.php");
	function TLC_SIP2 ($data=array()) {
		if ($_SESSION['debug']) {
			print "<div align='left'><pre>"; print_r ($data); print "</pre></div>";
		}
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
			} else if (strtoupper($data['orgConfigs']['authentication_password']) == "PIN"){
				$mysip->patronpwd = $data['patronPIN'];  
			} else {
				$mysip->patronpwd = $data['patronPIN']; 
		}
		
    // connect to SIP server 
	    $result = $mysip->connect();

		if ($data['orgConfigs']['authentication_protocol_username'] != '') { //SIP2 login required
			$loginMsg = $mysip->msgTLCLogin($data['orgConfigs']['authentication_protocol_username'],$data['orgConfigs']['authentication_protocol_password'],"1");
			$response = $mysip->parseLoginResponse( $mysip->get_message($loginMsg) );
			
			if ($response['fixed']['Ok'] != 1) {
				//unable to login to SIP2 server
				$return_data['status'] = "Invalid";
				$return_data['reason'] = "Cannot Login to SIP Server" ;
				return ($return_data);
				exit ();
			}
		}
	
		//call PatronInformation Request
		$patronInfo = $mysip->msgPatronInformation('none');
	  // parse the raw response into an array
    $response = $mysip->parsePatronInfoResponse( $mysip->get_message($patronInfo) );
	
		if ($_SESSION['debug']) {
			print "response<div align='left'><pre>";
			print_r ($response);
			print "</pre></div>";
		}
	
		//set up return array
			$return_data = array();
	

		//Patron Barcode - AA
		$return_data['patronNumber'] = trim($response['variable']['AA'][0]);


		//Patron Type - PC
			if (isset($response['variable']['PC'][0])) {
				$return_data['patronType'] = trim($response['variable']['PC'][0]);
			} else {
				$return_data['patronType'] = '';
			}
	
		//Zip Code - parse out of BD
			if (isset($response['variable']['BD'][0])) {
				$splitAddress = explode(",",$response['variable']['BD'][0]);
				$zipcode = trim($splitAddress[2]);
				$return_data['zipCode'] = substr($zipcode,0,5);  //only want the first 5 digits
			} else {
				$return_data['zipCode'] = '';
			}
	
		//Age - PB in format YYYYMMDD for Date of Birth
			if (isset($response['variable']['PB'][0])) {
				$today = date('Y-m-d');
				$todayTimestamp = strtotime($today);
					
				$sip2Year = substr($response['variable']['PB'][0],0,4);
				$sip2Month = substr($response['variable']['PB'][0],4,2);
				$sip2Day = substr($response['variable']['PB'][0],6,2);
					
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
				
		//Name - AE.  Full name Last name first, firstname seperated by space
			$return_data['fullName'] = trim($response['variable']['AE'][0]);
			//list ($return_data['lastName'],$return_data['firstName']) = explode(", ", $response['variable']['AE'][0],2);
			list ($return_data['firstName'],$return_data['lastName']) = explode(" ", $response['variable']['AE'][0],2);
	
		//Expiration Date - PE
			if (isset($response['variable']['PE'][0])) {
				$expirationDate = substr($response['variable']['PE'][0],0,8);
				if (date('Ymd') > $expirationDate) {
					$return_data['status'] = "invalid";
					$return_data['reason'] = "We apologize for the inconvenience, but according to our records your library card has expired.";
						return ($return_data);
						exit ();
				}
			}
	
		//Patron Status - BL
			$return_data['patronStatus'] = trim($response['variable']['BL'][0]);
	
		//Zip Code check	
/*		if ($data['orgConfigs']['authentication_zipcodes'] != '') {
			$zipArray = explode(",",$data['orgConfigs']['authentication_zipcodes']);
			if (!in_array($return_data['zipCode'],$zipArray)) { //bad
				$return_data['status'] = "invalid";
				$return_data['reason'] = "Invalid Zip Code.  You must live with-in " . $data['orgConfigs']['organization_servicearea'] . " to use the Discover & Go Service.";
				return ($return_data);
				exit ();
			}
		}
	
		//Patron Type check
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
*/	
	/* 			if (isset($response['variable']['AF']) and $response['variable']['AF'][0] == "Soft Block" or $response['variable']['AF'][0] == "No Override" or $response['variable']['AF'][0] == "Blocked") { */
/*					$return_data['status'] = "invalid";
					$return_data['reason'] = "Account blocked - " . $response['variable']['AF'][0];
					return ($return_data);
					exit ();
				}
			}
*/

		//Patron Status and Password (Name/PIN) check
			if ($response['variable']['BL'][0] == "Y") {   //valid patron
				//now compare last names entered before returning valid
	    	if (strtoupper($data['orgConfigs']['authentication_password']) == "LASTNAME") { //compare last name
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
				}
				else if (strtoupper($data['orgConfigs']['authentication_password']) == "PIN"){ //check PIN
					if (strtoupper($response['variable']['CQ'][0] == "Y")) { //PIN matched
						$return_data['status'] = "valid";
						return ($return_data);
						exit ();
					} else {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "PIN does not match";
						return ($return_data);
						exit ();
					}
				}
				else {
					if (strtoupper($response['variable']['BL'][0] == "Y")) { //NONE
						$return_data['status'] = "valid";
						return ($return_data);
						exit ();
					} else {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Not a valid patron";
						return ($return_data);
						exit ();
					}
				}
			}
			else {
				$return_data['status'] = "invalid";
				$return_data['reason'] = "Invalid Patron";
				return ($return_data);
				exit ();
			}
		
							
		//Return Data
		return($return_data);
		exit();		
}
?>