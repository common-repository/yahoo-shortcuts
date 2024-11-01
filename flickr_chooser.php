<?php
add_action('admin_print_scripts', 'yfsc_js_admin_header' );

function yfsc_js_admin_header() {
    wp_print_scripts( array( 'sack' ));
  print '
<style type="text/css" media="screen">
<!--
@import url("'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/styles/yfsc.css");
@import url("'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/styles/flickr-embed.css");
@import url("'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/styles/flickr-hover.css");
@import url("'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/styles/flickr-preview.css");
.yfsc_image_menu_tab { background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/hiddentableft.png) no-repeat; }
.yfsc_embed_menu_open {	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/slideleftbg.png) no-repeat; }
.yfsc_chooser_arrow_right { background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrow-chooser-right.gif) no-repeat;display:block;height:10px;width:6px; }
.yfsc_chooser_arrow_left { background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrow-chooser-left.gif) no-repeat;display:block;height:10px;width:6px; }
-->
</style>';
return true;
}

function getFlickrChooser($flickr_obj) {
  //  $valid_elements = 'p/-div[*],-strong/-b[*],-em/-i[*],-font[*],-ul[*],-ol[*],-li[*],*[*]';
//   print("<pre>");
//   print_r($flickr_obj);
//   print("</pre>");
  $json = new Services_JSON();
  $retval =  '
	<div class="flickr-hover-mod yfsc" id="flickr-preview-overlay">
  	<div class="hd"></div>
    <div class="bd">
    	<div class="image-body">
      	<img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/slug.jpg" id="flickr-preview-image" />
      </div>
    	<div class="attribution">
        <ul class="no-bullet-list inline-list image-rights right">
        	<li><img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/icon-sm-by.gif" /></li>
        	<li><img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/icon-sm-free.gif" /></li>
        	<li><img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/icon-sm-equals.gif" /></li>
        </ul>
        <div class="clear"></div>
      </div>
      <a href="index.html" id="button-add-to-post" class="button-add-to-post"><img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/button-add-to-post.gif" width="97" border="0" height="19" /></a>
    </div>
    <div class="ft">
    	<div class="ft-body"></div>
    	<div class="arrow"></div>
    </div>
  </div>


  <div class="yfsc flickr-preview-mod" id="flickr-preview-window">
  	<div class="hd">
    	<h1>Add this photo to my post</h1>
    </div>
    <div class="bd">
    	<h2>Select a size</h2>
			<div id="flickr-preview-window-options">
      	<div class="radio-group-left left">
          <input type="radio" name="size" class="left" value="_s." id="square" /> 
          <label for="square" class="left"><strong>Square</strong> (75x75)</label>
          <div class="clear"></div>
          <input type="radio" name="size" class="left" value="_m." id="small" />
          <label for="small" class="left"><strong>Small</strong> (240px wide)</label>
          <div class="clear"></div>
        </div>
        <div class="radio-group-right left">
          <input type="radio" name="size" class="left" value="_t." id="thumbnail" />
          <label for="thumbnail" class="left"><strong>Thumbnail</strong> (100px wide)</label>
          <div class="clear"></div>
          <input type="radio" name="size" class="left" value="." id="medium" />
          <label for="medium" class="left"><strong>Medium</strong> (500px longest side)</label>
          <div class="clear"></div>
        </div>
        <div class="clear"></div>
			</div>
      <div class="image-preview" id="flickr-preview-window-image"></div>
				<div class="copyright">
					<img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/icon-sm-cc-2.gif" alt="&copy;" id="cc" class="left" /> 
					<span class="left"> by <strong id="yfsc_image_owner_preview"></strong> <span id="yfsc_attribution_message"></span></span>
					<div class="clear"></div>
				</div>
      <div class="licensing">
				
      	<h3>License information</h3>
        <h4>Creative Commons <span><a href="http://flickr.com/creativecommons">Attribution-Noncommercial-No Derivative Works 2.0</a></span> Generic license</h4>
        Proper attribution will be automatically added to the photo if added
				<br /><br />
        <p><img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/rights-badges.gif" class="clear" style="height:33px;width:206px;" alt="&copy; Some rights reserved" /> 
          </p>
          <p class="agreement">
          	By selecting \'Submit\' you agree to the conditions of this license
          </p>
      </div>
		</div>
    <div class="ft">
    	<img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/button-submit-disabled.gif" class="left" id="preview-button-submit" />
      <img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/button-cancel.gif" class="left" id="preview-button-cancel" />
      <div class="clear"></div>
    </div>
  </div>


	<div class="yfsc flickr-mod clear">
  	<div class="hd">
      <div class="hd-left corner"></div>
      <div class="hd-center"></div>
      <div class="hd-right corner"></div>
      <div class="hd-body">
				<h1><span>flickr</span></h1> <p class="result-tags" id="result-text">Found images for ';
				$result_text = '';
				foreach($flickr_obj as $obj) {
					$result_text .= '<strong><a href="javascript:void(0);" onclick="narrowSearch(\''.$obj['text'].'\'); false;">' . $obj['text'] . '</a></strong>(' . $obj['total'] . '), ';
					$res_total += $obj['total'];
				}
				$retval .= rtrim($result_text, ", ").'</p>';

		$retval .= '</div><div class="clear"></div>
    </div>
		<div class="bd">
		<div id="chooser-body">
			<div id="arrow-back" class="arrow-back left"></div>
    	<div class="image-results left" id="image-results">';
  //  print("<pre>");
		//		$retval .= drawChooserArrow("back", $flickr_obj);
		$retval .= drawImagePanel($flickr_obj);
  //  print("</pre>");
  	$retval .= '
      </div>
			<div id="arrow-forward" class="arrow-forward left">';
			
		$retval .= drawChooserArrow("forward", $flickr_obj);
			
		$retval .= 	'</div>

		</div>
			<fieldset class="flickr-search">
      	<label for="q">Looking for something else?</label>
        <input type="text" id="yfsc-q" name="yfsc-q" class="text-field" />
        <input type="image" src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/button-search.gif" class="button-submit left" id="yfsc-submit" name="yfsc-submit" value="search" />
			</fieldset>
    </div>
    <div class="ft">
      <div class="ft-left left corner"></div>
      <div class="ft-center left"></div>
      <div class="ft-right left corner"></div>
    </div>
  </div>
		
<script type="text/javascript">
	flickr_resp = \'' .   addslashes($json->encode($flickr_obj)) . '\';
	flickr_obj = eval(flickr_resp);
</script>
<script type="text/javascript" src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/scripts/yfsc-core.js"></script>
<script type="text/javascript" src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/scripts/yfsc-menu.js"></script>
<script type="text/javascript" src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/scripts/yfsc-effects.js"></script>
<script type="text/javascript" src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/scripts/yfsc-attribution.js"></script>
<script language="javascript1.2" type="text/javascript">
	var	FlickrSC = new YFSC;
	FlickrSC.base_path = "'.get_bloginfo('wpurl').'";
	FlickrSC.path = "'.get_bloginfo('wpurl').'/'.PLUGINDIR.'";
	FlickrSC.effects = new YFSC_Effects(FlickrSC);
	FlickrSC.menu = YFSC_Menu;
	FlickrSC.attribution = new YFSC_Attribution(FlickrSC);
	try {
		if (typeof(FlickrSC.get("preview_rendering")) == "object") {
			FlickrSC.attribution.tagImages("preview_rendering", true, "preview");
		}
	}catch(e) {}
	FlickrSC.effects.prepImages();
	FlickrSC.addListener("onclick", FlickrSC.get("yfsc-submit"), function() {narrowSearch(FlickrSC.get("yfsc-q").value);return false;});
	FlickrSC.addListener("onkeypress", FlickrSC.get("yfsc-q"), function(event) {
		e = event || window.event;
		if (e.keyCode == 13) {
			narrowSearch(FlickrSC.get("yfsc-q").value);
			return false;
		}
	});

function getOwnerInfo(owner) {
		FlickrSC.get("yfsc_image_owner_preview").innerHTML = \'<img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/flickr-ajax-loader.gif" />\';
var mysack = new sack("' . get_bloginfo( "wpurl" ) .'/wp-admin/admin-ajax.php" );    
	mysack.method = "POST";
	mysack.setVar( "action", "yfsc_get_user" );
	mysack.setVar( "user_id", owner );
	mysack.encVar( "cookie", document.cookie, false );
	mysack.onError = function() { alert("AJAX error in getting owner info" )};
	mysack.runAJAX();
	mysack.element = owner;
	mysack.onCompletion = function() {		
			FlickrSC.effects.setUserInfo(mysack.response);
	};
}

var ta,fp;
function narrowSearch(text) {
fp = 1;
var mysack = new sack("' . get_bloginfo( "wpurl" ) .'/wp-admin/admin-ajax.php" );    
	resulttext = document.getElementById("result-text");
	resulttext.style.textAlign = "right";
	resulttext.innerHTML = "Found images for <strong>" + text + "</strong>";
	chooserDiv = document.getElementById("chooser-body");
	chooserDiv.innerHTML = "<div style=\"clear:both;margin:0px auto;width:100%;text-align:center;padding:20px 0px 25px 0px;\"><img src=\"'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/flickr-ajax-loader.gif\" /></div>";
	mysack.method = "POST";
	mysack.setVar( "action", "yfsc_narrow_search" ); 
	mysack.setVar( "text", text );
	mysack.encVar( "cookie", document.cookie, false );
	mysack.onError = function() { alert("AJAX error in getting data" )};
	mysack.runAJAX();
	mysack.onLoading = function() {return false;};
	mysack.element = "image-results";
	ta = Array(text);
	mysack.onCompletion = function() {buildChooser(mysack.response);};
	return false;
}

function pageSearch(direction, page, textArray) {
var mysack = new sack("' . get_bloginfo( "wpurl" ) .'/wp-admin/admin-ajax.php" );    
	chooserDiv = document.getElementById("chooser-body");
	chooserDiv.innerHTML = "<div style=\"clear:both;margin:0px auto;width:100%;text-align:center;padding:20px 0px 25px 0px;\"><img src=\"'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/flickr-ajax-loader.gif\" /></div>";
	ta = textArray;
	fp = page;
	mysack.method = "POST";
	mysack.setVar( "action", "yfsc_page_search" ); 
	mysack.setVar( "text", textArray.join("|") );
	mysack.setVar( "page", page );
	mysack.encVar( "cookie", document.cookie, false );
	mysack.onError = function() { alert("AJAX error in getting owner info" )};
	mysack.runAJAX();
	mysack.onLoading = function() {return false;};
	mysack.onCompletion = function() {buildChooser(mysack.response);};
	return false;
}

function arrowSearch() {
	pageSearch(this.direction,this.page, this.textArray);
}

function buildChooser(resp) {
flickr_obj = eval( "(" + resp + ")" );
div = FlickrSC.create("div", {"attributes": { "id":"chooser-body" }});
imageDiv = FlickrSC.create("div", {"attributes": { "id":"image-results" }});

backArrowDiv = FlickrSC.create("div", {"attributes": { id:"arrow-back" }});
backArrow = FlickrSC.create("a", {"attributes": { "href":"javascript:void(0);" }});

backArrowDiv.appendChild(backArrow);
backArrowDiv.className = "arrow-back left";
backArrow.className = "yfsc_chooser_arrow_left";
backArrow.onclick = function() {pageSearch(\'back\', fp-1, ta);};
div.appendChild(backArrowDiv);

div.appendChild(imageDiv);
imageDiv.className = "image-results left";

for(i in flickr_obj) {
	for(j in flickr_obj[i]) {
		for(photoset in flickr_obj[i][j].photo) {
		current_set = flickr_obj[i][j].photo[photoset];
		img =  FlickrSC.create("img", 
					 {attributes:
		{id:current_set.owner, src:"http://farm" + current_set.farm + ".static.flickr.com/" + current_set.server + "/" + current_set.id + "_" + current_set.secret + "_s.jpg", width: 50, height: 50}});
		imageDiv.appendChild(img);
		}
	}
}

forwardArrowDiv = FlickrSC.create("div", {"attributes": { "id":"arrow-forward" }});
forwardArrow = FlickrSC.create("a", {"attributes": { "href":"javascript:void(0);" }});

forwardArrowDiv.appendChild(forwardArrow);
div.appendChild(forwardArrowDiv);
forwardArrowDiv.className = "arrow-forward left";
forwardArrow.className = "yfsc_chooser_arrow_right";
forwardArrow.onclick = function() {pageSearch(\'forward\', fp+1, ta);};
div.appendChild(FlickrSC.create("div", {"attributes": { "class":"clear" }}));

if(flickr_obj[0].photos.total == 0) {
document.getElementById("result-text").innerHTML = "No images found";
div.innerHTML = "";
}

oldDiv = document.getElementById("chooser-body");
oldDiv.parentNode.replaceChild(div,oldDiv);
	FlickrSC.effects.prepImages();
//document.body.appendChild(div);

}

function whenCompleted(){
	var e = document.getElementById("image-results");
	e.innerHTML = mysack.response;
	FlickrSC.effects.prepImages();
}
</script>



';
  return $retval;
}

function drawImagePanel($flickr_obj) {
  if($flickr_obj->photos) {
    foreach($flickr_obj->photos->photo as $photoset) {
      $retval .= '<img src="http://farm' . $photoset->farm . '.static.flickr.com/' . $photoset->server . '/' . $photoset->id . '_' . $photoset->secret . '_s.jpg" alt=" " width="50" id="' . $photoset->owner . '" />'."\n";
    }
  } else {
    if(! $flickr_obj[0]['photos']) {
      return "<script type=\"text/javascript\">e=document.getElementById('result-text');e.innerHTML='No images found';</script>";
    } else {
      foreach($flickr_obj as $obj) {
	foreach($obj['photos'] as $photoset) {
	  //            print($photoset->owner);
	  $retval .= '<img src="http://farm' . $photoset->farm . '.static.flickr.com/' . $photoset->server . '/' . $photoset->id . '_' . $photoset->secret . '_s.jpg" alt=" " width="50" id="' . $photoset->owner . '" />'."\n";
	}
      }
    }
  }
  return $retval;
}


function drawChooserArrow($direction, $flickr_obj) {

  foreach($flickr_obj as $obj) {
    $pager_text[] = "'" . $obj['text'] . "'";
  }
  if(! $pager_text) {
    return;
  } else {
    $direction == 'forward' ? $class = 'yfsc_chooser_arrow_right' : $class = 'yfsc_chooser_arrow_left';
    $direction == 'forward' ? $page = 2 : $page = 0;
    $retval = "<a href=\"javascript:void(0);\" onClick=\"pageSearch('$direction', $page, [" . implode(",",$pager_text) . "])\" id=\"arrow-" . $direction . "\" class=\"".$class."\"></a>";
    return $retval;
  }
}

?>
