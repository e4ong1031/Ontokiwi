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
 * @file EditAnnotationHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Form;

use OKW\HTML\DisplayHTML;

use OKW\Store\RDFStore\RDFQueryHelper;
use OKW\Store\SQLStore\SQLStore;

class EditAnnotationHTML implements DisplayHTML {
	public static function headerHTML() {
		$html = 
<<<END
<!-- OKW Form Annotation Field Start -->
<div id="okw-form-annotation" class="annotation">
<div id="okw-form-annotation-heading" class="heading">Annotation</div>
END;
		
		return $html;
	}
	
	public static function bottomHTML() {
		$html = 
<<<END
</table>
</div>
<!-- OKW Form Annotation Field End -->
END;
		
		return $html;
	}
	
	public static function getHTML( $magic ) {
		$html = self::headerHTML();
		
		$html .= self::selectAnnotationTypeHTML( $magic );
		
		$html .= self::queueAnnotationHTML( $magic );
		
		$html .= self::bottomHTML();
		
		return $html;
	}
	
	protected static function selectAnnotationTypeHTML( $magic ) {
		$html = 
<<<END
<label id="okw-form-annotation-input-label" class="input-label">Annotation Type: </label>
<select id="okw-form-annotation-select" class="select">
<option disabled selected value=""> -- select an annotation type -- </option>
END;
		
		foreach ( $magic as $name => $value ) {
			$html .= '<option value="';
			$html .= $name;
			$html .= '">';
			$html .= $name;
			$html .= '</option>';
		}
		
		$html .= '</select>';
		
		return $html;
	}
	
	protected static function queueAnnotationHTML( $magic ) {
		$annotations = $GLOBALS['okwCache']['annotation'];
		$dictMagicIRI = array();
		foreach ( $magic as $name => $value ) {
			$dictMagicIRI[$value['iri']] = $name;
		}
		
		$html =
<<<END
	<button id="okw-form-annotation-input-add" class="add" type="button">ADD</button>
	<table id="okw-form-annotation-main" class="main">
END;
		
		foreach ( $annotations as $iri => $annotation ) {
			foreach ( $annotation['value'] as $text ) {
				$html .= '<tr class="queue"><td class="queue-type"><input name="annotation-type[]" class="queue-type-input" type="text" value="';
				$html .= $dictMagicIRI[$iri];
				$html .= '" readonly></td><td class="queue-text"><input name="annotation-text[]" class="queue-text-input" type="text" value="';
				$html .= $text;
				$html .= '"><button class="delete" type="button">DEL</button></td></tr>';
			}
			
		}
		
		return $html;
	}
}
?>