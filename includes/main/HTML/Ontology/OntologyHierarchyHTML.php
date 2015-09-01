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
 * @file OntologyHierarchyHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Ontology;

use OKW\Display\DisplayHelper;

use OKW\HTML\DisplayHTML;

class OntologyHierarchyHTML implements DisplayHTML {
	/**
	 * Static function to generate Hierarchy Header HTML
	 *
	 * @return $html
	 */
	public static function headerHTML() {
		$html = 
<<<END
<div id="okw-hierarchy-main" class="main">
<ul id="okw-hierarchy-top" class="top">
<li id="okw-hierarchy-top-term" class="top-term">Thing
<ul>
END;
		
		return $html;
	}
	
	/**
	 * Static function to generate Hierarchy Bottom HTML
	 *
	 * @return $html
	 */
	public static function bottomHTML() {
		$html = '</ul></li></ul></div>' .
				'</div>';
		return $html;
	}
	
	/**
	 * Static function to generate the full Hierarchy with the given inputs
	 * 
	 * @param $ontology
	 * @param $term
	 * @param $hierarchy
	 * @return $html
	 */
	public static function getHTML( $ontology, $term, $hierarchy ) {
		$path = $hierarchy['path'];
		$subClasses = $hierarchy['subClasses'];
		$sibClasses = $hierarchy['sibClasses'];
		$hasChild = $hierarchy['hasChild'];
		
		$ontAbbr = $ontology->getOntAbbr();
		#TODO: RootURL in GLOBAL
		
		$rootURL = $GLOBALS['wgServer'] . $GLOBALS['wgScriptPath'] . '/index.php/';
	
		$html = self::headerHTML();
	
		if ( !empty( $path ) || !empty( $subClasses ) ) {
				
			if ( !empty( $path ) ) {
				$html .= self::supClassHeaderHTML( $ontAbbr, $rootURL, $path );
	
				if( !empty( $sibClasses ) ) {
					$html .= self::sibClassSectionHTML( $ontAbbr, $rootURL, $sibClasses, $hasChild );
				}
	
				$html .= self::curClassHeaderHTML( $ontAbbr, $rootURL, $term, $hasChild );
	
				if ( !empty( $subClasses ) ) {
					$html .= self::subClassSectionHTML( $ontAbbr, $rootURL, $subClasses, $hasChild );
				}
	
				$html .= self::curClassBottomHTML();
	
				$html .= self::supClassBottomHTML( $path );
			} else {
				$html .= self::curClassHeaderHTML( $ontAbbr, $rootURL, $term, $hasChild );
	
				$html .= self::subClassSectionHTML( $ontAbbr, $rootURL, $subClasses, $hasChild );
	
				$html .= self::curClassBottomHTML();
			}
		} else {
			#TODO: Special case where the term has no super/sub classes.
		}
	
		$html .= self::bottomHTML();
	
		return $html;
	}
	
	/**
	 * Static function to generate Entity HTML with specified Class
	 *
	 * @param $class
	 * @param $link
	 * @param $label
	 * @return $html
	 */
	protected static function entitiyHTML( $class, $link, $label ) {
		$html = '<a class="';
		$html .= $class;
		$html .= '" oncontextmenu="return false;" href="';
		$html .= $link;
		$html .= '">';
		$html .= DisplayHelper::convertUTFToUnicode( $label );
		$html .= '</a>';
		return $html;
	}
	
	/**
	 * Static function to generate More/Less HTML with specified class
	 *
	 * @param $class
	 * @return $html
	 */
	protected static function moreHTML( $class ) {
		#TODO: Modify add more to HTML checkbox that trigger more terms to display or not
		$html = '<li>';
		$html .= '<a id="okw-hierarchy-';
		$html .= $class;
		$html .= '-more" class="';
		$html .= $class;
		$html .= '-more">more...</a>';
		$html .= '<a id="okw-hierarchy-';
		$html .= $class;
		$html .= '-less" class="';
		$html .= $class;
		$html .= '-less">less...</a>';
		$html .= '</li">';
		return $html;
	}
	
	/**
	 * Static function to generate Super-Class Header HTML
	 * 
	 * @param $ontAbbr
	 * @param $rootURL
	 * @param $path
	 * @return $html
	 */
	protected static function supClassHeaderHTML( $ontAbbr, $rootURL, $path ) {
		$html = '<!-- OKW Hierarchy Super Class Opening -->';
		foreach ( $path as $supClassIRI => $supClassLabel ) {
			if ( $supClassLabel == '' ) {
				$supClassLabel = DisplayHelper::getShortTerm( $supClassIRI );
			}
			if ( $supClassIRI != 'http://www.w3.org/2002/07/owl#Thing' ) {
				$html .= '<li>+ ';
				$html .= self::entitiyHTML(
					'sup-term',
					$rootURL . $ontAbbr . ':' . DisplayHelper::getShortTerm( $supClassIRI ), 
					$supClassLabel 
				);
				$html .= '<ul>';
			}
		}
		return $html;
	}
	
	/**
	 * Static function to generate Sibling-Class Section HTML
	 * 
	 * @param $ontAbbr
	 * @param $rootURL
	 * @param $sibClasses
	 * @param $hasChild
	 * @return $html
	 */
	protected static function sibClassSectionHTML( $ontAbbr, $rootURL, $sibClasses, $hasChild ) {
		$sibHasChildMax = $GLOBALS['okwHierarchyConfig']['sibClassHasChildMax'];
		$sibNoChildMax = $GLOBALS['okwHierarchyConfig']['sibClassNoChildMax'];
		
		$html = '<!-- OKW Hierarchy Sibling Class Opening -->';
		$noChildCount = 0;
		$hasChildCount = 0;
		$showMore = false;
		foreach ( $sibClasses as $sibClassIRI => $sibClassLabel ) {
			if ( ( $hasChildCount > $sibHasChildMax ) && ( $noChildCount > $sibNoChildMax ) ) {
				break;
			}
			if ( $sibClassLabel == '' ) {
				$sibClassLabel = DisplayHelper::getShortTerm( $sibClassIRI );
			}
			if ( $hasChild[$sibClassIRI] && ( $hasChildCount <= $sibHasChildMax) ) {
				$hasChildCount++;
				$html .= '<li>+ ';
				$html .= self::entitiyHTML(
					'sup-term',
					$rootURL . $ontAbbr . ':' . DisplayHelper::getShortTerm( $sibClassIRI ), 
					$sibClassLabel 
				);
				$html .= '</li>';
			} elseif ( $hasChildCount > $sibHasChildMax ) {
				$showMore = true;
			}
			if ( !$hasChild[$sibClassIRI] && ( $noChildCount <= $sibNoChildMax ) ) {
				$noChildCount++;
				$html .= '<li>- ';
				$html .= self::entitiyHTML(
					'sup-term',
					$rootURL . $ontAbbr . ':' . DisplayHelper::getShortTerm( $sibClassIRI ), 
					$sibClassLabel 
				);
				$html .= '</li>';
			} elseif ( $noChildCount > $sibNoChildMax ) {
				$showMore = true;
			}
		}
		if ( $showMore ) {
			#TODO: Modify add remaining terms as hiddenthat trigger base on display more checkbox
			$html .= self::moreHTML( 'sib' );
		}
		$html .= '<!-- OKW Hierarchy Sibling Class Closing -->';
		return $html;
	}
	
	/**
	 * Static function to generate Current-Class Header HTML
	 * 
	 * @param $ontAbbr
	 * @param $rootURL
	 * @param $term
	 * @return $html
	 */
	protected static function curClassHeaderHTML( $ontAbbr, $rootURL, $term, $hasChild ) {
		$html = '<!-- OKW Hierarchy Current Class Opening -->';
		$curClassIRI = $term->iri;
		$curClassLabel = $term->label;
		if ( isset( $hasChild[ $curClassIRI ] ) ) {
			$html .= '<li>+ ';
		} else {
			$html .= '<li>- ';
		}
		$html .= self::entitiyHTML(
			'cur-term',
			$rootURL . $ontAbbr . ':' . DisplayHelper::getShortTerm( $curClassIRI ),
			$curClassLabel
		);
		return $html;
	}
	
	/**
	 * Static function to generate Sub-Class Section HTML
	 * 
	 * @param $subClasses
	 * @param $hasChild
	 * @return $html
	 */
	protected static function subClassSectionHTML( $ontAbbr, $rootURL, $subClasses, $hasChild ) {
		$subHasChildMax = $GLOBALS['okwHierarchyConfig']['subClassHasChildMax'];
		$subNoChildMax = $GLOBALS['okwHierarchyConfig']['subClassNoChildMax'];
		
		$html = '<!-- OKW Hierarchy Sub Class Opening -->';
		$html .= '<ul>';
		$noChildCount = 0;
		$hasChildCount = 0;
		$showMore = false;
		foreach ( $subClasses as $subClassIRI => $subClassLabel ) {
			if ( ( $hasChildCount > $subHasChildMax ) && ( $noChildCount > $subNoChildMax ) ) {
				break;
			}
			if ( $subClassLabel == '' ) {
				$subClassLabel = DisplayHelper::getShortTerm( $subClassIRI );
			}
			if ( $hasChild[$subClassIRI] && ( $hasChildCount <= $subHasChildMax ) ) {
				$hasChildCount++;
				$html .= '<li>+ ';
				$html .= self::entitiyHTML(
					'sub-term',
					$rootURL . $ontAbbr . ':' . DisplayHelper::getShortTerm( $subClassIRI ),
					$subClassLabel
				);
				$html .= '</li>';
			} elseif ( $hasChildCount > $subHasChildMax ) {
				$showMore = true;
			}
			if ( !$hasChild[$subClassIRI] && ( $noChildCount <= $subNoChildMax ) ) {
				$noChildCount++;
				$html .= '<li>- ';
				$html .= self::entitiyHTML(
					'sub-term',
					$rootURL . $ontAbbr . ':' . DisplayHelper::getShortTerm( $subClassIRI ),
					$subClassLabel
				);
				$html .= '</li>';
			} elseif ( $noChildCount > $subNoChildMax ) {
				$showMore = true;
			}
		}
		if ( $showMore ) {
			#TODO: Modify add remaining terms as hiddenthat trigger base on display more checkbox
			$html .= self::moreHTML( 'sub' );
		}
		$html .= '</ul>';
		$html .= '<!-- OKW Hierarchy Sub Class Closing -->';
		return $html;
	}
	
	/**
	 * Static function to generate Current-Class Bottom HTML
	 * 
	 * @return $html
	 */
	protected static function curClassBottomHTML () {
		return '</li><!-- OKW Hierarchy Super Class Closing -->';
	}
	
	/**
	 * Static function to generate Sup-Class Bottom HTML
	 * 
	 * @param $path
	 * @return $html
	 */
	protected static function supClassBottomHTML( $path ) {
		$html = '';
		foreach ( $path as $supClassIRI => $supClassLabel ) {
			if ( $supClassIRI != 'http://www.w3.org/2002/07/owl#Thing' ) {
				$html .= '</ul></li>';
			}
		}
		$html .= '<!-- OKW Hierarchy Super Class Closing -->';
		return $html;
	}
	
}

?>
