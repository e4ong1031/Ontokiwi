<?php

/**
 * Copyright © 2015 The Regents of the University of Michigan
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 * 
 * For more information, questions, or permission requests, please contact:
 * Yongqun “Oliver” He - yongqunh@med.umich.edu
 * Unit for Laboratory Animal Medicine, Center for Computational Medicine & Bioinformatics
 * University of Michigan, Ann Arbor, MI 48109, USA
 * He Group:  http://www.hegroup.org
 */

/**
 * @file DisplayHelper.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Display;

use OKW\Ontology\OntologyValidator;

class DisplayHelper {
	
	public static function tidyHTML( $html ) {
		if ( extension_loaded( tidy ) ) {
			$tidy = new \tidy();
			$cleanHTML = $tidy->repairString( $html, array(
					'indent' => true,
					'indent-spaces' => 2,
					'show-body-only' => true,
					'merge-divs' => false,
			) );
			return $cleanHTML;
		} else {
			return $html;
		}
	}
	
	public static function getShortTerm( $term ) {
		if ( preg_match( '/^http/', $term ) ) {
			$tmp_array = preg_split( '/[#\/]/', $term );
			return( self::convertUTFToUnicode( array_pop( $tmp_array ) ) );
		}
		else {
			return(self::convertUTFToUnicode( $term ) );
		}
	}
	
	public static function convertToIRI( $ontology, $input ) {
		if ( preg_match( '/http:\/\/.+/', $input ) ) {
			$term = $input;
		} else if ( preg_match( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $input, $match ) ) {
			if ( $match[2] == '' ) {
				$term = $match[1] . '_' . $match[3];
			} else {
				$term = $match[2] . '_' . $match[3];
			}
		} else {
			return null;
		}
		return $ontology->convertToIRI( $term );
	}
	
	public static function decodeURL( $URL ) {
		return( urldecode( $URL ) );
	}
	
	public static function encodeURL( $URL) {
		return( preg_replace( '/#/', '%23', $URL ) );
	
	}
	
	#TODO: Reformat function
	public static function convertUTFToUnicode($input, $array=False) {
		$bit1  = pow(64, 0);
		$bit2  = pow(64, 1);
		$bit3  = pow(64, 2);
		$bit4  = pow(64, 3);
		$bit5  = pow(64, 4);
		$bit6  = pow(64, 5);
		$value = '';
		$val   = array();
		for($i=0; $i< strlen( $input ); $i++){
			$ints = ord ( $input[$i] );
			$z = ord ( $input[$i] );
			if( $ints >= 0 && $ints <= 127 ){
				// 1 bit
				//$value .= '&#'.($z * $bit1).';';
				$value .= htmlentities($input[$i]);
				$val[]  = $value;
			}
			if( $ints >= 192 && $ints <= 223 ){
				$y = ord ( $input[$i+1] ) - 128;
				// 2 bit
				$value .= '&#'.(($z-192) * $bit2 + $y * $bit1).';';
				$val[]  = $value;
			}
			if( $ints >= 224 && $ints <= 239 ){
				$y = ord ( $input[$i+1] ) - 128;
				$x = ord ( $input[$i+2] ) - 128;
				// 3 bit
				$value .= '&#'.(($z-224) * $bit3 + $y * $bit2 + $x * $bit1).';';
				$val[]  = $value;
			}
			if( $ints >= 240 && $ints <= 247 ){
				$y = ord ( $input[$i+1] ) - 128;
				$x = ord ( $input[$i+2] ) - 128;
				$w = ord ( $input[$i+3] ) - 128;
				// 4 bit
				$value .= '&#'.(($z-240) * $bit4 + $y * $bit3 + $x * $bit2 + $w * $bit1).';';
				$val[]  = $value;
			}
			if( $ints >= 248 && $ints <= 251 ){
				$y = ord ( $input[$i+1] ) - 128;
				$x = ord ( $input[$i+2] ) - 128;
				$w = ord ( $input[$i+3] ) - 128;
				$v = ord ( $input[$i+4] ) - 128;
				// 5 bit
				$value .= '&#'.(($z-248) * $bit5 + $y * $bit4 + $x * $bit3 + $w * $bit2 + $v * $bit1).';';
				$val[]  = $value;
			}
			if( $ints == 252 && $ints == 253 ){
				$y = ord ( $input[$i+1] ) - 128;
				$x = ord ( $input[$i+2] ) - 128;
				$w = ord ( $input[$i+3] ) - 128;
				$v = ord ( $input[$i+4] ) - 128;
				$u = ord ( $input[$i+5] ) - 128;
				// 6 bit
				$value .= '&#'.(($z-252) * $bit6 + $y * $bit5 + $x * $bit4 + $w * $bit3 + $v * $bit2 + $u * $bit1).';';
				$val[]  = $value;
			}
			if( $ints == 254 || $ints == 255 ){
				echo 'Wrong Result!<br>';
			}
		}
		if( $array === False ){
			$value = str_replace('~', ';  ', $value);
			$unicode = $value;
			return $unicode;
		}
		if($array === True ){
			$val     = str_replace('&#', '', $value);
			$val     = explode('~', $val);
			$len = count($val);
			//unset($val[$len-1]);
			return $unicode = $val;
		}
	}
}

?>