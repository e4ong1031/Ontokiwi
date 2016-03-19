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
 * @file RDFQueryHelper.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Store\RDFStore;

use OKW\Ontology\OntologyData;
use OKW\Display\DisplayHelper;

class RDFQueryHelper {
	public static function parseSPARQLResult( $json ) {
		$json = json_decode( $json, true );
		$results = array();
		if ( isset( $json['results']['bindings'] ) ) {
			foreach ( $json['results']['bindings'] as $binding ) {
				$result = array();
				foreach ( $binding as $key => $value ) {
					$result[$key] = $value['value'];
				}
				$results[] = $result;
			}
		}
		return $results;
	}
	
	public static function parseCountResult( $json ) {
		$json = json_decode( $json, true );
		$var = $json['head']['vars'][0];
		return $json['results']['bindings'][0][$var]['value'];
	}
	
	public static function parseSearchResult( $keywords, $graph, $searchResult ) {
		$match = array();
		$terms = array();
		$count = 0;
		
		# IRI Match
		$results1 = array();
		foreach ( $searchResult as $result ) {
			if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $keywords, $matches, PREG_SET_ORDER ) ) {
				if ( $matches[0][2] == '' ) {
					$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][1] . '_' . $matches[0][3];
				} else {
					$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][2] . '_' . $matches[0][3];
				}
				$term = array_pop(preg_split( '/[\/#]/', $result['s'] ) );
				
				if ( $searchTermURL == $result['s'] ) {
					$count++;
					$match[] = array(
							'id' => DisplayHelper::encodeURL( $term ),
							'iri' => $result['s'],
							'label' => $result['o'],
					);
					$terms[$result['o']] = 1;
				}
			}
		}
		
		# Exact Match
		$results2 = array();
		foreach ( $searchResult as $result) {
			if ( strtolower( $result['o'] ) == strtolower( $keywords ) ) {
				$term = array_pop(preg_split( '/[\/#]/', $result['s'] ) );
	
				if ( !isset( $terms[$result['o']] ) ) {
					$count++;
					$match[] = array(
						'id' => DisplayHelper::encodeURL( $term ),
						'iri' => $result['s'],
						'label' => $result['o'],
					);
					$terms[$result['o']] = 1;
				}
			}
			else {
				$results2[] = $result;
			}
		}
		
		# Partial Match
		$results3=array();
		foreach ( $searchResult as $result ) {
			if ( $count>100 ) {
				break;
			}
			if ( strpos( strtolower( $result['o'] ), strtolower( $keywords ) ) === 0 ) {
				$tokens = preg_split( '/[\/#]/', $result['s'] );
				$term = array_pop( $tokens );
	
				if ( !isset( $terms[$result['o']] ) ) {
					$count++;
					$match[] = array(
						'id' => DisplayHelper::encodeURL( $term ),
						'iri' => $result['s'],
						'label' => $result['o'],
					);
					$terms[$result['o']] = 1;
				}
			}
			else {
				$results3[]=$result;
			}
		}
		
		# Remaining Match (Regular Expression Match return by SPARQL)
		foreach ( $searchResult as $result ) {
			if ( $count>100 ) {
				break;
			}
			$tokens = preg_split( '/[\/#]/', $result['s'] );
			$term = array_pop( $tokens );
	
			if ( !isset( $terms[$result['o']] ) ) {
				$count++;
				$match[] = array(
					'id' => DisplayHelper::encodeURL( $term ),
					'iri' => $result['s'],
					'label' => $result['o'],
				);
				$terms[$result['o']] = 1;
			}
		}
		
		return $match;
	}
	
	public static function parseEntity( $entityResult, $label = 'entity', $var = array() ) {
		$entity = array();
		foreach ( $entityResult as $result ) {
			if ( !empty( $var ) ) {
				foreach( $var as $name ) {
					if ( array_key_exists( $name, $result ) ) {
						$entity[$result[$label]][$name] = $result[$name];
					} else {
						$entity[$result[$label]][$name] = null;
					}
				}
			} else {
				$entity[] = $result[$label];
			}
		}
		return $entity;
	}
	
	public static function parseClassResult( $classResult ) {
		$classes = array();
		foreach ( $classResult as $result ) {
			if ( isset( $classes[$result['class']] ) ) {
				if ( isset( $result['subClass'] ) ) {
					$classes[$result['class']]->hasChild = true;
				}
			} else {
				$class = OntologyData::makeClass( array(
					'iri' => $result['class'],
					'label' => null,
					'type' => null,
					'hasChild' => null,
				) );
				$class->label = '';
				if (isset( $result['label'] ) ) {
					$class->label = $result['label'];
				}
				$class->hasChild = false;
				if ( isset( $result['subClass'] ) ) {
					$class->hasChild = true;
				}
				$classes[$result['class']] = $class;
			}
			
		}
		asort( $classes );
		return $classes;
	}
	
	public static function parseTypeResult( $typeResult ) {
		$types = array();
		foreach ( $typeResult as $result ) {
			$types[$result['s']] = $result['o'];
		}
		return $types;
	}
	
	public static function parseLabelResult( $labelResult ) {
		$labels = array();
		foreach ( $labelResult as $result ) {
			$labels[$result['s']] = $result['o'];
		}
		return $labels;
	}
	
	public static function parseTransitivePath( $transitiveResult, $type, $supClassIRI ) {
		$tmpPath = array();
		foreach ( $transitiveResult as $result ) {
			$tmpPath[$result['path']][] = $result;
		}
		if ( !empty( $tmpPath ) ) {
			$pathQuery = array();
			$pathSize = array();
			
			foreach( $tmpPath as $index => $pathArray ) {
				if ( count( $pathArray ) == 1 ) {
					continue;
				}
				
				# Remove the first element in the path, which is always the term being queried
				array_shift( $pathArray );
				
				if ( $pathArray[0][link] == $supClassIRI ) {
					$pathQuery[$index] = self::extractTransitivePathLabel( $pathArray );
					$pathSize[$index] = count( $pathArray ) ;
				}	
			}
			
			arsort( $pathSize );
			
			$path = array();
			foreach( $pathSize as $id => $size ) {
				$pathTest = $pathQuery[$id];
				if ( empty( $path ) ) {
					$path[] = $pathTest;
					continue;
				}
				$duplicate = true;
				foreach( $path as $pathCheck ) {
					$pathDiff = array_diff( $pathTest, $pathCheck );
					if ( sizeof( $pathDiff ) > 0 ) {
						$duplicate = false;
					}
				}
				if ( !$duplicate ) {
					$path[] = $pathTest;
				}
			}
			
			if ( $type == 'all' ) {
				return $path;
			} else if ( $type == 'max' ) {
				$maxSize = 0;
				foreach( $path as $pathArray ) {
					if ( count( $pathArray ) > $maxSize ) {
						$maxSize = count( $pathArray );
						$maxPath = $pathArray;
					}
				}
				
				return array( $maxPath );
			} else if ( $type == 'min' ) {
				$minSize = INF;
				foreach( $path as $pathArray ) {
					if ( count( $pathArray ) < $minSize ) {
						$minSize = count( $pathArray );
						$minPath = $pathArray;
					}
				}
				
				return array( $minPath );
			} else {
				#TODO: Throw exception
			}	
		}
	}
	
	private static function extractTransitivePathLabel( $transitiveResult ) {
		$path = array();
		foreach ( $transitiveResult as $result ) {
			$path[$result['link']] = '';
			if ( isset( $result['label'] ) ) {
				$path[$result['link']] = $result['label'];
			}
		}
		return array_reverse( $path, true );
	}
	
	public static function parseRDF ( $json, $term ) {
		$json = json_decode( $json, true );
		/*
		if ( isset( $json[$term] ) ) {
			return $json;
		} else {
			return array();
		}*/
		if ( array_key_exists( $term, $json ) ) {
			$results = $json[$term];
			foreach ( $results as $propertyIRI => $properties ) {
				foreach ( $properties as $index => $property ) {
					if ( $property['type'] == 'bnode' ) {
						$results[$propertyIRI][$index] = self::parseRecursiveRDFNode( $json, $property['value'] );
					}
				}
			}
			return $results;
		} else {
			return array();
		}
	}

	public static function parseRecursiveRDFNode( $rdfResult, $nodeIRI ) {
		$objEquivalent = array();
		
		$operations = $GLOBALS['okwRDFConfig']['restriction']['operation'];
		$types = $GLOBALS['okwRDFConfig']['restriction']['type'];
		$lists = $GLOBALS['okwRDFConfig']['restriction']['list'];
		
		$onPropertyIRI = $GLOBALS['okwRDFConfig']['restriction']['onProperty'];
		$nilIRI = $GLOBALS['okwRDFConfig']['restriction']['nil'];
		
		if ( isset($rdfResult[$nodeIRI] ) ) {
			$curResult = $rdfResult[$nodeIRI];
			
			if ( isset( $curResult[$onPropertyIRI] ) ) {
				$objEquivalent['restrictionValue'][] = $curResult[$onPropertyIRI][0]['value'];
			}
			
			foreach ( $types as $type => $typeIRI ) {
				if ( isset( $curResult[$typeIRI] ) ) {
					$objEquivalent['restrictionType'] = $type;
					
					if ( $curResult[$typeIRI][0]['type'] == 'uri' ) {
						$objEquivalent['restrictionValue'][] = $curResult[$typeIRI][0]['value'];
					} else {
						$objEquivalent['restrictionValue'][] = self::parseRecursiveRDFNode( $rdfResult, $curResult[$typeIRI][0]['value'] );
					}
				}
			}
			
			foreach ( $operations as $operation => $operationIRI ) {
				if ( isset( $curResult[$operationIRI] ) ) {
					$objEquivalent['restrictionType'] = $operation;
					$objEquivalent['restrictionValue'] = array();
					
					$curNodeID = $curResult[$operationIRI][0]['value'];
					$curNode = $rdfResult[$curNodeID];
					
					while ( $curNode[$lists['rest']][0]['value'] != $nilIRI ) {
						if ( $curNode[$lists['first']][0]['type'] == 'uri' ) {
							$objEquivalent['restrictionValue'][] = $curNode[$lists['first']][0]['value'];
						} else {
							$objEquivalent['restrictionValue'][] = self::parseRecursiveRDFNode( $rdfResult, $curNode[$lists['first']][0]['value'] );
						}
					
						$curNodeID = $curNode[$lists['rest']][0]['value'];
						if ( $curNodeID = "http://www.w3.org/2002/07/owl#Nothing" ) {
							break;
						}
						$curNode = $rdfResult[$curNodeID];
					}
					
					if ( $curNode[$lists['first']][0]['type'] == 'uri' ) {
						$objEquivalent['restrictionValue'][] = $curNode[$lists['first']][0]['value'];
					} else {
						$objEquivalent['restrictionValue'][] = self::parseRecursiveRDFNode( $rdfResult, $curNode[$lists['first']][0]['value'] );
					}
				}
			}
		}
			
		return($objEquivalent);
	}
	
	public static function parseManchesterData( $data ) {
		$query = '';
		
		$operations = $GLOBALS['okwRDFConfig']['restriction']['operation'];
		$types = $GLOBALS['okwRDFConfig']['restriction']['type'];
		
		$type = $data['restrictionType'];
		$value = $data['restrictionValue'];
		$property = $value[0];
		
		if ( in_array( $type, array_values( $types ) ) ) {
			$query .=
<<<END
[ rdf:type owl:Restriction ; owl:onProperty <$property> ; <$type> 
END;
			if ( !is_array( $value[1] ) ) {
				$query .= '<' . $value[1] . '> ]';
			} else {
				$query .= self::parseManchesterData( $value[1] ) . ' ] ';
			}
		} else if ( in_array( $type, array_values( $operations ) ) ) {
			$query .=
<<<END
[ rdf:type owl:Class ; <$type> 
END;
			$start = '';
			$end = '';
			foreach ( $value as $index => $node ) {
				$start .= ' [ rdf:first ';
				$end .= ' ]';
				if ( $index == ( sizeof( $value ) - 1 ) ) {
					if ( !is_array( $node ) ) {
						$start .= '<' . $node . '> ; ';
					} else {
						$start .= self::parseManchesterData( $node ) . ' ; ';
					}
				
					$start .= ' rdf:rest rdf:nil ';
				} else {
					if ( !is_array( $node ) ) {
						$start .= '<' . $node . '> ; ';
					} else {
						$start .= self::parseManchesterData( $node ) . ' ; ';
					}
					
					$start .= 'rdf:rest ';
					
				}
			}
			
			$query .=  $start . $end . ' ]';
		}
		
		return $query;
	}
}

?>