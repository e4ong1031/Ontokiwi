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
 * @file ext.okw.form.autocomplete.js
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

$( function() {
	function split( val ) {
		return val.split( /,\s*/ );
	}
	
	function extractLast( term ) {
		return split( term ).pop();
	}
	
	$( "#okw-form-subclass-input" ).autocomplete( {
		source: function( request, response ) {
			var ontAbbr = $( "#okw-form-ontology" ).val();
			$.ajax( {
				type: "GET",
				url: "api.php",
				data: { action:"okwauto", ontology:ontAbbr, keywords:extractLast( request.term ), format:"json" },
				success: function( data ) {
					var terms = [];
					response( data["okwauto"] );
				}
			} )
		},
		minLength: 3,
		select: function( event, ui ) {
			//$( "#okw-form-subclass-input-label" ).text( ui.item.label );
			var ontAbbr = $( "#okw-form-ontology" ).val();
			var term = ui.item.iri;
			var label = ui.item.label;
			$.ajax( {
				type: "GET",
				url: "api.php",
				data: { action:"okwauto", ontology:ontAbbr, check:term, format:"json" },
				success: function( data ) {
					if ( data["okwauto"]["exist"] ) {
						if ( label == '' ) {
							label = data["okwauto"]["label"];
						}
						if ( $( "#okw-form-subclass-main tr" ).length == 0) {
							$( "#okw-form-subclass-main" ).append(
								'<tr class="queue"><td><label class="queue-label">' +
								label +
								'</label></td><td><input name="subclassof[]" class="queue-iri" type="text" value="' +
								term +
								'" readonly><button class="delete" type="button">DEL</button></td></tr>'
							);
						} else {
							$( "#okw-form-subclass-main tr:last" ).after(
								'<tr class="queue"><td><label class="queue-label">' +
								label +
								'</label></td><td><input name="subclassof[]" class="queue-iri" type="text" value="' +
								term +
								'" readonly><button class="delete" type="button">DEL</button></td></tr>'
							);
						}
						$( "#okw-form-subclass-input" ).val( '' );
					} else {
						alert( "Incorrect term input" );
					}
				}
			} );
		}
	} );
	
	$( "#okw-form-subclass-input-create" ).autocomplete( {
		source: function( request, response ) {
			var ontAbbr = $( "#okw-form-ontology" ).val();
			$.ajax( {
				type: "GET",
				url: "api.php",
				data: { action:"okwauto", ontology:ontAbbr, keywords:extractLast( request.term ), format:"json" },
				success: function( data ) {
					var terms = [];
					response( data["okwauto"] );
				}
			} )
		},
		minLength: 3,
		select: function( event, ui ) {
			//$( "#okw-form-subclass-input-label" ).text( ui.item.label );
			var ontAbbr = $( "#okw-form-ontology" ).val();
			var term = ui.item.iri;
			var label = ui.item.label;
			$.ajax( {
				type: "GET",
				url: "api.php",
				data: { action:"okwauto", ontology:ontAbbr, check:term, format:"json" },
				success: function( data ) {
					if ( data["okwauto"]["exist"] ) {
						if ( label == '' ) {
							label = data["okwauto"]["label"];
						}
						$( "#okw-form-subclass-main tr:last" ).html(
							'<td><label class="queue-label">' +
							label +
							'</label></td><td><input name="subclassof[]" class="queue-iri" type="text" value="' +
							term +
							'" readonly><button class="clear" type="button">CLEAR</button></td>'
						);
					} else {
						alert( "Incorrect term input" );
					}
				}
			} );
		}
	} );
} );

