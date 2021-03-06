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
 * @file RDFStoreFactory.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Store\RDFStore;

use OKW\Store\RDFStore\RDFStore;


class RDFStoreFactory {
	private $engine;
	
	public function __construct() {
		$this->engine = $GLOBALS['okwConfig']['sparqlEndpointEngine'];
	}
	
	public function getEngine() {
		return $this->engine;
	}
	
	public function setEngine( $engine ) {
		$this->engine = $engine;
	}
	
	public function createRDFStore( $endpoint ) {
		switch( $this->engine ) {
			case 'Virtuoso':
				return new VirtuosoStore( $endpoint );
			default:
				error_log( "Undefine SPARQL Engine.", 0 );
		}
		
	}
}

?>