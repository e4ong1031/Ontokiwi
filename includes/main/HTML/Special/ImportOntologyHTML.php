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
 * @file ImportOntologyHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Special;

use OKW\HTML\DisplayHTML;

use OKW\Special\ImportOntology;

class ImportOntologyHTML implements DisplayHTML {
	
	public static function getHTML( $type, $status = null, $classes = null ) {
		if ( $type == 'output' ) {
			if ( !is_null( $status ) ) {
				list( $header, $html ) = self::outputHTML( $status, $classes );
			}
		} else {
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
	
	protected static function outputHTML( $status, $classes ) {
		if ( $status == ImportOntology::SUCCESS ) {
			$header = null;
			$count = sizeof( $classes );
			$html =
<<<END
<p>Page creation will run in background. A total of <u>$count</u> terms are imported from the given ontology:</p>
<ul id="import-list" class="import-list">
END;
			foreach ( $classes as $class => $label ) {
				$html .= "<li>$label - <a href=\"$class\">$class</a></li>";
			}
			$html .= '</ul>';
		} else {
			return ImportOntology::ErrorMessage( $status );
		}
			
		
		return array( $header, $html );
	}
	
	protected static function inputHTML() {
		$html =
<<<END
<form id="import-form" class="import-form" action="" method="post">
<table>
	
<tr>
<td class="column-1">URL: </td>
<td class="column-2"><input class="import-input" name="ontology_url" type="text">
</tr>
	
<tr>
<td class="column-1">Name: </td>
<td class="column-2"><input class="import-input" name="ontology_fullname" type="text"></td>
</tr>
	
<tr>
<td class="column-1">Abbreviation: </td>
<td class="column-2"><input class="import-input" name="ontology_abbrv" type="text"></td>
</tr>
	
<tr>
<td class="column-1">SPARQL Endpoint: </td>
<td class="column-2"><input class="import-input" name="end_point" type="text">
</tr>
	
<tr>
<td class="column-1">Graph URL: </td>
<td class="column-2"><input class="import-input" name="ontology_graph_url" type="text"></td>
</tr>
	
<tr>
<td class="column-1">Prefix URL: </td>
<td class="column-2"><input class="import-input" name="term_url_prefix" type="text"></td>
</tr>
	
<tr>
<td class="column-1">Term Creation Digit: </td>
<td class="column-2"><input class="import-input" name="ontology_creation_digit" type="text"></td>
</tr>
	
<tr>
<td class="column-1">Source: </td>
<td class="column-2"><input class="import-input" name="source" type="text"></td>
</tr>
	
</table>
	
<input type="submit" value="Import" name="import" >
</form>
END;
	
		return $html;
	}
}

?>