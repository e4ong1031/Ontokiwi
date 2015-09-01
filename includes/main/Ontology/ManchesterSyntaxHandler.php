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
 * @file ManchesterSyntaxHandler.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Ontology;

use OKW\Ontology\OntologyData;
use OKW\Ontology\OntologyValidator;
use OKW\Display\DisplayHelper;

class ManchesterSyntaxHandler {
	
	
	private static function extractFirst( $text ) {
		$tmp = preg_split( '/[\s]/', $text, 2, PREG_SPLIT_NO_EMPTY );
		if ( sizeof( $tmp ) > 1 ) {
			return array( trim( $tmp[0], ' ' ), trim( $tmp[1] ), ' ' );
		} else {
			return array( trim( $tmp[0], ' ' ), '' );
		}
	}
	
	private static function checkBracket( $text ) {
		return ( preg_match( '/^\s*\(/', $text ) && preg_match( '/\)$/', $text ) );
	}
	
	private static function checkObject( $term, $objects ) {
		return array_key_exists( $term, $objects );
	}
	
	private static function checkType( $term, $types ) {
		return array_key_exists( $term, $types );
	}
	
	private static function checkOperation( $term, $operations ) {
		return array_key_exists( $term, $operations );
	}
	
	private static function trimBracket( $text ) {
		if ( preg_match( '/^\s*\(/', $text ) ) {
			$text = substr( $text, 1 );
		}
		if ( preg_match( '/\)$/', $text ) ) {
			$text = substr( $text, 0, -1 );
		}
		return $text;
	}
	
	private static function checkOperationConsistency( $checks, $operations ) {
		if ( sizeof( array_unique( $checks ) ) == 1) {
			if ( self::checkOperation( array_pop( $checks ), $operations ) ) {
				return true;
			}
		}
		return false;
	}
	
	private static function checkClass( $termIRI, $ontology, $newWiki ) {
		$term = $ontology->parseTermByIRI( $termIRI );
		if ( !is_null( $term ) ) {
			$title = $ontology->getOntAbbr() . ':' . $term->id;
			if ( $newWiki ) {
				return OntologyValidator::isValidTitleText( $title );
			} else {
				return OntologyValidator::isExistTitleText( $title );
			}
		} else {
			return false;
		}
	}
	
	public static function parseRecursiveManchester( $valid, $text, $ontology, $objects, $operations, $types, $newWiki ) {
		if ( !$valid ) {
			return array( false, null );
		}
		if ( self::checkBracket( $text ) ) {
			$data = array();
			$text = self::trimBracket( $text );
			preg_match_all( '/(\([^)]*\)|[^(\s]*)[\s]?(and|or|not)[\s]?(\([^)]*\)*|[^(\s]*)/', $text, $matches, PREG_SET_ORDER );
			
			$checks = array();
			foreach ( $matches as $match ) {
				$checks[] = $match[2];
			}
			
			if ( self::checkOperationConsistency( $checks, $operations ) ) {
				$first = array_shift( $matches );
				$operation = $operations[$first[2]];
			} else {
				return array( false, null );
			}
			
			$data['restrictionType'] = $operation;
			$data['restrictionValue'] = array();
			
			list( $recurValid, $recurData ) = self::parseRecursiveManchester( true, self::trimBracket( $first[1] ), $ontology, $objects, $operations, $types, $newWiki );
			if ( !$recurValid ) {
				return array( false, null );
			}
			$data['restrictionValue'][] = $recurData;
			
			list( $recurValid, $recurData ) = self::parseRecursiveManchester( true, self::trimBracket( $first[3] ), $ontology, $objects, $operations, $types, $newWiki );
			if ( !$recurValid ) {
				return array( false, null );
			}
			$data['restrictionValue'][] = $recurData;
			
			foreach ( $matches as $match ) {
				list( $recurValid, $recurData ) = self::parseRecursiveManchester( true, self::trimBracket( $match[3] ), $ontology, $objects, $operations, $types, $newWiki );
				if ( !$recurValid ) {
					return array( false, null );
				}
				$data['restrictionValue'][] = $recurData;
			}
		} else {
			list( $first, $text ) = self::extractFirst( $text );
			
			$firstIRI = $ontology->convertToIRI( $first );
			if ( self::checkObject( $first, $objects ) ) {
				$data = array();
				$object = $first;
				list( $type, $text ) = self::extractFirst( $text );
				
				if ( self::checkType( $type, $types) ) {
					$data['restrictionValue'] = array();
					$data['restrictionType'] = $types[$type];
					$data['restrictionValue'][] = $objects[$object];
					if ( self::checkBracket( $text ) ) {
						list( $recurValid, $recurData ) = self::parseRecursiveManchester( $valid, $text, $ontology, $objects, $operations, $types, $newWiki );
						if ( $recurValid ) {
							$data['restrictionValue'][] = $recurData;
						} else {
							return array( false, null );
						}
					} else {
						list( $class, $text ) = self::extractFirst( $text );
						$classIRI = $ontology->convertToIRI( $class );
						if ( self::checkClass( $classIRI, $ontology, $newWiki ) ) {
							$data['restrictionValue'][] = $classIRI;
						} else {
							return array( false, null );
						}
					}
				} else {
					return array( false, null );
				}
			} else if ( self::checkClass( $firstIRI, $ontology, $newWiki) ) {
				$data = $firstIRI;
			} else {
				return array( false, null );
			}
		}
		return array( $valid, $data );
	}
	
	private static function doMap( $term, $mapping ) {
		if ( array_key_exists( $term, $mapping ) ) {
			return $mapping[$term];
		} else {
			return DisplayHelper::getShortTerm( $term );
		}
	}
	
	public static function writeRecursiveManchester( $data, $mapping = array() ) {
		$manchester = '';
			
		$operations = $GLOBALS['okwRDFConfig']['restriction']['operation'];
		$types = $GLOBALS['okwRDFConfig']['restriction']['type'];
		
		$type = $data['restrictionType'];
		$value = $data['restrictionValue'];
		$property = self::doMap( $value[0], $mapping );
		
		if ( in_array( $type, array_keys( $types ) ) ) {
			$manchester .= "$property $type ";
			if ( !is_array( $value[1] ) ) {
				$manchester .= self::doMap( $value[1], $mapping );
			} else {
				$manchester .= self::writeRecursiveManchester( $value[1], $mapping );
			}
		} else if ( in_array( $type, array_keys( $operations ) ) ) {
			$manchester .= '(';
			foreach ( $value as $index => $node ) {
				if ( $index != 0 ) {
					$manchester .= ' and ';
				}
				
				if ( !is_array( $node ) ) {
					$manchester .= self::doMap( $node, $mapping );
				} else {
					$manchester .= '(' . self::writeRecursiveManchester( $value[1], $mapping ) . ')';
				}
			}
			
			$manchester .= ')';
		}
		
		return $manchester;
	}
}

?>