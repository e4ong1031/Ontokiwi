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
 * @file ExportOntology.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Special;

use DumpOutput;
use SpecialPage;
use XmlDumpWriter;

use OKW\HTML\Special\ExportOntologyHTML;

use OKW\Ontology\OntologyData;
use OKW\Ontology\OntologyValidator;

use OKW\Store\RDFStore\RDFStoreFactory;

class ExportOntology extends SpecialPage {
	const SUCCESS = 0;
	
	public function __construct() {
		parent::__construct( 'export_ontology', 'read' );
	}
	
	public function execute( $query ) {
		$this->setHeaders();
		
		$output = $this->getOutput();
		$request = $this->getRequest();
		
		if ( $request->getVal( 'exportTerm' ) && $this->getUser()->isAllowed( 'read' ) ) {
			$this->exportTerm();
			
			return;
		} elseif ( $request->getVal( 'exportOntology' ) && $this->getUser()->isAllowed( 'read' ) ) {
			$this->exportOntology();
			
			return;
		} else {
			list( $header, $html ) = ExportOntologyHTML::getHTML( 'input' );
			
			wfDebugLog( 'OntoKiWi', 'OKW\Special\ExportOntology: display: export form');
		}
		
		if ( !is_null( $header ) ) {
			$output->setPageTitle( $header );
		}
		
		$output->addHTML( $html );
		$output->addModules( array(
			'ext.okw.special.css',
			'ext.okw.special.js',
		) );
	}
	
	private function exportTerm() {
		$this->getOutput()->disable();
		
		wfResetOutputBuffers();
		$request = $this->getRequest();
		$config = $this->getConfig();
		
		$request->response()->header( "Content-type: application/xml; charset=utf-8" );
		if ( $request->getCheck( 'downloadTerm' ) ) {
			$filename = urlencode( $config->get( 'Sitename' ) . '-' . wfTimestamp() . '.xml' );
			$request->response()->header( "Content-disposition: attachment; filename={$filename}" );
		}
		
		$pages = explode( "\n", $request->getVal( 'pages' ) );
		
		$pageSet = array();
		foreach ( $pages as $title ) {
			$title = trim( $title );
			if ( OntologyValidator::isExistTitleText( $title ) ) {
				$titleArray = explode( ':', $title );
				$ontAbbr = $titleArray[0];
				$termID = str_replace( ' ' , '_' , $titleArray[1]);
				if ( !array_key_exists( $ontAbbr, $pageSet ) ) {
					$pageSet[$ontAbbr][] = $termID;
				} else if ( !in_array( $termID, $pageSet[$ontAbbr] ) ) {
					$pageSet[$ontAbbr][] = $termID;
				}
			}
		}
		
		$export = '';
		$initial = true;
		foreach ( $pageSet as $ontAbbr => $terms ) {
			$ontology = new OntologyData( $ontAbbr );
			$iris = array();
			foreach ( $terms as $term ) {
				$iris[] = $ontology->getPrefix() . $term;
			}
			$rdf = $ontology->getRDF();
			
			$xml = $rdf->exportDescribe( $ontology->getGraph(), $iris );
			
			if ( $initial ) {
				$initial = false;
			} else {
				$xml = preg_replace( '/^<\?xml[^?>]*\?>[\s]?/', '', $xml );
				$xml = preg_replace( '/<rdf\:RDF[\s]?xmlns\:rdf[^\s>]*[\s]?xmlns:rdfs[^\s>]*[\s]?>[\s]?/', '', $xml );
			}
			
			$xml = preg_replace( '/<\/rdf\:RDF>/', '', $xml );

			$export .= $xml;
		}
		$export .= '</rdf:RDF>';
		
		wfDebugLog( 'OntoKiWi', 'OKW\Special\ExportOntology: output term RDF/XML generated' );
		
		print( $export );
	}
	
	private function exportOntology() {
		$this->getOutput()->disable();
	
		wfResetOutputBuffers();
		$request = $this->getRequest();
		$config = $this->getConfig();
	
		$request->response()->header( "Content-type: application/xml; charset=utf-8" );
		if ( $request->getCheck( 'downloadOntology' ) ) {
			$filename = urlencode( $config->get( 'Sitename' ) . '-' . wfTimestamp() . '.xml' );
			$request->response()->header( "Content-disposition: attachment; filename={$filename}" );
		}
	
		$ontAbbr = $request->getVal( 'ontology' );
		$ontology = new OntologyData( $ontAbbr );
		$rdf = $ontology->getRDF();
		
		$xml = $rdf->exportOntology( $ontology->getGraph() );
	
		wfDebugLog( 'OntoKiWi', 'OKW\Special\ExportOntology: output ontology RDF/XML generated' );
	
		print( $xml );
	}
	
	protected function getGroupName() {
		return 'pagetools';
	}
}

?>