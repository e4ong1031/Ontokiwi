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
 * @file AnnotationParser.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Parser;

use OKW\Parser\OntologyParser;

use OKW\Store\SQLStore\SQLStore;

class AnnotationParser extends OntologyParser {
	
	public static function parse( $parser ) {
		$parser->disableCache();
		
		$title = $parser->getTitle()->getText();
		$titleArray = explode( ':', $title );
		$ontAbbr = $titleArray[0];
		
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		$magic = $sql->getAnnotationMagicWords( $ontAbbr );
		
		$params = array();
		for ( $i = 2; $i < func_num_args(); $i++ ) {
			$params[] = func_get_arg( $i );
		}
		
		list ( $options, $valids, $invalids ) = self::extractAnnotation( $params, $magic );
		
		$annotations = array();
		$cache = &$GLOBALS['okwCache']['annotation'];
		foreach ( $valids as $index => $annotation ) {
			$annotations[] = $annotation['iri'];
			if ( array_key_exists( $annotation['iri'], $cache ) ) {
				if ( $annotation['type'] == 'unique' ) {
					$cache[$annotation['iri']]['value'] = $annotation['value'];
				} else {
					$cache[$annotation['iri']]['value'] = array_merge( $annotation['value'], $cache[$annotation['iri']]['value'] );
				}
			} else {
				$cache[$annotation['iri']]['name'] = $annotation['name'];
				$cache[$annotation['iri']]['value'] = $annotation['value'];
			}
		}
		
		wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Parser\AnnotationParser: parsed annotation {%s} for [[%s]]', join( ';', $annotations ), $title ) );
		
		wfDebugLog( 'OntoKiWi', '[caches] OKW\Parser\AnnotationParser: annotation' );
		
		return array( '', 'noparse' => true );
		
	}
	
	public static function extractAnnotation( $params, $magic ) {
		$options = array();
		$valids = array();
		$invalids = array();
		
		foreach ( $params as $param ) {
			$index = uniqid();
			$pair = explode( '=', $param, 2 );
			if ( count ( $pair ) == 2 ) {
				$name = $pair[0];
				$name = preg_replace( '/[\s]*(<!--.*?(?=-->)-->)[\s]*/', '', $name );
				$name = strtolower( trim( $name ) );
				
				$value = $pair[1];
				$value = trim( $value );
				
				$options[$index][] = $name;
				$options[$index][] = $value;
				
				if ( array_key_exists( $name, $magic ) ) {
					$valids[$index]['name'] = $name;
					$valids[$index]['iri'] = $magic[$name]['iri'];
					$valids[$index]['type'] = $magic[$name]['type'];
					if ( $magic[$name]['type'] == 'unique' ) {
						$valids[$index]['value'] = $value;
					} else {
						$valids[$index]['value'][] = $value;
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
	
	public static function reformatWikiText( $wikiText, $validAnnotation = null ) {
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		$magic = $sql->getAnnotationMagicWords();
		$dictMagicIRI = array();
		foreach ( $magic as $name => $value ) {
			$dictMagicIRI[$value['iri']] = $name;
		}
		
		$text = '';
		$output = array();
		
		preg_match_all( '/{{\s*[#]?Annotation\s*:[\s]*[^|]([^}]*)}}/', $wikiText, $matches, PREG_SET_ORDER );
		
		$options = array();
		$valids = array();
		$invalids = array();
		if ( !empty( $matches ) || !is_null( $validAnnotation ) ) {
			foreach ( $matches as $match ) {
				preg_match_all( '/[\s]*[|]([^|]*)/', $match[1], $params, PREG_PATTERN_ORDER );
				
			
				list ( $option, $valid, $invalid ) = self::extractAnnotation( $params[1], $magic );
				$options = array_merge( $options, $option );
				$valids = array_merge( $valids, $valid );
				$invalids = array_merge( $invalids, $invalid );
			}
			
			if ( !is_null( $validAnnotation ) ) {
				$output = array();
				foreach ( $validAnnotation as $iri => $value ) {
					$output[$iri]['name'] = $dictMagicIRI[$iri];
					$output[$iri]['value'] = $value;
				}
			} else {
				foreach ( $valids as $index => $term ) {
					if ( !array_key_exists( $term['iri'], $output ) ) {
						$output[$term['iri']]['name'] = $term['name'];
						if ( $term['type'] == 'unique' ) {
							$output[$term['iri']]['value'] = array( $term['value'] );
						} else {
							$output[$term['iri']]['value'] = $term['value'];
						}
					} else {
						if ( $term['type'] == 'unique' ) {
							$output[$term['iri']]['value'] = array( $term['value']);
						} else {
							$output[$term['iri']]['value'] = array_merge( $output[$term['iri']]['value'], $term['value'] );
						}
					}
				}
			}
			
			$text .=
<<<END
{{ #Annotation: <!-- Auto formatted ontology annnotation wikitext -->
END;
			
			foreach ( $output as $iri => $term ) {
				$value = $term['value'];
				$name = $term['name'];
				
				foreach ( $value as $annote ) {
					$text .=
<<<END

| $name = $annote
END;
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
				
			$text .= preg_replace( '/([\s]?{{\s*[#]?Annotation\s*:[\s]*[^|][^}]*}}[\s]?)/', '', $wikiText );
		} else {
			$text = $wikiText;
		}
			
		
		foreach ( $dictMagicIRI as $iri => $name ) {
			if ( !array_key_exists( $iri, $output ) ) {
				$output[$iri]['value'] = null;
			}
		}
		
		return array( $text, $output );
	}
}

?>