<?php
/*
 Global variables
*/
date_default_timezone_set('Asia/Calcutta');
$TIMETREX_URL = 'http://192.168.59.203:8085/api/json/api.php';
$TIMETREX_USERNAME = 'mithun';
$TIMETREX_PASSWORD = 'qwerty1234';
function buildURL( $class, $method, $session_id = FALSE ) {
	global $TIMETREX_URL, $TIMETREX_SESSION_ID;
	$url = $TIMETREX_URL.'?Class='.$class.'&Method='.$method;
	if ( $session_id != '' OR $TIMETREX_SESSION_ID != '' ) {
		if ( $session_id == '' ) {
			$session_id = $TIMETREX_SESSION_ID;
		}
		$url .= '&SessionID='.$session_id;
	}

	return $url;
}

//Handle complex result.
function handleResult( $result, $raw = FALSE ) {
	if ( is_array($result) AND isset($result['api_retval'])) {
		if ( $raw === TRUE ) {
			return $result;
		} else {
			if ( $result['api_retval'] === FALSE ) {
				//Display any error messages that might be returned.
				$output[] = '  Returned:';
				$output[] = ( $result['api_retval'] === TRUE ) ? '    IsValid: YES' : '    IsValid: NO';
				if ( $result['api_retval'] === TRUE ) {
					$output[] = '    Return Value: '. $result['api_retval'];
				} else {
					$output[] = '    Code: '. $result['api_details']['code'];
					$output[] = '    Description: '. $result['api_details']['description'];
					$output[] = '    Details: ';

					$details = $result['api_details']['details'];
					if ( is_array($details) ) {
						foreach( $details as $row => $detail ) {
							$output[] = '      Row: '. $row;
							foreach( $detail as $field => $msgs ) {
								$output[] = '      --Field: '. $field;
								foreach( $msgs as $key => $msg ) {
									$output[] = '      ----Message: '. $msg;
								}
							}
						}
					}
				}
				
			}

			return $result['api_retval'];
		}
	}

	return $result;
}

//Post data (array of arguments) to URL
function postToURL( $url, $data, $raw_result = FALSE ) {
	$curl_connection = curl_init( $url );
	curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 600 );
	curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, FALSE );
	curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt($curl_connection, CURLOPT_REFERER, $url ); 
	$post_data = 'json='.urlencode( json_encode($data) );
	curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_data );
	$result = curl_exec($curl_connection);
	curl_close($curl_connection);

	return handleResult( json_decode($result, TRUE ), $raw_result );
}

$arguments = array('user_name' => $TIMETREX_USERNAME, 'password' => $TIMETREX_PASSWORD );
$TIMETREX_SESSION_ID = postToURL( buildURL( 'APIAuthentication', 'Login' ), $arguments );
if ( $TIMETREX_SESSION_ID == FALSE ) {
	exit;
}

$status_id = 0;
$type_id = 0;
$time_stamp = date("d-m-Y h:ma");

if (date("h") == "9" && date("a")=="am") {
	$GLOBALS['status_id'] = 10;
	$GLOBALS['type_id'] = 10;
	$GLOBALS['time_stamp'] = date("d-m-Y h:ma");

} elseif (date("h") == "1" && date("a")=="pm") {
	$GLOBALS['status_id'] = 20;
	$GLOBALS['type_id'] = 20;
	$GLOBALS['time_stamp'] = date("d-m-Y h:ma");

}elseif (date("h") == "2" && date("a")=="pm") {
	$GLOBALS['status_id'] = 10;
	$GLOBALS['type_id'] = 20;
	$GLOBALS['time_stamp'] = date("d-m-Y h:ma");
} 
else {
	$GLOBALS['status_id'] = 20;
	$GLOBALS['type_id'] = 10;
	$GLOBALS['time_stamp'] = date("d-m-Y h:ma");

}
$arguments = array( 'filter_data' => array('user_name' => 'mithun',));
$user_data = postToURL( buildURL( 'APIUser', 'getUser' ), array( $arguments ) );
$user_id = $user_data[0]['id'];
$punch_data = array('user_id' => $user_id,
					'type_id' => $type_id, // 10 -> Normal, 20 -> lunch
					'status_id' => $status_id, //10 -> In, 20->out 
					'time_stamp' => $time_stamp, // 5:30 ahead logging 
					);
$result = postToURL( buildURL( 'APIPunch', 'setPunch' ), array( $punch_data ) );
?>
