<?php
/**
* III PAPIS User Authentication 
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
* @link https://github.com/enkisys/authentication/blob/master/incs/III_PAPIS.php
*/
/*
	Protocol - III Patron API Shared System
*/

//print "III_PAPIS<br>";

function III_PAPIS ($data=array()) {
	//print "<div align='left'><pre>"; print_r ($data); print "</pre></div>";

	if ($_SESSION['debug']) {
		print "in III_PAPIS<br>";
	}

	//set up return array
	$return_data = array();
	$return_data['status'] = "valid";	

	//check to see if authentication password is Pin, and if so do pin test function first
	if ($_SESSION['debug']) {
		print "password type: " . $data['orgConfigs']['authentication_password'] . "<br>";
	}

 	if ($data['orgConfigs']['authentication_password'] == "PIN") {
	 		// Added to handle those using https (setting is stored in enkitools.enki_libraries)			
			if ($data['orgConfigs']['auth_https'] == 'Y') {
				$url = 'https://';
			}
			else {
				$url = 'http://';
			}
			
		$url .= $data['orgConfigs']['authentication_ip'];
		$url .= ":" . $data['orgConfigs']['authentication_port'];
		$url .= "/PATRONAPI/";
		$url .= $data['patronNumber'];
		$url .= "/";
		$url .= $data['patronPIN'];
		$url .= "/pintest";

		if ($_SESSION['debug']) {
			print "API URL: " . $url . "<br>";
		}

		$returnData = file_get_contents($url);
		$returnData = ltrim($returnData, "<HTML>");
		$returnData = ltrim($returnData, "<BODY>");
		$returnData = rtrim($returnData, "</BODY>");
		$returnData = rtrim($returnData, "</HTML>");
		
		$patronInfo = explode("<BR>",$returnData);

		foreach ($patronInfo as $key => $value) {
			$value = trim($value);
			$valueLen = strlen($value);
			$delimiterPos = strpos($value, "=");
	
			if ($delimiterPos != '' and ($valueLen - $delimiterPos) > 1) {  //have parameter value
				list ($fieldInfo,$patronData) = explode('=',$value);
				trim ($fieldInfo);
				trim ($patronData);
			}
			else { //don't have a parameter value
				unset ($patronData);
			}

			if (substr($fieldInfo, 0, 6) == "RETCOD" and $patronData == 1) { //pin did not match			
				$return_data['status'] = "invalid";
				$return_data['reason'] = "We apologize for the inconvenience, but according to our records your PIN does not match.";
				return $return_data;
				exit;
			}
			elseif (substr($fieldInfo, 0, 6) == "RETCOD" and $patronData == 2) { //patron record found, but no pin assigned
				$return_data['status'] = "invalid";
				$return_data['reason'] = "We apologize for the inconvenience, but according to our records there is no PIN associated with your library card.  Please contact us to set your PIN.";
				return $return_data;
				exit;
			}
			elseif (substr($fieldInfo, 0, 6) == "ERRNUM" and $patronData == 1) { //patron record not found
				$return_data['status'] = "invalid";
				$return_data['reason'] = "We apologize for the inconvenience, but we are unable to locate your library account.  Please try again or contact us for further help.";
				return $return_data;
				exit;
			}
		}
	}

	//set url
	//print "<pre>orgConfigs"; print_r ($data); print "</pre>";
	//print "<pre>_SESSION"; print_r ($_SESSION); print "</pre>";
	// Added to handle those using https (setting is stored in enkitools.enki_libraries)			
	if ($data['orgConfigs']['auth_https'] == 'Y') {
		$url = 'https://';
	}
	else {
		$url = 'http://';
	}
	$url .= $data['orgConfigs']['authentication_ip'];
	$url .= ":" . $data['orgConfigs']['authentication_port'];
	$url .= "/PATRONAPI/";
	$url .= $data['patronNumber'];
	$url .= "/dump";

	$returnData = file_get_contents($url);
	$returnData = ltrim($returnData, "<HTML>");
	$returnData = ltrim($returnData, "<BODY>");
	$returnData = rtrim($returnData, "</BODY>");
	$returnData = rtrim($returnData, "</HTML>");
	
	$patronInfo = explode("<BR>",$returnData);

	if ($_SESSION['debug']) {
		print "<div align='left'>";
		print "<pre>"; print_r ($http_response_header); print "</pre>";
		print "<pre>"; print_r ($_SERVER); print "</pre>";
		print "<pre>"; print_r ($patronInfo); print "</pre>";
		print "</div>";
	}

	if ($data['patronNumber'] == "21157019745457") {
		//print "<pre>"; print_r ($http_response_header); print "</pre>";
		//print "<pre>"; print_r ($_SERVER); print "</pre>";
		//print "<pre>"; print_r ($patronInfo); print "</pre>";
	}

	if ($patronInfo[0] == '') { //nothing returned by III PAPI, invalid patron account
		$return_data['status'] = "invalid";
		$return_data['reason'] = "No Account";
		return ($return_data);
		exit ();
	} 

	//unset $ageDOB	
	if (isset($ageDOB)) {
		unset ($ageDOB);
	}

	foreach ($patronInfo as $key => $value) {
		$value = trim($value);
		$valueLen = strlen($value);
		$delimiterPos = strpos($value, "=");

		if ($delimiterPos != '' and ($valueLen - $delimiterPos) > 1) {  //have parameter value
			list ($fieldInfo,$patronData) = explode('=',$value);
		}
		else { //don't have a parameter value
			unset ($patronData);
		}

		if (isset($patronData)) {
			$patronData = trim($patronData);
			list ($fieldName,$fieldNumber) = explode("[",$fieldInfo);
			$fieldName = trim($fieldName);
			$fieldNumber = rtrim($fieldNumber, "]");
	
			//print $fieldName . "<br>";
			switch (trim($fieldName)) {
				case "REC INFO": //not in use
				break;	
	
				case "EXP DATE":
					//mm-dd-yy
					list ($month,$day,$year) = explode("-",$patronData);
					$year = "20" . $year;
	
					$expDate = mktime(0,0,0,$month,$day,$year);
					$today = mktime (0,0,0,date("m"),date("d"),date("y"));
	
					if ($expDate < $today) { //card expired
						$return_data['status'] = "invalid";
						$return_data['reason'] = "We apologize for the inconvenience, but according to our records your library card has expired.";
						return $return_data;
						exit;
					}
				break;

				case "PCODE1": //not in use
				break;	

				case "PCODE2": //not in use
				break;	

				case "CENSUS": //not in use
				break;	

				case "P TYPE":  //patron Type
					//print $patronData . "<br>";
					//print "<pre>"; print_r ($patronTypeArray); print "</pre>";
					$patronTypeArray = explode(",",$data['orgConfigs']['authentication_patronTypes']);
					if (!in_array($patronData,$patronTypeArray)) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Invalid Patron Type.";
						return ($return_data);
						exit ();
					}
				
					$return_data['patronType'] = trim($patronData);
				break;

				case "TOT CHKOUT": //not in use
				break;	

				case "TOT RENWAL": //not in use
				break;	

				case "CUR CHKOUT": //not in use
				break;	

				case "BIRTH DATE":  //Age
					if ($patronData != '') {
						$today = date('Y-m-d');
						$todayTimestamp = strtotime($today);
						list ($month,$day,$year) = explode ("-",$patronData);
						
						if ($_SESSION['debug']) {
							print "month: " . $month . "<br>";
						}
						
						
						if ($month == '' or $day == '' or $year == '') {  //no birth date supplied
							//do nothing.  Age will be set in Age Determination section after this switch
						}
						else {
							$dob = $year . "-" . $month . "-" . $day;
							$dobTimestamp = strtotime($dob);
			
					    $diff_secs = abs($todayTimestamp - $dobTimestamp);
					    $base_year = min(date("Y", $todayTimestamp), date("Y", $dobTimestamp));
					    $diff = mktime(0, 0, $diff_secs, 1, 1, $base_year);
			        $years  = ((date("Y", $diff) - $base_year) * 12 + date("n", $diff) - 1)/12;
		
							$ageDOB = floor($years);
							if ($_SESSION['debug']) {
								print "ageDOB: " . $ageDOB . "<br>";
							}
						}
					}
				break;

				case "HOME LIBR": //for shared systems, check for 'home library' code
					if (isset($data['orgConfigs']['authentication_librarycode']) && $data['orgConfigs']['authentication_librarycode'] != '') {
						$libraryCodeArray = explode(",", $data['orgConfigs']['authentication_librarycode']);
						if (!in_array($patronData,$libraryCodeArray)) {
							$return_data['status'] = "invalid";
							$return_data['reason'] = "Library code doesn't match.";
							return ($return_data);
							exit ();
						}					
					}
				break;

				case "PMESSAGE": //not in use
				break;	

				case "HLODUES": //not in use
				break;	
				
				case "MBLOCK": //Used for checking blocked patron status types
					$patronStatusTypesArray = explode(",", $data['orgConfigs']['authentication_patronStatusTypes']);
					if (in_array($patronData,$patronStatusTypesArray)) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Your account is blocked.";
						return ($return_data);
						exit ();
					}					
				break;

				case "REC TYPE": //Not in use
				break;
				
				case "RECORD #": //Not in use
				break;
				
				case "REC LENG": //Not in use
				break;
				
				case "CREATED": //Not in use
				break;
				
				case "UPDATED": //Not in use
				break;
				
				case "REVISIONS": //Not in use
				break;
				
				case "AGENCY": //Not in use
				break;
				
				case "CL RTRND": //Not in use
				break;
				
				case "MONEY OWED": //Not in use
				break;
				
				case "CUR ITEMA": //Not in use
				break;
							
				case "CUR ITEMB": //Not in use
				break;
				
				case "CUR ITEMC": //Not in use
				break;

				case "CUR ITEMD": //Not in use
				break;

				case "PCODE4": //not in use
				break;	

				case "PAT AGENCY": //not in use
				break;	

				case "CIRCACTIVE": //not in use
				break;	

				case "LANG PREF": //not in use
				break;	

				case "NOTICE PREF": //not in use
				break;	

				case "PATRN NAME":
					//last name, first name
					list ($lastName, $firstName) = explode (",", $patronData);
					if ($data['orgConfigs']['authentication_password'] == "LastName") {
						if (strtoupper($lastName) != stripslashes(strtoupper($data['patronLastName']))) {
							$return_data['status'] = "invalid";
							$return_data['reason'] = "Last Name does not match account.";
							return ($return_data);
							exit ();
						}
					}

					$return_data['fullName'] = $patronData;
					list ($return_data['lastName'],$return_data['firstName']) = explode(",", $patronData);
					
				break;

				case "ADDRESS": 
					$LOCADDR_valid = true;

					$patronData = rtrim($patronData,"$"); //trim trailing $
					
					$patronAddress = explode("$", $patronData);
					//find the zip code
					foreach ($patronAddress as $key => $info) {
						if (preg_match("/[0-9]{5}/", $info)) {
							//print "found zip at element " . $key . "<br>";
							list ($cityState, $zip) = preg_split("/([0-9]{5})/", $info,2,PREG_SPLIT_DELIM_CAPTURE);
							//print "cityState: " . $cityState . "<br>";
							//print "zip: " . $zip . "<br>";
						}
					}					

					if (is_array($data['orgConfigs']['authentication_zipcodes'])) { //see if there are any zip codes to check
						$zipCodeArray = explode(",", $data['orgConfigs']['authentication_zipcodes']);
						foreach ($zipCodeArray as $key => $value) {  //remove spaces between zips
							$zipCodeArray[$key] = trim($value);
						}
		
						//validate zip code
						if ($_SESSION['debug']) {
							print "<div align='left'>zipCodeArray<pre>"; print_r ($zipCodeArray); print "</pre></div>";
							print "count: " . count($zipCodeArray) . "<br>";
						}
						$zipCheck = substr($zip,0,5);
						if (!in_array($zipCheck,$zipCodeArray)) {
							$LOCADDR_valid = false;
						} else {
							$LOCADDR_zip = substr($zip,0,5); //first five only
						}
					}
					else {
						$LOCADDR_zip = substr($zip,0,5); //first five only
					}

				break;	

				case "MAIL ADDR": 
					$LOCADDR_valid = true;

					$patronData = rtrim($patronData,"$"); //trim trailing $
					
					if ($_SESSION['debug']) {
						print $patronData . "<br>";
					}
					
					$patronAddress = explode("$", $patronData);
					if ($_SESSION['debug']) {
						print "<div align='left'><pre>"; print_r ($patronAddress); print "</pre></div>";
					}
					//find the zip code
					foreach ($patronAddress as $key => $info) {
						if (preg_match("/[0-9]{5}/", $info)) {
							if ($_SESSION['debug']) {
								print "found zip at element " . $key . "<br>";
							}
							list ($cityState, $zip) = preg_split("/([0-9]{5})/", $info,2,PREG_SPLIT_DELIM_CAPTURE);
							if ($_SESSION['debug']) {
								print "cityState: " . $cityState . "<br>";
								print "zip: " . $zip . "<br>";
							}
						}
					}					
	
					if (is_array($data['orgConfigs']['authentication_zipcodes'])) { //see if there are any zip codes to check
						$zipCodeArray = explode(",", $data['orgConfigs']['authentication_zipcodes']);
						foreach ($zipCodeArray as $key => $value) {  //remove spaces between zips
							$zipCodeArray[$key] = trim($value);
						}
		
						//validate zip code
						if ($_SESSION['debug']) {
							print "<div align='left'>zipCodeArray<pre>"; print_r ($zipCodeArray); print "</pre></div>";
							print "count: " . count($zipCodeArray) . "<br>";
						}
						$zipCheck = substr($zip,0,5);
						if (!in_array($zipCheck,$zipCodeArray)) {
							$LOCADDR_valid = false;
						} else {
							$LOCADDR_zip = substr($zip,0,5); //first five only
						}
					}
					else {
						$LOCADDR_zip = substr($zip,0,5); //first five only
					}
				break;	

				case "TELEPHONE": //not in use
				break;	

				case "NOTE": //not in use
				break;	

				case "P BARCODE": 
					//$return_data['patronNumber'] = trim($patronData);

					if (trim($patronData) == trim($data['patronNumber'])) { 
						$return_data['patronNumber'] = trim($patronData);
					}
				break;	

				case "EMAIL ADDR": 
					$return_data['email'] = $patronData;
				break;

				case "PIN": //not in use
				break;	

				case "INPUT BY": //not in use
				break;	

				default:
				break;
			}
		} //if (isset($patronData))
	}



	//Address determination
	if (!isset($LOCADDR_valid)) { 
/*
		$return_data['status'] = "invalid";
		$return_data['reason'] = "Invalid Zip Code.  You must live with-in " . $data['orgConfigs']['organization_servicearea'] . " to use the Discover & Go service.";
		return ($return_data);
		exit ();
*/
		$return_data['zipCode'] = "";
	} else {
		$return_data['zipCode'] = $LOCADDR_zip;
	}

	//Age determination.   III has age date ranges and birth dates.  Birth dates may not be required.
	if ($_SESSION['debug']) {
		print "<div align='left'><pre>"; print_r ($_SESSION); print "</pre></div>";
		print "<div align='left'><pre>"; print_r ($data); print "</pre></div>";
		print "ageDOB: " . $ageDOB . "<br>";
	}
	if (isset($ageDOB)) {
		$return_data['age'] = $ageDOB;
	} else { //using date ranges or patron status type
	
		$juvenilePatronTypeArray = explode(",",$data['orgConfigs']['authentication_juvenilePatronStatusTypes']);
		if ($_SESSION['debug']) {
			print "<div align='left'>juvenilePatronTypeArray:<pre>"; print_r ($juvenilePatronTypeArray); print "</pre></div>";
		}

		if (in_array($return_data['patronType'],$juvenilePatronTypeArray)) {
			$return_data['age'] = ($data['orgConfigs']['patronAgeLimit'] - 1); //force to juvenile based on library's age limit
			//print "return_data['age']: " . $return_data['age'] . "<br>";
			//print "juvenile<br>";
		}
		else {
			$return_data['age'] = $_SESSION['Age Limit'];
		}
	}
	
	
	//Return Data
	return($return_data);
	exit();		

}