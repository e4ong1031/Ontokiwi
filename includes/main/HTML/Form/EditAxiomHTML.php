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
 * @file EditAxiomHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Form;

use OKW\HTML\DisplayHTML;

use OKW\Store\RDFStore\RDFQueryHelper;

class EditAxiomHTML implements DisplayHTML {
	public static function headerHTML() {
		$html = 
<<<END
<!-- OKW Form Axiom Field Start -->
<div id="okw-form-axiom" class="axiom">
<div id="okw-form-axiom-heading" class="heading">Axiom</div>
END;
		
		return $html;
	}
	
	public static function bottomHTML() {
		$html = 
<<<END
</table>
</div>
<!-- OKW Form Axiom Field End -->
END;
		
		return $html;
	}
	
	public static function getHTML( $ontAbbr ) {
		$html = self::headerHTML();
		
		$html .= self::queueAxiomHTML( $ontAbbr );
		
		$html .= self::bottomHTML();
		
		return $html;
	}
	
	protected static function queueAxiomHTML( $ontAbbr ) {
		$axioms = $GLOBALS['okwCache']['axiom'];
		
		$html =
<<<END
<label id="okw-form-axiom-input-label" class="input-label">Assertion Type: </label>
<select id="okw-form-axiom-select" class="select">
<option disabled selected value=""> -- select an annotation type -- </option>
<option value="subclassof">SubClassOf</option>
<option value="equivalent">Equivalent</option>
</select>
<button id="okw-form-axiom-input-add" class="add" type="button">ADD</button>
<table id="okw-form-axiom-main" class="main">
END;
		
		foreach ( $axioms['subclassof'] as $axiom ) {
			$html .=
<<<END
<tr class="queue">
<td class="queue-type"><input name="axiom-type[]" type="text" class="queue-type-input" value="subclassof" readonly></td>
<td class="queue-text"><input name="axiom-text[]" type="text" class="queue-text-input" value="$axiom"><button class="delete" type="button">DEL</button></td>
</tr>
END;
			
		}
		
		foreach ( $axioms['equivalent'] as $axiom ) {
			$html .=
			<<<END
<tr class="queue">
<td class="queue-type"><input name="axiom-type[]" type="text" class="queue-type-input" value="equivalent" readonly></td>
<td class="queue-text"><input name="axiom-text[]" type="text" class="queue-text-input" value="$axiom"><button class="delete" type="button">DEL</button></td>
</tr>
END;
			
		}
		
		
		return $html;
	}
}
?>