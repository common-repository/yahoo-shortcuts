<?php

if (!class_exists('Services_JSON')) {
  require_once(ABSPATH.PLUGINDIR.'/yahoo-shortcuts/JSON.php');
 }

if($_POST['newSearch']) {
  echo(yfsc_get_pix());
  die;
 }

define ('FLICKR_KEY', 'c70842e0f6dfa62c7555dd73396a33fe');
define ('PATH', 'http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction');


function yfsc_get_pix($entities) {
  $post_ID = $_GET['post_ID'];
  $post = get_post($post_ID);
  $title = $post->post_title;
  // get rid of the annotations, we don't need them
  $despanned_entities = strip_tags($entities);
  // construct our request to get a json object back
  $data_array = Array("text" => htmlentities($despanned_entities),"frcode" => "csc_wordpress", "output" => "json", "annotate_unique" => 1,"title" => $title); // csc_wordpress has no "weight"?
  $entity_obj = ysc_ping_api($data_array);
  // turn it into php
  $json = new Services_JSON();
  $entity_php_obj = $json->decode($entity_obj['content']);
  // build datastruct for ultimate json use
  if($entity_php_obj->annotationSet) {
     foreach($entity_php_obj->annotationSet as $k=>$v) {
      $yfsc_query_array[] = array('weight' =>$v->weight, 'text' => $v->text);
    }
  }
  // if we get this far and have 3 terms, we're happy.  If not,
  // get some backup terms
  if(!$entity_php_obj->annotationSet or count($yfsc_query_array) < 3) {
# split the words apart by spaces. no accounting for quotes
    $contents = preg_replace("/[^\w\s\']/i",' ',strip_tags($despanned_entities));
    $snoopy_array["appid"] = ".Kch5LTV34Hn.41IJ.xZLqcf0pRk7Esb2SMrz5vyDuA7RNlN5nDaWD6L_fXS9CI2KI7T"; // Our API Key
    $snoopy_array["context"] = urlencode($contents);
    $snoopy_array["output"] ="php"; // how cool that it will do php data
    $url = PATH;
    $words = unserialize(snoopy_req(PATH,$snoopy_array));
    if(is_array($words["ResultSet"]["Result"])) {
      foreach($words["ResultSet"]["Result"] as $k) {
	$yfsc_query_array[] = array('weight' => .01, 'text' => $k); // try to be sure that these terms sort to the bottom
      }
    }
  }
  // not really sure what to do if we get this far and still don't have enough terms... give up?

  if(! is_array($yfsc_query_array)) {
    $yfsc_query_array = array();
  }

  //  sort by weight
    arsort($yfsc_query_array);
  // limit to top three terms
  $top_three = array_splice($yfsc_query_array,0,3);
  
  // search flickr for each term and adjust struct accordingly
  Foreach($top_three as $k=>$v) {
    $flickr_array['text'] = preg_replace('/\s/','+',$v['text']);
    $flickr_array['api_key'] = FLICKR_KEY;
    $flickr_array['method'] = 'flickr.photos.search';
    $flickr_array['license'] = '1,2,3,4,5,6';
    $flickr_array['ss'] = '0';
    $flickr_array['per_page'] = 9/ceil(count($top_three));
    $flickr_array['format'] = 'json';
    $flickr_array['nojsoncallback'] = 1;
    $json = new Services_JSON();
    $flickr_obj = $json->decode(snoopy_req('http://api.flickr.com/services/rest/', $flickr_array));
    for($i=0;$i<sizeof($flickr_obj->photos->photo);$i++) {
      // touch up the text a bit - special chars make stuff misbehave later
      $flickr_obj->photos->photo[$i]->title = htmlspecialchars($flickr_obj->photos->photo[$i]->title);
    }
    $top_three[$k]['photos'] = $flickr_obj->photos->photo;
    $top_three[$k]['total'] = $flickr_obj->photos->total;
    // seed the page so we can use it later
    $top_three[$k]['page'] = 1;
  }
  return($top_three);
  //   print("</pre>");
}

function yfsc_narrow_search() {
    $flickr_array['text'] = preg_replace('/\s/','+',$_POST['text']);
    $flickr_array['api_key'] = FLICKR_KEY;
    $flickr_array['method'] = 'flickr.photos.search';
    $flickr_array['license'] = '1,2,3,4,5,6';
    $flickr_array['ss'] = '0';
    $flickr_array['per_page'] = 9;
    $flickr_array['format'] = 'json';
    $flickr_array['nojsoncallback'] = 1;
    $json = new Services_JSON();
    $flickr_obj = $json->decode(snoopy_req('http://api.flickr.com/services/rest/', $flickr_array));
    for($i=0;$i<sizeof($flickr_obj->photos->photo);$i++) {
      $flickr_obj->photos->photo[$i]->title = htmlspecialchars($flickr_obj->photos->photo[$i]->title);
    }
//    print("('" . json_encode($flickr_obj) . "')");
//    $result = drawImagePanel($flickr_obj);
//    die($result);
//  error_log(json_encode($flickr_obj));
    $json = new Services_JSON();
    die($json->encode(array($flickr_obj)));
}

function yfsc_page_search() {
  $textArray = explode('|', $_POST['text']);
  $k = 0;
  $per_page = 3;
  $_POST['per_page'] ? $per_page = $_POST['per_page'] : $per_page = 3;
  if(count($textArray) == 1) {
    $per_page = 9;
  }
  foreach($textArray as $t) {
    $flickr_array[$k]['text'] = preg_replace('/\s/','+',$t);
    $flickr_array[$k]['api_key'] = FLICKR_KEY;
    $flickr_array[$k]['method'] = 'flickr.photos.search';
    $flickr_array[$k]['license'] = 'cc';
    $flickr_array['ss'] = '0';
    $flickr_array[$k]['per_page'] = $per_page;
    $flickr_array[$k]['format'] = 'json';
    $flickr_array[$k]['nojsoncallback'] = 1;
    $flickr_array[$k]['page'] = $_POST['page'];
    $json = new Services_JSON();
    $flickr_obj[$k] = $json->decode(snoopy_req('http://api.flickr.com/services/rest/', $flickr_array[$k]));
    for($i=0;$i<sizeof($flickr_obj[$k]->photos->photo);$i++) {
      $flickr_obj[$k]->photos->photo[$i]->title = htmlspecialchars($flickr_obj[$k]->photos->photo[$i]->title);
    }
    $k++;
  }
    //    $result = drawImagePanel($flickr_obj);
  //  error_log(json_encode($flickr_obj));
  $json = new Services_JSON();
  die($json->encode($flickr_obj));
}


function yfsc_get_user() {
  // http://api.flickr.com/services/rest/?method=flickr.people.getInfo&api_key=c70842e0f6dfa62c7555dd73396a33fe&user_id=24342868%40N00&format=json&nojsoncallback=1
  $user_array['user_id'] = $_POST['user_id'];
  $user_array['api_key'] = FLICKR_KEY;
  $user_array['method'] = 'flickr.people.getInfo';
  $user_array['format'] = 'json';
  $user_array['nojsoncallback'] = 1;
  $flickr_obj = snoopy_req('http://api.flickr.com/services/rest/',$user_array);
  die($flickr_obj);
}


function snoopy_req($url,$my_array) {
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	foreach($my_array as $k => $v) { 
	  //	  error_log($my_array[$k]);
		$my_array[$k] = stripslashes($v);	
	}
	$snoop = new Snoopy;
	$snoop->read_timeout = 5;
	$snoop->submit(
		       $url
		       //		'http://api.flickr.com/services/rest/'
		, $my_array
	);
	return $snoop->results;
}

require_once(ABSPATH.PLUGINDIR.'/yahoo-shortcuts/flickr_chooser.php');

?>
