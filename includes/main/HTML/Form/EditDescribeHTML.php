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
 * @file EditDescribeHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Form;

use OKW\Action\FormEditAction;

use OKW\Display\DisplayHelper;

use OKW\HTML\DisplayHTML;

class EditDescribeHTML implements DisplayHTML {
	public static function getHTML( $perm, $term ) {
		$html = self::headerHTML();
		
		$html .= self::describeFieldHTML( $perm, $term );
		
		$html .= self::bottomHTML();
		
		return $html;
	}
	
	public static function headerHTML() {
		$html = 
<<<END
<!-- OKW Form Describe Field Start -->
<div id="okw-form-describe" class="describe">
END;
		
		return $html;
	}
	
	public static function bottomHTML() {
        $html = 
<<<END
</div>
<!-- OKW Form Describe Field End -->
END;
        
        return $html;
	}
	
	protected static function describeFieldHTML( $perm, $term ) {
		$types = $GLOBALS['okwRDFConfig']['type'];
		
		$html = '<table id="okw-describe-main" class="main">';
		
		$html .= '<tr><td><label id="okw-describe-label" class="term-label"><strong>Label: </strong></label>';
		$html .= '<input id="okw-describe-label-input" class="term-input-label" name="term-label" value="';
		$html .= $term->label;
		$html .= '"></td>';
		
		$html .= '<td><label id="okw-describe-iri" class="term-iri"><strong>IRI: </strong></label>';
		$html .= '<input id="okw-describe-iri-input" class="term-input-iri" name="term-iri" value="';
		$html .= $term->iri;
		if ( $perm == FormEditAction::ONTOLOGY_MASTER ) {
			$html .= '"></td>';
		} else {
			$html .= '" readonly></td>';
		}
		
		$html .= '<td><label id="okw-describe-type" class="type"><strong>Type: </strong></label>';
		if ( $perm == FormEditAction::ONTOLOGY_MASTER ) {
			$html .= '<select id="okw-describe-type-select" class="term-select-type" name="term-type">';
		} else {
			$html .= '<select id="okw-describe-type-select" class="term-select-type" name="term-type" disabled>';
		}
		foreach ( $types as $type ) {
			$typeLabel = DisplayHelper::getShortTerm( $type );
			$html .= '<option value="';
			$html .= $type;
			if ( $typeLabel == $term->type ) {
				$html .= '" selected>';
			} else {
				$html .= '">';
			}
			$html .= $typeLabel;
			$html .= '</option>';
		}
		$html .= '</select></td>';
		
		$html .= '</tr></table>';
		
		return $html;
	}
	

	
}

?>