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
 * @file OntoKiWi.i18n.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

$messages = array();

$messages['en'] = array(
	'okw_description'								=>	'Ontology integration of Media Wiki',
	'okw_edit'										=>	'Edit Source',
	'okw_create'									=>	'Create Page',
	
	#Special Page: Import_ontology
	'import_ontology'								=>	'Import pages from ontology',
	'okw_special_import_link'						=>	'import ontology',
	'okw_special_import_link_text'					=>	'Import Ontology using SPARQL',
	'okw_special_import_duplicate_property'			=> 	'Duplicate Magicword/IRI defined for following object properties',
	'okw_special_import_duplicate_property_example'	=> 	'[Magicword/IRI]:[Existing ID]:[Importing ID]',
	
	#Special Page: Export_ontology
	'export_ontology'								=> 	'Export pages as ontology',
	'okw_special_export_link'						=> 	'export ontology',
	'okw_special_export_link_text'					=>	'Export Ontology using SPARQL',
	
	'formedit'										=>	'Edit Ontology',
	'formcreate'									=>	'Create Ontology Page',
	'form_edit_fail'								=>	'Modifying [[$1]] failed.'
	
);

?>