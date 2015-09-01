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
 * @file OntologyDescribeHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Ontology;

use OKW\HTML\DisplayHTML;

class OntologyDescribeHTML implements DisplayHTML {
	public static function getHTML( $term ) {
		$html = self::headerHTML();
		
		$html .= '<li id="okw-describe-label" class="term"><strong>Label: </strong>';
		$html .= $term->label;
		$html .= '</li><li id="okw-describe-iri" class="iri"><strong>IRI: </strong><a href="';
		$html .= $term->iri;
		$html .= '">';
		$html .= $term->iri;
		$html .= '</a></li><li id="okw-describe-type" class="type"><strong>Type: </strong>';
		$html .= $term->type;
		
		$html .= self::bottomHTML();
		
		return $html;
	}
	
	public static function headerHTML() {
		$html = '<ul id="okw-describe" class="describe">';
		
		return $html;
	}
	
	public static function bottomHTML() {
		$html ='</ul>';
		return $html;
	}
	
	protected static function contentHTML( $term ) {
		
		
		
		return $html;
	}
	

	
}

?>