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
 * @file ExportOntologyHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Special;

use OKW\HTML\DisplayHTML;

use OKW\Special\ImportOntology;

use OKW\Store\SQLStore\SQLStore;

class ExportOntologyHTML implements DisplayHTML {
	
	public static function getHTML( $type, $status = null) {
		if ( $type == 'input' ) {
			$header = null;
			$html = self::inputHTML();
		}
	
		$html = self::headerHTML() . $html . self::bottomHTML();
	
		return array( $header, $html );
	}
	
	public static function headerHTML() {
		$html = 
<<<END
<!-- OKW Special Import Start -->
END;
		
		return $html;
	}
	
	public static function bottomHTML() {
        $html = 
<<<END
<!-- OKW Special Import End -->
END;
        
        return $html;
	}
	
	protected static function inputHTML() {
		$sql = new SQLStore( wfGetDB( DB_SLAVE ) );
		$ontologies = $sql->getOntologies();
		
		$html =
<<<END
<form id="export-form" class="export-form" action="" method="post">

<fieldset>
<legend>Export Ontology</legend>

<p>Please select an ontology to export.</p>

<select name="ontology">
<option disabled selected value=""> -- select an ontology -- </option>

END;
		foreach ( $ontologies as $ont ) {
			$html .= '<option value="';
			$html .= $ont['ontAbbr'];
			$html .= '">';
			$html .= $ont['fullName'] . ' (' . $ont['ontAbbr'] . ')';
			$html .= '</option>';
		}
		
		$html .=
<<<END

</select></br>

<input type="checkbox" value="1" name="downloadOntology" checked>
<label for="download">Save as file</label><br/>

<input type="submit" value="Export" name="exportOntology">
</fieldset>

<fieldset>
<legend>Export Pages as Ontology</legend>

<p>Please enter the titles in the text box below to export corresponding WikiPages as ontology, one title per line.</p>

<textarea name="pages" cols="40" rows="10"></textarea><br/>

<input type="checkbox" value="1" name="downloadTerm" checked>
<label for="download">Save as file</label><br/>

<input type="submit" value="Export" name="exportTerm">
</fieldset>

</form>
END;
		
		return $html;
	}
}