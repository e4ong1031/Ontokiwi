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
 * @file OntologyUpdate.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Ontology;

use OKW\Parser\AnnotationParser;
use OKW\Parser\HierarchyParser;
use OKW\Parser\AxiomParser;

use OKW\Store\RDFStore\RDFStoreFactory;
use OKW\Store\RDFStore\RDFStore;
use OKW\Parser\CommonParser;

class OntologyUpdate {
	private $ontology;
	private $term;
		
	public function __construct( $ontAbbr, $termID  ) {
		$this->ontology = new OntologyData( $ontAbbr );
		$term = $this->ontology->parseTermByID( $termID );
		if ( is_null( $term ) ) {
			$this->term = $this->ontology->getPrefix() . $termID;
		} else {
			$this->term = $term->iri;
		}
	}
	
	public function doUpdate( $title, $wikiText ) {
		list( $wikiText, $annotations ) = AnnotationParser::reformatWikiText( $wikiText );
		
		list( $wikiText, $axioms ) = AxiomParser::reformatWikiText( $this->ontology->getOntAbbr(), $wikiText );
		
		list( $wikiText, $supClasses ) = HierarchyParser::reformatWikiText( $this->ontology->getOntAbbr(), $wikiText );
		
		$this->updateAnnotations( $annotations );
		
		$this->updateSubClassOf( $supClasses, $axioms['subclassof'] );
		
		$this->updateEquivalent( $axioms['equivalent'] );
		
		list( $wikiText, $common ) = CommonParser::reformatWikiText( $wikiText );
		if ( array_key_exists( 'label', $common ) ) {
			$this->updateLabel( $common['label'] );
		}
		
		return $wikiText;
	}
	
	public function updateAnnotations( $annotations ) {
		$rdf = $this->ontology->getRDF();
		
		foreach ( $annotations as $iri => $term ) {
			$rdf->updateObject( $this->ontology->getGraph(), $this->term, $iri, $term['value'], 'literal' );
		}
	}
	
	public function updateSubClassOf( $supClasses, $axioms ) {
		$subClassOf = $GLOBALS['okwRDFConfig']['prefixNS']['rdfs'] . 'subClassOf';
		$rdf = $this->ontology->getRDF();
		
		if ( !empty( $supClasses) && !empty( $axioms ) ) {
			$rdf->updateObject( $this->ontology->getGraph(), $this->term, $subClassOf, $supClasses, 'iri' );
			
			$rdf->insertAxiom( $this->ontology->getGraph(), $this->term, $subClassOf, $axioms );
		} else if ( !empty( $axioms ) ) {
			$rdf->deleteObject( $this->ontology->getGraph(), $this->term, $subClassOf, null, 'iri' );
			
			$rdf->insertAxiom( $this->ontology->getGraph(), $this->term, $subClassOf, $axioms );
		} else if ( !empty( $supClasses ) ) {
			$rdf->updateObject( $this->ontology->getGraph(), $this->term, $subClassOf, $supClasses, 'iri' );
		} else {
			$rdf->deleteObject( $this->ontology->getGraph(), $this->term, $subClassOf, null, 'iri' );
		}
	}
	
	public function updateEquivalent( $axioms ) {
		$equivalentClass = $GLOBALS['okwRDFConfig']['prefixNS']['owl'] . 'equivalentClass';
		$rdf = $this->ontology->getRDF();
		
		$rdf->deleteObject( $this->ontology->getGraph(), $this->term, $equivalentClass, null, 'iri' );
		
		if ( !empty( $axioms ) ) {
			$rdf->insertAxiom( $this->ontology->getGraph(), $this->term, $equivalentClass, $axioms );
		}
	}
	
	public function updateLabel( $label ) {
		$prefixNS = $GLOBALS['okwRDFConfig']['prefixNS'];
		$rdf = $this->ontology->getRDF();
		$rdf->updateObject( $this->ontology->getGraph(), $this->term, $prefixNS['rdfs'] . 'label', $label, 'literal' );
	}
	
	public function updateIRI( $iri ) {
		if ( $iri != $this->term ) {
			$rdf = $this->ontology->getRDF();
			#TODO
		}
	}
	
	public function updateType( $type ) {
		$prefixNS = $GLOBALS['okwRDFConfig']['prefixNS'];
		$rdf = $this->ontology->getRDF();
		$rdf->updateObject( $this->ontology->getGraph(), $this->term, $prefixNS['rdf'] . 'type', $type, 'iri' );
	}
	
	
	public function deleteTerm() {
		$rdf = $this->ontology->getRDF();
		$rdf->deleteSubject( $this->ontology->getGraph(), $this->term );
	}
	
}

?>