<?php
function get_access_token(){
	
	$client_id = '';
	$client_secret = '';
	
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.helpscout.net/v2/oauth2/token?grant_type=client_credentials&client_id='.$client_id.'&client_secret='.$client_secret,
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'POST',
	));

	$response = curl_exec($curl);

	curl_close($curl);
	echo "<p>Getting access token, response:</p>";
	echo "<pre>";
	var_dump($response);
	echo "</pre>";
	return $response;
}

$response_arr = json_decode(get_access_token(), true);

$token_type = $response_arr['token_type'];
$token = $response_arr['access_token'];
$expire_in = $response_arr['expires_in'];

echo 'Token Type : '.$token_type.'<br>';
echo 'Token : '.$token.'<br>';
echo 'Expire in : '.$expire_in.'<br>';

global $auth_token;
$auth_token = $token;

function get_conversation_list($page){
	global $auth_token;
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://api.helpscout.net/v2/conversations?status=all&page='.$page.'&query=(attachments:true)',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
	  CURLOPT_HTTPHEADER => array(
		'Content-Type: application/json',
		'Authorization: Bearer '.$auth_token
	  ),
	));

	$response = curl_exec($curl);

	curl_close($curl);
	echo "<p>Getting conversion list for page : #".$page.", response:</p>";
	echo "<pre>";
	//var_dump($response);
	echo "</pre>";
	return $response; 
}

$response_arr = array();
$start = 1;
$end = 1500;

for($page_no=$start;$page_no<=$end;$page_no++){
	
	$response = get_conversation_list($page_no);
    $response_arr[$page_no] = $response;
	
    $file_name = 'conversation_page_' . $page_no . '.json';
    $file_path = dirname(__FILE__) . '/conversation-page-x/' . $file_name;
    //file_put_contents($file_path, $response);
	
	$con_list_arr = json_decode($response,true);
	foreach($con_list_arr['_embedded']['conversations'] as $con){
		$id = $con['id'];
		//$id = 2409438388;
		$single_con_json = get_single_conversation($id);
		
		$file_name1 = 'conversation-' . $id . '.json';
		$single_folder_name = "/single-conversations/single-conversations-page-".$page_no;
		if (!file_exists(dirname(__FILE__) . $single_folder_name)) {
			mkdir(dirname(__FILE__) . $single_folder_name, 0777, true);
		}
		$file_path1 = dirname(__FILE__) ."/". $single_folder_name."/". $file_name1;
		//file_put_contents($file_path1, $single_con_json);
	    $json_array = json_decode($single_con_json,true);
        $threads = $json_array["_embedded"]["threads"];
        
        foreach($threads as $thread){
            if(!empty($thread["_embedded"]["attachments"])){
                $t_att =     $thread["_embedded"]["attachments"];
                $attachment_folder_name = "/attachments-page-".$start.'-'.$end;
		        
                if (!file_exists(dirname(__FILE__) . $attachment_folder_name)) {
        			mkdir(dirname(__FILE__) . $attachment_folder_name, 0777, true);
        		}
        		
                foreach($t_att as $tatt){
                    $web_url = $tatt["_links"]["web"]["href"];
                    if(!empty($web_url)){
                        $attachments_path = dirname(__FILE__) . "/".$attachment_folder_name."/".$tatt["filename"];
                 		copy($web_url,$attachments_path);
                		//file_put_contents($attachments_path, file_get_contents($web_url));
                    }
                }
            }
        }

	//	break;
	}
}

echo '<script>alert("Thai gayu");</script>';

function get_single_conversation($conversation_id){
	$curl = curl_init();
    global $auth_token;
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.helpscout.net/v2/conversations/'.$conversation_id.'/?embed=threads',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $auth_token
      ),
    ));
    
    $response = curl_exec($curl);
    
    curl_close($curl);
    echo "<p>Getting single conversion for conversion : #".$conversation_id.", response:</p>";
    echo "<pre>";
	//var_dump($response);
	echo "</pre>";
    return $response;

}


function get_collections(){
	
    $docs_api_key = '';
	
	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => 'https://docsapi.helpscout.net/v1/collections',
	  CURLOPT_RETURNTRANSFER => true,
	  CURLOPT_ENCODING => '',
	  CURLOPT_MAXREDIRS => 10,
	  CURLOPT_TIMEOUT => 0,
	  CURLOPT_FOLLOWLOCATION => true,
	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	  CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_USERPWD =>  $docs_api_key . ':' . 'X'
	));

	$response = curl_exec($curl);

	curl_close($curl);
	echo "<p>Getting document collections, response:</p>";
	echo "<pre>";
	var_dump($response);
	echo "</pre>";
	return $response;

}
//get_collections();
?>
