<?php
error_reporting(E_ALL); 
ini_set( 'display_errors','0');


$USERNAME = 'admin';
$PASSWORD = '123456';

//digest login request authentication
$login = 'curl -I --digest -u '.$USERNAME.':'.$PASSWORD.'  http://10.10.90.248//digest/frmUserLogin';
$output = shell_exec ($login);

//get and store session value
preg_match('/S-HASH: (.*)/', $output, $s_hash);
preg_match('/C-HASH: (.*)/', $output, $c_hash);
preg_match('/X-HASH: (.*)/', $output, $x_hash);
$s_hash = $s_hash[1];
$c_hash = $c_hash[1];
$x_hash = $x_hash[1];

//do range-datetime photo info requests
$params_handle = '{"""Type""":0,"""Data""":{"""Channels""":[1],"""BeginDateTime""":""2020-05-14 00:00:00"","""EndDateTime""":""2020-05-14 23:59:59"","""SearchType""":0,"""TrafficType""":0,"""SreachNum""":1000,"""Id""":""""","""Name""":""""","""FuzzySearch""":0}}';
$query_date = 'curl --digest -u '.$USERNAME.':'.$PASSWORD.' -H "S-HASH:'.$s_hash.'"  -H "C-HASH:'.$c_hash.'" -H "X-HASH:'.$x_hash.'"  -d "'.$params_handle.'"   http://10.10.90.248//digest//frmTrafficPeople';
$second_request = shell_exec($query_date);
$fetch =  json_decode($second_request,true);
//get and store resulthandle object ID
foreach($fetch as $data) {
    $handle_id = $data['ResultHandle'];
}
//get search result row count
$params_handle = '{"""Type""":5,"""Data""":{"""ResultHandle""":'.$handle_id.'}}';
$query_count = 'curl --digest -u '.$USERNAME.':'.$PASSWORD.' -H "S-HASH:'.$s_hash.'"  -H "C-HASH:'.$c_hash.'" -H "X-HASH:'.$x_hash.'"  -d "'.$params_handle.'"   http://10.10.90.248//digest//frmTrafficPeople';
$count_request = shell_exec($query_count);
$fetch_count =  json_decode($count_request,true);

//get and store total fetched rows count
foreach($fetch_count as $data) {
    $tot_count = $data['TotalSearchNum'];
}

for ($i = 0; $i < $tot_count; $i ++) {
//get photo info using current object ID and offset number
$params_photo = '{"""Type""":6,"""Data""":{"""ResultHandle""":'.$handle_id.',"""Offset""":'.$i.',"""Num""":1}}';
$query_photo = 'curl --digest -u '.$USERNAME.':'.$PASSWORD.' -H "S-HASH:'.$s_hash.'"  -H "C-HASH:'.$c_hash.'" -H "X-HASH:'.$x_hash.'"  -d "'.$params_photo.'"   http://10.10.90.248//digest//frmTrafficPeople';

$third_request = shell_exec($query_photo);
$fetch_data =  json_decode($third_request,true);
//get Picture Data
foreach($fetch_data as $data) {
    $faceresult = $data['FaceResults'];
}
foreach($faceresult as $el) {
    $registration_info = array();
    $face_image = $el['FaceData'];
    $face_score = array('FaceScore' => $el['FaceScore']);
    $temperature = array('Temperature' => $el['Temp']);
    $datetime = array('Datetime' => $el['Time']);

    $uniqid = uniqid();
    array_push($registration_info,  $datetime, $face_score, $temperature);
//convert base64 image to final format and export
file_put_contents('C:/xampp/htdocs/digest/Photos/'.$uniqid.'.jpg', file_get_contents('data:image/jpg;base64,'.$face_image));
//save photo info as json file
file_put_contents('C:/xampp/htdocs/digest/RegistrationInfo/'.$uniqid.'.json', json_encode($registration_info));
}
}
?>
