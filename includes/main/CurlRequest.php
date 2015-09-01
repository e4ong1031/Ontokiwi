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
 * @file CurlRequest.php
 * @author Edison Ong
 * @since Jul 01, 2015
 * @version 1.0
 * @comment 
 */

namespace OKW;

class CurlRequest {

	public static function curlPostContents( $url, $fields, $showQuery = true ) {
		
		if ( $showQuery ) {
			wfDebugLog( 'OntoKiWi', sprintf( "OKW\CurlRequest: SPARQL Query:\n\t%s", preg_replace( '/[\n\r]/', "\n\t", $fields['query'] ) ) );
		}
		
		$request = curl_init();

		$fieldsQuery = http_build_query( $fields );

		curl_setopt($request, CURLOPT_URL,$url);
		curl_setopt($request, CURLOPT_POST,count($fields));
		curl_setopt($request, CURLOPT_POSTFIELDS,$fieldsQuery);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request, CURLOPT_TIMEOUT, 120);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($request, CURLOPT_TIMEOUT, 30);

		$result = curl_exec( $request );

		if ( $result === false ) {
			wfDebugLog( 'OntoKiWi', sprintf( 'OKW\CurlRequest: CURL Error: %s', curl_error($request) ) );
		}

		curl_close($request);
		
		return(trim($result));
	}

}

?>