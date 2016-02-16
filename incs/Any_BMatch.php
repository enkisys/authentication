<?php
/**
* Barcode Match User Authentication 
*
* This library provides a method of validating patron barcodes 
* when not able to authenticate against an ILS.
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
* @link https://github.com/enkisys/authentication/blob/master/incs/Any_BMatch.php
*/
/*
	Protocol - Any BMatch
*/

	function Any_BMatch ($data=array()) {
		if ($_SESSION['debug']) {
			print "<div align='left'><pre>"; print_r ($data); print "</pre></div>";
		}
	
		//
		
		
		
		//  still need to make sure the library id matches
		
		
		
		
		//	
		$return_data['lastName'] = $data['patronLastName'];
	//	$return_data['email'] = 'email';
		$return_data['fullName'] = $data['patronLastName'];
	//	$return_data['firstName'] = 'firstname';
		// see if the barcode is valid
		if (!$bok = barcodelen($data['patronNumber'],$data['bcode_min'],$data['bcode_max'])) {
			$return_data['status'] = "invalid";
			$return_data['reason'] = $data['bcode_min'] . "-" . $data['bcode_max'];
			return ($return_data);
			exit ();
		} else {
			//barcode length is ok. check prefix
			
			if (!$bokk = barcodedigits($data['patronNumber'],$data['bcode_prefix'])) {
				$return_data['status'] = "invalid";
				$return_data['reason'] = "barcode prefix incorrect";
				return ($return_data);
				exit ();
			} else {
				
				if ($data['patronNumber']=='codabar') {
					//barcode digits are ok. check the checkdigit
					$bokkk = check_codabar($data['patronNumber']);
				} else {
					$bokkk = true;
				}

				if (!$bokkk) {
					$return_data['status'] = "invalid";
					$return_data['reason'] = "checkdigit invalid";
					return ($return_data);
					exit ();
				} else {
					// Barcode is ok, see if patron is in the database
					if ($vfpUserId = userExistsIn_VFPDB_test($data['patronNumber'])) {
						// Barcode is in the table. Now check to see if the last name matches
						if ($vfpUserId = userExistsIn_VFPDB($data['patronNumber'],$data['patronPIN'])) {
						
							// try to figure out the locationid for the library, and set to blank if none
							// Enki library will only have one location per library
							
							//user found in VFP user table (both barcode and last name)
							
							$return_data['status'] = "valid";
							$return_data['reason'] = $vfpUserId;
							return ($return_data);
							exit ();
						}else { // last name doesn't match
							$return_data['status'] = "invalid";
							$return_data['reason'] = "last name does not match";
							return ($return_data);
							exit ();
						}
					}
					else { // user not found in table add them
						$return_data['status'] = "valid";
						if ($data['orgConfigs']['authentication_password'] == "LastName") {
							$return_data['lastName'] = $data['patronPIN'];
							$return_data['fullName'] = $data['patronPIN'];
						} else {
							$return_data['lastName'] = "";
							$return_data['fullName'] = "";
						}
						
						$return_data['firstName'] = "";
					//	$return_data['reason'] = $vfpUserId.":2";
						return ($return_data);
						exit ();
	
	
					}
				}
			}
		}
	}
?>