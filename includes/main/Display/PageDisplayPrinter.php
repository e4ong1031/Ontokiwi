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
 * @file PageDisplayPrinter.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Display;

use OKW\Display\DisplayHelper;

use OKW\HTML\Ontology\OntologyHierarchyHTML;
use OKW\HTML\Ontology\OntologyAxiomHTML;
use OKW\HTML\Ontology\OntologyDescribeHTML;
use OKW\HTML\Ontology\OntologyAnnotationHTML;

use OKW\Ontology\OntologyData;

class PageDisplayPrinter {
	public static function display( $title ) {
		$cache = $GLOBALS['okwCache'];
		$html = '';
		
		$titleArray = explode( ':', $title );
		$ontAbbr = $titleArray[0];
		$termID = str_replace( ' ' , '_' , $titleArray[1]);
		$ontology = new OntologyData( $ontAbbr );
		$term = $ontology->parseTermByID( $termID );
		
		if ( !empty( $cache['hierarchy'] ) || !empty( $cache['axiom']['subclassof'] ) || !empty( $cache['axiom']['equivalent'] ) ) {
			$html .= self::printPageSidebar( $ontology, $term, $cache );
		}
		
		if ( $ontology->existClass( $term->iri ) ||  !empty( $cache['annotation'] ) ) {
			$html .= self::printPageHeader( $ontology, $term, $cache );
		}
		
		return DisplayHelper::tidyHTML( $html );
	}
	
	protected static function printPageSidebar( $ontology, $term, $cache ) {
		$html =
<<<END
<!-- OKW Sidebar Display start -->
<div id="okw-sidebar-wrapper" class="okw-sidebar-wrapper">
END;
		
		if ( !empty( $cache['hierarchy'] ) ) {
			$hierarchy = array();
			foreach ( $cache['hierarchy'] as $paths ) {
				foreach ( $paths as $path ) {
					$hierarchy[] = OntologyHierarchyHTML::getHTML( $ontology, $term, $path );
				}
			}
			if ( !empty( $hierarchy ) ) {
				$html .= self::printHierarchy( $hierarchy );
			}
			
			wfDebugLog( 'OntoKiWi', 'OKW\Display\PageDisplayPrinter::display: sidebar: hierarchy');
		}
		
		$related = $ontology->parseTermRelated( $term );
		
		if ( !empty( $cache['axiom']['subclassof'] ) || !empty( $cache['axiom']['equivalent'] ) ) {
			$html .= OntologyAxiomHTML::getHTML( $ontology, $term, $related, $cache['axiom'] );
			wfDebugLog( 'OntoKiWi', 'OKW\Display\PageDisplayPrinter::display: sidebar: axiom');
		}
	
		$html .=
<<<END
</div>
<!-- OKW Sidebar Display end -->
END;
	
		return $html;
	}
	
	protected static function printPageHeader( $ontology, $term, $cache ) {
		$html =
<<<END
<!-- OKW Header Display start -->
<div id="okw-header-wrapper" class="okw-header-wrapper">
END;
	
		$html .= OntologyDescribeHTML::getHTML( $term );
		
		wfDebugLog( 'OntoKiWi', 'OKW\Display\PageDisplayPrinter::display: header: common');
		
		if ( !empty( $cache['annotation'] ) ) {
			$html .= OntologyAnnotationHTML::getHTML( $cache['annotation'] );
			
			wfDebugLog( 'OntoKiWi', 'OKW\Display\PageDisplayPrinter::display: header: annotation');
		}
	
		$html .=
		<<<END
</div>
<!-- OKW Header Display end -->
END;
	
		return $html;
	}
	
	protected static function printHierarchy( $hierarchy ) {
		$html =
<<<END
<!-- OKW Hierarchy Wrapper START -->
END;
		if ( sizeof( $hierarchy ) == 1 ) {
			$html .=
			<<<END

<div id="okw-hierarchy-heading" class="heading">Class Hierarchy</div>
<div id="okw-hierarchy" class="hierarchy">
$hierarchy[0]
END;
		} else {
			$start = array_shift( $hierarchy);
			$html .=
<<<END

<div id="okw-hierarchy-heading" class="heading">
<button id="okw-hierarchy-prev" style="visibility:hidden;">&#60;</button>
Class Hierarchy
<button id="okw-hierarchy-next">&#62;</button>
</div>
<div id="okw-hierarchy-1" class="hierarchy first current">
$start
END;
			
			$end = array_pop( $hierarchy );
			
			$num = 2;
			
			foreach( $hierarchy as $path ) {
				$html .=
<<<END

<div id="okw-hierarchy-$num" class="hierarchy" style="display:none">
$path
END;
				
				$num += 1;
			}
			
			$html .=
			<<<END
			
<div id="okw-hierarchy-$num" class="hierarchy last" style="display:none">
$end
END;
			
		}
		
		return $html;
	}
	
}

?>