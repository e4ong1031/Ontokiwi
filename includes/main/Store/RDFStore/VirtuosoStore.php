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
 * @file VirtuosoStore.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Store\RDFStore;

use OKW\Store\RDFStore\RDFStore;
use OKW\CurlRequest;
use OKW\Store\RDFStore\RDFQueryHelper;

class VirtuosoStore extends RDFStore {
	
	public function getDescribe( $graph, $term ) {
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE <$term>
FROM <$graph>
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/rdf+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		# Potential virtuoso problem reported in ontobee
		#$json = preg_replace( '/\'\);\ndocument.writeln\(\'/', '', $json );
		$result = RDFQueryHelper::parseRDF( $json, $term );
		return $result;
	}
	
	public function getAxiom( $graph, $term ) {
		$rdf = $this->prefixNS['rdf'];
		$rdfs = $this->prefixNS['rdfs'];
		$owl = $this->prefixNS['owl'];
		
		$subclassof = $this->getBlankNode( $graph, $term, $rdfs . 'subClassOf' );
		$equivalent = $this->getBlankNode( $graph, $term, $owl . 'equivalentClass' );
		
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
PREFIX rdf: <$rdf>
PREFIX rdfs: <$rdfs>
PREFIX owl: <$owl>
DESCRIBE ?axiom FROM <$graph> WHERE {
    {
        <$term> rdfs:subClassOf ?axiom .
        FILTER ( isBLANK( ?axiom ) )
    } UNION {
        <$term> owl:equivalentClass ?axiom .
        FILTER ( isBLANK( ?axiom ) )
    }
}
END;
		
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/rdf+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = json_decode( $json ,true );
		
		$axioms = array( 'subclassof' => array(), 'equivalent' => array() );
		foreach ( $subclassof as $node ) {
			$node = str_replace( 'nodeID://', '_:v', $node );
			$axioms['subclassof'][] = RDFQueryHelper::parseRecursiveRDFNode( $result, $node );
		}
		foreach ( $equivalent as $node ) {
			$node = str_replace( 'nodeID://', '_:v', $node );
			$axioms['equivalent'][] = RDFQueryHelper::parseRecursiveRDFNode( $result, $node );
		}
		
		return $axioms;
	}
	
	public function exportDescribe( $graph, $terms ) {
		$termsQuery = "<" . join( '>,<', $terms ) . ">";
		
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE ?class FROM <$graph> WHERE {
	?class ?property ?object .
	FILTER ( ?class in ( $termsQuery ) )
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/rdf+xml';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$rdf = CurlRequest::curlPostContents( $this->endpoint, $fields );
		return $rdf;
	}
	
	public function exportOntology( $graph ) {
		$query =
<<<END
DEFINE sql:describe-mode "CBD"
DESCRIBE ?class FROM <$graph> WHERE {
	?class ?property ?object
}
END;
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/rdf+xml';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$rdf = CurlRequest::curlPostContents( $this->endpoint, $fields );
		return $rdf;
	}
	
	public function getTransitiveSupClass( $graph, $pathType, $term, $supClass ) {
		$query =
<<<END
PREFIX rdf: <{$this->prefixNS['rdf']}>
PREFIX rdfs: <{$this->prefixNS['rdfs']}>
PREFIX owl: <{$this->prefixNS['owl']}>
SELECT ?path ?link ?label FROM <{$graph}> WHERE {
	{
		SELECT ?s ?o ?label WHERE {
			{
				?s rdfs:subClassOf ?o .
				FILTER (isURI(?o)).
				OPTIONAL {?o rdfs:label ?label}
			} UNION {
				?s owl:equivalentClass ?s1 .
				?s1 owl:intersectionOf ?s2 .
				?s2 rdf:first ?o  .
				FILTER (isURI(?o))
				OPTIONAL {?o rdfs:label ?label}
			}
		}
	}
	OPTION (TRANSITIVE, t_in(?s), t_out(?o), t_step (?s) as ?link, t_step ('path_id') as ?path).
	FILTER (isIRI(?o)).
	FILTER (?s= <$term>)
}
END;
		
		$fields = array();
		$fields['default-graph-uri'] = '';
		$fields['format'] = 'application/sparql-results+json';
		$fields['debug'] = 'on';
		$fields['query'] = $query;
		$json = CurlRequest::curlPostContents( $this->endpoint, $fields );
		$result = RDFQueryHelper::parseSPARQLResult( $json );
		$transitivePath = RDFQueryHelper::parseTransitivePath( $result, $pathType, $supClass );
		return $transitivePath;
	}
	
	
}

?>