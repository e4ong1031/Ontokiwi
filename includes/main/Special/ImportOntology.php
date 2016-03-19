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
 * @file ImportOntology.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Special;

use SpecialPage;
use MWException;
use Title;

use OKW\Display\DisplayHelper;

use OKW\HTML\Special\ImportOntologyHTML;

use OKW\Ontology\OntologyData;
use OKW\Ontology\OntologyValidator;
use OKW\Ontology\ManchesterSyntaxHandler;

use OKW\Parser\CommonParser;
use OKW\Parser\HierarchyParser;
use OKW\Parser\AnnotationParser;
use OKW\Parser\AxiomParser;

use OKW\Store\RDFStore\RDFStore;
use OKW\Store\RDFStore\RDFStoreFactory;
use OKW\Store\SQLStore\SQLStore;

set_time_limit( 300 );

class ImportOntology extends SpecialPage {
	const SUCCESS = 0;
	const INVALID_SPARQL = 1;
	const NO_CLASS_FOUND = 2;
	const EXCESSIVE_CLASS = 3;
	
	public function __construct() {
		parent::__construct( 'import_ontology', 'ontology_master' );
	}
	
	public static function ErrorMessage( $status ) {
		switch ( $status ) {
			case self::INVALID_SPARQL:
				$type = 'Import error';
				$msg = 'Invalid ontology SPARQL endpoint provided. Please check your RDF database endpoint.';
				break;
			case self::NO_CLASS_FOUND:
				$type = 'Import error';
				$msg = 'There exists no term in the given ontology. Please check your RDF database or graph URL.';
				break;
			case self::EXCESSIVE_CLASS:
				$type = 'Import error';
				$msg = 'There are too many terms (over 10,000) in the given ontology. Please import the ontology using maintenance script.';
				break;
		}
		
		return array( $type, $msg );
	}
	
	public function execute( $query ) {
		$this->setHeaders();
		
		$output = $this->getOutput();
		$request = $this->getRequest();
		
		if ( $this->getUser()->isAllowed( 'ontology_master' ) ) {
			if ( $request->getVal( 'import' ) ) {
				list( $status, $classes ) = $this->importOntology();
				list( $header, $html ) = ImportOntologyHTML::getHTML( 'output', $status, $classes );
				
				wfDebugLog( 'OntoKiWi', 'OKW\Special\ImportOntology: display: importing terms list');
			} else {
				list( $header, $html ) = ImportOntologyHTML::getHTML( 'input' );
				
				wfDebugLog( 'OntoKiWi', 'OKW\Special\ImportOntology: display: import form');
			}
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
	
	private function recursiveRemoveDirectory( $dir ) {
	    foreach ( glob( "{$dir}/*" ) as $file ) {
	        if( is_dir( $file ) ) { 
	            $this->recursiveRemoveDirectory( $file );
	        } else {
	            unlink( $file );
	        }
	    }
	    rmdir( $dir );
	}
	
	private function importObjectProperty( $ontID, $options, $sql, $rdf, $graph ) {
		$prefixNS = $GLOBALS['okwRDFConfig']['prefixNS'];
		$iris = $rdf->getSubject( $graph, $prefixNS['rdf'] . 'type', $prefixNS['owl'] . 'ObjectProperty' );
		$labels = $rdf->getLabel( $graph, $iris );
		$objects = array();
		foreach ( $labels as $iri => $label ) {
			$id = DisplayHelper::getShortTerm( $iri );
			$label = str_replace( ' ', '_', $label );
			$objects[$id]['id'] = $id;
			$objects[$id]['iri'] = $iri;
			$objects[$id]['magicword'] = $label;
			if ( preg_match( '/([\w\d]*)_[\d]+/', $id , $match ) ) {
				$objects[$id]['ontology'] = $match[1];
				if ( $options['ontology_abbrv'] == $match[1] ) {
					$objects[$id]['source'] = $options['ontology_url'];
				}
			}
		}
		$sql->insertObjectProperty( $ontID, $objects );
	}
	
	private function importAnnotationProperty( $ontID, $options, $sql, $rdf, $graph ) {
		$prefixNS = $GLOBALS['okwRDFConfig']['prefixNS'];
		$iris = $rdf->getSubject( $graph, $prefixNS['rdf'] . 'type', $prefixNS['owl'] . 'AnnotationProperty' );
		$iris = $rdf->getLabel( $graph, $iris );
		$annotations = array();
		foreach ( $iris as $iri => $label ) {
			if ( $iri == $prefixNS['rdfs'] . 'label' ) {
				continue;
			}
			$id = DisplayHelper::getShortTerm( $iri );
			$label = str_replace( ' ', '_', $label );
			$annotations[$id]['id'] = $id;
			$annotations[$id]['iri'] = $iri;
			$annotations[$id]['magicword'] = $label;
			if ( preg_match( '/([\w\d]*)_[\d]+/', $id , $match ) ) {
				$annotations[$id]['ontology'] = $match[1];
				if ( $options['ontology_abbrv'] == $match[1] ) {
					$annotations[$id]['source'] = $options['ontology_url'];
				}
			}
		}
		$sql->insertAnnotationProperty( $ontID, $annotations );
	}
	
	private function importOntology() {
		$tmp = sys_get_temp_dir() . '/OntoKiWiQueue/';
		if ( file_exists( $tmp ) ) {
			$this->recursiveRemoveDirectory( $tmp );
		}
		mkdir( $tmp );
		
		if ( array_key_exists( 'OntoKiWi', $GLOBALS['wgDebugLogGroups'] ) ) {
			$log = $GLOBALS['wgDebugLogGroups']['OntoKiWi'];
		} else if ( $GLOBALS['wgDebugLogFile'] && $GLOBALS['wgDebugLogFile'] != '' ) {
			$log = $GLOBALS['wgDebugLogFile'];
		} else {
			$log = sys_get_temp_dir() . '/mediawikiimportfromtext.log';
		}
		
		$request = $this->getRequest();
		$options = $request->getValues();
		
		$ontAbbr = $options['ontology_abbrv'];
		$graph = $options['ontology_graph_url'];
		$fullname = $options['ontology_fullname'];
		$id = strtolower( $ontAbbr );
		
		$sql = new SQLStore( wfGetDB( DB_MASTER ) );
		
		$status = $sql->insertOntology( $id, $options );
		
		if ( $status ) {
			wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Special\ImportOntology: valid ontology: queued pages will be stored in $s', $tmp ) );
			
			$ontology = new OntologyData( $ontAbbr );
			$rdf = $ontology->getRDF();
			
			$this->importObjectProperty( $id, $options, $sql, $rdf, $graph );
			$this->importAnnotationProperty( $id, $options, $sql, $rdf, $graph );
			
			$ontID = $sql->getOntologyID( $ontAbbr );
			$annotationMagic = $sql->getAnnotationMagicWords( $ontID );
			$objectMagic = $sql->getObjectMagicWords( $ontID );
			
			$objects = array();
			foreach ( $objectMagic as $magic => $object ) {
				$objects[$magic] = $magic;
				$objects[$object['iri']] = $magic;
				$objects[$object['id']] = $magic;
			}
			$operations = array();
			foreach ( $GLOBALS['okwRDFConfig']['restriction']['operation'] as $operation => $operationIRI ) {
				$operations[$operationIRI] = $operation;
				$operations[$operation] = $operation;
			}
			$types = array();
			foreach ( $GLOBALS['okwRDFConfig']['restriction']['type'] as $type => $typeIRI ) {
				$types[$typeIRI] = $type;
				$types[$type] = $type;
			}
			
			$count = $rdf->countAllClass( $graph );
			
			if ( $count >= 10000 ) {
				$source = file_get_contents( $options['source'] );
				preg_match_all( '/xmlns:([\w]*)[\s]?=[\s]?"([^"]*)"[\s]?/', $source, $matches, PREG_SET_ORDER );
				$prefix = array();
				foreach ( $matches as $match ) {
					$prefix[$match[1]] = $match[2];
				}
				if ( preg_match_all( '/[\s]?<owl:Class[\s]?rdf:about[\s]?=[\s]?"(&([\w]*);)?([^"]*)"[\s]?[\/]?>/', $source, $matches, PREG_SET_ORDER ) ) {
					$classes = array();
					
					foreach( $matches as $match ) {
						if ( $match[1] != '' && $match[2] != '' ) {
							$classes[] = $prefix[$match[2]] . $match[3];
						} else {
							$classes[] = $match[3];
						}
					}
				} else {
					$sql->deleteOntology( $id );
					return array( self::EXCESSIVE_CLASS, null );
				} 
			} else if ( $count == 0 ) {
				$sql->deleteOntology( $id );
				return array( self::NO_CLASS_FOUND, null );
			} else {
				$classes = $rdf->getAllClass( $graph );
			}
			
			$filename = "Category:$ontAbbr";
			file_put_contents( $tmp . $filename, $fullname );
			
			$output = array();
			
			foreach( $classes as $index => $class ) {
				if ( $class == $GLOBALS['okwRDFConfig']['Thing'] ) {
					continue;
				}
				$term = $ontology->parseTermByIRI( $class );
				
				$id = $term->id;
				$filename = "$ontAbbr:$id";
				
				if ( !OntologyValidator::isValidTitleText( $filename ) ) {
					throw new MWException ( "Unable to process term: $id. Please check the correctness of the Ontology" );
				}
				
				$related = $ontology->parseTermRelated( $term );
				$wikiText = "[[Category:$ontAbbr]]";
				
				$title = Title::newFromText( $filename );
				if ( $title->exists() ) {
					continue;
				}
				
				$output[$class] = $term->label . " ($ontAbbr:$id)";
				
				$annotations = array();
				foreach ( $annotationMagic  as $name => $value ) {
					if ( array_key_exists( $value['iri'] , $related) ) {
						$annotations[$value['iri']] = $rdf->getObject( $graph, $term->iri, $value['iri'] );
					}
				}
				list( $wikiText, $annotations ) = AnnotationParser::reformatWikiText( $wikiText, $annotations );
				
				$axiomData = $rdf->getAxiom( $graph, $term->iri );
				$axioms = array();
				foreach ( $axiomData['subclassof'] as $data ) {
					$axiom = array();
					$axiom['type'] = 'subclassof';
					$axiom['text'] =  ManchesterSyntaxHandler::writeRecursiveManchester( $data, array_merge( $objects, $operations, $types ) );
					$axioms[] = $axiom;
				}
				foreach ( $axiomData['equivalent'] as $data ) {
					$axiom = array();
					$axiom['type'] = 'equivalent';
					$axiom['text'] =  ManchesterSyntaxHandler::writeRecursiveManchester( $data, array_merge( $objects, $operations, $types ) );
					$axioms[] = $axiom;
				}
				list( $wikiText, $axioms ) = AxiomParser::reformatWikiText( $ontAbbr, $wikiText, $axioms, true );
				
				$supClasses = array_keys( $rdf->getSupClass( $graph, $term->iri ) );
				if ( empty( $supClasses ) ) {
					$supClasses = array( $GLOBALS['okwRDFConfig']['Thing'] );
				}
				list( $wikiText, $supClasses ) = HierarchyParser::reformatWikiText( $ontAbbr, $wikiText, $supClasses );
				
				$common['label'] = $term->label;
				list( $wikiText, $common ) = CommonParser::reformatWikiText( $wikiText, $common );
				
				file_put_contents( $tmp . $filename, $wikiText );
				
			}
			
			wfDebugLog(
				'OntoKiWi',
				'OKW\Special\ImportOntology: ontology SPARQL query completes, pages will be created using maintenance scripts in the background'
			);
			
			$cmd = "( cd $tmp && for file in *; do php " . $GLOBALS['IP'] . "/maintenance/edit.php -u bot \$file < \$file; done && rm -R /tmp/OntoKiWiQueue ) > $log 2>&1 &";
			exec( $cmd, $output, $return );
			
			return array( self::SUCCESS, $output );
		} else {
			return array( self::INVALID_SPARQL, null );
		}
	}
	
	protected function getGroupName() {
		return 'pagetools';
	}
}

?>