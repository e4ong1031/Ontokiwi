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
 * @file EditSubClassOfHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Form;

use OKW\HTML\DisplayHTML;

use OKW\Store\RDFStore\RDFQueryHelper;

class EditSubClassOfHTML implements DisplayHTML {
	public static function headerHTML() {
		$html = 
<<<END
<!-- OKW Form SubClassOf Field Start -->
<div id="okw-form-subclass" class="subclass">
<div id="okw-form-subclass-heading" class="heading">SubClassOf</div>
END;
		
		return $html;
	}
	
	public static function bottomHTML() {
		$html = 
<<<END
</table>
</div>
<!-- OKW Form SubClassOf Field End -->
END;
		
		return $html;
	}
	
	public static function getHTML( $ontAbbr ) {
		$html = self::headerHTML();
		
		$html .= self::queueSubClassOfHTML( $ontAbbr );
		
		$html .= self::bottomHTML();
		
		return $html;
	}
	
	protected static function queueSubClassOfHTML( $ontAbbr ) {
		$cache = $GLOBALS['okwCache']['hierarchy'];
		$supClasses = array();
		foreach ( $cache as $index => $hierarchy ) {
			foreach ( $hierarchy as $path ) {
				end( $path['path'] );
				$iri = key( $path['path'] );
				$label = $path['path'][$iri];
				$elements = preg_split( '/\//', $iri );
				$id = array_pop( $elements );
				
				$supClasses[$iri]['label'] = $label;
				$supClasses[$iri]['id'] = $id;
			}
		}
		
		$html = 
<<<END
<label id="okw-form-subclass-input-label" class="input-label">Keyword: </label>
<input id="okw-form-subclass-input" class="input-iri" type="text" value="">
<table id="okw-form-subclass-main" class="main">
END;
		
		foreach ( $supClasses as $iri => $supClass ) {
			$html .= '<tr class="queue"><td><label class="queue-label">';
			$html .= $supClass['label'];
			$html .= ' (';
			$html .= $ontAbbr;
			$html .= ':';
			$html .= $supClass['id'];
			$html .= ')';
			$html .= '</label></td><td><input name="subclassof[]" class="queue-iri" type="text" value="';
			$html .= $iri;
			$html .= '" readonly><button class="delete" type="button">DEL</button></td></tr>';
		}
		
		
		return $html;
	}
}
?>