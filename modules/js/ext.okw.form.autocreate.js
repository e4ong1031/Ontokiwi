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
 * @file ext.okw.form.autocreate.js
 * @author Edison Ong
 * @since Aug 01, 2015
 * @comment 
 */

$( function() {
	function ChangeURL(page, url) {
	    if (typeof (history.pushState) != "undefined") {
	        var obj = { Page: page, Url: url };
	        history.pushState(obj, obj.Page, obj.Url);
	    } else {
	        alert("Browser does not support HTML5.");
	    }
	};
	
	$( "#okw-describe-ontology" ).on( "change", function() {
		var ontAbbr = $(this).val();
		$( "#okw-form-ontology" ).val( ontAbbr );
	
		$.ajax( {
			type: "GET",
			url: "api.php",
			data: { action:"okwcreate", ontology:ontAbbr, format:"json"},
			success: function( data ) {
				$( "#okw-describe-iri-input" ).val( data["okwcreate"]['iri'] );
				var title = data["okwcreate"]["title"];
				//console.log(mw.config.get( ['wgTitle','wgPageName']));
				mw.config.set(
					{
						"wgPageName":title,
						"wgTitle":title,
					}
				);
				$( "#firstHeading" ).text( title );
				//console.log(mw.config.get( ['wgTitle','wgPageName']));
				ChangeURL( title, "index.php?title=" + title + "&action=formedit" );
			}
		} );
	} );
	
	$( "#okw-describe-iri-input" ).on( "change", function() {
		var ontAbbr = $( "#okw-form-ontology" ).val();
		var iri = $( this ).val();
		var id = iri.split("/").pop();
		var title = ontAbbr + ':' + id;
		//console.log(mw.config.get( ['wgTitle','wgPageName']));
		mw.config.set(
			{
				"wgPageName":title,
				"wgTitle":title,
			}
		);
		$( "#firstHeading" ).text( title );
		//console.log(mw.config.get( ['wgTitle','wgPageName']));
		ChangeURL( title, "index.php?title=" + title + "&action=formedit" );
	} );
} );
	