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
 * @file RDFStore.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */
namespace OKW\Store\RDFStore;

use OKW\CurlRequest;
use OKW\Store\RDFStore\RDFQueryHelper;

abstract class RDFStore {
	protected $endpoint;
	
	protected $prefixNS, $properties;
	
	public function __construct( $endpoint ) {
		$this->endpoint =  $endpoint;
		try {
			$this->prefixNS = $GLOBALS['okwRDFConfig']['prefixNS'];
		} catch ( Exception $e ) {
#TODO: Throw exception
		}
		try {
			$this->properties = $GLOBALS['okwAutocomplete']['property'];
		} catch ( Exception $e ) {
#TODO: Throw exception				
		}
	}
	
	public function ping() {
		$query = 'ASK WHERE{ ?s ?p ?o .}';
		
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$json = json_decode( $json, true );
		if ( $json ) {
			return true;
		}
		
		$query = 'SELECT ?s WHERE{ ?s ?p ?o . } LIMIT 1';
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		if ( !empty($result) ) {
			return true;
		}
		
		return false;
	}
	
	public function query( $query, $showQuery = false, $defaultGraph = '', $format = 'application/sparql-results+json', $debug = 'on') {
		$fields = array();
		$fields['default-graph-uri'] = $defaultGraph;
		$fields['format'] = $format;
		$fields['debug'] = $debug;
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields, $showQuery );
		return $json;
	}
	
	public function searchSubjectIRIPattern( $graph, $pattern ) {
		$query =
<<<END
SELECT DISTINCT ?subject FROM <$graph> WHERE {
	?subject ?property ?object .
	FILTER regex( ?subject, "$pattern" )
}
END;

		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$subject = RDFQueryHelper::parseEntity( $result, 'subject' );
		return $subject;
	}
	
	public function searchSubject( $graph, $keywords ) {
		$propertiesQuery = '<' . join( '>,<', $this->properties ) . '>';
		
		if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $keywords, $matches, PREG_SET_ORDER ) ) {
			if ( $matches[0][2] == '' ) {
				$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][1] . '_' . $matches[0][3];
			} else {
				$searchTermURL='http://purl.obolibrary.org/obo/' . $matches[0][2] . '_' . $matches[0][3];
			}
			
			
			$query =
<<<END
SELECT * FROM <$graph> WHERE{
	?s ?p ?o .
	FILTER ( ?p in ( $propertiesQuery ) ) .
	FILTER ( ?s in ( <$searchTermURL> ) ) .
}
LIMIT 50
END;
		} else {
			$query =
<<<END
SELECT * FROM <$graph> WHERE{
	?s ?p ?o .
	FILTER ( ?p in ( $propertiesQuery ) ) .
	FILTER ( isIRI( ?s ) ) .
	FILTER ( REGEX( STR( ?o ), "$keywords", "i" ) ) .
}
LIMIT 5000
END;
		}
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$match = RDFQueryHelper::parseSearchResult( $keywords, $graph, $result );
		return $match;
	}
	
	public function existClass( $graph, $term ) {
		$rdf = $this->prefixNS['rdf'];
		$owl = $this->prefixNS['owl'];
		
		$query =
<<<END
PREFIX rdf: <$rdf>
PREFIX owl: <$owl>
SELECT DISTINCT ?class FROM <$graph> WHERE {
	?class rdf:type owl:Class .
	FILTER ( ?class = <$term> )
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$class = RDFQueryHelper::parseEntity( $result, 'class' );
		if ( sizeof( $class ) >= 1 ) {
			return true;
		}
	}
	
	public function getAllClass( $graph ) {
		$rdf = $this->prefixNS['rdf'];
		$owl = $this->prefixNS['owl'];
		
		$query =
<<<END
PREFIX rdf: <$rdf>
PREFIX owl: <$owl>
SELECT DISTINCT ?class FROM <$graph> WHERE {
	?class rdf:type owl:Class .
	FILTER ( isURI( ?class ) )
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$classes = RDFQueryHelper::parseEntity( $result, 'class' );
		return $classes;
	}
	
	public function countAllClass( $graph ) {
		$rdf = $this->prefixNS['rdf'];
		$rdfs = $this->prefixNS['rdfs'];
		$owl = $this->prefixNS['owl'];
	
		$query =
<<<END
PREFIX rdf: <$rdf>
PREFIX rdfs: <$rdfs>
PREFIX owl: <$owl>
SELECT COUNT( ?class ) FROM <$graph> WHERE {
	{
		?class rdf:type owl:Class .
		FILTER ( isURI( ?class ) ).
	}
}
END;
	
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$count = RDFQueryHelper::parseCountResult( $json );
		return $count;
	}
	
	public function getSubClass( $graph, $term ) {
		$rdf = $this->prefixNS['rdf'];
		$rdfs = $this->prefixNS['rdfs'];
		$owl = $this->prefixNS['owl'];
		
		/*
		$query =
<<<END
PREFIX rdf: <$rdf>
PREFIX rdfs: <$rdfs>
PREFIX owl: <$owl>
SELECT DISTINCT ?class ?label ?subClass FROM <$graph> WHERE { 
	{
		?class rdfs:subClassOf <$term> .
		FILTER (isIRI(?class)).
		OPTIONAL {?class rdfs:label ?label} .
		OPTIONAL {?subClass rdfs:subClassOf ?class}
	} UNION {
		?class owl:equivalentClass ?s1 .
		FILTER (isIRI(?class)).
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first <$term> .
		OPTIONAL {?class rdfs:label ?label} .
		OPTIONAL {?subClass rdfs:subClassOf ?class}
	} UNION {
		?class rdfs:subClassOf <$term> .
		FILTER (isIRI(?class)).
		OPTIONAL {?class rdfs:label ?label} .
		OPTIONAL {?subClass owl:equivalentClass ?s1 .
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first ?s}
	} UNION {
		?class owl:equivalentClass ?s1 .
		FILTER (isIRI(?class)).
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first <$term> .
		OPTIONAL {?class rdfs:label ?label} .
		OPTIONAL {?subClass owl:equivalentClass ?s3 .
		?s3 owl:intersectionOf ?s4 .
		?s4 rdf:first ?class}
	}
}
END;
		*/
		
		$query =
<<<END
PREFIX rdf: <$rdf>
PREFIX rdfs: <$rdfs>
PREFIX owl: <$owl>
SELECT DISTINCT ?class ?label ?subClass FROM <$graph> WHERE {
	{
		?class rdfs:subClassOf <$term> .
		FILTER (isIRI(?class)).
		OPTIONAL {?class rdfs:label ?label} .
		OPTIONAL {?subClass rdfs:subClassOf ?class}
	}
}
END;
		
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$subClasses = RDFQueryHelper::parseClassResult( $result );
		return $subClasses;
	}
	
	public function getSupClass( $graph, $term ) {
		$rdf = $this->prefixNS['rdf'];
		$rdfs = $this->prefixNS['rdfs'];
		$owl = $this->prefixNS['owl'];
		
		/*
		$query =
<<<END
PREFIX rdf: <$rdf>
PREFIX rdfs: <$rdfs>
PREFIX owl: <$owl>
SELECT ?class ?label FROM <$graph> WHERE {
	{
		<$term> rdfs:subClassOf ?class .
		FILTER (isURI(?class)).
		OPTIONAL {?class rdfs:label ?label}
	} UNION {
		<$term> owl:equivalentClass ?s1 .
		?s1 owl:intersectionOf ?s2 .
		?s2 rdf:first ?class  .
		FILTER (isURI(?class))
		OPTIONAL {?class rdfs:label ?label}
	}
}
END;
		*/
		
		$query =
<<<END
PREFIX rdf: <$rdf>
PREFIX rdfs: <$rdfs>
PREFIX owl: <$owl>
SELECT ?class ?label FROM <$graph> WHERE {
	{
		<$term> rdfs:subClassOf ?class .
		FILTER (isURI(?class)).
		OPTIONAL {?class rdfs:label ?label}
	}
}
END;
		
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$supClasses = RDFQueryHelper::parseClassResult( $result );
		return $supClasses;
	}
	
	public function getBlankNode( $graph, $subject, $property ) {
		$rdf = $this->prefixNS['rdf'];
		$rdfs = $this->prefixNS['rdfs'];
		$owl = $this->prefixNS['owl'];
		
		$query =
<<<END
SELECT ?node FROM <$graph> WHERE {
    {
        <$subject> <$property> ?node .
        FILTER ( isBLANK( ?node ) )
    }
}
END;
	
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$nodes = RDFQueryHelper::parseEntity( $result, 'node' );
		return $nodes;
	}
	
	public function getType( $graph, $terms ) {
		$rdf = $this->prefixNS['rdf'];
		
		$termsQuery = "<http://null>, <" . join( '>, <' , $terms ) . ">";
		$query =
<<<END
PREFIX rdf: <$rdf>
SELECT * FROM <$graph> WHERE {
	?s rdf:type ?o.
	FILTER (?s in ( $termsQuery ) )
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$types = RDFQueryHelper::parseTypeResult( $result );
		return $types;
	}
	
	public function getLabel( $graph, $terms ) {
		$query = "
		SELECT * FROM <{$graph}> WHERE {
		?s <http://www.w3.org/2000/01/rdf-schema#label> ?o.
		FILTER (?s in(<http://null>, <" . join( '>, <' , $terms ) . ">))
			}
		";
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$labels = RDFQueryHelper::parseLabelResult( $result );
		return $labels;
	}
	
	public function getObject( $graph, $subject, $property ) {
		$query =
<<<END
SELECT ?object FROM <$graph> WHERE {
	<$subject> <$property> ?object
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$object = RDFQueryHelper::parseEntity( $result, 'object' );
		return $object;
	}
	
	public function getSubject( $graph, $property, $object ) {
		$query =
		<<<END
SELECT ?subject FROM <$graph> WHERE {
	?subject <$property> <$object>
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$subject = RDFQueryHelper::parseEntity( $result, 'subject' );
		return $subject;
		}
	
	public function deleteObject( $graph, $subject, $property, $object = null, $type = 'iri') {
		if ( is_null( $object ) ) {
			$query =
<<<END
DELETE FROM GRAPH <$graph> { ?subject ?property ?object } WHERE {
	?subject ?property ?object .
	FILTER ( ?subject = <$subject> ) .
	FILTER ( ?property = <$property> )
}
END;
		} else {
			if ( empty( $object ) ) {
				return false;
			}
			
			$objectQuery = '';
			foreach ( $object as $o ) {
				if ( $type == 'iri' ) {
					$objectQuery .= "
						<{$subject}> <{$property}> " . "<{$o}>" . ' .';
				} else if ( $type == 'literal' ) {
					$objectQuery .= "
						<{$subject}> <{$property}> " . '"' . "$o" . '" .';
				} else {
					#TODO: Throw Exception
					return false;
				}
			}
			$objectQuery = trim( $objectQuery, '.' );
			$query =
<<<END
DELETE FROM <$graph> {
$objectQuery
}
END;
		}
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		return true;
	}
	
	public function deleteSubject( $graph, $subject ) {
		$query =
<<<END
DELETE FROM GRAPH <$graph> { ?subject ?property ?object } WHERE {
	?subject ?property ?object .
	FILTER ( ?subject = <$subject> ) .
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		file_put_contents('query', $query);
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		return true;
	}
	
	public function insertObject( $graph, $subject, $property, $object, $type = 'iri' ) {
		if ( empty( $object ) ) {
			return false;
		} else {
			
			$objectQuery = '';
			if ( is_array( $object ) ) {
				foreach ( $object as $o ) {
					if ( $type == 'iri' ) {
						$objectQuery .= "<$subject> <$property> <$o> .";
					} else if ( $type == 'literal' ) {
						$objectQuery .= "<$subject> <$property> \"$o\" .";
					} else {
						#TODO: Throw Exception
						return false;
					}
				}
				$objectQuery = trim( $objectQuery, '.' );
			} else {
				if ( $type == 'iri' ) {
					$objectQuery .= "<$subject> <$property> <$object>";
				} else if ( $type == 'literal' ) {
					$objectQuery .= "<$subject> <$property> \"$object\"";
				}
			}
			$query =
<<<END
INSERT IN <$graph> {
$objectQuery
}
END;
			$fields = array();
			$fields['default-graph-uri'] = '';
			$fields['format'] = 'application/sparql-results+json';
			$fields['debug'] = 'on';
			$fields['query'] = $query;
			$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
			return true;
		}
	}
	
	public function updateObject( $graph, $subject, $property, $object, $type) {
		$this->deleteObject( $graph, $subject, $property, null, $type );
		$this->insertObject( $graph, $subject, $property, $object, $type );
	}
	
	public function insertAxiom( $graph, $subject, $property, $axioms ) {
		$query =
<<<END
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>
PREFIX ro: <http://www.obofoundry.org/ro/ro.owl#>
INSERT IN <$graph> {

END;

		foreach ( $axioms as $axiom ) {
			$object = RDFQueryHelper::parseManchesterData( $axiom );
			$query .=
<<<END
	<$subject> <$property> $object .

END;
		}
		
		$query .= '}';
		
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
	}
	
	
	abstract public function getDescribe( $graph, $term );

}

?>