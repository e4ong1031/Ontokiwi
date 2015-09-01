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
 * @file OntologyTermAutocomplete.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\API;

use ApiBase;

use OKW\Ontology\OntologyData;

use OKW\Store\RDFStore\RDFQueryHelper;

class OntologyTermAutocomplete extends ApiBase {
	private $ontology;
	private $properties;
	
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
		
		$this->properties = $GLOBALS['okwAutocomplete']['property'];
	}
	
	protected function getAllowedParams() {
		return array (
				'limit' => array (
						ApiBase::PARAM_MIN => 2,
				),
				'ontology' => null,
				'keywords' => null,
				'check' => null,
		);
	}
	
	protected function getParamDescription() {
		return array (
				'ontology' => 'Ontology Abbreviation',
				'keywords' => 'Keywords to be searched within RDF Database',
				'check' => 'The existence of the IRI of a term to be checked within RDF Database'
		);
	}
	
	protected function getDescription() {
		return 'Autocompletion call used by the Ontology Wiki Data extension';
	}
	
	protected function getExamples() {
		return array (
				'api.php?action=okwauto&ontology=HINO&keywords=human',
				'api.php?action=okwauto&ontology=HINO&keywords=interaction&format=json',
				'api.php?action=okwauto&ontology=HINO&check=http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FINO_0000021&format=json'
		);
	}
	
	public function execute() {
		$params = $this->extractRequestParams();
		$keywords = $params['keywords'];
		$ontAbbr = $params['ontology'];
		$check = urldecode($params['check']);
		
		$this->ontology = new OntologyData( $ontAbbr );
		
		if ( $keywords ) {
			$this->doSearch( $keywords );
		} else if ( $check ) {
			$this->doCheck( $check );
		} else {
#TODO: Throw Error
		}
	}
	
	private function doSearch( $keywords ) {
		$rdf = $this->ontology->getRDF();
		$tmps = $rdf->searchSubject( $this->ontology->getGraph(), $keywords );

		$match = array();
		foreach ( $tmps as $tmp ) {
			$match[] = array(
				'id' => $tmp['id'],
				'iri' => $tmp['iri'],
				'label' => $tmp['label'] . ' (' . $this->ontology->getOntAbbr() . ':' . $tmp['id'] . ')'
			);
		}
		
		$result = $this->getResult();
		$result->addValue( null, $this->getModuleName(), $match );
	}
	
	private function doCheck( $check ) {
		$prefixNS = $GLOBALS['okwRDFConfig']['prefixNS'];
		
		$rdf = $this->ontology->getRDF();
		
		$exist = $rdf->existClass( $this->ontology->getGraph(), $check );

		$result = $this->getResult();
		if ( $exist ) {
			$label = $rdf->getLabel( $this->ontology->getGraph(),  array( $check ) );
			$result->addValue( null, $this->getModuleName(), array( 'exist' => 1, 'label' => $label ) );
		} else {
			$result->addValue( null, $this->getModuleName(), array( 'exist' => 0 ) );
		}
	}
}




?>