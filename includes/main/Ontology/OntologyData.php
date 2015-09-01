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
 * @file OntologyData.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Ontology;

use stdClass;

use OKW\Display\DisplayHelper;

use OKW\Store\RDFStore\RDFQueryHelper;
use OKW\Store\RDFStore\RDFStoreFactory;
use OKW\Store\RDFStore\RDFStore;
use OKW\Store\SQLStore\SQLStore;

class OntologyData {
	private $ontAbbr;
	private $endpoint;
	private $graph;
	private $prefix;
	private $digit;
	
	private $prefixNS;
		
	private $rdf;
	
	public function __construct( $ontAbbr ) {
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		$sqlResult = $sql->getOntologyAttributes(
				$ontAbbr,
				array(
						'end_point',
						'ontology_graph_url',
						'term_url_prefix',
						'ontology_creation_digit',
				)
		);
		
		$this->ontAbbr = $ontAbbr;
		$this->endpoint = $sqlResult->end_point;
		$this->graph = $sqlResult->ontology_graph_url;
		$this->prefix = $sqlResult->term_url_prefix;
		$this->digit = intval( $sqlResult->ontology_creation_digit );
		
		$this->prefixNS = $GLOBALS['okwRDFConfig']['prefixNS'];
		
		$rdfFactory = new RDFStoreFactory();
		$this->rdf = $rdfFactory->createRDFStore( $sqlResult->end_point );
	}
	
	/**
	 * @param $ontAbbr
	 */
	public final function setOntAbbr( $ontAbbr ) {
		$this->ontAbbr = $ontAbbr ;
	}
	
	/**
	 * @return $ontAbbr
	 */
	public final function getOntAbbr() {
		return $this->ontAbbr;
	}
	
	/**
	 * @param $endpoint
	 */
	public final function setEndpoint( $endpoint ) {
		$this->endpoint = $endpoint ;
	}
	
	/**
	 * @return $endpoint
	 */
	public final function getEndpoint() {
		return $this->endpoint;
	}
	
	/**
	 * @param $graph
	 */
	public final function setGraph( $graph ) {
		$this->graph = $graph ;
	}
	
	/**
	 * @return $graph
	 */
	public final function getGraph() {
		return $this->graph;
	}
	
	/**
	 * @param $prefix
	 */
	public final function setPrefix( $prefix ) {
		$this->prefix = $prefix ;
	}
	
	/**
	 * @return $prefix
	 */
	public final function getPrefix() {
		return $this->prefix;
	}
	
	/**
	 * @param $digit
	 */
	public final function setDigit( $digit ) {
		$this->digit = $digit ;
	}
	
	/**
	 * @return $digit
	 */
	public final function getDigit() {
		return $this->digit;
	}
	
	
	/**
	 * @return $rdf
	 */
	public final function getRDF() {
		return $this->rdf;
	}
	
	public static function makeClass( $input ) {
		$class = new stdClass;
		foreach ( $input as $name => $value ) {
			$class->$name = $value;
		}
		return $class;
	}
	
	public function createTerm( $label ) {
		$allTerms = $this->rdf->searchSubjectIRIPattern( $this->graph, $this->prefix . $this->ontAbbr . '_' );
		$allTerms = str_replace( $this->prefix . $this->ontAbbr . '_', '', $allTerms );
		$allTerms = array_map( 'intval', $allTerms );
		sort( $allTerms );
		
		array_unshift( $allTerms, 0 );
		foreach( $allTerms as $index => $value ) {
			if ( $index != $value ) {
				$id = str_repeat( '0', $this->digit - strlen($index) ) . $index;
				break;
			}
		}
		
		$term = self::makeClass( array(
			'id' => $id,
			'iri' => $this->prefix . $this->ontAbbr . '_' . $id,
			'label' => $label,
			'type' => 'class',
		) );
				
		return $term;
	}
	
	public function existClass( $term ) {
		return $this->rdf->existClass( $this->graph , $term );
	}
	
	/**
	 * 
	 * @param $termIRI
	 * @return $term
	 */
	public function parseTermByIRI( $termIRI ) {
		$term = new stdClass;
		
		$termDescribe = $this->rdf->getDescribe( $this->graph, $termIRI );
		
		if ( 
			!empty( $termDescribe )  && 
			array_key_exists( $this->prefixNS['rdfs'] . 'label', $termDescribe ) &&
			array_key_exists( $this->prefixNS['rdf'] . 'type', $termDescribe )
		) {
			$termLabel = $termDescribe[$this->prefixNS['rdfs'] . 'label'][0]['value'];
			$termType = DisplayHelper::getShortTerm( $termDescribe[$this->prefixNS['rdf'] . 'type'][0]['value'] );
			
			$term = self::makeClass( array(
				'id' => DisplayHelper::getShortTerm( $termIRI ),
				'iri' => $termIRI,
				'label' => $termLabel,
				'type' => $termType,
				'describe' => $termDescribe,
			) );
					
			return $term;
		} else {
			return null;
		}
	}
	
	public function parseTermByID( $termID ) {
		return $this->parseTermByIRI( $this->prefix . $termID );
	}
	
	public function parseTermByTitle( $termTitle ) {
		preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $term, $matches, PREG_SET_ORDER );
		return $this->parseTermByIRI( $this->prefix . $matches[0][2] . '_' . $matches[0][3] );
	}
	
	public function parseTermRelated( $term ) {
		/*if ( !is_object( $term ) ) {
			$term = $this->parseTermByIRI( $term );
		}*/
		
		$termDescribe = $term->describe;
		$relatedClasses = array();
	
		foreach ( $termDescribe as $property => $propertyObjects ) {
			$relatedClasses[$property] = self::makeClass( array(
				'id' => DisplayHelper::getShortTerm( $property ),
				'iri' => $property,
				'label' => null,
				'type' => null,
				'hasChild' => null,
			) );
			foreach ( $propertyObjects as $object ) {
				if ( array_key_exists( 'type', $object ) && $object['type'] == 'uri' ) {
					$relatedClasses[$object['value']] = self::makeClass( array(
						'id' => DisplayHelper::getShortTerm( $object['value'] ),
						'iri' => $object['value'],
						'label' => null,
						'type' => null,
						'hasChild' => null,
					) );
				} else if ( array_key_exists( 'restrictionValue', $object ) ) {
					$recursiveClasses = $this->parseRecursiveRelated( $object );
					$recursiveClasses = array_unique( $recursiveClasses );
					foreach ( $recursiveClasses as $class ) {
						$relatedClasses[$class] = self::makeClass( array(
								'id' => DisplayHelper::getShortTerm( $class ),
								'iri' => $class,
								'label' => null,
								'type' => null,
								'hasChild' => null,
						) );
					}
				}
			}
		}
		
		$types = $this->rdf->getType( $this->graph, array_keys( $relatedClasses ) );
		foreach ( $relatedClasses as $termIRI => $termClass ) {
			if ( isset( $types[$termIRI] ) ) {
				$termClass->type = $types[$termIRI];
			}
		}
		
		$labels = $this->rdf->getLabel( $this->graph, array_keys( $relatedClasses ) );
		foreach ( $relatedClasses as $termIRI => $termClass ) {
			if ( isset( $labels[$termIRI] ) ) {
				$termClass->label = $labels[$termIRI];
			}
		}
		return $relatedClasses;
	}
	
	public function parseRecursiveRelated( $input ) {
		$result = array();
		
		$objects = $input['restrictionValue'];
		foreach ( $objects as $object ) {
			if ( !is_array( $object ) && ( strpos( $object, 'http://' ) === 0 ) ) {
				$result[] = $object;
			} else {
				if ( array_key_exists( 'restrictionValue', $object ) ) {
					$result = array_merge( $this->parseRecursiveRelated( $object ) );
				}
			}
		}
		
		return $result;
	}
	
	public function convertToIRI( $term ) {
		if ( preg_match( '/http:\/\/.+/', $term ) ) {
			$termIRI = $term;
		} else if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $term, $matches, PREG_SET_ORDER ) ) {
			if ( $matches[0][2] == '' ) {
				$termIRI = $this->prefix . $matches[0][1] . '_' . $matches[0][3];
			} else {
				$termIRI = $this->prefix . $matches[0][2] . '_' . $matches[0][3];
			}
		} else {
			$termIRI = $this->prefix . $term;
		}
		
		return $termIRI;
	}
	
	public function convertToID( $term ) {
		if ( preg_match( '/http:\/\/.+/', $term ) ) {
			$elements = preg_split( '/\//', $term );
			$termID = array_pop( $elements );
		} else if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $term, $matches, PREG_SET_ORDER ) ) {
			if ( $matches[0][2] == '' ) {
				$termID = $matches[0][1] . '_' . $matches[0][3];
			} else {
				$termID = $matches[0][2] . '_' . $matches[0][3];
			}
		} else {
			$termID = $term;
		}
		
		return $termID;
	}
	
	public function convertToTitle( $term ) {
		if ( preg_match( '/http:\/\/.+/', $term ) ) {
			$elements = preg_split( '/\//', $term );
			$termTitle = $this->ontAbbr . ':' . array_pop( $elements );
		} else if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $term, $matches, PREG_SET_ORDER ) ) {
			if ( $matches[0][2] == '' ) {
				$termTitle = $this->ontAbbr . ':' . $matches[0][1] . '_' . $matches[0][3];
			} else {
				$termTitle = $term;
			}
		} else {
			$termTitle = $term;
		}
		
		return $termTitle;
	}
	
	# If $supClass is not supplied, this function will return any path that matches the $pathType
	# Otherwise, this function will only return the path having $supClass as direct superClass of $term that matches the $pathType
	public function parseTermHierarchy( $term, $pathType, $supClass = null ) {
		/*if ( !is_object( $term ) ) {
			$term = $this->parseTermByIRI( $term );
		}*/
		
		$hierarchy = array();
		$supClasses = array();
		$sibClasses = array();
		$subClasses = array();
		$hasChild = array();
		
		if ( !preg_match( '/http:\/\/.+/', $supClass ) ) {
			$tmpClass = $this->convertToIRI( array( $supClass ) );
			$supClass = $tmpClass[0];
		}
		
		$supClassResults = $this->rdf->getTransitiveSupClass( $this->graph, $pathType, $term->iri, $supClass );
		
		if ( !empty( $supClassResults ) ) {
			foreach ( $supClassResults as $supClassResult ) {
				foreach ( $supClassResult as $supClassIRI => $supClassLabel) {
					if ( $supClassLabel != '' ) {
						$supClasses[$supClassIRI] = $supClassLabel;
					} else {
						$supClasses[$supClassIRI] = DisplayHelper::getShortTerm( $supClassIRI );
					}
				}
				
				if ( !empty( $supClasses ) ) {
					$tmpClasses = $supClasses;
					end( $tmpClasses );
					$supClass = key( $tmpClasses );
					$sibClassResult = $this->rdf->getSubClass( $this->graph , $supClass );
					unset( $sibClassResult[$term->iri] );
					foreach ( $sibClassResult as $sibClassIRI => $sibClassObject ) {
						$sibClasses[$sibClassIRI] = $sibClassObject->label;
						if ( $sibClassObject->hasChild ) {
							$hasChild[$sibClassIRI] = true;
						} else {
							$hasChild[$sibClassIRI] = false;
						}
					}
				}
			
				$subClassResult = $this->rdf->getSubClass( $this->graph, $term->iri );
				foreach ( $subClassResult as $subClassIRI => $subClassObject ) {
					$subClasses[$subClassIRI] = $subClassObject->label;
					if ( $subClassObject->hasChild ) {
						$hasChild[$subClassIRI] = true;
					} else {
						$hasChild[$subClassIRI] = false;
					}
				}
			
				$hierarchy[] = array(
					'path' => $supClasses,
					'supClass' => $supClass,
					'sibClasses' => $sibClasses,
					'subClasses' => $subClasses,
					'hasChild' => $hasChild,
				);
			}
		} else {
			$subClassResult = $this->rdf->getSubClass( $this->graph, $term->iri );
			foreach ( $subClassResult as $subClassIRI => $subClassObject ) {
				$subClasses[$subClassIRI] = $subClassObject->label;
				if ( $subClassObject->hasChild ) {
					$hasChild[$subClassIRI] = true;
				} else {
					$hasChild[$subClassIRI] = false;
				}
			}
				
			$hierarchy[] = array(
					'path' => null,
					'supClass' => null,
					'sibClasses' => null,
					'subClasses' => $subClasses,
					'hasChild' => $hasChild,
			);
		}
		
		return $hierarchy;
	}
}

?>