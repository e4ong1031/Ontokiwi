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
 * @file CommonParser.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Parser;

use OKW\Parser\OntologyParser;

#TODO: IRI, Type with user privilage editing
class CommonParser extends OntologyParser {
	public static function parse( $parser ) {
		$parser->disableCache();
		
		$params = array();
		for ( $i = 2; $i < func_num_args(); $i++ ) {
			$params[] = func_get_arg( $i );
		}
		
		wfDebugLog( 'OntoKiWi', 'OKW\Parser\CommonParser: value will be retrieved from RDF store instead of wikitext' );
		
		return array( '', 'noparse' => true );
	}
	
	public static function extractCommon( $params ) {
		$options = array();
		$valids = array();
		$invalids = array();
		
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
		
				if ( $name == 'label' ) {
					$valids['label'] = $value;
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
	
	public static function reformatWikiText( $wikiText, $validCommon = null ) {
		preg_match_all( '/{{\s*[#]?Common\s*:[\s]*[^|]([^}]*)}}/', $wikiText, $matches, PREG_SET_ORDER );
		
		$options = array();
		$valids = array();
		$invalids = array();
		if ( !empty( $matches ) || !is_null( $validCommon ) ) {
			foreach ( $matches as $match ) {
				preg_match_all( '/[\s]*[|]([^|]*)/', $match[1], $params, PREG_PATTERN_ORDER );
			
				list( $option, $valid, $invalid ) = self::extractCommon( $params[1] );
			
				$options = array_merge( $options, $option );
				$valids = array_merge( $valids, $valid );
				$invalids = array_merge( $invalids, $invalid );
			}
			
			if ( !is_null( $validCommon ) ) {
				if ( array_key_exists( 'label', $validCommon ) ) {
					$valids['label'] = $validCommon['label'];
				}
			}
			
			$label = $valids['label'];
			$text =
<<<END
{{ #Common: <!-- Auto formatted ontology common wikitext -->
| label = $label
}}

END;
			
			$text .= preg_replace( '/([\s]?{{\s*[#]?Common\s*:[\s]*[^|][^}]*}}[\s]?)/', '', $wikiText );
			
			return array( $text, $valids );
		}
				
	}
	
}

?>