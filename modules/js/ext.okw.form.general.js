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
 * @file ext.okw.form.general.js
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

$( "#okw-form-subclass-main" ).on( 'click', ".delete", function( event ) {
	if ( $( "#okw-form-subclass-main tr").length > 1) {
		$(this).parent('td').parent('tr').remove();
	} else {
		alert( "Term must have at least one parent!" );
	}
} );

$( "#okw-form-subclass-main" ).on( 'click', ".clear", function( event ) {
	$( "#okw-form-subclass-main >tbody >tr >td >input").val( "" );
	$( "#okw-form-subclass-main >tbody >tr >td >label").text( "" );
} );

$( "#okw-form-annotation-main" ).on( 'click', ".delete", function( event ) {
	$(this).parent('td').parent('tr').remove();
} );

$( "#okw-form-annotation" ).on( 'click', ".add", function( event ) {
	var type = $( "#okw-form-annotation-select" ).find( ":selected" ).val();
	if ( type != "" ) {
		if ( $( "#okw-form-annotation-main tr" ).length == 0) {
			$( "#okw-form-annotation-main" ).append(
				'<tr class="queue"><td class="queue-type"><input name="annotation-type[]" class="queue-type-input" type="text" value="' +
				type +
				'" readonly></td><td class="queue-text"><input name="annotation-text[]" class="queue-text-input" type="text" value=""><button class="delete" type="button">DEL</button></td></tr>'
			);
		} else {
			$( "#okw-form-annotation-main tr:last" ).after(
				'<tr class="queue"><td class="queue-type"><input name="annotation-type[]" class="queue-type-input" type="text" value="' +
				type +
				'" readonly></td><td class="queue-text"><input name="annotation-text[]" class="queue-text-input" type="text" value=""><button class="delete" type="button">DEL</button></td></tr>'
			);
		}
	}
} );

$( "#okw-form-axiom-main" ).on( 'click', ".delete", function( event ) {
	$(this).parent('td').parent('tr').remove();
} );

$( "#okw-form-axiom" ).on( 'click', ".add", function( event ) {
	var type = $( "#okw-form-axiom-select" ).find( ":selected" ).val();
	if ( type != "" ) {
		if ( $( "#okw-form-axiom-main tr" ).length == 0) {
			$( "#okw-form-axiom-main" ).append(
				'<tr class="queue"><td class="queue-type"><input name="axiom-type[]" class="queue-type-input" type="text" value="' +
				type +
				'" readonly></td><td class="queue-text"><input name="axiom-text[]" class="queue-text-input" type="text" value=""><button class="delete" type="button">DEL</button></td></tr>'
			);
		} else {
			$( "#okw-form-axiom-main tr:last" ).after(
				'<tr class="queue"><td class="queue-type"><input name="axiom-type[]" class="queue-type-input" type="text" value="' +
				type +
				'" readonly></td><td class="queue-text"><input name="axiom-text[]" class="queue-text-input" type="text" value=""><button class="delete" type="button">DEL</button></td></tr>'
			);
		}
	}
} );