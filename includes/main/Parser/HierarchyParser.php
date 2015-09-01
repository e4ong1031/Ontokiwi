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
 * @file HierarchyParser.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Parser;

use OKW\HTML\Ontology\OntologyHierarchyHTML;

use OKW\Ontology\OntologyData;

use OKW\Parser\OntologyParser;
use OKW\Ontology\OntologyValidator;
use OKW\Display\DisplayHelper;

class HierarchyParser extends OntologyParser {
	
	public static function parse( $parser ) {
		$parser->disableCache();
		
		$title = $parser->getTitle()->getText();
		$titleArray = explode( ':', $title );
		$ontAbbr = $titleArray[0];
		$termID = str_replace( ' ' , '_' , $titleArray[1]);
		$ontology = new OntologyData( $ontAbbr );
		
		$term = $ontology->parseTermByID( $termID );
		
		$params = array();
		for ( $i = 2; $i < func_num_args(); $i++ ) {
			$params[] = func_get_arg( $i );
		}
		
		list( $options, $valids, $invalids ) = self::extractSupClass( $params, $ontology );
		
		$pathType = $GLOBALS['okwHierarchyConfig']['pathType'];
		
		$supClasses = array();
		if ( !empty( $valids ) ) {
			foreach( $valids as $index => $value ) {
				$supClasses[] = $value['iri'];
				$hierarchy = $ontology->parseTermHierarchy( $term, $pathType, $value['iri'] );
				
				if ( $value['iri'] == $GLOBALS['okwRDFConfig']['Thing'] ) {
					$GLOBALS['okwCache']['hierarchy'][$index] = $hierarchy;
				} else {
					foreach ( $hierarchy as $path ) {
						if ( !empty( $path['path'] ) ) {
							$GLOBALS['okwCache']['hierarchy'][$index] = $hierarchy;
						}
					}
				}
			}
		}
		
		wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Parser\HierarchyParser: parsed hierarchy {%s} for [[%s]]', join( ';', $supClasses ), $title ) );
		
		wfDebugLog( 'OntoKiWi', '[caches] OKW\Parser\HierarchyParser: hierarchy' );
		
		return array( '', 'noparse' => true );
		
	}
	
	public static function extractSupClass( $params, $ontology ) {
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
					if ( strtolower( $value ) == 'thing' ) {
						$supClassIRI = $GLOBALS['okwRDFConfig']['Thing'];
						$valids[$index]['iri'] = $supClassIRI;
						$valids[$index]['id'] = DisplayHelper::getShortTerm( $supClassIRI );
						$valids[$index]['title'] = DisplayHelper::getShortTerm( $supClassIRI );
					} else {
						$supClassIRI = $ontology->convertToIRI( $value );
						$term = $ontology->parseTermByIRI( $supClassIRI );
						if ( !is_null( $term ) ) {
							$title = $ontology->getOntAbbr() . ':' . $term->id;
							if ( OntologyValidator::isExistTitleText( $title ) ) {
								$valids[$index]['iri'] = $supClassIRI;
								$valids[$index]['id'] = $ontology->convertToID( $supClassIRI );
								$valids[$index]['title'] = $ontology->convertToTitle( $supClassIRI );
							} else {
								$invalids[$index] = self::ERROR_INVALID_TERM;
							}
						} else {
							$invalids[$index] = self::ERROR_INVALID_TERM;
						}
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
	
	public static function reformatWikiText( $ontAbbr, $wikiText, $validSupClasses = null ) {
		preg_match_all( '/{{\s*[#]?Hierarchy\s*:[\s]*[^|]([^}]*)}}/', $wikiText, $matches, PREG_SET_ORDER );
		
		$options = array();
		$valids = array();
		$invalids = array();
		
		if ( !empty( $matches ) || !is_null( $validSupClasses ) ) {
			$ontology = new OntologyData( $ontAbbr );
			
			foreach ( $matches as $match ) {
				preg_match_all( '/[\s]*[|]([^|]*)/', $match[1], $params, PREG_PATTERN_ORDER );
				list( $option, $valid, $invalid ) = self::extractSupClass( $params[1], $ontology );
			
				$options = array_merge( $options, $option );
				$valids = array_merge( $valids, $valid );
				$invalids = array_merge( $invalids, $invalid );
			}
			
			if ( !is_null( $validSupClasses ) ) {
				$wikiTextValids = array();
				foreach ( $valids as $index => $term ) {
					$wikiTextValids[$term['iri']] = $index;
				}
				
				$remove = array_diff( array_keys( $wikiTextValids ), $validSupClasses );
				foreach ( $remove as $iri) {
					unset( $valids[$wikiTextValids[$iri]] );
				}
				$add = array_diff( $validSupClasses, array_keys( $wikiTextValids ) );
				foreach( $add as $iri ) {
					$index = uniqid();
					$valids[$index]['iri'] = $iri;
					$valids[$index]['id'] = $ontology->convertToID( $iri );
					$valids[$index]['title'] = $ontology->convertToTitle( $iri ); 
				}
			}
			
			if ( !empty( $valids ) || !empty( $invalids ) ) {
				$duplicate = array();
				$text =
<<<END
{{ #Hierarchy: <!-- Auto formatted ontology hierarchy wikitext -->
END;
				foreach ( $valids as $index => $term ) {
					$title = $term['title'];
					$id = $term['id'];
					$iri = $term['iri'];
					if ( !in_array( $iri, $duplicate ) ) {
						$text .=
<<<END

| subclassof = $title <!-- IRI=$iri; ID=$id -->
END;
					
						$duplicate[] = $iri;
					} else {
						continue;
					}
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
	
		}
		
		$text .= preg_replace( '/([\s]?{{\s*[#]?Hierarchy\s*:[\s]*[^|][^}]*}}[\s]?)/', '', $wikiText );
		
		$output = array();
		foreach ( $valids as $supClass ) {
			$output[] = $supClass['iri'];
		}
		
		return array( $text, $output );
	}
	
}

?>