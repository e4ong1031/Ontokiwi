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
 * @file AxiomParser.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Parser;

use OKW\Display\DisplayHelper;

use OKW\Ontology\OntologyData;
use OKW\Ontology\ManchesterSyntaxHandler;

use OKW\Parser\OntologyParser;

use OKW\Store\SQLStore\SQLStore;

class AxiomParser extends OntologyParser {
	
	public static function parse( $parser ) {
		$parser->disableCache();
		
		$title = $parser->getTitle()->getText();
		$titleArray = explode( ':', $title );
		$ontAbbr = $titleArray[0];
		$termID = str_replace( ' ' , '_' , $titleArray[1]);
		$ontology = new OntologyData( $ontAbbr );
		
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		$magics = $sql->getObjectMagicWords( $ontAbbr );
		
		$objects = array();
		foreach ( $magics as $magic => $object ) {
			$objects[$magic] = $object['iri'];
			$objects[$object['iri']] = $object['iri'];
			$objects[$object['id']] = $object['iri'];
		}
		$operations = $GLOBALS['okwRDFConfig']['restriction']['operation'];
		$types = $GLOBALS['okwRDFConfig']['restriction']['type'];
		
		$params = array();
		for ( $i = 2; $i < func_num_args(); $i++ ) {
			$params[] = func_get_arg( $i );
		}
		
		list ( $options, $valids, $invalids ) = self::extractAxiom( $params, $ontology, $objects, $operations, $types, false );
		
		$axioms = array();
		foreach( $valids as $index => $value ) {
			$axioms[] = $value['text'];
			$GLOBALS['okwCache']['axiom'][$value['type']][] = $value['text'];
		}
		
		wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Parser\AxiomParser: parsed axiom {%s} for [[%s]]', join( ';', $axioms ), $title ) );
		
		wfDebugLog( 'OntoKiWi', '[caches] OKW\Parser\AxiomParser: axiom' );
		
		return array( '', 'noparse' => true );
	}
	
	public static function extractAxiom( $params, $ontology, $objects, $operations, $types, $newWiki ) {
		$options = array();
		$valids = array();
		$invalids = array();
		$supClasses = array();
	
		foreach ( $params as $param ) {
			$index = uniqid();
			$pair = explode( '=', $param, 2 );
			if ( count( $pair ) == 2 ) {
				$name = $pair[0];
				$name = preg_replace( '/[\s]*(<!--.*?(?=-->)-->)[\s]*/', '', $name );
				$name = strtolower( trim( $name ) );
	
				$value = $pair[1];
				$value = preg_replace( '/[\s]*(<!--.*?(?=-->)-->)[\s]*/', '', $value );
				$value = trim( $value );
	
				$options[$index][] = $name;
				$options[$index][] = $value;
				
				if ( $name == 'subclassof' ) {
					list( $valid, $data ) = ManchesterSyntaxHandler::parseRecursiveManchester( true, $value, $ontology, $objects, $operations, $types, $newWiki );
					if ( $valid ) {
						$valids[$index]['type'] = 'subclassof';
						$valids[$index]['text'] = $value;
						$valids[$index]['data'] = $data;
					} else {
						$invalids[$index] = self::ERROR_INVALID_SYNTAX;
					}
				} else if ( $name == 'equivalent' ) {
					list( $valid, $data ) = ManchesterSyntaxHandler::parseRecursiveManchester( true, $value, $ontology, $objects, $operations, $types, $newWiki );
					if ( $valid ) {
						$valids[$index]['type'] = 'equivalent';
						$valids[$index]['text'] = $value;
						$valids[$index]['data'] = $data;
					} else {
						$invalids[$index] = self::ERROR_INVALID_SYNTAX;
					}
				} else {
					$invalids[$index] = self::ERROR_INVALID_MAGICWORD;
				}
			} else {
				$options[$index][] = $param;
				$invalids[$index] = self::ERROR_EXCESS_INPUT;
			}
		}
	
		return array( $options, $valids, $invalids );
	}
	
	public static function reformatWikiText( $ontAbbr, $wikiText, $validAxioms = null, $newWiki = false ) {
		preg_match_all( '/{{\s*[#]?Axiom\s*:[\s]*[^|]([^}]*)}}/', $wikiText, $matches, PREG_SET_ORDER );
		
		$options = array();
		$valids = array();
		$invalids = array();
		if ( !empty( $matches ) || !is_null( $validAxioms ) ) {
			$ontology = new OntologyData( $ontAbbr );
			
			$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
			$magics = $sql->getObjectMagicWords( $ontAbbr );
			
			$objects = array();
			foreach ( $magics as $magic => $object ) {
				$objects[$magic] = $object['iri'];
				$objects[$object['iri']] = $object['iri'];
				$objects[$object['id']] = $object['iri'];
			}
			$operations = $GLOBALS['okwRDFConfig']['restriction']['operation'];
			$types = $GLOBALS['okwRDFConfig']['restriction']['type'];
				
			foreach ( $matches as $match ) {
				preg_match_all( '/[\s]*[|]([^|]*)/', $match[1], $params, PREG_PATTERN_ORDER );
					
				list( $option, $valid, $invalid ) = self::extractAxiom( $params[1], $ontology, $objects, $operations, $types, $newWiki );
					
				$options = array_merge( $options, $option );
				$valids = array_merge( $valids, $valid );
				$invalids = array_merge( $invalids, $invalid );
			}
		}
		
		if ( !is_null( $validAxioms ) ) {
			$valids = array();
			$output = array();
			foreach( $validAxioms as $value ) {
				$index = uniqid();
				
				$options[$index][] = $value['type'];
				$options[$index][] = $value['text'];
				
				list( $valid, $data ) = ManchesterSyntaxHandler::parseRecursiveManchester( true, $value['text'], $ontology, $objects, $operations, $types, $newWiki );
				
				if ( $valid ) {
					$valids[$index]['type'] = $value['type'];
					$valids[$index]['text'] = $value['text'];
					$valids[$index]['data'] = $data;
				} else {
					$invalids[$index] = self::ERROR_INVALID_SYNTAX;
				}
			}
		} else {
			$output = array();
			foreach ( $valids as $axiom ) {
				$output[$axiom['type']][] = $axiom['data'];
			}
		}
		
		#TODO: Duplication checking
		if ( !empty( $valids ) || !empty( $invalids ) ) {
			$text =
<<<END
{{ #Axiom: <!-- Auto formatted ontology axiom wikitext -->
END;
			
			foreach ( $valids as $index => $axiom ) {
				$type = $axiom['type'];
				$value = $axiom['text'];
				$text .=
<<<END

| $type = $value
END;
			}
			
			foreach ( $invalids as $index => $error ) {
				$msg = self::getErrorMessage( $error );
				
				if ( sizeof( $options[$index] ) == 1 ) {
					$param = $options[$index][0];
					$text .=
<<<END

| $msg $param
END;
				} else {
					$name = $options[$index][0];
					$value = $options[$index][1];
					$text .=
<<<END

| $msg $name = $value
END;
				}
			}
			
			$text .=
<<<END

}}

END;
		}
		
		$text .= preg_replace( '/([\s]?{{\s*[#]?Axiom\s*:[\s]*[^|][^}]*}}[\s]?)/', '', $wikiText );
		
		return array( $text, $output );
	}
	
}

?>