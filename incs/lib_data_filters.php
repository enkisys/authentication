<?php



/*
Function:	untaint_input()
Input:		NONE
Return:		$_SAFE
Purpose:	Used to make sure that all single and double quotes are 
			escaped and all html chars are html-encoded.  Called from
			index.php before beginning any processing of user input.
*/
function untaint_input() {
	global $_SAFE;
	//Check to see if magic_quotes_gpc is on, and set a var to tell us its state.
	if( get_magic_quotes_gpc() ) {
    	$have_slashes = 'Y';
    }
    else {
    	$have_slashes = 'N';
    }

	//Loop over all vars in $_REQUEST	
	if ($_REQUEST) {
		foreach($_REQUEST as $key => $value) {
			//print "<p>first array level - $key:  $value</p>\n";
			if (!is_array($value)) {// string variable
				// If we don't have slashes for single and double quotes, add them.			    
				if ($have_slashes == 'N') {$value = addslashes($value);}

				// HTML-Encode all chars that have meaning to HTML			
					$value = htmlspecialchars($value);

				// Add cleansed vars to $_SAFE array			
					$_SAFE[$key] = $value;
			}
			else { //array or array of arrays
				foreach ($value as $arrKey => $arrValue) {
					if (!is_array($arrValue)) { // one level array
						// If we don't have slashes for single and double quotes, add them.			    
						if ($have_slashes == 'N') {$value = addslashes($value);}
		
						// HTML-Encode all chars that have meaning to HTML			
							$arrValue = htmlspecialchars($arrValue);
		
						// Add cleansed vars to $_SAFE array			
							$_SAFE[$key][$arrKey] = $arrValue;
					}
					else {
						foreach ($arrValue as $arrKey2 => $arrValue2) {
							if (!is_array($arrValue2)) { //two level array
								// If we don't have slashes for single and double quotes, add them.			    
								if ($have_slashes == 'N') {$value = addslashes($value);}
				
								// HTML-Encode all chars that have meaning to HTML			
									$arrValue2 = htmlspecialchars($arrValue2);
				
								// Add cleansed vars to $_SAFE array			
									$_SAFE[$key][$arrKey][$arrKey2] = $arrValue2;
							}
							else {//three level array
								foreach ($arrValue2 as $arrKey3 => $arrValue3) {
									// If we don't have slashes for single and double quotes, add them.			    
									if ($have_slashes == 'N') {$value = addslashes($value);}
					
									// HTML-Encode all chars that have meaning to HTML			
										$arrValue3 = htmlspecialchars($arrValue3);
					
									// Add cleansed vars to $_SAFE array			
										$_SAFE[$key][$arrKey][$arrKey2][$arrKey3] = $arrValue3;
								}// end foreach ($arrValue2 as $arrKey3 => $arrValue3)
							}// end if (!is_array($arrValue2)
						}//end foreach ($arrValue as $arrKey2 => arrValue2)
					}// end if (!is_array($arrValue))
				} //end foreach ($value as $arrKey => $arrValue)
			} //end if (!is_array($value))
		} //end foreach($_REQUEST as $key => $value)
	}// end if ($_REQUEST)
	return $_SAFE;
} // End of function untaint_input()




?>