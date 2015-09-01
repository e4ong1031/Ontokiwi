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
 * @file OntologyParser.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

namespace OKW\Parser;

abstract class OntologyParser {
	const ERROR_UNKNOWN = 0;
	const ERROR_INVALID_TERM = 1;
	const ERROR_INVALID_MAGICWORD = 2;
	const ERROR_EXCESS_INPUT = 3;
	const ERROR_INVALID_SYNTAX = 4;
	
	public static function getErrorMessage( $error ) {
		switch ( $error ) {
			case 0:
				$msg = '<!-- Unknown Error -->';
				break;
			case 1:
				$msg = '<!-- Invalid Term -->';
				break;
			case 2:
				$msg = '<!-- Invalid Magicword -->';
				break;
			case 3:
				$msg = '<!-- Invalid Input -->';
				break;
			case 4:
				$msg = '<!-- Invalid Syntax -->';
				break;
		}
		return $msg;
	}
}

?>