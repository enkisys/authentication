<?php
/**
* Kansas State Library User Authentication
*
* This library provides a method of communicating with the Kansas 
* State Library's authentication system for authenticating users 
* to an external system.
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
* @link https://github.com/enkisys/authentication/blob/master/incs/Kansas_XML.php
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