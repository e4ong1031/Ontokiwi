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
 * @file OntologyValidator.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Ontology;

use OutputPage;
use Title;
use OKW\Store\SQLStore\SQLStore;
use OKW\Store\RDFStore\RDFStoreFactory;
use OKW\Store\RDFStore\RDFStore;

class OntologyValidator {
	public static function isExistOutputPage( OutputPage $output ) {
		$title = $output->getPageTitle();
		return self::isExistTitleText( $title );
	}
	
	public static function isExistTitle( Title $title ) {
		return self::isExistTitleText( $title->getText() );
	}
	
	public static function isExistTitleText( $title ) {
		if ( Title::newFromText( $title )->exists() && self::isValidTitleText( $title ) ) {
			$title = explode( ':', $title );
			$ontAbbr = $title[0];
			$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
			if ( $sql->hasOntology( $ontAbbr ) ) {
				$sqlResult = $sql->getOntologyAttributes(
						$ontAbbr,
						array(
								'end_point',
								'ontology_graph_url',
								'term_url_prefix'
						)
				);
				$endpoint = $sqlResult->end_point;
				$graph = $sqlResult->ontology_graph_url;
				$prefix = $sqlResult->term_url_prefix;
				$term = $prefix . str_replace( ' ' , '_' , $title[1]);
				 
				$rdfFactory = new RDFStoreFactory();
				$rdf = $rdfFactory->createRDFStore( $sqlResult->end_point );
				if ( $rdf->existClass( $graph , $term ) ) {
					return true;
				}
			}
		}
		return false;
	}
	
	public static function isValidOutputPage( OutputPage $output ) {
		$title = $output->getPageTitle();
		return self::isValidTitleText( $title );
	}
	
	public static function isValidTitle( $title ) {
		return self::isValidTitleText( $title->getText() );
	}
	
	public static function isValidTitleText( $title ) {
		return preg_match( '/[\w]+:[\w]+[_ ]?[0-9]?/', $title);
	}
				
	
}

?>