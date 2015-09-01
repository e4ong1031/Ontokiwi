<?php

/**
 * A substantial portion of code is re-used and modified From SF_FormEdit.php in SemanticForms,
 * written by Yaron Koren.
 * https://git.wikimedia.org/git/mediawiki/extensions/SemanticForms.git
 * 
 * Copyright (C) 2007-2015  Yaron Koren, Stephan Gambke (Original Author)
 * 
 *****************************************************************************************************
 * 
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
 * @file FormEditAction.php
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment
 */

namespace OKW\HTML\Form;

use Html;
use Linker;

class FormButtonHTML {
	public static function saveButtonHTML() {
		$id = 'ofSave';
		$label = wfMessage( 'savearticle' )->text();
		$attr = array(
				'id'        => $id,
				'accesskey' => wfMessage( 'accesskey-save' )->text(),
				'title'     => wfMessage( 'tooltip-save' )->text(),
		);
		return Html::input( $id, $label, "submit", $attr );
	}
	
	public static function cancelLinkHTML() {
		global $wgTitle, $wgParser;
	
		#$label = $wgParser->recursiveTagParse( wfMessage( 'cancel' )->text() );
		$label = wfMessage( 'cancel' )->text();
	
		if ( $wgTitle == null ) {
			$cancel = '';
		} else {
			$cancel = Linker::link( $wgTitle, $label, array(), array(), 'known' );
		}
		return "\t\t" . Html::rawElement( 'span', array( 'class' => 'editHelp' ), $cancel ) . "\n";
	}
}

?>