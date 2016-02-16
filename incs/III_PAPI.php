<?php
/**
* III PAPI User Authentication
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
* @link https://github.com/enkisys/authentication/blob/master/incs/III_PAPI.php
*/
/*
	Protocol - III Patron API - Individual Millenium systems
*/

function III_PAPI ($data=array()) {

	if ($_SESSION['debug']) {
		print "in III_PAPI.php<br>";
		print "<div align='left'><pre>"; print_r ($data); print "</pre></div>";
	}
	//set up return array
	$return_data = array();
	$return_data['status'] = "valid";	

	//determine authentication type, LastName or PIN
	if ($_SESSION['debug']) {
		print $data['orgConfigs']['authentication_password'] . "<br>";
	}
	
	switch (strtoupper($data['orgConfigs']['authentication_password'])) {
		case "NONE":
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
			
			if ($_SESSION['debug']) {		
				print "API URL: " . $url . "<br>";
		
			}		
			$patronInfo = explode('<BR>',file_get_contents($url));
			
			if ($_SESSION['debug']) {
				print "<div align='left'><pre>API Return Array:";print_r ($patronInfo); print "</pre></div>";
			}
		break;
		
		case "LASTNAME":
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
			
	if ($_SESSION['debug']) {		
				print "API URL: " . $url . "<br>";
		
		}		
			$patronInfo = explode('<BR>',file_get_contents($url));
			
			if ($_SESSION['debug']) {
				print "<div align='left'><pre>API Return Array:";print_r ($patronInfo); print "</pre></div>";
			}
		break;
		
		case "PIN":
			//verify PIN
			if ($_SESSION['debug']) {
				print "PIN verification <br>";
			}
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
	
			if ($_SESSION['debug']) {
				print "<pre>API Return Array - PIN Test:";print_r ($patronInfo); print "</pre>";
			}	
		
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
					$return_data['reason'] = "PIN does not match. Please try again or contact us.";
					return ($return_data);
					exit ();
				}
				elseif (substr($fieldInfo, 0, 6) == "RETCOD" and $patronData == 2) { //patron record found, but no pin assigned
					$return_data['status'] = "invalid";
					$return_data['reason'] = "There is no PIN associated with your record.  Please Contact Us to get a PIN assigned.";
					return ($return_data);
					exit ();
				}
				elseif (substr($fieldInfo, 0, 6) == "ERRNUM" and $patronData == 1) { //patron record not found
					$return_data['status'] = "invalid";
					$return_data['reason'] = "We were unable to locate your account.  Please try again or Contact Us.";
					return ($return_data);
					exit ();
				}
				else {  //patron record found and PIN matches.  Get Patron record
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
					
					if ($_SESSION['debug']) {
						print "API URL: " . $url . "<br>";
					}

					$returnData = file_get_contents($url);
					$returnData = ltrim($returnData, "<HTML>");
					$returnData = ltrim($returnData, "<BODY>");
					$returnData = rtrim($returnData, "</BODY>");
					$returnData = rtrim($returnData, "</HTML>");
					
					$patronInfo = explode("<BR>",$returnData);

					if ($_SESSION['debug']) {
						print "<pre>API Return Array:";print_r ($patronInfo); print "</pre>";
					}
					break;
				}	

			} //end 			foreach ($patronInfo as $key => $value) {

		break;
	}

	if ($_SESSION['debug']) {
		print "<div align='left'>";
		print "<pre>http response headers"; print_r ($http_response_header); print "</pre>";
		print "<pre>_SERVER"; print_r ($_SERVER); print "</pre>";
		print "<pre>patronInfo"; print_r ($patronInfo); print "</pre>";
		print "</div>";
	}

	if ($patronInfo[0] == '') { //nothing returned by III PAPI, invalid patron account
		$return_data['status'] = "invalid";
		$return_data['reason'] = "No Account";
		return ($return_data);
		exit ();
	} 
	

	foreach ($patronInfo as $key => $value) {
/* 		print "<div align='left'>value: "; print $value . "</div><br>"; */
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
	
/*
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
				
				case "AGE LEVEL":
					$ageArray = explode(",",$data['orgConfigs']['authentication_patronAgeRanges']);
					if (!in_array($patronData,$ageArray)) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "You must be older than 14 to use this service.";
						return ($return_data);
						exit ();
					}
					//$return_data['age'] = $data['orgConfigs']['authenticationBaseAge']; //pass the minimum age regardless of actual
				break;

				case "AGE":  //age range
					$ageArray = explode(",",$data['orgConfigs']['authentication_patronAgeRanges']);
					if (!in_array($patronData,$ageArray)) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "You must be older than 14 to use this service.";
						return ($return_data);
						exit ();
					}
					//$return_data['age'] = $data['orgConfigs']['authenticationBaseAge']; //pass the minimum age regardless of actual
				break;
*/

				case "GENDER": //not in use
				break;

				case "LIB JURISD": //not in use
				break;

				case "LANGUAGE": //not in use
				break;
				
				case "RESIDENCE": //not in use
				break;

/*
				case "P TYPE":  //patron Type
					//In cc_params.ini will need list of P Type numerics that are allowed to go to D&G
					$patronTypeArray = explode(",",$data['orgConfigs']['authentication_patronTypes']);

					if ($patronTypeArray[0] != '' && !in_array($patronData,$patronTypeArray)) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Invalid Patron Type.";
						return ($return_data);
						exit ();
					}
				
					$return_data['patronType'] = trim($patronData);
				break;
*/

				case "TOT CHKOUT": //not in use
				break;
				
				case "TOT RENWAL": //not in use
				break;
				
				case "CUR CHKOUT": //not in use
				break;

/*
				case "BIRTH DATE":  //Age
					if ($patronData != '') {
						$today = date('Y-m-d');
						$todayTimestamp = strtotime($today);
						list ($month,$day,$year) = explode ("-",$patronData);

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
						}
					}
				break;

				case "HOME LIBR": //for shared systems, check for 'home library' code
					$libraryCodeArray = explode(",", $data['orgConfigs']['authentication_librarycode']);
					if (!in_array($partonData,$libraryCodeArray)) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Library code doesn't match.";
						return ($return_data);
						exit ();
					}					

				break;
*/

				case "PMESSAGE": //Not in use
				break;

/*
				case "MBLOCK": //Used for checking blocked patron status types
					$patronStatusTypesArray = explode(",", $data['orgConfigs']['authentication_patronStatusTypes']);
					if (in_array($patronData,$patronStatusTypesArray)) {
						$return_data['status'] = "invalid";
						$return_data['reason'] = "Your account is blocked.";
						return ($return_data);
						exit ();
					}					
				break;
*/

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

				case "CIRCACTIVE": //Not in use
				break;

				case "NOTICE PREF": //Not in use
				break;

				case "P BARCODE": 
					$return_data['patronNumber'] = trim($patronData);
				break;

				case "PATRN NAME":
					// Account for multiple last names in ILS
					if ($lastName != '') {
						//skip
						$lastName1 = $lastName;
					}
					//	print "LastName-prev: " . $lastName . "<br>";
					//	$lastName1 = $lastName;
			//		} else {
						
					
					// Split into First and Last Names
					//First look for a comma and split by that
					if (strpos($patronData, ",")) { //have comma delimiter
						//last name, first name
						list ($lastName, $firstName) = explode (",", $patronData);
					}
					else { //try with splitting on space
						//last name {space} first name
						list ($firstName,$lastName) = explode (" ", $patronData);
						if ($_SESSION['debug']) {
							print "LastName: " . $lastName . "<br>";
							print "FirstName: " . $firstName . "<br>";
						}
					}
					$lastName = trim($lastName);
					$firstName = trim($firstName);
					if ($data['orgConfigs']['authentication_password'] == "LastName") {
						// Remove Punctuation for testing but not for saving
						$punctuation = array("-", "_","."," ","'");
						$ilastname1 = strtoupper(str_replace($punctuation, "", $lastName1));
						$ilastname = strtoupper(str_replace($punctuation, "", $lastName));
						$dlastname = stripslashes(strtoupper(str_replace($punctuation, "", $data['patronLastName'])));
						$return_data['lastnames'] = "ilastname:" . strtoupper($ilastname) . "|dlastname:" .  stripslashes(strtoupper($dlastname)). "|";

						if ($ilastname != $dlastname && $ilastname1 != $dlastname)  {
							$return_data['status'] = "invalid";
							$return_data['reason'] = "Last Name does not match account." . "|" . $ilastname . "|" .  $dlastname . "|";
						}
						else {
							$return_data['status'] = "valid";
							$return_data['reason'] = "";
						}
					}

					$return_data['fullName'] = $patronData;
					$return_data['lastName'] = $lastName;
					$return_data['firstName'] = $firstName;
			//		}
					//list ($return_data['lastName'],$return_data['firstName']) = explode(",", $patronData);
				break;

				case "ADDRESS":  //mailing address - street, city, state, zip
					$LOCADDR_valid = true;  //just use LOCADDR for remaining info
	
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

		
				case "LOC ADDR":  //mailing address - street, city, state, zip
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
	
					$zipCodeArray = explode(",", $data['orgConfigs']['authentication_zipcodes']);
					foreach ($zipCodeArray as $key => $value) {  //remove spaces between zips
						$zipCodeArray[$key] = trim($value);
					}
	
					//validate zip code
					$zipCheck = substr($zip,0,5);
					if (!in_array($zipCheck,$zipCodeArray)) {
						$LOCADDR_valid = false;
					} else {
						$LOCADDR_zip = substr($zip,0,5); //first five only
					}
				break;			

				case "TELEPHONE": //not in use
				break;
				
				case "HOME TEL": //not in use
				break;

				case "UNIQUE ID": //not in use
				break;

				case "ENEWS": //not in use
				break;

				case "EMAIL ADDR": 
					$return_data['email'] = $patronData;
				break;

				case "ERRMSG":
					$return_data['status'] = "invalid";
					$return_data['reason'] = $patronData;
					return ($return_data);
					exit ();
				break;
	
/*
				case "PERM ADDR":  //permanent address - street, city, state, zip
					$PERMADDR_valid = true;
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
	
					$zipCodeArray = explode(",", $data['orgConfigs']['authentication_zipcodes']);
					foreach ($zipCodeArray as $key => $value) {  //remove spaces between zips
						$zipCodeArray[$key] = trim($value);
					}
	
					//validate zip code
					$zipCheck = substr($zip,0,5);
					if (!in_array($zipCheck,$zipCodeArray)) {
						$PERMADDR_valid = false;
					} else {
						$PERMADDR_zip = substr($zip,0,5); //first five only
					}
				break;
*/			
	
				default:
				break;
			}
		} //if (isset($patronData))
	}

	//Address determination
/* 	if (!isset($PERMADDR_valid) and !isset($LOCADDR_valid)) { // */
/*
		$return_data['status'] = "invalid";
		$return_data['reason'] = "Invalid Zip Code.  You must live with-in " . $data['orgConfigs']['organization_servicearea'] . " to use the Discover & Go service.";
*/
/*
		$return_data['zipCode'] = "";
	} elseif (isset($PERMADDR_valid)) {
		$return_data['zipCode'] = $PERMADDR_zip;
	} else {
		$return_data['zipCode'] = $LOCADDR_zip;
	}
*/

	//Age determination.   III has age date ranges and birth dates.  Birth dates may not be required.
/*
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
*/


	//Return Data
	return($return_data);
	exit();		

}