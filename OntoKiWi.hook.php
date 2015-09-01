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
 * @file OntoKiWi.hook.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment OntoKiWi Hook functions
 *     Hooks and corresponding functions:
 *         1) BeforePageDisplay <=> onBeforePageDisplay
 *         2) ParserFirstCallInit <=> onParserFirstCallInit
 *         3) PageContentSave <=> onPageContentSave
 *         4) ArticleDeleteComplete <=> onArticleDeleteComplete
 *         5) SkinTemplateTabs <=> displayTab
 *         6) SkinTemplateNavigation <=> displayTab2
 *         7) ContributionsToolLinks <=> addToolLinks
 */

use OKW\Display\PageDisplayPrinter;

use OKW\Ontology\OntologyUpdate;
use OKW\Ontology\OntologyValidator;

class OntoKiWiHook {
	
    /**
     * Function
     * 
     * onBeforePageDisplay
     *
     * @param OutputPage $out
     * @param Skin $skin
     * @return boolean
     */
    public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
    	# Modify OutputPage HTML base on WebRequest's action
    	switch ( $GLOBALS['wgRequest']->getVal( 'action' ) ) {
    		
    		# If purge page, do nothing
    		case 'purge':
    			break;
    		
    		# If OntoKiWi edit ontology with form, change displayed title and load form resources
    		case 'formedit':
    			$title = $out->getPageTitle();
    			$title = str_replace( ' ' , '_' , $title );
    			$out->mPagetitle = $title;
    			$out->addModules( array( 'ext.okw.form.js', 'ext.okw.form.css' ) );
    			break;
    		
    		# If delete page, check if page has ontology data, and:
    		#     1) change displayed title
    		#     2) add "Delete Ontology Data" checkbox
    		case 'delete':
    			global $wgRequest;
    			if ( OntologyValidator::isExistTitleText( $wgRequest->getVal( 'title' ) ) ) {
    				$title = $out->getPageTitle();
    				$title = str_replace( ' ' , '_' , $title );
    				$html = preg_replace(
    						'/(<input[^>]*name=[\'"]wpWatch[\'"].+?(?=<div>))/',
    						'${1}&#160;<input name="okwDelete" type="checkbox" value="1" id="wpWatch" checked/>&#160;' .
    						'<label for="okwDelete">Delete Ontology Data</label>',
    						$out->getHTML() );
    				 
    				$out->clearHTML();
    				$out->addHTML( $html );
    			}
    			break;
    		
    		# Default display to check if page has ontology data, and:
    		#     1) Change displayed title
    		#     2) Call PageDisplayPrinter::display
    		#     3) Load page resources
    		#     4) Redirect if only ID is provided and is valid
    		default:
    			$title = $out->getPageTitle();
    			$titleName = str_replace( ' ' , '_' , $title );
    			if ( OntologyValidator::isExistOutputPage( $out ) ) {
    				$out->mPagetitle = $titleName;
    				$html = $out->getHTML();
    				$out->clearHTML();
    				$html = PageDisplayPrinter::display( $titleName ) . $html;
    				$out->addHTML( $html );
    				$out->addModules( array( 'ext.okw.page.js', 'ext.okw.page.css' ) );
    			} else if ( preg_match_all( '/([a-zA-Z]+)[:_]([a-zA-Z]*)[:_]?(\d+)/', $titleName, $matches, PREG_SET_ORDER ) ) {
    				if ( $matches[0][2] == '' ) {
						$title = Title::newFromText( $matches[0][1] . ':' . $matches[0][1] . '_' . $matches[0][3] );
						
						if ( OntologyValidator::isExistTitle( $title ) ) {
							$out->redirect( $title->getFullURL() );
							$out->output();
						}
					}
    			}
    			break;
    	}
    	
    	return true;
    }
	
	/**
	 * Function
	 *
	 * @param Parser $parser
	 * @return boolean
	 */
	public static function onParserFirstCallInit( Parser &$parser ) {
		
		$parser->setFunctionHook( 'common', array( 'OKW\Parser\CommonParser', 'parse' ) );
		
		$parser->setFunctionHook( 'annotation', array( 'OKW\Parser\AnnotationParser', 'parse' ) );
		
		$parser->setFunctionHook( 'hierarchy', array( 'OKW\Parser\HierarchyParser', 'parse' ) );
		
		$parser->setFunctionHook( 'axiom', array( 'OKW\Parser\AxiomParser', 'parse' ) );
		
		return true;
	}
	
	/**
	 * Function
	 *
	 * @param WikiPage $wikiPage
	 * @param User $user
	 * @param Content $content
	 * @param string $summary
	 * @param boolean $isMinor
	 * @param null $isWatch
	 * @param null $section
	 * @param unknown $flags
	 * @param Status $status
	 * 
	 * Update RDF Store using SPARQL based on parsed Wiki-Text
	 */
	public static function onPageContentSave( WikiPage &$wikiPage, User &$user, Content &$content, &$summary,
			$isMinor, $isWatch, $section, &$flags, &$status ) {
		if ( OntologyValidator::isValidTitle( $wikiPage->getTitle() ) ) {
			$wikiText = $content->getWikitextForTransclusion();
			
			$title = $wikiPage->getTitle()->getText();
			$titleArray = explode( ':', $title );
			$ontAbbr = $titleArray[0];
			$termID = str_replace( ' ' , '_' , $titleArray[1]);
		
			$update = new OntologyUpdate( $ontAbbr, $termID );
			
			$wikiText = $update->doUpdate( $title, $wikiText );
			
			$content = ContentHandler::makeContent( $wikiText, $wikiPage->getTitle() );
		}
	}
	
	/**
	 * Function
	 *
	 * @param WikiPage $article
	 * @param User $user
	 * @param string $reason
	 * @param unknown $id
	 * @param Content $content
	 * @param unknown $logEntry
	 * 
	 * Delete corresponding RDF data of the deleting page if "Delete Ontology Data" option is selected
	 */
	public static function onArticleDeleteComplete( WikiPage &$article, User &$user, $reason, $id, $content, $logEntry ) {
		global $wgRequest;
		if ( $wgRequest->getVal('okwDelete') ) {
			$title = $article->getTitle();
			if ( $title->userCan( 'ontology_master' ) ) {
				$titleArray = explode( ':', $title->getText() );
				$ontAbbr = $titleArray[0];
				$termID = str_replace( ' ' , '_' , $titleArray[1]);
				
				$update = new OntologyUpdate( $ontAbbr, $termID );
				$update->deleteTerm();
			}
		}
	}
	
	/**
	 * Function
	 *
	 * @param string $skin
	 * @param array $contentActions
	 * @return boolean
	 */
	public static function displayTab( $skin, &$contentActions ) {
		global $wgRequest, $wgUser;
	
		if ( method_exists ( $skin, 'getTitle' ) ) {
			$title = $skin->getTitle();
		} else {
			$title = $skin->mTitle;
		}
		
		if ( !isset( $title ) || ( $title->getNamespace() == NS_SPECIAL ) ) {
			return true;
		}
	
		
		
		if ( $title->userCan( 'viewedittab' ) ) {
		
			if ( $title->exists() ) {
				$contentActions['edit']['text'] = wfMessage( 'okw_edit' )->text();
				$formEditTabText = 'formedit';
				if ( !OntologyValidator::isExistTitle( $title ) ) {
					return true;
				}
			} else {
				$contentActions['edit']['text'] = wfMessage( 'okw_create' )->text();
				$formEditTabText = 'formcreate';
			}
	
			$formEditTabText = wfMessage( $formEditTabText )->text();
	
			if ( $wgRequest->getVal( 'action' ) == 'formedit' ) {
			 $class = 'selected';
			 } else {
			 $class = '';
			}
	
			$formEditTab = array(
					'class' => $class,
					'text' => $formEditTabText,
					'href' => $title->getLocalURL( 'action=formedit' )
			);
	
			$tabKeys = array_keys( $contentActions );
			$tabValues = array_values( $contentActions );
			$editTabLocation = array_search( 'edit' , $tabKeys );
	
			if ( $editTabLocation == null ) {
				$editTabLocation = array_search( 'viewsource', $tabKeys );
			}
	
			if ( $editTabLocation == null ) {
				$editTabLocation = -1;
			}
	
			array_splice( $tabKeys, $editTabLocation, 0, 'form_edit' );
			array_splice( $tabValues, $editTabLocation, 0, array( $formEditTab ) );
			$contentActions = array();
			for ( $i = 0; $i < count( $tabKeys ); $i++ ) {
				$contentActions[$tabKeys[$i]] = $tabValues[$i];
			}
		}
	
		return true;
	}
	
	/**
	 * Function
	 *
	 * @param string $skin
	 * @param array $links
	 * @return boolean
	 */
	public static function displayTab2( $skin, &$links ) {
		return self::displayTab( $skin, $links['views'] );
	}
	
	/**
	 * Function
	 *
	 * @param string $id
	 * @param Title $title
	 * @param array $tools
	 * @return boolean
	 */
	public static function addToolLinks( $id, $title, &$tools ) {
		global $wgUser;
		
		if ( $wgUser->isAllowed( 'ontology_master' ) ) {
			$tools[] = Linker::link(
				SpecialPage::getTitleFor( 'import_ontology' ),
				wfMessage( 'okw_special_import_link' )->escaped(),
				array( 'title' => wfMessage( 'okw_special_import_link_text' ) ),
				array( 'target' => $title->getText() )
			);
		}
		
		if ( $wgUser->isAllowed( 'read' ) ) {
			$tools[] = Linker::link(
					SpecialPage::getTitleFor( 'export_ontology' ),
					wfMessage( 'okw_special_export_link' )->escaped(),
					array( 'title' => wfMessage( 'okw_special_export_link_text' ) ),
					array( 'target' => $title->getText() )
			);	
		}
		
		
		return true;
	}
	
}

?>