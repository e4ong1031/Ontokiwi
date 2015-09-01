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
 * @file OntologyAxiomHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Ontology;

use OKW\Display\DisplayHelper;

use OKW\HTML\DisplayHTML;

use OKW\Store\SQLStore\SQLStore;

class OntologyAxiomHTML implements DisplayHTML {
	
	public static function getHTML( $ontology, $term, $related, $axioms ) {
		$ontAbbr = $ontology->getOntAbbr();
		$rootURL = $GLOBALS['wgServer'] . $GLOBALS['wgScriptPath'] . '/index.php/';
		$html = self::headerHTML();
		
		if ( !empty( $axioms['subclassof'] ) ) { 
			$html .= self::supClassAxiomHTML( $ontAbbr, $rootURL, $ontology, $term, $related, $axioms['subclassof'] );
		}
		
		if ( !empty( $axioms['equivalent'] ) ) {
			$html .= self::equivalentAxiomHTML( $ontAbbr, $rootURL, $ontology, $term, $related, $axioms['equivalent'] );
		}
		
		$html .= self::bottomHTML();
		
		return $html;
	}
	
	public static function headerHTML() {
		$html = 
<<<END
<div id="okw-axiom" class="axiom">
<!-- OKW Axiom Display Start -->
END;
		
		return $html;
	}
	
	public static function bottomHTML() {
		$html = 
<<<END
<!-- OWD Axiom Display End -->
</div>
END;
		return $html;
	}
	
	protected static function equivalentAxiomHTML( $ontAbbr, $rootURL, $ontology, $term, $related, $axioms ) {
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		$magics = $sql->getObjectMagicWords( $ontAbbr );
		$objects = array();
		foreach ( $magics as $magic => $object ) {
			$objects[$magic] = $magic;
			$objects[$object['iri']] = $magic;
			$objects[$object['id']] = $magic;
		}
		$operations = $GLOBALS['okwRDFConfig']['restriction']['operation'];
		$types = $GLOBALS['okwRDFConfig']['restriction']['type'];
		
		$html .=
<<<END
<!-- OKW Equivalent Axiom Display Start -->
<div id="okw-axiom-equivalent-heading" class="equivalent-heading">Equivalent Axiom</div>
<div id="okw-axiom-equivalent-main" class="equivalent-main">
<ul>
END;
		
		foreach ( $axioms as $axiom ) {
			$axiomHTML = '<li>';
			$axiomArray = preg_split( '/\s|([()])/', $axiom, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
			foreach( $axiomArray as $word ) {
				if ( $word == '' ) {
					continue;
				} else if ( array_key_exists( $word, $objects ) ) {
					$objectLabel = $objects[$word];
					$axiomHTML .= "<b>$objectLabel</b>";
				} else if ( array_key_exists( $word, $types ) || array_key_exists( $word, $operations ) ) {
					$axiomHTML .= " $word ";
				} else if ( $word == '(' || $word == ')' ) {
					$axiomHTML .= $word;
				} else {
					$classIRI = $ontology->convertToIRI( $word );
					$classID = $related[$classIRI]->id;
					$classLabel = $related[$classIRI]->label;
					$axiomHTML .=
<<<END
<a href="$rootURL$ontAbbr:$classID">$classLabel</a>
END;
				}
			}
			$axiomHTML .= '</li>';
			
			$html .= $axiomHTML;
		}
		
		$html .=
		<<<END
</ul></div>
<!-- OKW Equivalent Axiom Display End -->
END;
		
		return $html;
	}
	
	protected static function supClassAxiomHTML( $ontAbbr, $rootURL, $ontology, $term, $related, $axioms ) {
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		$magics = $sql->getObjectMagicWords( $ontAbbr );
		$objects = array();
		foreach ( $magics as $magic => $iri ) {
			$objects[$magic] = $magic;
			$objects[$iri] = $magic;
			$objects[DisplayHelper::getShortTerm( $iri )] = $magic;
		}
		$operations = $GLOBALS['okwRDFConfig']['restriction']['operation'];
		$types = $GLOBALS['okwRDFConfig']['restriction']['type'];
		
		$html .=
<<<END
<!-- OKW SupClass Axiom Display Start -->
<div id="okw-axiom-supclass-heading" class="supclass-heading">SubClassOf Axiom</div>
<div id="okw-axiom-supclass-main" class="supclass-main">
<ul>
END;
		
		foreach ( $axioms as $axiom ) {
			$axiomHTML = '<li>';
			$axiomArray = preg_split( '/\s|([()])/', $axiom, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
			foreach( $axiomArray as $word ) {
				if ( $word == '' ) {
					continue;
				} else if ( array_key_exists( $word, $objects ) ) {
					$objectLabel = $objects[$word];
					$axiomHTML .= "<b>$objectLabel</b>";
				} else if ( array_key_exists( $word, $types ) || array_key_exists( $word, $operations ) ) {
					$axiomHTML .= " $word ";
				} else if ( $word == '(' || $word == ')' ) {
					$axiomHTML .= $word;
				} else {
					$classIRI = $ontology->convertToIRI( $word );
					$classID = $related[$classIRI]->id;
					$classLabel = $related[$classIRI]->label;
					$axiomHTML .=
<<<END
<a href="$rootURL$ontAbbr:$classID">$classLabel</a>
END;
				}
			}
			$axiomHTML .= '</li>';
			
			$html .= $axiomHTML;
		}
		
		$html .=
		<<<END
</ul></div>
<!-- OKW SupClass Axiom Display End -->
END;
		
		return $html;
	}
}

?>