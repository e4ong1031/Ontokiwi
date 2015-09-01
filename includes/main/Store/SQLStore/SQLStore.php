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
 * @file SQLStore.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Store\SQLStore;

use MWException;

use OKW\Store\RDFStore\RDFStoreFactory;
use OKW\Store\RDFStore\RDFStore;
use OKW\Store\SQLStore\SQLQueryHelper;

class SQLStore {
	private $db;
	
	public function __construct( $db ) {
		$this->db = $db;
	}
	
	public function setup() {
		if ( !$this->db->tableExists( 'okw_ontology' ) ) {
			$this->setupOntologyTable();
			wfDebugLog( 'OntoKiWi', 'Insert done: okw_ontology' );
		}
		if ( !$this->db->tableExists( 'okw_annotation_property' ) && !$this->db->tableExists( 'okw_annotation_link' ) ) {
			$this->setupAnnotationTable();
			wfDebugLog( 'OntoKiWi', 'Insert done: okw_annotation_property' );
			wfDebugLog( 'OntoKiWi', 'Insert done: okw_annotation_link' );
		}
		if ( !$this->db->tableExists( 'okw_object_property' ) && !$this->db->tableExists( 'okw_object_link' ) ) {
			$this->setupObjectTable();
			wfDebugLog( 'OntoKiWi', 'Insert done: okw_object_property' );
			wfDebugLog( 'OntoKiWi', 'Insert done: okw_object_link' );
		}
	}
	
	public function isSetUp() {
		if ( 
			$this->db->tableExists( 'okw_ontology' ) && 
			$this->db->tableExists( 'okw_annotation_property' &&
			$this->db->tableExists( 'okw_object_property' )
		) ) {
			return true;
		} else {
			return false;
		}
	}
	
	private function setupOntologyTable() {
		$helper = new SQLQueryHelper( $this->db );
		
		$sql = $helper->writeCreateTable(
			'okw_ontology',
			array(
				'id' => 'varchar(45) NOT NULL PRIMARY KEY UNIQUE KEY',
				'ontology_abbrv' => 'varchar(45) NOT NULL UNIQUE KEY',
				'ontology_url' => 'varchar(128) NOT NULL UNIQUE KEY',
				'ontology_fullname' => 'varchar(256) NOT NULL',
				'end_point' => 'varchar(128) NOT NULL',
				'ontology_graph_url' => 'varchar(128) NOT NULL UNIQUE KEY',
				'term_url_prefix' => 'varchar(128) NOT NULL',
				'ontology_creation_digit' => 'integer NOT NULL',
				'source' => 'varchar(256) DEFAULT NULL',
				'loaded' => "varchar(1) NOT NULL DEFAULT 'n'" ,
				)
			);
		$this->db->query( $sql, __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_abbrv', 'okw_ontology', 'ontology_abbrv' ), __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_fullname', 'okw_ontology', 'ontology_fullname' ), __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_url', 'okw_ontology', 'ontology_url' ), __METHOD__ );
	}
	
	private function setupAnnotationTable() {
		$helper = new SQLQueryHelper( $this->db );
		
		$sql = $helper->writeCreateTable(
				'okw_annotation_property',
				array(
						'id' => 'varchar(45) NOT NULL PRIMARY KEY UNIQUE KEY',
						'magicword' => 'varchar(45) NOT NULL UNIQUE KEY',
						'iri' => 'varchar(128) NOT NULL UNIQUE KEY',
						'type' => "varchar(10) NOT NULL DEFAULT 'list'",
						'global' => "varchar(1) NOT NULL DEFAULT 'n'" ,
						'ontology' => 'varchar(45) DEFAULT NULL',
						'source' => 'varchar(256) DEFAULT NULL',
				)
		);
		$this->db->query( $sql, __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_magicword', 'okw_annotation_property', 'magicword' ), __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_iri', 'okw_annotation_property', 'iri' ), __METHOD__ );
		
		$sql = $helper->writeCreateTable(
				'okw_annotation_link',
				array(
						'id' => 'INT NOT NULL PRIMARY KEY AUTO_INCREMENT',
						'annotation_id' => 'varchar(45) NOT NULL ',
						'ontology_id' => 'varchar(45) NOT NULL',
				)
		);
		$this->db->query( $sql, __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_annotation', 'okw_annotation_link', 'annotation_id' ), __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_ontology', 'okw_annotation_link', 'ontology_id' ), __METHOD__ );
		
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000589',
			'magicword' => 'OBO_foundry_unique_label',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000589',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'unique',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000118',
			'magicword' => 'alternative_term',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000118',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000427',
			'magicword' => 'antisymmetric_property',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000427',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000232',
			'magicword' => 'curator_note',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000232',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000115',
			'magicword' => 'definition',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000115',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000119',
			'magicword' => 'definition_source',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000119',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000116',
			'magicword' => 'editor_note',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000116',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000111',
			'magicword' => 'editor_preferred_term',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000111',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000600',
			'magicword' => 'elucidation',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000600',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000112',
			'magicword' => 'example_of_usage',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000112',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000425',
			'magicword' => 'expand_assertion_to',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000425',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000424',
			'magicword' => 'expand_expression_to',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000424',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000426',
			'magicword' => 'first_order_logic_expression',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000426',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000596',
			'magicword' => 'has_ID_digit_count',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000596',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000598',
			'magicword' => 'has_ID_policy_for',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000598',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000599',
			'magicword' => 'has_ID_prefix',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000599',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000597',
			'magicword' => 'has_ID_range_allocated_to',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000597',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000602',
			'magicword' => 'has_associated_axiom(fol)',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000602',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000601',
			'magicword' => 'has_associated_axiom(nl)',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000601',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0010000',
			'magicword' => 'has_axiom_label',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0010000',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000114',
			'magicword' => 'has_curation_status',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000114',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000231',
			'magicword' => 'has_obsolescence_reason',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000231',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000412',
			'magicword' => 'imported_from',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000412',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000113',
			'magicword' => 'in_branch',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000113',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000603',
			'magicword' => 'is_allocated_id_range',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000603',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000411',
			'magicword' => 'is_denotator_type',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000411',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0000117',
			'magicword' => 'term_editor',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0000117',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_annotation_property', array(
			'id' => 'IAO_0100001',
			'magicword' => 'term_replaced_by',
			'iri' => 'http://purl.obolibrary.org/obo/IAO_0100001',
			'ontology' => 'IAO-Onto-Meta',
			'type' => 'list',
			'source' => 'http://purl.obolibrary.org/obo/iao/ontology-metadata.owl',
			'global' => 'y',
		) );
	}
	
	private function setupObjectTable() {
		$helper = new SQLQueryHelper( $this->db );
	
		$sql = $helper->writeCreateTable(
				'okw_object_property',
				array(
					'id' => 'varchar(45) NOT NULL PRIMARY KEY UNIQUE KEY',
					'magicword' => 'varchar(45) NOT NULL UNIQUE KEY',
					'iri' => 'varchar(128) NOT NULL UNIQUE KEY',
					'global' => "varchar(1) NOT NULL DEFAULT 'n'" ,
					'ontology' => 'varchar(45) DEFAULT NULL',
					'source' => 'varchar(256) DEFAULT NULL',
				)
		);
		$this->db->query($sql, __METHOD__ );
		$this->db->query($helper->writeCreateIndex( 'index_magicword', 'okw_object_property', 'magicword' ), __METHOD__ );
		$this->db->query($helper->writeCreateIndex( 'index_iri', 'okw_object_property', 'iri' ), __METHOD__ );
		
		$sql = $helper->writeCreateTable(
				'okw_object_link',
				array(
						'id' => 'INT NOT NULL PRIMARY KEY AUTO_INCREMENT',
						'object_id' => 'varchar(45) NOT NULL ',
						'ontology_id' => 'varchar(45) NOT NULL',
				)
		);
		$this->db->query( $sql, __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_object', 'okw_object_link', 'object_id' ), __METHOD__ );
		$this->db->query( $helper->writeCreateIndex( 'index_ontology', 'okw_object_link', 'ontology_id' ), __METHOD__ );
		
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000055',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000055',
			'magicword' => 'realizes',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000082',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000082',
			'magicword' => 'located_in_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000130',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000130',
			'magicword' => 'occupies_spatiotemporal_region',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000066',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000066',
			'magicword' => 'occurs_in',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000185',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000185',
			'magicword' => 'has_history',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000083',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000083',
			'magicword' => 'occupies_spatial_region_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000086',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000086',
			'magicword' => 'has_quality_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000054',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000054',
			'magicword' => 'realized_in',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000059',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000059',
			'magicword' => 'concretizes_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000067',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000067',
			'magicword' => 'contains_process',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000079',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000079',
			'magicword' => 'function_of_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000084',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000084',
			'magicword' => 'generically_depends_on_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000087',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000087',
			'magicword' => 'has_role_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000052',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000052',
			'magicword' => 'inheres_in_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000070',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000070',
			'magicword' => 'specifically_depends_on_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000058',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000058',
			'magicword' => 'concretized_by_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000053',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000053',
			'magicword' => 'bearer_of_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000056',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000056',
			'magicword' => 'participates_in_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000057',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000057',
			'magicword' => 'has_participant_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000080',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000080',
			'magicword' => 'quality_of_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000081',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000081',
			'magicword' => 'role_of_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000085',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000085',
			'magicword' => 'has_function_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000123',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000123',
			'magicword' => 'has_spatial_occupant_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000164',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000164',
			'magicword' => 'concretizes_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000107',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000107',
			'magicword' => 'disposition_of_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000157',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000157',
			'magicword' => 'during_which_exists',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000110',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000110',
			'magicword' => 'has_continuant_part_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000186',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000186',
			'magicword' => 'part_of_continuant_at_all_times_that_whole_exists',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000117',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000117',
			'magicword' => 'has_occurrent_part',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000118',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000118',
			'magicword' => 'has_proper_occurrent_part',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000124',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000124',
			'magicword' => 'has_location_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000129',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000129',
			'magicword' => 'member_part_of_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000175',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000175',
			'magicword' => 'proper_part_of_continuant_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000132',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000132',
			'magicword' => 'part_of_occurrent',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000136',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000136',
			'magicword' => 'proper_temporal_part_of',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000138',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000138',
			'magicword' => 'proper_part_of_occurrent',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000137',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000137',
			'magicword' => 'proper_part_of_continuant_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000177',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000177',
			'magicword' => 'part_of_continuant_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000153',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000153',
			'magicword' => 'projects_onto_temporal_region',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000160',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000160',
			'magicword' => 'has_function_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000161',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000161',
			'magicword' => 'has_role_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000166',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000166',
			'magicword' => 'participates_in_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000173',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000173',
			'magicword' => 'member_part_of_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000184',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000184',
			'magicword' => 'history_of',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000125',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000125',
			'magicword' => 'has_specific_dependent_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000101',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000101',
			'magicword' => 'has_generic_dependent_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000112',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000112',
			'magicword' => 'has_disposition_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000115',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000115',
			'magicword' => 'has_member_part_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000133',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000133',
			'magicword' => 'process_profile_of',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000139',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000139',
			'magicword' => 'temporal_part_of',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000154',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000154',
			'magicword' => 'temporal_projection_of_spatiotemporal',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000158',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000158',
			'magicword' => 'bearer_of_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000159',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000159',
			'magicword' => 'has_quality_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000162',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000162',
			'magicword' => 'has_disposition_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000163',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000163',
			'magicword' => 'material_basis_of_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000165',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000165',
			'magicword' => 'concretized_by_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000167',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000167',
			'magicword' => 'has_participant_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000169',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000169',
			'magicword' => 'specifically_depends_on_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000171',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000171',
			'magicword' => 'located_in_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000108',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000108',
			'magicword' => 'exists_at',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000178',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000178',
			'magicword' => 'has_continuant_part_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000111',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000111',
			'magicword' => 'has_proper_continuant_part_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000174',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000174',
			'magicword' => 'has_proper_continuant_part_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000113',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000113',
			'magicword' => 'has_material_basis_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000119',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000119',
			'magicword' => 'has_profile',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000121',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000121',
			'magicword' => 'has_temporal_part',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000126',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000126',
			'magicword' => 'has_spatiotemporal_occupant',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000127',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000127',
			'magicword' => 'material_basis_of_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000176',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000176',
			'magicword' => 'part_of_continuant_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000181',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000181',
			'magicword' => 'has_proper_temporal_part',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000151',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000151',
			'magicword' => 'projects_onto_spatial_region_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000152',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000152',
			'magicword' => 'spatial_projection_of_spatiotemporal_at_some_time',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000155',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000155',
			'magicword' => 'occupies_temporal_region',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000156',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000156',
			'magicword' => 'has_temporal_occupant',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000168',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000168',
			'magicword' => 'has_specific_dependent_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000170',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000170',
			'magicword' => 'has_location_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000172',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000172',
			'magicword' => 'has_member_part_at_all_times',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
		$this->db->insert( 'okw_object_property', array(
			'id' => 'BFO_0000187',
			'iri' => 'http://purl.obolibrary.org/obo/BFO_0000187',
			'magicword' => 'has_continuant_part_at_all_times_that_part_exists',
			'ontology' => 'BFO',
			'source' => 'http://purl.obolibrary.org/obo/bfo.owl',
			'global' => 'y',
		) );
	}
	public function hasOntology( $ontologyAbbrv ) {
		$result = $this->db->select( 'okw_ontology', 'ontology_abbrv', "ontology_abbrv = '$ontologyAbbrv'" );
		if ( property_exists( $result->current(), 'ontology_abbrv' ) ) {
			return true;
		} else {
			return false;
		}
	}
	
	public function getOntologyID( $ontologyAbbrv ) {
		$result = $this->db->select( 'okw_ontology', array( 'id' ), array( "ontology_abbrv = '$ontologyAbbrv'" ) );
		return $result->current()->id;
	}
	
	public function getOntologyAttributes( $ontologyAbbrv, $varName ) {
		$result = $this->db->select( 'okw_ontology', $varName, array( "ontology_abbrv = '$ontologyAbbrv'" ) );
		return $result->current();
	}
	
	public function getOntologies() {
		$result = $this->db->select( 'okw_ontology', array( 'ontology_abbrv', 'ontology_fullname') );
		$output = array();
		foreach ( $result as $row ) {
			$tmp = array( 'ontAbbr' => $row->ontology_abbrv, 'fullName' => $row->ontology_fullname );
			$output[] = $tmp;
		}
		return $output;
	}
	
	public function insertOntology( $id, $options ) {
		$valid = array(
				'ontology_url',
				'ontology_fullname',
				'ontology_abbrv',
				'end_point',
				'ontology_graph_url',
				'term_url_prefix',
				'ontology_creation_digit',
				'source',
				'loaded',
		);
		foreach( $options as $name => $value ) {
			if ( !in_array( $name, $valid ) ) {
				unset( $options[$name] );
			}
		}
	
		$options['id'] = $id;
	
		$rdfFactory = new RDFStoreFactory();
		$rdf = $rdfFactory->createRDFStore( $options['end_point'] );
		if ( $rdf->ping() ) {
			$this->db->insert( 'okw_ontology', $options );
			return true;
		} else {
			return false;
		}
	}
	
	public function updateOntology( $id, $options ) {
		$valid = array(
				'ontology_url',
				'ontology_fullname',
				'ontology_abbrv',
				'end_point',
				'ontology_graph_url',
				'term_url_prefix',
				'ontology_creation_digit',
				'source',
				'loaded',
		);
		foreach( $options as $name => $value ) {
			if ( !in_array( $name, $valid ) ) {
				unset( $options[$name] );
			}
		}
	
		$this->db->update( 'okw_ontology', $options, array( "id = '$id'" ) );
	}
	
	public function deleteOntology( $ontID ) {
		$this->db->delete( 'okw_ontology', array( "id = '$ontID'" ) );
		
		$result = $this->db->select( 'okw_object_link', array( 'id', 'object_id' ), array( "ontology_id = '$ontID'" ) );
		foreach ( $result as $row ) {
			$this->db->delete( 'okw_object_link', array( "id = '$row->id'" ) );
			$this->db->delete( 'okw_object_property', array( "id = '$row->object_id'" ) );
		}
		
		$result = $this->db->select( 'okw_annotation_link', array( 'id', 'annotation_id' ), array( "ontology_id = '$ontID'" ) );
		foreach ( $result as $row ) {
			$this->db->delete( 'okw_annotation_link', array( "id = '$row->id'" ) );
			$this->db->delete( 'okw_annotation_property', array( "id = '$row->annotation_id'" ) );
		}
	}
	
	public function getAnnotationMagicWords( $ontID = '' ) {
		$output = array();
		
		$result = $this->db->select( 'okw_annotation_property', array( 'iri', 'magicword', 'type' ), array( "global = 'y'" ) );
		foreach ( $result as $row ) {
			$output[$row->magicword]['iri'] = $row->iri;
			$output[$row->magicword]['type'] = $row->type;
		}
		
		if ( $ontID != '' ) {
			$result = $this->db->select( 'okw_annotation_link', array( 'annotation_id' ), array( "ontology_id = '$ontID'" ) );
			$cond = array();
			foreach ( $result as $row ) {
				$cond[] = $row->annotation_id;
			}
			$query = implode( "','", $cond );
			$result = $this->db->select( 'okw_annotation_property', array( 'iri', 'magicword', 'type' ), array( "id IN ('$query')" ) );
			foreach( $result as $row ) {
				$output[$row->magicword]['iri'] = $row->iri;
				$output[$row->magicword]['type'] = $row->type;
			}
		}
		
		return $output;
	}
	
	public function insertAnnotationProperty( $ontID, $annotations ) {
		$query = implode( "','", array_keys( $annotations ) );
		$result = $this->db->select( 'okw_annotation_property', array( 'id', 'ontology', 'source' ), array( "id IN ('$query')" ) );
	
		$oldAnnotations = array();
		foreach( $result as $row ) {
			$id = $row->id;
			$oldAnnotations[] = $id;
			if ( $row->ontology && array_key_exists( 'ontology', $annotations[$id] ) ){
				if ($row->ontology != $annotations[$id]['ontology'] ) {
					$this->db->update( 'okw_annotation_property', array( 'ontology' => $annotations[$id]['ontology'] ), array( "id = '$id'", "global = 'n'" ) );
				}
			}
			if ( $row->source && array_key_exists( 'source', $annotations[$id] ) ) {
				if ( $row->source != $annotations[$id]['source'] ) {
					$this->db->update( 'okw_annotation_property', array( 'source' => $annotations[$id]['source'] ), array( "id = '$id'", "global = 'n'" ) );
				}
			}
		}
	
		$newAnnotations = array_diff( array_keys( $annotations ), $oldAnnotations );
		
		$magics = array();
		$iris = array();
		foreach ( $newAnnotations as $id ) {
			$magics[$annotations[$id]['magicword']] = $id;
			$iris[$annotations[$id]['iri']] = $id;
		}
		
		$duplicateMagic = array();
		$query = implode( "','", array_keys( $magics ) );
		$result = $this->db->select( 'okw_annotation_property', array( 'id', 'magicword' ), array( "magicword IN ('$query')" ) );
		foreach ( $result as $row ) {
			$duplicateMagic[$magics[$row->magicword]] = $row->id;
		}
		
		$duplicateIRI = array();
		$query = implode( "','", array_keys( $iris ) );
		$result = $this->db->select( 'okw_annotation_property', array( 'id', 'iri' ), array( "iri IN ('$query')" ) );
		foreach ( $result as $row ) {
			$duplicateIRI[$iris[$row->iri]] = $row->id;
		}
		
		if ( empty( $duplicateMagic ) && empty( $duplicateIRI ) ) {
			foreach ( $newAnnotations as $id ) {
				$this->db->insert( 'okw_annotation_property', $annotations[$id] );
				$this->db->insert( 'okw_annotation_link', array( 'ontology_id' => $ontID, 'annotation_id' => $id ) );
			}
		} else {
			$msg = 
				wfMessage( 'okw_special_import_duplicate_property' ) .
				PHP_EOL . 
				wfMessage( 'okw_special_import_duplicate_property_example' )
			;
			foreach ( $duplicateMagic as $newID => $oldID ) {
				$oldMagic = array_search( $newID, $magics );
				$msg .= PHP_EOL . "[$oldMagic] : [$oldID] : [$newID]";
			}
			foreach ( $duplicateIRI as $newID => $oldID ) {
				$oldIRI = array_search( $newID, $iris );
				$msg .= PHP_EOL . "[$oldIRI] : [$oldID] : [$newID]";
			}
			throw new MWException( $msg );
		}
	}
	
	public function getObjectMagicWords( $ontID = '' ) {
		$output = array();
		
		$result = $this->db->select( 'okw_object_property', array( 'id', 'iri', 'magicword' ), array( "global = 'y'" ) );
		foreach ( $result as $row ) {
			$output[$row->magicword]['id'] = $row->id;
			$output[$row->magicword]['iri'] = $row->iri;
		}
		
		if ( $ontID != '' ) {
			$result = $this->db->select( 'okw_object_link', array( 'object_id' ), array( "ontology_id = '$ontID'" ) );
			$cond = array();
			foreach ( $result as $row ) {
				$cond[] = $row->object_id;
			}
			$query = implode( "','", $cond );
			$result = $this->db->select( 'okw_object_property', array( 'id', 'iri', 'magicword' ), array( "id IN ('$query')" ) );
			foreach( $result as $row ) {
				$output[$row->magicword]['id'] = $row->id;
				$output[$row->magicword]['iri'] = $row->iri;
			}
		}
		
		return $output;
	}
	
	public function insertObjectProperty( $ontID, $objects ) {
		$query = implode( "','", array_keys( $objects ) );
		$result = $this->db->select( 'okw_object_property', array( 'id', 'ontology', 'source' ), array( "id IN ('$query')" ) );
		
		$oldObjects = array();
		foreach( $result as $row ) {
			$id = $row->id;
			$oldObjects[] = $id;
			if ( $row->ontology && array_key_exists( 'ontology', $objects[$id] ) ){
				if ($row->ontology != $objects[$id]['ontology'] ) {
					$this->db->update( 'okw_object_property', array( 'ontology' => $objects[$id]['ontology'] ), array( "id = '$id'", "global = 'n'" ) );
				}
			}
			if ( $row->source && array_key_exists( 'source', $objects[$id] ) ) {
				if ( $row->source != $objects[$id]['source'] ) {
					$this->db->update( 'okw_object_property', array( 'source' => $objects[$id]['source'] ), array( "id = '$id'", "global = 'n'" ) );
				}
			}
		}
		
		$newObjects = array_diff( array_keys( $objects ), $oldObjects );
		
		$magics = array();
		$iris = array();
		foreach ( $newObjects as $id ) {
			$magics[$objects[$id]['magicword']] = $id;
			$iris[$objecs[$id]['iri']] = $id;
		}
		
		$duplicateMagic = array();
		$query = implode( "','", array_keys( $magics ) );
		$result = $this->db->select( 'okw_object_property', array( 'id', 'magicword' ), array( "magicword IN ('$query')" ) );
		foreach ( $result as $row ) {
			$duplicateMagic[$magics[$row->magicword]] = $row->id;
		}
		
		$duplicateIRI = array();
		$query = implode( "','", array_keys( $iris ) );
		$result = $this->db->select( 'okw_object_property', array( 'id', 'iri' ), array( "iri IN ('$query')" ) );
		foreach ( $result as $row ) {
			$duplicateIRI[$iris[$row->iri]] = $row->id;
		}
		
		if ( empty( $duplicateMagic ) && empty( $duplicateIRI ) ) {
			foreach ( $newObjects as $id ) {
				$this->db->insert( 'okw_object_property', $objects[$id] );
				$this->db->insert( 'okw_object_link', array( 'ontology_id' => $ontID, 'object_id' => $id ) );
			}
		} else {
			$msg = 
				wfMessage( 'okw_special_import_duplicate_property' ) .
				PHP_EOL . 
				wfMessage( 'okw_special_import_duplicate_property_example' )
			;
			foreach ( $duplicateMagic as $newID => $oldID ) {
				$oldMagic = array_search( $newID, $magics );
				$msg .= PHP_EOL . "[$oldMagic] : [$oldID] : [$newID]";
			}
			foreach ( $duplicateIRI as $newID => $oldID ) {
				$oldIRI = array_search( $newID, $iris );
				$msg .= PHP_EOL . "[$oldIRI] : [$oldID] : [$newID]";
			}
			throw new MWException( $msg );
		}
	}
}

?>