<?php
require_once "csvloader.php";

function filterVideos($video_json){
    $csv = $GLOBALS["csv"];    
    $vid_json = json_decode($video_json, TRUE);
    //echo gettype($vid_json);
    $filtered_vids = array();

    foreach($vid_json["videos"] as $vid){
        if ($vid["author_id"] == 235) {
            //print $vid["uuid"]."\n";
            $vid_metadata = in_array_r($vid["uuid"], $csv);
            //error_log($vid_metadata["Description"]);
            if ($vid_metadata){
                $vid["description"] = $vid_metadata["Description"];
                $vid["manifest_json"]["author"]["name"] = $vid_metadata["Owner"];
                $vid["tags"] = $vid_metadata["Tags"];
                $vid["title"] = $vid_metadata["Title"];
            }
            else {
                $vid["description"] = "";
                //$vid["manifest_json"]["author"]["name"] = "";
                $vid["tags"] = "";
                $vidtitle = $vid["title"];
                $vidtitle = substr($vidtitle, 0, strpos($vidtitle, ".mp4"));
                $vid["title"] = $vidtitle;
                //error_log($vidtitle);
            }
            
            $filtered_vids[] = $vid;
        }
    }
    
    //print_r($filtered_vids);
    $filtered_result = array("videos" => $filtered_vids);
    //print_r($filtered_result["videos"]);
    return $filtered_result;
}

$achso_public_url = "https://achrails.herokuapp.com/achrails/en/videos/all.json";
$video_json = file_get_contents($achso_public_url);
//print_r($video_json);

//for demo (filtered videos only) use the following line
//filterVideos($video_json); //to test this function
$encoded_output = json_encode(filterVideos($video_json), JSON_UNESCAPED_UNICODE);
//$encoded_output = json_encode(filterVideos($video_json));

//print_r($encoded_output);
//error_log($encoded_output);
echo $encoded_output;

//echo json_encode($video_json);


?>