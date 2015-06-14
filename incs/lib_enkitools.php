<?php


function barcodelen($bcode,$bmin,$bmax) {
		$blen = strlen($bcode);
		if ($blen >= $bmin && $blen <= $bmax) {
			return true;
		}
		else {
			return false;
		}
} // end of function barcodelen


function barcodedigits($bcode,$btest) {
		$blen = strlen($btest);
		if (substr($bcode,0,$blen) == trim($btest)) {
			return true;
		}
		else {
			return false;
		}
} // end of function barcodedigits

function check_codabar($bcode) {
		$chkdigit = substr($bcode,13,1);
		$new_barcode = substr($bcode,0,13);

		//Now calculate check digit on new number

		$pos_1 = substr($new_barcode,0,1);
		$pos_2 = substr($new_barcode,1,1);
		$pos_3 = substr($new_barcode,2,1);
		$pos_4 = substr($new_barcode,3,1);
		$pos_5 = substr($new_barcode,4,1);
		$pos_6 = substr($new_barcode,5,1);
		$pos_7 = substr($new_barcode,6,1);
		$pos_8 = substr($new_barcode,7,1);
		$pos_9 = substr($new_barcode,8,1);
		$pos_10 = substr($new_barcode,9,1);
		$pos_11 = substr($new_barcode,10,1);
		$pos_12 = substr($new_barcode,11,1);
		$pos_13 = substr($new_barcode,12,1);
		

		$total = $pos_2 + $pos_4 + $pos_6 + $pos_8 + $pos_10 + $pos_12;  //add all even position digits

		$pos_1_prod = $pos_1 * 2;  //multiply odd digits by 2
		if ($pos_1_prod >= 10) { //if equal or greater than 10, subtract 9 from product and add to total
			$total = $total + ($pos_1_prod - 9);
		}
		else {
			$total = $total + $pos_1_prod;
		}

		$pos_3_prod = $pos_3 * 2;  //multiply odd digits by 2
		if ($pos_3_prod >= 10) { //if equal or greater than 10, subtract 9 from product and add to total
			$total = $total + ($pos_3_prod - 9);
		}
		else {
			$total = $total + $pos_3_prod;
		}

		$pos_5_prod = $pos_5 * 2;  //multiply odd digits by 2
		if ($pos_5_prod >= 10) { //if equal or greater than 10, subtract 9 from product and add to total
			$total = $total + ($pos_5_prod - 9);
		}
		else {
			$total = $total + $pos_5_prod;
		}

		$pos_7_prod = $pos_7 * 2;  //multiply odd digits by 2
		if ($pos_7_prod >= 10) { //if equal or greater than 10, subtract 9 from product and add to total
			$total = $total + ($pos_7_prod - 9);
		}
		else {
			$total = $total + $pos_7_prod;
		}

		$pos_9_prod = $pos_9 * 2;  //multiply odd digits by 2
		if ($pos_9_prod >= 10) { //if equal or greater than 10, subtract 9 from product and add to total
			$total = $total + ($pos_9_prod - 9);
		}
		else {
			$total = $total + $pos_9_prod;
		}

		$pos_11_prod = $pos_11 * 2;  //multiply odd digits by 2
		if ($pos_11_prod >= 10) { //if equal or greater than 10, subtract 9 from product and add to total
			$total = $total + ($pos_11_prod - 9);
		}
		else {
			$total = $total + $pos_11_prod;
		}

		$pos_13_prod = $pos_13 * 2;  //multiply odd digits by 2
		if ($pos_13_prod >= 10) { //if equal or greater than 10, subtract 9 from product and add to total
			$total = $total + ($pos_13_prod - 9);
		}
		else {
			$total = $total + $pos_13_prod;
		}


		$remd = $total % 10;
		if ($remd == 0) {
			$check_digit = 0;
		}
		else {
			$check_digit = abs(10 - $remd);
		}

		if ($chkdigit == $check_digit) {
			return true;
		}
		else {
			return false;
		}

}


?>