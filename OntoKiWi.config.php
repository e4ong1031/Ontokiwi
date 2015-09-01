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
 * @file OntoKiWi.config.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment Default configuration of OntoKiWi
 */

###########################
# OntoKiWi ROOT Directory #
###########################

$dir = __DIR__ . '/';

###########################
# MediaWiki Configuration #
###########################

$wgEnableAPI = true;
$wgEnableWriteAPI = true;

#############################
# OntoKiWi Global Variables #
#############################

# Main Configuration
$GLOBALS['okwConfig'] = array();
$GLOBALS['okwConfig']['fileRoot'] = $dir;
$GLOBALS['okwConfig']['sparqlEndpointEngine'] = 'Virtuoso';

# Define OntoKiWi Cache
$GLOBALS['okwCache']['hierarchy'] = array();
$GLOBALS['okwCache']['annotation'] = array();
$GLOBALS['okwCache']['axiom'] = array( 'subclassof' => array(), 'equivalent' => array() );

# Define OntoKiWi Hierarchy Display Setting
$GLOBALS['okwHierarchyConfig'] = array();
$GLOBALS['okwHierarchyConfig']['pathType'] = 'max';
$GLOBALS['okwHierarchyConfig']['sibClassHasChildMax'] = 10;
$GLOBALS['okwHierarchyConfig']['sibClassNoChildMax'] =10;
$GLOBALS['okwHierarchyConfig']['subClassHasChildMax'] = 10;
$GLOBALS['okwHierarchyConfig']['subClassNoChildMax'] =10;

# Define RDF Setting
$GLOBALS['okwRDFConfig'] = array();
$GLOBALS['okwRDFConfig']['prefixNS'] = array(
	'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
	'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
	'owl' => 'http://www.w3.org/2002/07/owl#',
	'xsd' => '<http://www.w3.org/2001/XMLSchema#>',
);
$GLOBALS['okwRDFConfig']['restriction']['operation'] = array(
	'and' => 'http://www.w3.org/2002/07/owl#intersectionOf',
	'or' => 'http://www.w3.org/2002/07/owl#unionOf',
	'not' => 'http://www.w3.org/2002/07/owl#complementOf',
);
$GLOBALS['okwRDFConfig']['restriction']['type'] = array(
	'some' => 'http://www.w3.org/2002/07/owl#someValuesFrom',
	'only' => 'http://www.w3.org/2002/07/owl#allValuesFrom',
	'value' => 'http://www.w3.org/2002/07/owl#hasValue',
);
$GLOBALS['okwRDFConfig']['restriction']['list'] = array(
	'first' =>'http://www.w3.org/1999/02/22-rdf-syntax-ns#first',
	'rest' =>'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest',
);
$GLOBALS['okwRDFConfig']['restriction']['onProperty'] = 'http://www.w3.org/2002/07/owl#onProperty';
$GLOBALS['okwRDFConfig']['restriction']['nil'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil';
$GLOBALS['okwRDFConfig']['type'] = array(
		'class' => 'http://www.w3.org/2002/07/owl#Class',
);
$GLOBALS['okwRDFConfig']['Thing'] = 'http://www.w3.org/2002/07/owl#Thing';

# Define Autocomplete setting
$GLOBALS['okwAutocomplete'] = array();
$GLOBALS['okwAutocomplete']['property'] = array(
		'http://www.w3.org/2000/01/rdf-schema#label',
		'http://purl.obolibrary.org/obo/IAO_0000111',
		'http://purl.obolibrary.org/obo/IAO_0000118',
);

################################
# OntoKiWi Ontology Properties #
################################

#####################
# OntoKiWi Messages #
#####################

$wgExtensionMessagesFiles['OntoKiWiMagic'] = $dir . 'OntoKiWi.i18n.magic.php';
$wgExtensionMessagesFiles['OntoKiWi'] = $dir . 'OntoKiWi.i18n.php';

#################################
# OntologyKiWi Autoload Classes #
#################################

# ROOT
$wgAutoloadClasses['OntoKiWiSetup'] = $dir . 'OntoKiWi.setup.php';
$wgAutoloadClasses['OntoKiWiHook'] = $dir . 'OntoKiWi.hook.php';

# includes/main
$wgAutoloadClasses['OKW\\CurlRequest'] = $dir . 'includes/main/CurlRequest.php';

# includes/main/Action
$wgAutoloadClasses['OKW\\Action\\FormEditAction'] = $dir . 'includes/main/Action/FormEditAction.php';
$wgAutoloadClasses['OKW\\Action\\ActionHelper'] = $dir . 'includes/main/Action/ActionHelper.php';

# includes/main/API
$wgAutoloadClasses['OKW\\API\\OntologyTermAutocomplete'] = $dir . 'includes/main/API/OntologyTermAutocomplete.php';
$wgAutoloadClasses['OKW\\API\\OntologyTermAutocreate'] = $dir . 'includes/main/API/OntologyTermAutocreate.php';

# includes/main/Display
$wgAutoloadClasses['OKW\\Display\\PageDisplayPrinter'] = $dir . 'includes/main/Display/PageDisplayPrinter.php';
$wgAutoloadClasses['OKW\\Display\\FormDisplayPrinter'] = $dir . 'includes/main/Display/FormDisplayPrinter.php';
$wgAutoloadClasses['OKW\\Display\\DisplayHelper'] = $dir . 'includes/main/Display/DisplayHelper.php';

# includes/main/HTML
$wgAutoloadClasses['OKW\\HTML\\DisplayHTML'] = $dir . 'includes/main/HTML/DisplayHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Ontology\\OntologyAnnotationHTML'] = $dir . 'includes/main/HTML/Ontology/OntologyAnnotationHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Ontology\\OntologyAxiomHTML'] = $dir . 'includes/main/HTML/Ontology/OntologyAxiomHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Ontology\\OntologyDescribeHTML'] = $dir . 'includes/main/HTML/Ontology/OntologyDescribeHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Ontology\\OntologyHierarchyHTML'] = $dir . 'includes/main/HTML/Ontology/OntologyHierarchyHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Form\\EditAxiomHTML'] = $dir . 'includes/main/HTML/Form/EditAxiomHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Form\\EditHierarchyHTML'] = $dir . 'includes/main/HTML/Form/EditHierarchyHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Form\\EditAnnotationHTML'] = $dir . 'includes/main/HTML/Form/EditAnnotationHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Form\\CreateDescribeHTML'] = $dir . 'includes/main/HTML/Form/CreateDescribeHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Form\\EditDescribeHTML'] = $dir . 'includes/main/HTML/Form/EditDescribeHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Form\\FormButtonHTML'] = $dir . 'includes/main/HTML/Form/FormButtonHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Special\\ImportOntologyHTML'] = $dir . 'includes/main/HTML/Special/ImportOntologyHTML.php';
$wgAutoloadClasses['OKW\\HTML\\Special\\ExportOntologyHTML'] = $dir . 'includes/main/HTML/Special/ExportOntologyHTML.php';

# includes/main/Ontology
$wgAutoloadClasses['OKW\\Ontology\\ManchesterSyntaxHandler'] = $dir . 'includes/main/Ontology/ManchesterSyntaxHandler.php';
$wgAutoloadClasses['OKW\\Ontology\\OntologyData'] = $dir . 'includes/main/Ontology/OntologyData.php';
$wgAutoloadClasses['OKW\\Ontology\\OntologyUpdate'] = $dir . 'includes/main/Ontology/OntologyUpdate.php';
$wgAutoloadClasses['OKW\\Ontology\\OntologyValidator'] = $dir . 'includes/main/Ontology/OntologyValidator.php';

# includes/main/Parser
$wgAutoloadClasses['OKW\\Parser\\OntologyParser'] = $dir . 'includes/main/Parser/OntologyParser.php';
$wgAutoloadClasses['OKW\\Parser\\AxiomParser'] = $dir . 'includes/main/Parser/AxiomParser.php';
$wgAutoloadClasses['OKW\\Parser\\CommonParser'] = $dir . 'includes/main/Parser/CommonParser.php';
$wgAutoloadClasses['OKW\\Parser\\AnnotationParser'] = $dir . 'includes/main/Parser/AnnotationParser.php';
$wgAutoloadClasses['OKW\\Parser\\HierarchyParser'] = $dir . 'includes/main/Parser/HierarchyParser.php';

# includes/main/Special
$wgAutoloadClasses['OKW\\Special\\ImportOntology'] = $dir . 'includes/main/Special/ImportOntology.php';
$wgAutoloadClasses['OKW\\Special\\ExportOntology'] = $dir . 'includes/main/Special/ExportOntology.php';

# includes/main/Store
$wgAutoloadClasses['OKW\\Store\\SQLStore\\SQLStore'] = $dir . 'includes/main/Store/SQLStore/SQLStore.php';
$wgAutoloadClasses['OKW\\Store\\SQLStore\\SQLQueryHelper'] = $dir . 'includes/main/Store/SQLStore/SQLQueryHelper.php';
$wgAutoloadClasses['OKW\\Store\\RDFStore\\RDFStore'] = $dir . 'includes/main/Store/RDFStore/RDFStore.php';
$wgAutoloadClasses['OKW\\Store\\RDFStore\\VirtuosoStore'] = $dir . 'includes/main/Store/RDFStore/VirtuosoStore.php';
$wgAutoloadClasses['OKW\\Store\\RDFStore\\RDFStoreFactory'] = $dir . 'includes/main/Store/RDFStore/RDFStoreFactory.php';
$wgAutoloadClasses['OKW\\Store\\RDFStore\\RDFQueryHelper'] = $dir . 'includes/main/Store/RDFStore/RDFQueryHelper.php';

?>
