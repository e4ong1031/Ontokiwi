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
 * @file OntologyTermAutocreate.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\API;

use ApiBase;

use OKW\Ontology\OntologyData;

use OKW\Store\RDFStore\RDFQueryHelper;

class OntologyTermAutocreate extends ApiBase {
	private $ontology;
	private $properties;
	
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}
	
	protected function getAllowedParams() {
		return array (
				'limit' => array (
						ApiBase::PARAM_MIN => 1,
				),
				'ontology' => null,
		);
	}
	
	protected function getParamDescription() {
		return array (
				'ontology' => 'Ontology Abbreviation',
		);
	}
	
	protected function getDescription() {
		return 'Autocreate term IRI used by the Ontology Wiki Data extension';
	}
	
	protected function getExamples() {
		return array (
				'api.php?action=okwcreate&ontology=HINO',
		);
	}
	
	public function execute() {
		$params = $this->extractRequestParams();
		$ontAbbr = $params['ontology'];
				
		$this->ontology = new OntologyData( $ontAbbr );
		
		$term = $this->ontology->createTerm( 'newTerm' );
		
		$create = array(
			'title' => $ontAbbr . ':' . $ontAbbr . '_' . $term->id,
			'iri' => $term->iri,
		);
		
		$result = $this->getResult();
		$result->addValue( null, $this->getModuleName(), $create );
	}
}



?>