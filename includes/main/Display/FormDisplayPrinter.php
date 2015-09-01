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
 * @file FormDisplayPrinter.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Display;

use OKW\Action\FormEditAction;

use OKW\Display\DisplayHelper;

use OKW\HTML\Form\EditAxiomHTML;
use OKW\HTML\Form\EditHierarchyHTML;
use OKW\HTML\Form\EditAnnotationHTML;
use OKW\HTML\Form\EditDescribeHTML;
use OKW\HTML\Form\FormButtonHTML;
use OKW\HTML\Form\CreateDescribeHTML;
use OKW\HTML\Ontology\OntologyDescribeHTML;

use OKW\Ontology\OntologyData;

use OKW\Store\SQLStore\SQLStore;


class FormDisplayPrinter {	
	public static function display( $perm, $formName, $title ) {
		$html = '';
		
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		
		if ( $formName == 'edit' ) {
			$titleArray = explode( ':', $title );
			$ontAbbr = $titleArray[0];
			$termID = str_replace( ' ' , '_' , $titleArray[1]);
			
			$magic = $sql->getAnnotationMagicWords( $ontAbbr );
			
			$ontology = new OntologyData( $ontAbbr );
			$term = $ontology->parseTermByID( $termID );
			
			$html .= self::printEditForm( $perm, $ontAbbr, $term, $magic );
			
			wfDebugLog( 'OntoKiWi', 'OKW\Display\FormDisplayPrinter:: display: edit form');
		} else if ( $formName == 'create' ) {
			$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
			$ontologies = $sql->getOntologies();
			
			$magic = $sql->getAnnotationMagicWords();
			
			$html .= self::printCreateForm( $perm, $ontAbbr, $title, $ontologies, $magic );
			
			wfDebugLog( 'OntoKiWi', 'OKW\Display\FormDisplayPrinter:: display: create form');
		} else {
			#TODO: Throw Exception
		}
		
		return $html;
	}
	
	protected static function printEditForm( $perm, $ontAbbr, $term, $magic ) {
		$html = self::printFormHeader();
		
		$html .= '<input id="okw-form-ontology" type="hidden" value="' .
			$ontAbbr .
			'">';
		
		$html .= EditDescribeHTML::getHTML( $perm, $term );
		
		$html .= EditHierarchyHTML::getHTML( $ontAbbr );
		
		$html .= EditAxiomHTML::getHTML( $ontAbbr );
		
		$html .= EditAnnotationHTML::getHTML( $magic );
		
		$html .= self::printFormBottom();
		
		return DisplayHelper::tidyHTML( $html );
	}
	
	protected static function printCreateForm( $perm, $ontAbbr, $label, $ontologies, $magic ) {
		$html = self::printFormHeader();
		
		$html .= '<input id="okw-form-ontology" type="hidden" value="">';
		
		$html .= CreateDescribeHTML::getHTML( $perm, $label, $ontologies );
		
		$html .= EditHierarchyHTML::getHTML();
		
		$html .= EditAxiomHTML::getHTML( $ontAbbr );
		
		$html .= EditAnnotationHTML::getHTML( $magic );
		
		$html .= self::printFormBottom();
		
		return DisplayHelper::tidyHTML( $html );
	}
	
	private static function printFormHeader() {
		$html =
<<<END
<!-- OKW Form Start -->
<div id="okw-form-wrapper" class="okw-form-wrapper">
<div id="okw-form-heading" class="heading">Edit Ontology</div>
<div id="okw-form-main" class="main">
<form id="okw-form" name="okw-form" method="post">
END;
		
		return $html;
	}
	
	private static function printFormBottom() {
		global $wgUser;
		
		#TODO: Edit summary
		
		$html = 
<<<END
<br /><br />
<!-- OKW Form Edit Options Start -->
<div class='editOptions'>
END;
		/*
		 $summary_text = SFFormUtils::summaryInputHTML( $is_disabled );
		 $text = <<<END
		 $summary_text    <br />
		 END;
		 if ( $wgUser->isAllowed( 'minoredit' ) ) {
		 $text .= SFFormUtils::minorEditInputHTML( $form_submitted, $is_disabled, false );
		 }
		
		 if ( $wgUser->isLoggedIn() ) {
		 $text .= SFFormUtils::watchInputHTML( $form_submitted, $is_disabled );
		 }
		 */
		
		$html .= 
<<<END
<br />
<!-- OKW Form Edit Buttons Start -->
<div class='editButtons'>		
END;
		$html .= FormButtonHTML::saveButtonHTML();
		#$html .= self::showPreviewButtonHTML();
		#$html .= self::showChangesButtonHTML();
		$html .= FormButtonHTML::cancelLinkHTML();
		
		$html .= 
<<<END
</div><!-- OKW Form Edit Buttons End -->
</div><!-- OKW Form Edit Options End -->
</form>
</div>
<!-- OKW Form End -->
END;
		return $html;
	}
}

?>