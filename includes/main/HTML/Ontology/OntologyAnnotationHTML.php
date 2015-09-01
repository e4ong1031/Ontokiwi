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
 * @file OntologyAnnotationHTML.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\HTML\Ontology;

use OKW\HTML\DisplayHTML;

class OntologyAnnotationHTML implements DisplayHTML {
	public static function getHTML( $annotations ) {
		$html = self::headerHTML();
		
		foreach ( $annotations as $iri => $annotation ) {
			if ( !is_array( $annotation['value'] ) ) {
				$html .= '<li class="main-li"><a href="' .
					$iri .
					'">' .
					$annotation['name'] .
					': </a>' .
					$annotation['value'] .
					'</li>';
			}
		}
		
		foreach ( $annotations as $iri => $annotation ) {
			if ( is_array( $annotation['value'] ) ) {
				$html .= '<li><a href="' .
					$iri .
					'">' .
					$annotation['name'] .
					': </a>' .
					'<ul class="main-ul">';
				
				foreach ( $annotation['value'] as $text ) {
					$html .= '<li class="main-ul-li">' .
						$text .
						'</li>';
				}
				
				$html .= '</ul></li>';
			}
		}
		
		$html .= self::bottomHTML();
		
		return $html;
	}
	
	public static function headerHTML() {
		$html = '<div id="okw-annotation" class="annotation">' .
			'<div id="okw-annotation-heading" class="heading">Annotation: </div>' .
			'<div id="okw-annotation-main" class="main">' .
			'<ul id="okw-annotation-main-list" class="main-list">';
		return $html;
	}
	
	public static function bottomHTML() {
		$html = '</ul>' .
			'</div>';
			'</div>';
		return $html;
	}

}

?>