<?php 
/*
Copyright (c) 2007 Yahoo! Inc. All rights reserved. 
The copyrights to the software code in this file are licensed under the (revised) BSD open source license.

Plugin Name: Yahoo! Shortcuts (Beta)
Plugin URI: http://shortcuts.yahoo.com
Description: Yahoo! Shortcuts for Wordpress plugin intelligently enriches your blog post with great content from Yahoo!  Maps, Finance, and beyond.  We are excited to make the software code for Yahoo! Shortcuts plugin for Wordpress available under the BSD license.  Note, however, that the images, designs, logos, modules (including the content and services that flow through the plugin) are provided under the terms located <a href="http://info.yahoo.com/legal/us/yahoo/contextualshortcuts/contextualshortcuts-1815.html">here</a>, which you agree to by activating the plugin.
Version: 0.962
Author: Crowd Favorite and the Yahoo! Shortcuts Team
Author URI: http://crowdfavorite.com
*/

// flickr actions
add_action('wp_ajax_yfsc_get_user', 'yfsc_get_user' );
add_action('wp_ajax_yfsc_narrow_search', 'yfsc_narrow_search' );
add_action('wp_ajax_yfsc_page_search', 'yfsc_page_search' );
add_action('wp_footer', 'yfsc_add_attribution');

function yfsc_add_attribution($content) {
  print '<script type="text/javascript">
	try {
		var FlickrSC = new YFSC;
			FlickrSC.base_path = "'.get_bloginfo('wpurl').'";
			FlickrSC.path = "'.get_bloginfo('wpurl').'/'.PLUGINDIR.'";
			FlickrSC.attribution = new YFSC_Attribution(FlickrSC);
			FlickrSC.attribution.tagImages("content", "publish", false);
	}catch(e) {
	}
</script>';

}

if (!function_exists('is_admin_page')) {
	function is_admin_page() {
		if (function_exists('is_admin')) {
			return is_admin();
		}
		if (function_exists('check_admin_referer')) {
			return true;
		}
		else {
			return false;
		}
	}
}

function yfsc_js_files() {
  print '<script type="text/javascript" src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/scripts/yfsc-core.js"></script>
<script type="text/javascript" src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/scripts/yfsc-attribution.js"></script>';
}

if (!is_admin_page()) {
	add_action('wp_print_scripts', 'yfsc_js_files' );
}

require_once(ABSPATH.PLUGINDIR.'/yahoo-shortcuts/flickr_functions.php');

function ysc_agent_is_msie() {
	static $msie;
	if (!isset($msie)) $msie = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);
	return $msie;
}

function ysc_agent_is_safari2() {
	static $safari2;
	if (!isset($safari2)) {
		$matches = array();
		$found = ereg('Safari/([0-9]+)', $_SERVER['HTTP_USER_AGENT'], $matches);
		$safari2 = (
			($found) &&
			isset($matches[1]) &&
			(int)($matches[1]) < 522
		);
	}
	return $safari2;
}
wp_enqueue_script('jquery');

function ysc_ping_api($data_array) {
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	foreach($data_array as $k => $v) { 
		$data_array[$k] = stripslashes($v);	
	}
	$snoop = new Snoopy;
	$snoop->read_timeout = 5;
	$success = @$snoop->submit(
		'http://shortcuts.yahoo.com/annotate'
		, $data_array
	);
	if ($success) {
		return array(
			'headers' => $snoop->headers,
			'content' => $snoop->results
		);
	}
	else {
		return false;
	}
}

function ysc_request_handler() {
	// if we're coming from our preview page, defeat wp's return-to-referrer behavior
	if (strpos($_SERVER['HTTP_REFERER'], 'yahoo_shortcuts.php') !== false) {
		$_SERVER['HTTP_REFERER'] = '';
	}
	
	if (!empty($_POST['ysc_action'])) {
		switch ($_POST['ysc_action']) {
			case 'render_preview':
				require_once('admin-functions.php');
				$post_ID = -1;
				if (isset($_POST['action']) && $_POST['action'] == 'post') {
					// creating
					if (current_user_can('edit_posts')) {
						$post_ID = write_post();
					}
					else {
						wp_die('Sorry, you do not have permission to write a post.');
					}
				}
				else {
					// editing
					$post_ID = $_POST['post_ID'];
					if (current_user_can('edit_post', $post_ID)) {
						$post_ID = edit_post();
					}
					else {
						wp_die('Sorry, you are not allowed to edit this post.');
					}
				}
				if ($post_ID != -1) {
					if (get_post_meta($post_ID, 'ysc_entity_script')) {
						update_post_meta($post_ID, 'ysc_entity_script', $_POST['ysc_entity_script']);
					}
					else {
						add_post_meta($post_ID, 'ysc_entity_script', $_POST['ysc_entity_script']);
					}
					$url = get_bloginfo('wpurl').'/wp-admin/post-new.php?page=yahoo_shortcuts.php&ysc_action=render_preview&post_ID='.$post_ID;
					header("Location: $url");
					die();					
				}
				else {
					wp_die('Could not save your post.');
				}
			break;
			case 'submit_preview':
				if (!empty($_POST['ysc_preview_action'])) {
					switch ($_POST['ysc_preview_action']) {
						case 'Save':
							$post_ID = $_POST['post_ID'];
							if (current_user_can('edit_post', $post_ID)) {
								$content = html_entity_decode($_POST['ysc_content'], ENT_QUOTES);
								wp_update_post(array('ID' => $post_ID, 'post_content' => $content));
								if (get_post_meta($post_ID, 'ysc_entity_script')) {
									update_post_meta($post_ID, 'ysc_entity_script', $_POST['ysc_entity_script']);
								}
								else {
									add_post_meta($post_ID, 'ysc_entity_script', $_POST['ysc_entity_script']);
								}
								$url = get_bloginfo('wpurl').'/wp-admin/post.php?action=edit&post='.$post_ID;
								header("Location: $url");
								die();
							}
							else {
								wp_die('You are not allowed to edit this post.');
							}
						break;
						case 'Cancel':
							$post_ID = $_POST['post_ID'];
							$url = get_bloginfo('wpurl').'/wp-admin/post.php?action=edit&post='.$post_ID;							
							header("Location: $url");
							die();
						break;
					}
				}
			break;
		}
	}
	if (!empty($_GET['ysc_action'])) {
		switch ($_GET['ysc_action']) {
			case 'entity_funnel':
				$result = ysc_ping_api($_POST);				
				if ($result === false) {
					header('Content-Type: text/html; charset=utf-8');
					echo '__ysc_annotation_request_failed__';
				}
				else {
					foreach ($result['headers'] as $response_header) {
						header($response_header);
					}
					echo $result['content'];
				}
			die();
			case 'ysc_js':
				header("Content-type: text/javascript");
				print ('
ysc = new Object();
ysc.pluginURL = \''.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/\';
ysc.imageURL = \''.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/\';
ysc.agent_is_msie = '.(ysc_agent_is_msie() ? 'true' : 'false').';
ysc.agent_is_safari2 = '.(ysc_agent_is_safari2() ? 'true' : 'false').';
ysc.img_ext = '.(ysc_agent_is_msie() ? '\'gif\'' : '\'png\'').';
				');
				if ($_GET['ysc_post_script']) {
					$script = get_post_meta($_GET['ysc_post_script'], 'ysc_entity_script', true);
					print('
ysc.saved_y_script = \''.$script.'\';
					');
				} else {
					print('
ysc.saved_y_script = \'\';
					');
				}
				
				if (isset($_GET['ysc_page'])) {
					print('
ysc.page = \''.$_GET['ysc_page'].'\';
					');
				}
				else {
					print('
ysc.page = \'\';
					');
				}
				require(ABSPATH.PLUGINDIR.'/yahoo-shortcuts/yahoo_shortcuts.js');
			die();
			case 'ysc_admin_css':
				header("Content-type: text/css");
				print(ysc_get_css('admin'));
			die();
			case 'ysc_preview_css':
				header("Content-type: text/css");
				print(ysc_get_css('preview'));
				if (ysc_agent_is_msie()) {
					print(ysc_get_css('preview-msie'));
				}
			die();
			case 'ysc_rte_css':
				header("Content-type: text/css");
				print(ysc_get_css('rte'));			
			die();
			case 'ysc_published_css':
				header("Content-type: text/css");
				print(ysc_get_css('published'));
			die();
			case 'render_preview':
			break;
		}
	}
}
add_action('init', 'ysc_request_handler', 10);

function ysc_whitelist_span_tags() {
	// see kses.php for what this is about
	global $allowedposttags;
	if (!isset($allowedposttags['span'])) {
		$allowedposttags['span'] = array('id' => array(), 'class' => array());
	}
	else {
		$allowedposttags['span']['id'] = array();
		$allowedposttags['span']['class'] = array();
	}
}
//add_action('init', 'ysc_whitelist_span_tags', 1);

function ysc_get_css($which) {
	switch ($which) {
		case 'published':
		return '
.ysc_embed {
	float:left; 
	margin:10px 10px 10px 0;
}
.ysc_embed_right {
	float:right;
	margin:10px 0 10px 10px;
}
.ysc_embed_center {
	margin:10px auto;
	clear:both;
}
.ysc_embed_center div.lwEmbed {
	margin: 0px auto; 
}
.yshortcuts.highlighted {
	background-color:#c1d5ec;
}

.yfsc { font-family:Verdana, Arial, Helvetica, sans-serif;font-size:11px; }
.yfsc h1, .yfsc h2, .yfsc h3, 
.yfsc h4, .yfsc h5, .yfsc h6, 
.yfsc span, .yfsc form,
.yfsc fieldset, .yfsc ul { margin:0px;padding:0px; }
.yfsc img { border:0px; }
.yfsc .clear { clear:both; }
.yfsc .left { float:left; }
.yfsc .right { float:right; }
.yfsc .no-bullet-list li { list-style-type:none; }
.yfsc .inline-list li { display:inline; }
.yfsc_image { clear:both;height:auto;width:auto; }
.yfsc_wrapper { position:relative;width:auto;padding:0px 4px 4px 0px;}
.yfsc_wrapper_left { float:left; }
.yfsc_wrapper_right { float:right; }
.yfsc_wrapper_center { text-align:center;margin:0px auto;clear:both; }
.yfsc_wrapper img { }
.yfsc_attribution { text-align:left;color:#666666;clear:both; }
.yfsc_cc:link, .yfsc_cc:active, .yfsc_cc:visited { color:#666666 !important; text-decoration:underline !important; border:0px !important; }
.yfsc_cc:hover { color:#666666 !important;text-decoration:none !important; }
.yfsc_image_menu_tab { position:absolute;width:16px;height:20px;margin-left:-16px;margin-top:0px;cursor:pointer; }
.yfsc_menu ul {	color:#4692dc;font-size:11px;font-family: Helvetica, Arial, sans-serif;padding:0;list-style:none;cursor:pointer;margin-bottom:4px;padding-left:2px;margin: 8px 0 0 0; }
.yfsc_menu li span:hover { text-decoration:underline; }
.yfsc_menu ul img {	vertical-align:middle;padding-right:3px; }
.yfsc_embed_menu_open {	position:absolute;width:113px;height:80px;top:0px;left:-96px;padding-left:3px; }

';
		case 'admin': 
		return 	'
#moremeta fieldset div#ysc_num_shortcuts {
	font-size:12px;
	text-weight:bold;
	padding:0;
	margin-right:8px;
	margin-bottom:0;
	padding-right:2px;
	text-align:center;
}
#moremeta fieldset div#ysc_num_shortcuts span.num {
	font-size:18px;
}
#moremeta fieldset div#ysc_yahoo_this_post {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/btn_reviewpost.gif'.') no-repeat 16px 0px;
	width:157px;
	height:33px;
	margin-bottom:0;
	padding:0;
}
#ysc_yahoo_this_post:hover {
	cursor:pointer;
}
#ysc_yahoo_this_post p {
	display: none;
}
#moremeta fieldset div#ysc_searching_indicator {
	margin-right:8px;
	margin-bottom:2px;
	padding-right:2px;
	font-size:12px;
	text-align:center;	
	color: #666;
}
#ysc_badge_list {
	overflow:auto;
}
#ysc_badge_list ul {
	padding:5px;
}
#ysc_badge_list li {
	list-style: none;
	padding: 8px 0 8px 30px;
	margin-bottom: 10px;
	border-bottom: 1px dotted #444;
	border-top: 1px dotted #444;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/map_icon.gif'.') no-repeat 4px 7px;
}
		';
		case 'rte':
		return '
.yshortcuts {
	border-bottom:1px dashed rgb(0, 102, 204);
	cursor: pointer;
}		
.ysc_embed_proxy {
	float:left;
	display: inline;
	padding:3px;
	clear:both;
}
.ysc_embed_proxy_right {
	float: right;
}
.ysc_embed_proxy_center {
	display:block;
	margin:5px auto;
	clear:both;
}

		';
		case 'preview':
		return '
p, ul {
	margin:0;
	padding:0;
}
img.menulink {
}
img.menudelete {
	position:relative;	
	top:-1px;
}
#preview_rendering {
	margin:0px 0px 40px;
	font-family: Times New Roman, Times, serif;
	font-size:14px;
	text-align:justify;
}
#preview_rendering p {
	margin-top: 15px;
}
.byline_border {
	width:80px;
}
#post_title {
	line-height:18px;
	font-size:18px;
	font-weight:bold;
	font-family: Helvetica, Arial, sans-serif;
	width:100%;	
	border:none;
	margin:0px;
	background:none;
}
#preview_form {
	width:530px;
	margin:30px auto;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/blank.gif'.') no-repeat;
}
#byline {
	color:#666;
	font-style:italic;
	font-family:Times New Roman, Times, serif;
	margin:8px 0;
}
#preview_header {
	margin:20px auto;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/foundmodbg.png'.') no-repeat;
	width:526px;
	height:95px;
	padding:8px 12px;
}
#preview_header h3 {
	margin:0px;
	font-size:13px;
}
#preview_header p {
	font-size:10px;
	color:#333;
	width:380px;
	margin:2px 0;
}
#preview_header ul {
	margin:6px 0 0 14px;
	padding:0;
}
#preview_header li {
	margin:0 38px 0px 0px;
	font-size:11px;
	font-weight:bold;
	color:#4692dc;
	cursor:pointer;	
	display:block;
	float:left;
}
#preview_header li#remove_shortcuts {
	width: 180px;
}
#preview_header li img {
	vertical-align:middle;
}
#preview_header li span:hover {
	text-decoration:underline;
}
#preview_header li a {
	color:#4692dc;
	text-decoration:none;
}
#preview_header li.disabled span {
	color:#777777;
	cursor:default;
}
#preview_header li.disabled span:hover {
	text-decoration:none;
}
.yshortcuts.highlighted {
	background-color:#c1d5ec;
}
.ysc_embed {
	margin:10px 10px 10px 0;
}

.ysc_embed_right {
	margin:10px 0 10px 10px;
}
.ysc_embed_center {
	margin:10px auto;
}
.ysc_embed_center div.lwEmbed {
	margin: 0px auto; 
}
.ysc_embed hidden {
	padding:0;
	margin:0;
}
.ysc_menu_container {
	position: relative;
	display: inline;
	overflow: visible;
	z-index:1;
}
.ysc_menu ul {
	color:#4692dc;
	font-size:11px;
	font-family: Helvetica, Arial, sans-serif;
	padding:0;
	list-style:none;
	cursor:pointer;	
	margin-bottom:4px;
	padding-left:2px;
	margin: 8px 0 0 0;
	text-align:left;
}
.ysc_menu li span:hover {
	text-decoration:underline;
}
.ysc_menu ul img {
	vertical-align:middle;
	padding-right:3px;
}
.ysc_embed_container {
	position:relative;
	overflow:visible;
	float:left;
	clear:both;
	padding-top:10px;
}
.ysc_embed_container_right {
	position:relative;
	overflow:visible;
	float:right;
	clear:both;
	padding:10px 0 0 0;
	margin-left: 10px;
}
.ysc_embed_container_center {
	position:relative;
	overflow:visible;
	clear:both;
}
.ysc_embed_menu_open {
	position:absolute;
	margin:0;
	width:113px; 
	height:94px;
	top:30px; 
	left:-112px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/slideleftbg.png'.') no-repeat;
	padding-left:3px;
	z-index:3;
}
.ysc_embed_menu_open_right {
	position:absolute;
	margin:0;
	width:113px; 
	height:94px;
	top:30px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/sliderightbg.png'.') no-repeat;
	padding-left:3px;
	z-index:3;
}
.ysc_embed_menu_open.multi {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/slideleftbg-multi.png'.') no-repeat;
}
.ysc_embed_menu_open_right.multi {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/sliderightbg-multi.png'.') no-repeat;
}
.ysc_embed_menu_tab {
	position:absolute;
	margin:0;
	width:0px;
	height:94px;
	left:-15px;
	padding: 10px;
	top:30px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/hiddentableft.png'.') no-repeat;	
	cursor:pointer;
}
.ysc_embed_menu_tab_right {
	position:absolute;
	margin:0;
	width:0px;
	height:94px;
	padding: 10px;
	top:30px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/hiddentabright.png'.') no-repeat;	
	cursor:pointer;
}
.ysc_link_menu_open {
	z-index:2;
	position:absolute;
	width:112px;
	height:65px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/linkstateballoon_med.png'.') no-repeat;
}
.ysc_link_menu_open.small {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/linkstateballoon_small.png'.') no-repeat;	
}
.ysc_link_menu_open.small ul {
	padding-top:12px;
}
.ysc_link_menu_open ul {
	padding:10px 3px 0 6px;
}
.ysc_link_menu_tab {
	padding:0 10px 0 10px;
	margin:0 2px 0 2px;
	padding:0;
	display:inline;
	cursor:pointer;
}
.ysc_link_menu_tab img {
	vertical-align:middle;
}
.ysc_link_menu_tab.disabled {
}
.ysc_badge_select {
	font-size:10px;
	font-weight:bold;
	font-family: Helvetica, Arial, sans-serif;
	position:relative;
}
.ysc_badge_select span.select_prev {
	display:block;
	position:absolute;
	width:13px;
	top:5px;
	height:9px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowblueleft.png'.') no-repeat;	
}
.ysc_badge_select span.select_prev.disabled, .ysc_badge_select span.select_prev.disabled:hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowgreyleft.png'.') no-repeat;	
}
.ysc_badge_select span.select_prev:hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowhoverleft.png'.') no-repeat;	
}	
.ysc_badge_select span.selected_badge_num {
	position:absolute;
	left:13px;
	width:77px;
	top:3px;
	text-align:center;
}
.ysc_badge_select span.select_next {
	display:block;
	position:absolute;
	left:90px;	
	width:13px;
	top:5px;
	height:9px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowblueright.png'.') no-repeat;	
}
.ysc_badge_select span.select_next.disabled, .ysc_badge_select span.select_next.disabled:hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowgreyright.png'.') no-repeat;
}
.ysc_badge_select span.select_next:hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowhoverright.png'.') no-repeat;
}
#submit_buttons {
	padding-top:20px;
	text-align:center;
	clear:both;
}
#submit_buttons input {
	width:106px;
	height:30px;
	border:none;
	font-weight:bold;
	font-size:12px;
	pointer:cursor;
}
#submit_buttons input.save {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttondarkblue.png'.') no-repeat;
}
#submit_buttons input.preview {
	width:146px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttonblue_long.png'.') no-repeat;
}
#submit_buttons input.cancel {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttongrey.png'.') no-repeat;
}
#submit_buttons input:hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttonhover.png'.') no-repeat;	
}
#submit_buttons input.preview:hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttonhover_long.png'.') no-repeat;	
}
body {
	background:#ffffff none repeat scroll 0%;
}
#powered_by_y {
	text-align:center;
	width:100%;
	margin-top:45px;
}
#ysc_tos {
	text-align:center;
	color:#666;
	font-family:Times New Roman, Times, serif;
}
		';
	case 'preview-msie':
	return '
.hover {
	text-decoration:underline;
}
img.menulink {
	position:relative;
	top:2px;
}
img.menudelete {
	top:0px;
}
.ysc_menu_container {
	z-index:0;
}
.ysc_link_menu_tab img {
	position:relative;
	top:1px;
}	
#preview_header {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/foundmodbg.gif'.') no-repeat;
}	
.ysc_embed_menu_open {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/slideleftbg.gif'.') no-repeat;
}
.ysc_embed_menu_open.multi {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/slideleftbg-multi.gif'.') no-repeat;
}
.ysc_embed_menu_open_right.multi {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/sliderightbg-multi.gif'.') no-repeat;
}
.ysc_embed_menu_tab {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/hiddentableft.gif'.') no-repeat;	
}
.ysc_link_menu_open {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/linkstateballoon_med.gif'.') no-repeat;
}
.ysc_link_menu_open.small {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/linkstateballoon_small.gif'.') no-repeat;	
}
.ysc_badge_select {
	top:5px;
}
.ysc_badge_select span.select_prev_disabled, .ysc_badge_select span.select_prev_hover {
	display:block;
	position:absolute;
	width:13px;
	top:5px;
	height:9px;
}
.ysc_badge_select span.select_next_disabled, .ysc_badge_select span.select_next_hover {
	display:block;
	left:90px;
	position:absolute;
	width:13px;
	top:5px;
	height:9px;
}
.ysc_badge_select span.select_prev_disabled {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowgreyleft.gif'.') no-repeat;
}
.ysc_badge_select span.select_next_disabled {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowgreyright.gif'.') no-repeat;
}

.ysc_badge_select span.select_prev {
	top:6px;
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowblueleft.gif'.') no-repeat;	
}
.ysc_badge_select span.select_prev_hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowhoverleft.gif'.') no-repeat;	
}
.ysc_badge_select span.select_next {
	top:6px;	
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowblueright.gif'.') no-repeat;	
}
.ysc_badge_select span.select_next_hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/arrowhoverright.gif'.') no-repeat;
}
#submit_buttons input.save {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttondarkblue.gif'.') no-repeat;
}
#submit_buttons input.preview {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttonblue_long.gif'.') no-repeat;
}
#submit_buttons input.cancel {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttongrey.gif'.') no-repeat;
}
#submit_buttons input.hover {
	background: url('.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/buttonhover.gif'.') fixed top left no-repeat;	
}
		';
	}

}

function ysc_content_filter($content_str) {
	global $post;
	// don't insert a script tag when we're previewing, or for rss
	if (!is_feed() && !(isset($_GET['ysc_action']) || $_GET['ysc_action'] == 'render_preview')) {
		$json = stripslashes(get_post_meta($post->ID, 'ysc_entity_script', true));
		$script = '<script type="text/javascript">'.$json.'</script>';
		return $script.$content_str.'<div style="clear:both;"></div>';
	}
	return $content_str;
}
add_filter('the_content', 'ysc_content_filter');

function ysc_render_preview() {
	if (isset($_GET['post_ID'])) {
		$post_ID = $_GET['post_ID'];
		$post = get_post($post_ID);
	}
	else {
		wp_die('Sorry, no post matched your criteria.');
	}
	if (!current_user_can('read_post', $post_ID)) {
		wp_die('Sorry, you are not allowed to view this post.');
	}
	
	if (strpos($post->post_date, '0000-00-00') !== false) {
		$post->post_date = current_time('mysql');
	}

	$ysc_entity_script = get_post_meta($post_ID, 'ysc_entity_script', true);
	
	$content = $post->post_content;
	// run only select filters by hand here.
	$content = convert_smilies($content);
	$content = wpautop($content);
	$content = str_replace(']]>', ']]&gt;', $content);
	
	$img_ext = ysc_agent_is_msie() ? 'gif' : 'png';
	$yfsc_images = yfsc_get_pix($content);

	print('
		<div id="preview_header">
			<h3>We found <span id="ysc_preview_num_shortcuts">0 Shortcuts</span> for your post.</h3>
			<p>
				See below for the suggested Shortcuts in your post. 
				You can modify each one individually or remove or 
				create links for all of them at once.
			</p>
			<ul>
				<li id="remove_shortcuts">
					<img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/menudelete.'.$img_ext.'" class="menudelete"/>
					<span>Remove all Shortcuts</span>
				</li>
				<li id="convert_shortcuts">
					<img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/menulink.'.$img_ext.'" class="menulink"/>
					<span>Convert all links to badges</span>
				</li>
			</ul>
		</div>'.
		'<form name="post" action="post.php" method="post" id="preview_form" onsubmit="ysc.preview_submitted(); return true;">
			<h2 id="post_title">'.$post->post_title.'</h2>
			<p id="byline">'
				.date('F jS, Y', strtotime($post->post_date.' GMT'))
				.' &bull; '.$post->comment_count.' Comments
			</p>
			<hr class="byline_border" align="left" />
			<div id="preview_rendering" class="entry">'.$content.'</div>
			' . getFlickrChooser($yfsc_images) . '
			<input type="hidden" name="post_ID" value="'.$_GET['post_ID'].'" />
			<input type="hidden" name="ysc_action" value="submit_preview" />
			<input type="hidden" name="ysc_entity_script" value="" />
			<input type="hidden" name="ysc_content" value="" />
			<div id="submit_buttons">
				<input type="submit" name="ysc_preview_action" class="save" value="Save" onclick="FlickrSC.attribution.stripImages();" onmouseover="FlickrSC.effects.hoverStop();" />
				<input type="submit" name="ysc_preview_action" class="cancel" value="Cancel" onmouseover="FlickrSC.effects.hoverStop();" />
			</div>
			<div id="powered_by_y"><img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/poweredbyYshortcuts.gif" /></div>
			<p id="ysc_tos">
				The content within these shortcuts may be provided under the following <a href="http://info.yahoo.com/legal/us/yahoo/contextualshortcuts/contextualshortcuts-1815.html">terms of service</a>.
			</p>
		</form>
	');
}

function ysc_menu_items() {
	if (isset($_GET['ysc_action']) && $_GET['ysc_action'] == 'render_preview') {
		add_submenu_page(
			'post-new.php'
			, 'Review Shortcuts'
			, 'Review Shortcuts'
			, 1
			, basename(__FILE__)
			, 'ysc_render_preview'
		);
	}
}
add_action('admin_menu', 'ysc_menu_items');

function ysc_admin_head() {
	print('<script type="text/javascript" src="http://fe.shortcuts.search.yahoo.com/script?fr=csc_wordpress"></script>');
	if (isset($_GET['ysc_action']) && $_GET['ysc_action'] == 'render_preview'){
		$post_id_str = '';
		if(isset($_GET['post_ID'])) {
			$post_id_str = '&ysc_post_script='.$_GET['post_ID'];
		}
		print('
			<script type="text/javascript" src="'.get_bloginfo('wpurl').'/index.php?ysc_action=ysc_js'.$post_id_str.'"></script>
			<link type="text/css" href="'.get_bloginfo('wpurl').'/index.php?ysc_action=ysc_preview_css" rel="stylesheet" />
		');		
	}
	else {
		if (isset($_GET['action']) && ($_GET['action'] == 'edit') && isset($_GET['post'])) {
			$post_id_str = '&ysc_post_script='.$_GET['post'];
		}
		else {
			$post_id_str = '';
		}		
		print('
			<script type="text/javascript" src="'.get_bloginfo('wpurl').'/index.php?ysc_action=ysc_js'.$post_id_str.'"></script>
			<link type="text/css" href="'.get_bloginfo('wpurl').'/index.php?ysc_action=ysc_admin_css" rel="stylesheet" />
		');
	}
}
add_action('admin_head', 'ysc_admin_head');

function ysc_wp_head() {
	print('<script type="text/javascript" src="http://fe.shortcuts.search.yahoo.com/script?fr=csc_wordpress"></script>');
	print('
		<script type="text/javascript" src="'.get_bloginfo('wpurl').'/index.php?ysc_action=ysc_js&ysc_page=publish"></script>
		<link rel="stylesheet" href="'.get_bloginfo('wpurl').'/index.php?ysc_action=ysc_published_css" type="text/css" media="screen" />
	');	
}
add_action('wp_head', 'ysc_wp_head');

function ysc_dbx() {
	// to preview, we submit a save-and-continue-editing with our own special arguments
	// calculate these in wordpress-land, so filters can be applied, or in case they change.
	$submit_name = 'save';
	$submit_value = attribute_escape( __('Save and Continue Editing') );
	
	print('
		<fieldset id="ysc_dbx" class="dbx-box">
			<h3 class="dbx-handle"><img src="'.get_bloginfo('wpurl').'/'.PLUGINDIR.'/yahoo-shortcuts/images/y_logo_violet.gif" style="top:2px; position:relative;" /> Powered Shortcuts</h3>
			<div class="dbx-content">
				<div id="ysc_num_shortcuts"></div>
				<div id="ysc_searching_indicator">To find great content, just start writing.</div>
				<div id="ysc_yahoo_this_post" onclick="ysc.preview_post(); return false;"><p>Review This Post</p></div>
			</div>
		</fieldset>
	');	
}
add_action('dbx_post_sidebar', 'ysc_dbx', 1);

function ysc_addMCE_plugin($plugins) {
	$plugins[] = 'yahoo_shortcuts';
	return $plugins;
}
add_filter('mce_plugins', 'ysc_addMCE_plugin');

function ysc_addMCE_css($csv) {
	return $csv.','.get_bloginfo('wpurl').'/index.php?ysc_action=ysc_rte_css';
}
add_filter('mce_css', 'ysc_addMCE_css');
?>
