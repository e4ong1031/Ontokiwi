<?php

/*
 * A substantial portion of code is re-used and modified From SF_FormEditAction.php & SF_AutoeditAPI.php in SemanticForms,
 * written by Yaron Koren.
 * https://git.wikimedia.org/git/mediawiki/extensions/SemanticForms.git
 * 
 * Copyright (C) 2007-2015  Yaron Koren, Stephan Gambke (Original Author)
 * 
 *****************************************************************************************************
 * 
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
 * @file FormEditAction.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Action;

use Action;
use Article;
use EditPage;
use FauxRequest;
use RequestContext;
use Title;
use MWException;

use OKW\Action\ActionHelper;

use OKW\Display\FormDisplayPrinter;

use OKW\Ontology\OntologyUpdate;

use OKW\Parser\AxiomParser;
use OKW\Parser\AnnotationParser;
use OKW\Parser\HierarchyParser;

use OKW\Store\SQLStore\SQLStore;
use OKW\Parser\CommonParser;

class FormEditAction extends Action {
	
	const ACTION_FORMEDIT = 0;
	const ACTION_SAVE = 1;
	const ACTION_PREVIEW = 2;
	const ACTION_DIFF = 3;
	
	const ERROR = 0;
	const WARNING = 1;
	const NOTICE = 2;
	const DEBUG = 3;
	
	const ONTOLOGY_PUBLIC = 0;
	const ONTOLOGY_USER = 1;
	const ONTOLOGY_MASTER = 2;
	
	private $options = array();
	private $action;
	private $statuc;
	private $perm;
	
	function addOptionsFromString( $options ) {
		return $this->parseDataFromQueryString( $this->options, $options );
	}
	
	function getOptions() {
		return $this->options;
	}
	
	function getAction() {
		return $this->action;
	}
	
	function setOptions( $options ) {
		$this->options = $options;
	}
	
	function setOption( $option, $value ) {
		$this->options[$option] = $value;
	}
	
	function getStatus() {
		return $this->status;
	}
	
	public function getName() {
		return 'formedit';
	}
	
	public function show() {
#TODO: Check page ontology validation
		$this->parseAction();
		return $this->doAction();
	}
	
	public function doAction() {
		switch ( $this->action ) {
			case 1:
				return $this->doSave();
			default:
				return $this->doDisplay();
		}
	}
	
	public function doDisplay() {
		$action = $this;
		$article = $this->page;
		$title = $article->getTitle();
		$output = $action->getOutput();
		
		if ( $this->perm >= self::ONTOLOGY_USER ) {
			$titleText = str_replace( ' ' , '_' , $title->getText() );
			
			if ( $title->exists() ) {
				# Run throught the parser, so the okwCache is generated
				$article->getPage()->getContent()->getParserOutput( $title );
				
				$html = FormDisplayPrinter::display( $this->perm, 'edit', $titleText );
			} else {
				$html = FormDisplayPrinter::display( $this->perm, 'create', $titleText );				
			}
			
			$output->setPageTitle( $title );
			$output->addHTML( $html );
		} else {
			$output->redirect( $title->getFullURL() );
		}
		return false;
	}
	
	protected function parseAction() {
		global $wgVersion;
		if ( version_compare( $wgVersion, '1.20', '>=' ) ) {
			$data = $this->getRequest()->getValues();
		}
				
		$this->options = ActionHelper::array_merge_recursive_distinct( $data, $this->options );
		if ( array_key_exists( 'ofSave', $this->options ) ) {
		
			$this->action = self::ACTION_SAVE;
			unset( $this->options['ofSave'] );
#TODO: Add preview and compare difference function 
		} else {
			// set default action
			$this->action = self::ACTION_FORMEDIT;
		}
		
		$this->status = 200;
		
		if ( $this->getTitle()->userCan( 'ontology_master' ) ) {
			$this->perm = self::ONTOLOGY_MASTER;
		} else if ( $this->getTitle()->userCan( 'viewedittab' ) ) {
			$this->perm = self::ONTOLOGY_USER;
		} else {
			$this->perm = self::ONTOLOGY_PUBLIC;
		}
	}
	
	protected function doSave() {
		global $wgUser;
		
		$action = $this;
		$article = $this->page;
		$title = $article->getTitle();
		$output = $action->getOutput();
		
		$titleArray = explode( ':', $title->getText() );
		$ontAbbr = $titleArray[0];
		$termID = str_replace( ' ' , '_' , $titleArray[1]);
		
		$update = new OntologyUpdate( $ontAbbr, $termID );
		
		if ( $title->exists() ) {
			$wikiText = $article->getPage()->getContent()->getWikitextForTransclusion();
		} else {
			$wikiText = '';
		}
		
		/*
		if ( $title->exists() ) {
			if ( $this->perm >= self::ONTOLOGY_MANAGER ) {
				#$update->updateSubClassOf( $this->options['subclassof'] );
			} else if ( $this->perm == self::ONTOLOGY_MASTER ) {
				$update->updateIRI( $this->options['term-iri'] );
				$update->updateType( $this->options['term-type'] );
			}
		} else {
			if ( $this->perm >= self::ONTOLOGY_PUBLIC ) {
				#$update->updateSubClassOf( $this->options['subclassof'] );
				$update->updateType( $this->options['term-type'] );
			}
		}
		
		$update->updateLabel( $this->options['term-label'] );
		
		if ( key_exists( 'annotation-type', $this->options ) && key_exists( 'annotation-text', $this->options ) ) {
			$newWikiText = ActionHelper::changeOntologyAnnotation( $oldWikiText,
					$this->options['annotation-type'],
					$this->options['annotation-text']
			);
		}
		*/
		
		if ( $this->perm >= self::ONTOLOGY_USER ) {
			if ( $this->perm == self::ONTOLOGY_MASTER ) {
				if ( $title->exists() ) {
					$update->updateIRI( $this->options['term-iri'] );
					$update->updateType( $this->options['term-type'] );
				} else {
					$update->updateType( $this->options['term-type'] );
				}
				
				wfDebugLog( 'OntoKiWi',
					sprintf(
						'OKW\Action\FormEditAction: %s is allowed {ontology_master}: updated restricted information about the term [[%s]]',
						$wgUser->getName(),
						$title->getText()
					)
				);
			}
			
			$common['label'] = $this->options['term-label'];
			list( $wikiText, $common ) = CommonParser::reformatWikiText( $wikiText, $common );
			
			wfDebugLog( 'OntoKiWi',
				sprintf(
					'OKW\Action\FormEditAction: reformatted common information wikitext of the term [[%s]]',
					$title->getText()
				)
			);
			
			$update->updateLabel( $common['label'] );
			
			wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Action\FormEditAction: updated common information about the term [[%s]]', $title->getText() ) );
			
			if ( array_key_exists( 'annotation-type', $this->options ) && array_key_exists( 'annotation-text', $this->options) ) {
				if ( sizeof( $this->options['annotation-type'] ) != sizeof( $this->options['annotation-text'] ) ) {
					#TODO: Throw Exception
				}
				
				$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
				$magic = $sql->getAnnotationMagicWords();
				
				$annotations = array();
				foreach( $this->options['annotation-type'] as $index => $name ) {
					$iri = $magic[$name]['iri'];
					$text = $this->options['annotation-text'][$index];
					$annotations[$iri][] = $text;
				}
				list( $wikiText, $annotations ) = AnnotationParser::reformatWikiText( $wikiText, $annotations );
				
				wfDebugLog( 'OntoKiWi',
					sprintf(
						'OKW\Action\FormEditAction: reformatted annotation wikitext of the term [[%s]]',
						$title->getText()
					)
				);
				
				$update->updateAnnotations( $annotations );
				
				wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Action\FormEditAction: updated annotation of the term [[%s]]', $title->getText() ) );
			}
			
			if ( array_key_exists( 'subclassof', $this->options ) ) {
				list( $wikiText, $supClasses ) = HierarchyParser::reformatWikiText( $ontAbbr, $wikiText, $this->options['subclassof'] );
				
				wfDebugLog( 'OntoKiWi',
					sprintf(
							'OKW\Action\FormEditAction: reformatted hierarchy wikitext of the term [[%s]]',
							$title->getText()
					)
				);
				
				
			}
			
			if ( array_key_exists( 'axiom-type', $this->options ) && array_key_exists( 'axiom-text', $this->options) ) {
				if ( sizeof( $this->options['axiom-type'] ) != sizeof( $this->options['axiom-text'] ) ) {
					#TODO: Throw Exception
				}
				
				$axioms = array();
				foreach ( $this->options['axiom-type'] as $index => $type ) {
					$axioms[$index]['type'] = $type;
					$axioms[$index]['text'] = $this->options['axiom-text'][$index];
				}
				
				list( $wikiText, $axioms ) = AxiomParser::reformatWikiText( $ontAbbr, $wikiText, $axioms );
				wfDebugLog( 'OntoKiWi',
						sprintf(
								'OKW\Action\FormEditAction: reformatted axiom wikitext of the term [[%s]]',
								$title->getText()
						)
				);
			}
			
			$update->updateSubClassOf( $supClasses, $axioms['subclassof'] );
			
			wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Action\FormEditAction: updated hierarchy of the term [[%s]]', $title->getText() ) );
			
			#TODO: Equivalent update
			
			wfDebugLog( 'OntoKiWi', sprintf( 'OKW\Action\FormEditAction: updated axiom of the term [[%s]]', $title->getText() ) );
		}
		
		$editor = $this->setupEditPage( $wikiText );
		
		return $this->doMWStore( $output, $title, $editor );
	}
	
	protected function doMWStore( $output, $title, $editor ) {
		$permErrors = $title->getUserPermissionsErrors( 'edit', $this->getUser() );
		
		if ( !$title->exists() ) {
			$permErrors = array_merge( $permErrors, wfArrayDiff2( $title->getUserPermissionsErrors( 'create', $this->getUser() ), $permErrors ) );
		}
		
		if ( $permErrors ) {
		
			$this->getUser()->spreadAnyEditBlock();
		
			foreach ( $permErrors as $error ) {
				$this->logMessage( call_user_func_array( 'wfMessage', $error )->parse() );
			}
		
			return;
		}
		
		$resultDetails = false;
		# Allow bots to exempt some edits from bot flagging
		$bot = $this->getUser()->isAllowed( 'bot' ) && $editor->bot;
		
		$request = $editor->sfFauxRequest;
		if ( $editor->tokenOk( $request ) ) {
			$ctx = RequestContext::getMain();
			$tempTitle = $ctx->getTitle();
			$ctx->setTitle( $title );
			$status = $editor->internalAttemptSave( $resultDetails, $bot );
			$ctx->setTitle( $tempTitle );
		} else {
			throw new MWException( wfMessage( 'session_fail_preview' )->parse() );
		}
		
		switch ( $status->value ) {
			case EditPage::AS_HOOK_ERROR_EXPECTED: // A hook function returned an error
			case EditPage::AS_CONTENT_TOO_BIG: // Content too big (> $wgMaxArticleSize)
			case EditPage::AS_ARTICLE_WAS_DELETED: // article was deleted while editting and param wpRecreate == false or form was not posted
			case EditPage::AS_CONFLICT_DETECTED: // (non-resolvable) edit conflict
			case EditPage::AS_SUMMARY_NEEDED: // no edit summary given and the user has forceeditsummary set and the user is not editting in his own userspace or talkspace and wpIgnoreBlankSummary == false
			case EditPage::AS_TEXTBOX_EMPTY: // user tried to create a new section without content
			case EditPage::AS_MAX_ARTICLE_SIZE_EXCEEDED: // article is too big (> $wgMaxArticleSize), after merging in the new section
			case EditPage::AS_END: // WikiPage::doEdit() was unsuccessfull
		
				throw new MWException( wfMessage( 'form_edit_fail', $this->options['title'] )->parse() );
		
			case EditPage::AS_HOOK_ERROR: // Article update aborted by a hook function
		
				$this->logMessage( 'Article update aborted by a hook function', self::DEBUG );
				return false; // success
		
				// TODO: This error code only exists from 1.21 onwards. It is
				// suitably handled by the default branch, but really should get its
				// own branch. Uncomment once compatibility to pre1.21 is dropped.
				//            case EditPage::AS_PARSE_ERROR: // can't parse content
				//
				//                throw new MWException( $status->getHTML() );
				//                return true; // fail
		
				case EditPage::AS_SUCCESS_NEW_ARTICLE: // Article successfully created
					
					$output->redirect( $title->getFullURL() );
				
					return false; // success
		
				case EditPage::AS_SUCCESS_UPDATE: // Article successfully updated
		
					$output->redirect( $title->getFullURL() );
		
					return false; // success
		
				case EditPage::AS_BLANK_ARTICLE: // user tried to create a blank page
					
					$output->redirect( $editor->getContextTitle()->getFullURL() );
		
					return false; // success
		
				case EditPage::AS_SPAM_ERROR: // summary contained spam according to one of the regexes in $wgSummarySpamRegex
		
					$match = $resultDetails['spam'];
					if ( is_array( $match ) ) {
						$match = $this->getLanguage()->listToText( $match );
					}
		
					throw new MWException( wfMessage( 'spamprotectionmatch', wfEscapeWikiText( $match ) )->parse() ); // FIXME: Include better error message
		
				case EditPage::AS_BLOCKED_PAGE_FOR_USER: // User is blocked from editting editor page
					throw new UserBlockedError( $this->getUser()->getBlock() );
		
				case EditPage::AS_IMAGE_REDIRECT_ANON: // anonymous user is not allowed to upload (User::isAllowed('upload') == false)
				case EditPage::AS_IMAGE_REDIRECT_LOGGED: // logged in user is not allowed to upload (User::isAllowed('upload') == false)
					throw new PermissionsError( 'upload' );
		
				case EditPage::AS_READ_ONLY_PAGE_ANON: // editor anonymous user is not allowed to edit editor page
				case EditPage::AS_READ_ONLY_PAGE_LOGGED: // editor logged in user is not allowed to edit editor page
					throw new PermissionsError( 'edit' );
		
				case EditPage::AS_READ_ONLY_PAGE: // wiki is in readonly mode (wfReadOnly() == true)
					throw new ReadOnlyError;
		
				case EditPage::AS_RATE_LIMITED: // rate limiter for action 'edit' was tripped
					throw new ThrottledError();
		
				case EditPage::AS_NO_CREATE_PERMISSION: // user tried to create editor page, but is not allowed to do that ( Title->usercan('create') == false )
					$permission = $title->isTalkPage() ? 'createtalk' : 'createpage';
					throw new PermissionsError( $permission );
		
				default:
					// We don't recognize $status->value. The only way that can happen
					// is if an extension hook aborted from inside ArticleSave.
					// Render the status object into $editor->hookError
					$editor->hookError = '<div class="error">' . $status->getWikitext() . '</div>';
					throw new MWException( $status->getHTML() );
		}
	}
	
	protected function setupEditPage( $content ) {
		$title = Title::newFromText( $this->options['title'] );
	
		$article = new Article( $title );

		$editor = new EditPage( $article );
	
		$data = array_merge(
			array(
				'wpTextbox1' => $content,
				'wpSummary' => '',
				'wpStarttime' => wfTimestampNow(),
				'wpEdittime' => '',
				'wpEditToken' => isset( $this->options[ 'token' ] ) ? $this->options[ 'token' ] : $this->getUser()->getEditToken(),
				'action' => 'submit',
			),
			$this->options
		);
	
		if ( array_key_exists( 'format', $data ) ) {
			unset( $data['format'] );
		}

		$request = new FauxRequest( $data, true );

		$editor->importFormData( $request );
		$editor->sfFauxRequest = $request;

		return $editor;
	}
}

?>