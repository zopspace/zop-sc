<?php
require_once "verify.php";
require_once "csvloader.php";

function alphacomp($v1, $v2){
    $t1 = strtolower($v1->title);
    $t2 = strtolower($v2->title); 
    if ( $t1 == t2) return 0;
    elseif ($t1 > $t2 ) return 1;
    elseif ($t2 > $t1 ) return -1;
}

function filterVideos($video_json){
    $csv = $GLOBALS["csv"]; 
    $vid_json = json_decode($video_json);
    //echo gettype($vid_json);
    $filtered_vids = array();
    
    foreach($vid_json->videos as $vid){
        if ($vid->author_id == 235){
            
            $vid_metadata = in_array_r($vid->uuid, $csv);
            if ($vid_metadata){
                $vid->description = $vid_metadata["Description"];
                $vid->manifest_json->author->name = $vid_metadata["Owner"];
                $vid->tags = $vid_metadata["Tags"];
                $vid->title = $vid_metadata["Title"];
            }
            else {
                $vid->description = "";
                //$vid["manifest_json"]["author"]["name"] = "";
                $vid->tags = "";
                
                //update video title and remove .mp4
                $vidtitle = $vid->title;
                $vidtitle = substr($vidtitle, 0, strpos($vidtitle, ".mp4"));
                $vid->title = $vidtitle;
            }
            
            //error_log("Video Title=>".$vid->title.":".$vidtitle);
            $filtered_vids[] = $vid;
        }
    }
    
    $filtered_result = array("videos" => $filtered_vids);
    return json_encode($filtered_result, JSON_FORCE_OBJECT);
}

$achso_public_url = "https://achrails.herokuapp.com/achrails/en/videos/all.json";
$video_json = file_get_contents($achso_public_url);
//print_r($video_json);
//json_encode($video_json);

//20/sept/2016: for our demo, use filtered vids
$public_json = json_decode(filterVideos($video_json));
//$public_json = json_decode($video_json);

//echo $public_json;
//video id field for playing a single video from home page
//TODO: this link should be allowed to be called directly.
// If nothing is provided in the request, then the user should be redirected to the home page.
// otherwise php empty variables can break the code.

$video_id = isset($_REQUEST['videoId'])? $_REQUEST['videoId']:"";

//fields from create playlist form
$title_q = isset($_REQUEST['titlecontains'])? strtolower($_REQUEST['titlecontains']):"";
$ann_q = isset($_REQUEST['txtcontains'])? strtolower($_REQUEST['txtcontains']):"";
$author_q = isset($_REQUEST['author'])? strtolower($_REQUEST['author']):"";
$tags_q = isset($_REQUEST['tag_input'])? strtolower($_REQUEST['tag_input']):"";
$sort_opt = isset($_REQUEST['orderCtrl'])? strtolower($_REQUEST['orderCtrl']):"";

//retain the original values
$title_q_org = isset($_REQUEST['titlecontains'])? $_REQUEST['titlecontains']:"";
$ann_q_org = isset($_REQUEST['txtcontains'])? $_REQUEST['txtcontains']:"";
$author_q_org = isset($_REQUEST['author'])? $_REQUEST['author']:"";
$tags_q_org = isset($_REQUEST['tag_input'])? $_REQUEST['tag_input']:"";
$sort_opt_org = isset($_REQUEST['orderCtrl'])? $_REQUEST['orderCtrl']:"";


//error_log($author_q + ":" + $author_q_lc);

//check tags and remove the last , from string
//split them and then search for each tag

//echo var_dump($_REQUEST);

$result = array();

foreach($public_json->videos as $video)
{
   if (strlen($video_id) > 0 ){
       if ($video_id == $video->uuid) {
          $result [] = $video;
       }
   }
   else {
    $searchable = strtolower($video->searchable);
    $tag_found = FALSE;
    $title_found = FALSE;
    $descr_found = FALSE;
    $authr_found = FALSE;
    
    if ( strlen($tags_q) > 0 ) {
      $tags = explode(",", $tags_q);
      $vidTags = explode(", ", strtolower($video->tags));
      //print_r($tags);
      //print_r($vidTags);
      $intersection_result = array_intersect($vidTags, array_slice($tags, 0, -1));
      //if some common tag found in video's tag, add the video in search result
      if ( count($intersection_result) > 0 ) {
         //echo "found given tag.";
         $tag_found = TRUE;
      } else {
         $tag_found = FALSE;
      }
    } else {
        $tag_found = TRUE; //coz no tags provided by user
    }
    
    if ( strlen($title_q) > 0 ) {
        $vidtitle = strtolower($video->title);
        if ( strpos($vidtitle, $title_q) !== false )
            $title_found = TRUE;
        else 
            $title_found = FALSE; 
    }else {
        $title_found = TRUE; //coz no title was provied by user
    }
    
    if ( strlen($ann_q) > 0 ) {
        $vid_desc = strtolower($video->description);
        if ( strpos($vid_desc, $ann_q) !== false )
            $descr_found = TRUE;
        else 
            $descr_found = FALSE;
    }else {
        $descr_found = TRUE; //coz no ann/description was provied by user
    }
    
    if ( strlen($author_q) > 0 ) {
        $vid_author = strtolower($video->manifest_json->author->name);
        if ( strpos($vid_author, $author_q) !== false )
            $authr_found = TRUE;
        else 
            $authr_found = FALSE;
    }else {
        $authr_found = TRUE; //coz no author was provied by user
    }
    
    //echo $searchable."\n";
    //old mechanism
    /*if ( (strlen($ann_q) > 0 && strpos($searchable, $ann_q) !== false ) 
                || (strlen($author_q) > 0 && strpos($searchable, $author_q) !== false ) 
         ) {
        $result[]=$video;
    }*/
    //error_log("tag_found=$tag_found :titl_found=$title_found :desc_found=$descr_found :auth_found=$authr_found");
    if ( $tag_found && $title_found &&  $descr_found && $authr_found){
        $result [] = $video;
    }
    } //end of videoid else
}

//echo json_encode($result);
error_log("search result:".count($result));
$result_message = "Video(s) found:".count($result);
if ( count($result) === 0 ) {
   $result = $public_json->videos;
   $result_message = "No valid search filters used.";
}
//print_r($result);
if ( $sort_opt == 'view'){
    usort($result, function($v1, $v2){
        return $v2->views - $v1->views; 
    });
}elseif ($sort_opt == 'recent'){
    usort($result, function($v1, $v2){
        return $v2->created_at - $v1->created_at; 
    });
}elseif ($sort_opt == 'alpha'){
    uasort($result, 'alphacomp');
}

//echo json_encode($result);
$first_video = "";
/*if ( count($result) > 0){
    $first_video = array_shift($result);
}*/
?>

<!--
Author: W3layouts
Author URL: http://w3layouts.com
License: Creative Commons Attribution 3.0 Unported
License URL: http://creativecommons.org/licenses/by/3.0/
-->
<!DOCTYPE HTML>
<html lang="en">
<head>
<title>ZoP - Stokes Croft</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="" />
<meta property="og:url" content="http://zop.space/sc/" />
<meta property="og:image" content="http://zop.space/sc/images/boycot-tesco.jpg" />
<meta property="og:image" content="http://zop.space/sc/images/zoplogo200.png" />
<meta property="og:title" content="Urban Regeneration through culture, activism and risk taking. Create your own Zone of Possibility" />
<meta property="og:description" content="Hamilton House, PRSC, Cafe Kino and the Cube Microplex are all Zones of Possibility and together they make Stokes Croft a great case study for regeneration from the ground-up." />
<meta property="fb:app_id" content="966242223397117" />

<!-- bootstrap -->
<link href="../css/bootstrap.zop-sc.css" rel='stylesheet' type='text/css' media="all" />
<!-- //bootstrap -->
<link href="../css/dashboard.css" rel="stylesheet">
    
<!-- FlowPlayer -->
<!-- 1. skin -->
<link rel="stylesheet" href="http://releases.flowplayer.org/6.0.5/skin/functional.css">
<!-- 3. flowplayer -->
<script src="http://releases.flowplayer.org/6.0.5/flowplayer.min.js"></script>
    
<!-- Custom Theme files -->
<link href="../css/style.css" rel='stylesheet' type='text/css' media="all" />
<script src="../js/jquery-1.11.1.min.js"></script>

<!-- google plus link -->
<script src="https://apis.google.com/js/platform.js" async defer></script>

<!-- for all pop-up-boxes -->
<script type="text/javascript" src="../js/modernizr.custom.min.js"></script>    
<link href="../css/popuo-box.css" rel="stylesheet" type="text/css" media="all" />
<script src="../js/jquery.magnific-popup.js" type="text/javascript"></script>
<!--//pop-up-box -->
<script>
    $(document).ready(function() {
        $('.popup-with-zoom-anim').magnificPopup({
				type: 'inline',
				fixedContentPos: false,
				fixedBgPos: true,
				overflowY: 'auto',
				closeBtnInside: true,
				preloader: false,
				midClick: true,
				removalDelay: 300,
				mainClass: 'my-mfp-zoom-in'
        });																				
    });
</script>
<!-- fonts -->
<link href='//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800' rel='stylesheet' type='text/css'>
<link href='//fonts.googleapis.com/css?family=Poiret+One' rel='stylesheet' type='text/css'>
<!-- //fonts -->
<style>
    body {
        padding-top: 60px;
        /* Required padding for .navbar-fixed-top. Remove if using .navbar-static-top. Change if height of navigation changes. */
    }
    .is_active{
        border: 1px solid rgb(255,0,0);
        float: right;
    	width:100%;
    }
</style>
<!--script to show and hide welcome panel and replace with search panel plus the tag colour code-->
<script>
$(document).ready(function(){
    $("#welcome").click(function(){
    	$("#search-panel").hide();
		$("#search-parameters").show();
    });
    $(".search").click(function(){
        //$("#search-panel").show();
		//$("#welcome-panel").hide();
		$("#search-panel").toggle();
		$("#search-parameters").toggle();
    });

    $("#tag_input").val(""); //initialize the hidden field with empty value
	var num = 0;
	var limit = 15;
	$("#tagList").change(function(){
		if (limit > num) {
      		num	+= 1;
      	}
      	else {
      		num	-= 14;
      	}
    	var tag = $(this).val();
    	var labelId = tag.toLowerCase().replace(" ","_");
    	//already selected
    	if ( $("#tag_"+labelId).length > 0){
    		$.notify(tag + " already selected.", "info");
    		return;
    	}
    	var tagLabel = "<span id='tag_" + labelId+"' class='tagLabel tag"+ num+"'>" + tag +"</span>";
    	$("#selectedTags").append(tagLabel);
    	
    	//add selected tag to search form
    	$("#tag_input").val($("#tag_input").val() + tag + ",");
    });
});
</script>
</head>
  <body>
<!--Header and Navigation-->
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          		<button type="button" id="dropper" class="navbar-toggle collapsed right-pad" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
		            <span class="sr-only">Toggle navigation</span>
		            <span class="icon-bar"></span>
		            <span class="icon-bar"></span>
		            <span class="icon-bar"></span>
          		</button>
          		<a class="navbar-brand" href="../index.html"><img src="../images/zoplogotemp160.png" height="65" width="72" class="logo" alt="ZoP - Stokes Croft" /></a>
          		<p class="header10">STOKES CROFT</p>
          	<!--Dropdown menu-->
			<!-- Help popup -->
			<div id="small-dialog5" class="mfp-hide">
			<h3>Help</h3>
			<h4>This is a demo website so it doesn't have full functionality.</h4> 
				<div class="help-grid">	
				</div>
				<div class="help-grids">
					<div class="help-button-bottom">
						<h5>Rather than offer you help here, we'd rather that you'd let us know via the feedback page all those things you didn't understand or found hard to do.</h5>
						<h5>Thanks for your help.</h5>
					</div>
				</div>
			</div>
			<!-- Author popup -->
			<div id="author-popup" class="mfp-hide">
				<h3>Author Profile</h3>
				<h4>On the finished site clicking on the author name will take you to a profile page.</h4>
				<h4>For now, you can see all the videos by this author by using the Create Playlist button.</h4> 
			</div>
			<!-- tag popup -->
			<div id="tag-popup" class="mfp-hide">
				<h3>Clicking Tags</h3>
				<h4>Functionality not yet enabled.</h4>
				<h4>For now, you can see all the videos with this tag by using the Create Playlist button.</h4> 
			</div>
			
          	<div class="drop-navigation">
				  <ul class="nav nav-sidebar">
					<li><a href="index.html" class="home-icon"><span class="glyphicon glyphicon-home" aria-hidden="true"></span>Home</a></li>
					<li><a href="http://zop.space/sc/contribute.html"><span class="glyphicon glyphicon-home glyphicon-blackboard" aria-hidden="true"></span>Contribute</a></li>
					<li><a href="https://zopspace.onlinesurveys.ac.uk/zop-space-stokes-croft" target="blank" class="user-icon"><span class="glyphicon glyphicon-home glyphicon-blackboard" aria-hidden="true"></span>Feedback</a></li>
					<li><a href="http://zop.space/sc/about.html" class="sub-icon"><span class="glyphicon glyphicon-home glyphicon-hourglass" aria-hidden="true"></span>About</a></li>
			<!--	<li><a href="#small-dialog" class="play-icon popup-with-zoom-anim"><span class="glyphicon glyphicon-home glyphicon-hourglass" aria-hidden="true"></span>My-ZoP</a></li>-->
					<li><a href="#small-dialog5" class="play-icon popup-with-zoom-anim f-history f-help"><span class="glyphicon glyphicon-home glyphicon-hourglass" aria-hidden="true"></span>Help</a></li>
					<li class="active"><a class="search" href="javascript:void();"><span class="glyphicon glyphicon-search"></span></a></li>
						<!-- script-for-menu -->
						<script>
							$( "#dropper" ).click(function() {
							$( ".drop-navigation" ).slideToggle( 300, function() {
							// Animation complete.
							});
							});
						</script>
				  </ul>
			</div>
       	</div>
        <div id="navbar" class="navbar-collapse collapse">
			<div class="header-top-right">
              		<ul class="nav navbar-nav double">    
                		<li><a href="http://zop.space/sc/index.html">Home</a></li>
                		<li><a href="http://zop.space/sc/contribute.html">Contribute</a></li>
                		<li><a href="https://zopspace.onlinesurveys.ac.uk/zop-space-stokes-croft" target="blank">Feedback</a></li>
                		<li><a href="http://zop.space/sc/about.html">About</a></li>
            <!--   		<li><a href="#small-dialog" class="play-icon popup-with-zoom-anim f-help">My-ZoP</a></li>-->
                		<li><a href="#small-dialog5" class="play-icon popup-with-zoom-anim f-history f-help">Help</a></li>
                		<li><a class="search" href="javascript:void();"><span class="glyphicon glyphicon-search"></span></a></li>
              		</ul>
				<div class="clearfix"> </div>
			</div>
        </div>
		<div class="clearfix"> </div>
      </div>
    </nav>  
    <!--End of Header and Navigation-->
    	<!--Page Content-->
        <div class="col-sm-12">
<!-- search panel -->
				<div id="search-panel">
				<form method="post" id="search_form">
				<input type="hidden" id="tag_input" name="tag_input" value="" />
				<h3>Use the search tool to create a playlist</h3>
					<div id="search-panel-pad">
							<div class="col-xs-3">
								<div class="search-left">
									<p class="para11">Tags</p>
								</div>
							</div>
						
							<div class="col-xs-9">
								<div class="search-right">
										<ul>
											<li>
												<select class="pulldown form-control" id="tagList" name="tagList">
													<option value="" selected="true">Add tags</option>
													<option>Activism</option>
													<option>Art</option>
													<option>Capitalism</option>
													<option>Corporations</option>
													<option>Council</option>
													<option>Craft</option>
													<option>Crime</option>
													<option>Enterprise</option>
													<option>Environment</option>
													<option>Food</option>
													<option>Funding</option>
													<option>Full Video</option>
													<option>Gentrification</option>
													<option>Getting Started</option>
													<option>Globalisation</option>
													<option>Graffiti</option>
													<option>Health</option>
													<option>Homelessness</option>
													<option>Internet</option>
													<option>Mental Health</option>
													<option>Music</option>
													<option>Nightlife</option>
													<option>Property</option>
													<option>Pottery</option>
													<option>Riots</option>
													<option>Strategy</option>
													<option>Vegan</option>
												</select>
											</li>
										</ul>
								</div>
								<div id="selectedTags"></div>
							</div>
						
							<div class="clearfix"> </div>
						
						
						<div class="col-xs-3">
							<div class="search-left">
								<p class="para11">Title contains</p>
							</div>
						</div>
						<div class="col-xs-9">
							<div class="search-right">
  									<input type="text" placeholder="Enter words from Title" class="textinput form-control" 
  									value="<?=$title_q?>" id="titlecontains" name="titlecontains">
							</div>
						</div>
						<div class="clearfix"> </div>
						
						<div class="col-xs-3">
							<div class="search-left">
								<p class="para11">Text contains</p>
							</div>
						</div>
						<div class="col-xs-9">
							<div class="search-right">
  						        <input type="text" placeholder="Enter words from Description/Comments" class="textinput form-control" 
  						         value="<?=$ann_q?>" id="txtcontains" name="txtcontains">
							</div>
						</div>
						<div class="clearfix"> </div>
						
						
						<div class="col-xs-3">
							<div class="search-left">
								<p class="para11">Owner</p>
							</div>
						</div>
						<div class="col-xs-9">
							<div class="search-right">
								<ul>
									<li>
										<select class="pulldown form-control" id="author_dropdown" name="author">
											<option value="" >Select Owner</option>
											<!-- option>Person 1</option>
											<option>Person 2</option>
											<option>Person 3</option -->
										</select>
									</li>
								</ul>
							</div>
						</div>
						<div class="clearfix"> </div>
						
						<div class="col-xs-3">
							<div class="search-left">
								<p class="para11">Order by...</p>
							</div>
						</div>
						<div class="col-xs-4">
							<div class="search-right">
								<ul>
									<li>
										<select id="orderCtrl" class="pulldown form-control" name="orderCtrl">
											<!--option value='view'>Most viewed</option-->
											<!-- option value='like'>Most liked</option -->
											<option value='recent'>Most recent</option>
											<option value='alpha'>Alphabetical</option>
										</select>
									</li>
								</ul>
							</div>
						</div>
						<div class="col-xs-5">
							<div class="search-right">
						<!--		<div class="search-click"><a id="welcome" href="#" onClick="return ZopSpace.search();">Create playlist locally</a> -->
								</div>
                                <div class="search-click"><a id="welcome" href="#" onClick="return ZopSearch.submit();">Create playlist</a>
								</div>
							</div>
						</div>
						<div class="clearfix"> </div>	
					</div>
                    </form>
				</div>
<!-- //search panel -->        
        
			<div class="show-top-grids">
				<div id="search-parameters" class="col-lg-12 col-sm-12 col-md-12">
					<div class="create">
						<p class="header20">Your playlist was created from:</p>
						<div class="inline-search"> <!--was span-->
							<div class="dummyTag">
							<?php
							if ( strlen($video_id) > 0 ){
							   echo '<span class="header12">Video selection on Home page</span>';
							}
						//if video id is given then we don't need to check these
						if ( strlen($video_id) === 0 ) {
							if (strlen($tags_q) > 0){
							  $tags_q = array_map('ucfirst', explode(",", substr($tags_q,0,-1)) );
							  $limit = 15;
							  $num=0;
							  echo "";
							  foreach($tags_q as $tag){
								if ( $limit > $num){
									$num += 1;
								} else {
									$num -= 14;
								}
								echo '<div class="tagLabel tag'.$num.'">'.$tag.'</div>';
							  } //end of foreach
							}
						}// end of video_id length check
							?>
							</div>
						<div class="clear"></div>

						<p class="header12">&nbsp;
						<?
						if ( strlen($video_id) === 0 ) {
						if ( strlen($title_q) > 0 
						    || strlen($ann_q) > 0 
						    || strlen($author_q) > 0
						    ){
						
						    /*$tags_q = implode(",", array_map('ucfirst', explode(",", substr($tags_q,0,-1) )));
						    if (isset($tags_q) && strlen($tags_q) > 0)
						       echo "<strong>Tags: </strong>".$tags_q."</br>";
						       */
						    if (isset($title_q) && strlen($title_q) > 0)
						       echo "<strong>Title words: </strong>".$title_q_org."&nbsp;|&nbsp;";
						    if (isset($ann_q) && strlen($ann_q) > 0)
						       echo "<strong>Text words: </strong>".$ann_q_org."&nbsp;|&nbsp;";
						    if (isset($author_q) && strlen($author_q) > 0)
						       //echo "<strong>Owner: </strong>".ucfirst($author_q)."&nbsp;|&nbsp;";
						       echo "<strong>Owner: </strong>".$author_q_org."&nbsp;|&nbsp;";
						} 
						
						if ((is_array($tags_q) && count($tags_q)<1) && strlen($title_q) < 1 
						    && strlen($ann_q) < 1 && strlen($author_q) < 1) {
						    echo "<strong>No filters provided.</strong>";
						    
						}
						
						if ( strlen($result_message) > 1 ) {
						       echo "<strong>".$result_message."</strong>";
						}
						} //end of video_id length check
						?>
                        </p></div><!--was span-->
						<div class="clearfix"> </div>
						<div class="search-click"><a id="edit" href="#" onClick="return ZopSearch.editForm();">Edit playlist</a>
						</div>
					</div>
				</div>
			
				<div class="col-md-8 single-left">
				    <h3 id="vidTitle">.</h3>
				    <div class="sang">
				        <div class="js-video">
				            <div class="flowplayer"></div>
				        </div>
				    </div>
					
					<div class="song-grid-right">
						<div class="share">
						<br>
							<h5>Share this</h5>
							<ul>
								<li><a href="https://www.facebook.com/sharer/sharer.php?u=http://zop.space/sc/" class="icon fb-icon">Facebook</a></li>
								<li><a href="https://twitter.com/home?status=http://zop.space/sc/" class="icon twitter-icon">Twitter</a></li>
								<li><a href="#" class="icon pinterest-icon">Pinterest</a></li>
								<li><a href="https://plus.google.com/share?url=http://zop.space/sc/" class="icon google-plus-icon">Google+</a></li>
							</ul>
						</div>
					</div>
					<div class="clearfix"> </div>
					<div class="published">
						<div class="load_more">	
							<ul id="myList">
								<?php 
								$vidid=0;
								foreach($result as $video){ ?>
                                <li id="li-<?= $vidid ?>">
									<h4>Published on <?= $video->manifest_json->uploadedAt ?> by <a id="author_popuplink" href="#author-popup" class="play-icon popup-with-zoom-anim f-history f-help"><?= $video->manifest_json->author->name ?></a></h4>
									<!-- p><?php print_r($video); ?> </p -->
									<h5><?=$video->description?> </h5>
							<!--		<p class="author author-info"><a href="#" class="author">Uploaded by <?= $video->manifest_json->author->name ?></a></p>
									 p>Annotations: <?= count($video->manifest_json->annotations) ?></p -->
								</li>
                                <?php 
                                 $vidid += 1;
                                } 
                                ?>
							</ul>
						</div>
					</div>
					<br>
					<div class="tagbar"></div>
					
					<div class="all-comments">
						<p>Please add you comments to this video</p>
						<div class="all-comments-info">
							<a href="#" id="comments_count"></a>
							<div class="box">
								<form id="disc_form" data-url="discussion_action.php">
									<input name="disc_user" type="text" placeholder="Name" required=" ">
									<input id="frm_video_id" name="video_id" type="hidden" value="" />
									<textarea name="disc_body" placeholder="Message" required=" "></textarea>
									<input type="submit" value="SEND" onClick="return DiscussionController.submitComment();">
									<div class="clearfix"> </div>
								</form>
							</div>
							<!-- div class="all-comments-buttons">
								<ul>
									<li><a href="#" class="top">Top Comments</a></li>
									<li><a href="#" class="top newest">Newest First</a></li>
									<li><a href="#" class="top my-comment">My Comments</a></li>
								</ul>
							</div -->
						</div>
						<div class="media-grids">
							
						</div>
					</div>
				</div>
				<div class="col-md-4 single-right">
					<h3>Up Next</h3>
					<ul class="single-grid-right" style="list-style-type: none;">
						    
						<?php 
                          $vidid=0;
                          foreach($result as $video) {
                        ?>
                        <li id="vidd<?=$vidid?>" class="listing">
                        
                        <div id="vid<?= $vidid ?>" data-url="<?= $video->manifest_json->videoUri ?>" data-tags="<?=$video->tags?>" 
                             data-vidid="<?= $video->uuid ?>" class="single-right-grids">
                         </div>
                                                 
						<div class="col-sm-4 single-right-grid-left">
							<div class="js-video-bg">
								<a href="#" class="dummyvid" data-index="<?=$vidid?>"><img class="singleimage" src="<?= $video->manifest_json->thumbUri ?>" alt="" /></a>
							</div>
						</div>
						<div class="col-sm-8 single-right-grid-right">
							<a href="#" class="dummyvid" data-index="<?=$vidid?>"><p class="header90"><?= $video->title ?></p></a>
							<a id="author_popuplink" href="#author-popup" class="play-icon popup-with-zoom-anim f-history f-help"><h5><?= $video->manifest_json->author->name ?></h5></a>
						</div>
							
						
						</li>
						
						<!-- li -->
                        <?php 
                          $vidid += 1;
                        } ?>
						<!--/li-->
					</ul>
				</div>
				<div class="clearfix"> </div>
			</div>
			<!-- footer -->
				<div class="footer">
					<div class="footer-grids">
						<div class="footer-top">
							<div class="footer-bottom-nav">
								<ul>
									<li><a href="../privacy.html">Terms &amp; Conditions</a></li>
									<li><a href="../privacy.html">Privacy</a></li>
									<li><a href="../privacy.html">Cookies</a></li>
								</ul>
							</div>
						</div>
						<div class="footer-bottom">
							<div class="col-lg-1 col-md-2 col-xs-3">
									<img src="../images/Cc-by-nc_icon.svg.png" width="100%" height="100%" alt="CC License">
							</div>
								<div class="col-lg-11 col-md-10 col-xs-9">
									<p class="para10">Licenses may copy, distribute, display and perform the work and make derivative works and remixes based on it only if they give the author or licensor the credits (attribution) in the manner specified by these.</p>		
							</div>
						</div> 
						
					</div>
				</div><!-- //footer -->
		</div>
		<div class="clearfix"> </div>
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="../js/bootstrap.min.js"></script>
    <!-- Just to make our placeholder images work. Don't actually copy the next line! -->
    <script src="js/discussionController.js"></script>
    <script src="js/playlistController.js"></script>
    <script src="js/zopsearch.js"></script>
   
    <script>
        <?php if (count($result) > 0 ){ ?>
        PlaylistController.loadPlaylist();
        PlaylistController.attachVideoEvent();
        <?php } ?>
        
          $(".single-right-grids").on("click", function(){
              var vidURL = $(this).data("url");
              console.log(vidURL); 
              //loadVideoUrl(vidURL); 
          });
          
          //load author list and also set it to the previous selected value
          ZopSearch.loadAuthorList("<?=$author_q_org?>");
          
    </script>
    <script src="http://zop.space/sc/php/js/notify.min.js"></script>
    
        <!-- Google Analytics Script -->
    <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-84915946-1', 'auto');
  ga('send', 'pageview');

</script>  
  </body>
</html>