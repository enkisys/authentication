<?php
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