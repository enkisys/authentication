<?php
  /*
	Protocol - Kansas XML
*/
	function Kansas_XML ($data=array()) {
		
		
$hostname = $data['orgConfigs']['authentication_ip'];
$patron = $data['patronNumber'];
$patronpwd = $data['patronPIN'];


  /*
   * XML Sender/Client.
   */
  // Get our XML. You can declare it here or even load a file.
  
  
  $xml_builder = '<?xml version="1.0"?><AuthorizeRequest><UserID>' . $patron . '</UserID><Password>' . $patronpwd . '</Password></AuthorizeRequest>';


$header[] = "Content-type: text/xml";   
     
                 
  // We send XML via CURL using POST with a http header of text/xml.
  $ch = curl_init($hostname);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_builder);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
  curl_setopt($ch, CURLOPT_REFERER, 'http://www.enkilibrary.org');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  
  $ch_result = curl_exec($ch);
  curl_close($ch);
 
 
  $lastname = trim(getXmlValueByTag($ch_result,"LASTNAME"));
  $firstname = trim(getXmlValueByTag($ch_result,"FIRSTNAME"));
  $libraryid = trim(getXmlValueByTag($ch_result,"LibraryID"));
  $librarynm = trim(getXmlValueByTag($ch_result,"LibraryName"));
  $status = getXmlValueByTag($ch_result,"STATUS");
 


		//Patron Status and Name check
			if ($status == "1") {

				$return_data['fullName'] = $lastname . ', ' . $firstname;
				$return_data['lastName'] = $lastname;
				$return_data['firstName'] = $firstname;
				$return_data['libraryName'] = $librarynm;
				$return_data['libraryID'] = $libraryid;
				$return_data['status'] = "valid";
				return ($return_data);
				exit ();

			} else { //Status <> 1
				$return_data['status'] = $status;
				$return_data['reason'] = "No Account";
				return ($return_data);
				exit ();
			}
							
		//Return Data
		return($return_data);
		exit();		
	}
	

function getXmlValueByTag($inXmlset,$needle){ 
        $resource    =    xml_parser_create();//Create an XML parser 
        xml_parse_into_struct($resource, $inXmlset, $outArray);// Parse XML data into an array structure 
        xml_parser_free($resource);//Free an XML parser 
        
        for($i=0;$i<count($outArray);$i++){ 
            if($outArray[$i]['tag']==strtoupper($needle)){ 
                $tagValue    =    $outArray[$i]['value']; 
            } 
        } 
        return $tagValue; 
    } 

    echo getXmlValueByTag($inXmlset,$needle); 
?>