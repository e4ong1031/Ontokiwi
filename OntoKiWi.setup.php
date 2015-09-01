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
 * @file OntoKiWi.setup.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment OntoKiWi extension main setup function.
 *     This funciton registers:
 *         1) Database
 *         2) Resource
 *         3) Hook
 *         4) Action
 *         5) API
 *         6) Permission
 *         7) SpecialPage
 */

use OKW\Store\SQLStore\SQLStore;

class OntoKiWiSetup {
	
	/**
	 * Field
	 *
	 * @var $dir
	 */
	private $dir;
	
	/**
	 * Constructor
	 * 
	 * @param
	 */
	public function __construct() {
		$this->dir = $GLOBALS['okwConfig']['fileRoot'];
	}
	
	/**
	 * Function
	 * 
	 * setup()
	 */
	public function setup() {
		$this->registerDatabase();
		$this->registerResource();
		$this->registerHook();
		$this->registerAction();
		$this->registerAPI();
		$this->registerPermission();
		$this->registerSpecialPage();
	}
	
	/**
	 * Function
	 *
	 * registerDatabase()
	 * 
	 * Check and setup okw_ontology, okw_object_property and okw_annotation_property exists in MediaWiki database.
	 */
	private function registerDatabase() {
		$sql = new SQLStore( wfGetDB( DB_MASTER ) );
		if ( !$sql->isSetUp() ) {
			wfDebugLog( 'OntoKiWi', 'Database: setting up OntoKiWi MySQL tables');
			$sql->setup();
		}
	}
	
	/**
	 * Function
	 * 
	 * registerResource()
	 * 
	 * Register javascript and css for:
	 *     1) page - regular wiki page view
	 *     2) form - edit ontology form
	 *     3) special - OntoKiWi special page.
	 */
	private function registerResource() {
		global $wgResourceModules;
		
		$wgResourceModules['ext.okw.page.js'] = array(
				'scripts' => array(
					'ext.okw.page.general.js',
				),
				'localBasePath' => $this->dir . '/modules/js/',
				'remoteExtPath' => 'OntoKiWi'
		);
		
		$wgResourceModules['ext.okw.page.css'] = array(
				'styles' => array(
					'ext.okw.page.header.css',
					'ext.okw.page.sidebar.css',
				),
				'localBasePath' => $this->dir . '/modules/css/',
				'remoteExtPath' => 'OntoKiWi',
				'position' => 'top'
		);
		
		$wgResourceModules['ext.okw.special.css'] = array(
				'styles' => array(
					'ext.okw.special.import.css',
				),
				'localBasePath' => $this->dir . '/modules/css/',
				'remoteExtPath' => 'OntoKiWi',
				'position' => 'top'
		);
		
		$wgResourceModules['ext.okw.special.js'] = array(
				'scripts' => array(
					'ext.okw.special.import.js',
				),
				'localBasePath' => $this->dir . '/modules/js/',
				'remoteExtPath' => 'OntoKiWi',
				'position' => 'top'
		);
		
		$wgResourceModules['ext.okw.form.js'] = array(
				'scripts' => array(
					'ext.okw.form.autocomplete.js',
					'ext.okw.form.autocreate.js',
					'ext.okw.form.submit.js',
					'ext.okw.form.general.js',
				),
				'localBasePath' => $this->dir . '/modules/js/',
				'remoteExtPath' => 'OntoKiWi',
				'dependencies' => array(
					'jquery.ui.autocomplete',
				),
		);
		
		$wgResourceModules['ext.okw.form.css'] = array(
				'styles' => array(
						'ext.okw.form.css',
				),
				'localBasePath' => $this->dir . '/modules/css/',
				'remoteExtPath' => 'OntoKiWi',
				'position' => 'top'
		);
		
	}
	
	/**
	 * Function
	 *
	 * registerHook()
	 * 
	 * Call OntoKiWiHook for MediaWiki extension hooks.
	 * MediaWiki Hooks:
	 *     1) BeforePageDisplay
	 *     2) ParserFirstCallInit
	 *     3) PageContentSave
	 *     4) ArticleDeleteComplete
	 *     5) SkinTemplateTabs
	 *     6) SkinTemplateNavigation
	 *     7) ContributionsToolLinks
	 */
	private function registerHook() {
		global $wgHooks;
		
		$wgHooks['BeforePageDisplay'][] = 'OntoKiWiHook::onBeforePageDisplay';
		$wgHooks['ParserFirstCallInit'][] = 'OntoKiWiHook::onParserFirstCallInit';
		$wgHooks['PageContentSave'][] = 'OntoKiWiHook::onPageContentSave';
		$wgHooks['ArticleDeleteComplete'][] = 'OntoKiWiHook::onArticleDeleteComplete';
		
		$wgHooks['SkinTemplateTabs'][] = 'OntoKiWiHook::displayTab';
		$wgHooks['SkinTemplateNavigation'][] = 'OntoKiWiHook::displayTab2';
		
		$wgHooks['ContributionsToolLinks'][] = 'OntoKiWiHook::addToolLinks';
	}
	
	/**
	 * Function
	 * 
	 * registerAction()
	 * 
	 * Register OntoKiWi "edit ontology form" action
	 */
	private function registerAction() {
		global $wgActions;
		
		$wgActions['formedit'] = 'OKW\Action\FormEditAction';
	}
	
	/**
	 * Method
	 * 
	 * registerAPI()
	 * 
	 * Register OntoKiWi API:
	 *     1) okwauto - OntoKiWi autocomplete for "edit ontology form"
	 *     2) okwcreate - OntoKiWi create new term IRI
	 */
	private function registerAPI() {
		global $wgAPIModules;
		
		$wgAPIModules['okwauto'] = 'OKW\API\OntologyTermAutocomplete';
		$wgAPIModules['okwcreate'] = 'OKW\API\OntologyTermAutocreate';
	}
	
	/**
	 * Function
	 * 
	 * registerPermission()
	 * 
	 * Register OntoKiWi rights:
	 *     1) ontology_master - only ontology_master can: i) import ontology; ii) edit term IRI, type
	 *     2) viewedittab - any signed in user can: i) view and edit a valid ontology page using form; ii) export ontology
	 * Register OntoKiWi permission:
	 *     1) ontology_master
	 */
	private function registerPermission() {
		global $wgAvailableRights, $wgGroupPermissions;
		
		$wgAvailableRights[] = 'ontology_master';
		$wgAvailableRights[] = 'viewedittab';
		
		$wgGroupPermissions['user']['viewedittab'] = true;
		
		$wgGroupPermissions['ontologyAdmin']['ontology_master'] = true;
		
	}
	
	/**
	 * Function
	 * 
	 * registerSpecialPage()
	 * 
	 * Register OntoKiWi Special pages:
	 *     1) import_ontology
	 *     2) export_ontology
	 */
	private function registerSpecialPage() {
		global $wgSpecialPages;
		
		$wgSpecialPages['import_ontology'] = 'OKW\Special\ImportOntology';
		$wgSpecialPages['export_ontology'] = 'OKW\Special\ExportOntology';
	}
	
}

?>