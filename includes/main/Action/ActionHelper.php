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
 * @file ActionHelper.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Action;

class ActionHelper {

	/**
	 * array_merge_recursive merges arrays, but it converts values with duplicate
	 * keys to arrays rather than overwriting the value in the first array with the duplicate
	 * value in the second array, as array_merge does.
	 *
	 * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
	 * Matching keys' values in the second array overwrite those in the first array.
	 *
	 * Parameters are passed by reference, though only for performance reasons. They're not
	 * altered by this function.
	 *
	 * See http://www.php.net/manual/en/function.array-merge-recursive.php#92195
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return array
	 * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
	 * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
	 */
	public static function array_merge_recursive_distinct( array &$array1, array &$array2 ) {
	
		$merged = $array1;
	
		foreach ( $array2 as $key => &$value ) {
			if ( is_array( $value ) && isset( $merged[$key] ) && is_array( $merged[$key] ) ) {
				$merged[$key] = self::array_merge_recursive_distinct( $merged[$key], $value );
			} else {
				$merged[$key] = $value;
			}
		}
	
		return $merged;
	}
	
	/**
	 * Parses data from a query string into the $data array
	 *
	 * @param Array $data
	 * @param String $queryString
	 * @return Array
	 * @author Stephan Gambke
	 */
	public static function parseDataFromQueryString( &$data, $queryString ) {
		$params = explode( '&', $queryString );
	
		foreach ( $params as $param ) {
			$elements = explode( '=', $param, 2 );
	
			$key = trim( urldecode( $elements[0] ) );
			$value = count( $elements ) > 1 ? urldecode( $elements[1] ) : null;
	
			if ( $key == "query" || $key == "query string" ) {
				self::parseDataFromQueryString( $data, $value );
			} else {
				self::addToArray( $data, $key, $value );
			}
		}
	
		return $data;
	}
	
	/**
	 * This function recursively inserts the value into a tree.
	 *
	 * @param $array is root
	 * @param $key identifies path to position in tree.
	 *    Format: 1stLevelName[2ndLevel][3rdLevel][...], i.e. normal array notation
	 * @param $value: the value to insert
	 * @param $toplevel: if this is a toplevel value.
	 * @author Stephan Gambke
	 */
	public static function addToArray( &$array, $key, $value, $toplevel = true ) {
		$matches = array( );
	
		if ( preg_match( '/^([^\[\]]*)\[([^\[\]]*)\](.*)/', $key, $matches ) ) {
	
			// for some reason toplevel keys get their spaces encoded by MW.
			// We have to imitate that.
			if ( $toplevel ) {
				$key = str_replace( ' ', '_', $matches[1] );
			} else {
				$key = $matches[1];
			}
	
			// if subsequent element does not exist yet or is a string (we prefer arrays over strings)
			if ( !array_key_exists( $key, $array ) || is_string( $array[$key] ) ) {
				$array[$key] = array( );
			}
	
			self::addToArray( $array[$key], $matches[2] . $matches[3], $value, false );
		} else {
			if ( $key ) {
				// only add the string value if there is no child array present
				if ( !array_key_exists( $key, $array ) || !is_array( $array[$key] ) ) {
					$array[$key] = $value;
				}
			} else {
				array_push( $array, $value );
			}
		}
	}
	
	public static function changeOntologyHierarchy( $oldWikiText, $supClasses ) {
		file_put_contents('test', $supClasses );
		
		return $oldWikiText;
	}
	
	public static function changeOntologyAnnotation( $oldWikiText, $annotationType, $annotationText ) {
		if ( sizeof( $annotationText ) != sizeof( $annotationType ) ) {
			#TODO: Throw Exception
		} else if ( sizeof( $annotationText) == 0 ) {
			return '';
		}
		
		$newWikiText = '';
		foreach ( $annotationType as $index => $type ) {
			$newWikiText .= '<iao type="';
			$newWikiText .= $type;
			$newWikiText .= '">';
			$newWikiText .= $annotationText[$index];
			$newWikiText .= '</iao>';
		}
		$newWikiText .= preg_replace( '/<iao[^>]*type="([^"]*)"[^>]*>([^<]*)<\/iao>/', '', $oldWikiText );
		
		return $newWikiText;
	}
}


?>