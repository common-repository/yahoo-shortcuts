// JavaScript Document
function YFSC_Effects(core_obj) {
	this.core = core_obj;	
	this.hover_overlay_object = this.core.get("flickr-preview-overlay");
	this.hover_overlay_image = this.core.get("flickr-preview-image");
	this.hover_overlay_button = this.core.get("button-add-to-post");
	this.hover_overlay_button.href = "javascript:void(0);";
	this.preview_window_image = this.core.get("flickr-preview-window-image");
	this.hover_overlay_timer = false;
	this.is_over = false;
}

YFSC_Effects.prototype = {
	
	images: [],
	image_count: 0,
	user_image_info: false,
	attempt_count: 1,
	cancel_retry: false,

	setUserInfo: function(json_object) {
		try {
			this.user_image_info = (json_object) ? eval( "(" + json_object + ")" ) : "No Username Found";
			FlickrSC.get("yfsc_image_owner_preview").innerHTML = this.user_image_info.person.username._content;
			FlickrSC.addListener("onclick", FlickrSC.get("preview-button-submit"), this.chooseImage);
			FlickrSC.get("preview-button-submit").style.cursor = "pointer";
			FlickrSC.get("preview-button-submit").src = FlickrSC.path + '/yahoo-shortcuts/images/button-submit.gif';
			FlickrSC.get('yfsc_attribution_message').innerHTML = "";
		}catch(e) {
			if (!this.cancel_retry) {
				this.retrySetUserInfo(FlickrSC.get('flickr-preview-window-image').selected_id);
			}else{
				this.cancelSetUserInfo();
			}
		}
	},
	
	cancelSetUserInfo: function() {
			FlickrSC.get('yfsc_attribution_message').innerHTML = "";
			this.attempt_count = 1;
			this.cancel_retry = false;
	},
	
	retrySetUserInfo: function(id) {
		if (this.attempt_count <= 3) {
			FlickrSC.get('yfsc_attribution_message').innerHTML = "Attempt ("+this.attempt_count+") to retrieve attribution failed. Now retrying.";
			getOwnerInfo(id);
			this.attempt_count++;
		}else{
			FlickrSC.get('yfsc_attribution_message').innerHTML = "Service failed. <a href='javascript:void(0);' onclick='this.parentNode.innerHTML = \"\"; FlickrSC.effects.cancelSetUserInfo(); getOwnerInfo(\""+id+"\");'>Try again?</a>";
		}
	},
	
	getImages: function() {
		return this.core.get("image-results").getElementsByTagName("img");
	},

	getPixelSize: function() { 
			var isOpera = navigator.userAgent.toLowerCase().indexOf('opera') > -1;
			if (document.documentElement.currentStyle) { // IE and Opera
					return function(el, prop) {
							var current = el.currentStyle[prop],
									capped = prop.charAt(0).toUpperCase() + prop.substr(1), 
									offset = 'offset' + capped,                             
									pixel = 'pixel' + capped,                               
									value = el[offset];

							if (current == 'auto' || isOpera) { 
									el.style[prop] = value ; 
									if (el[offset] > value) { 
											value -= el[offset] - value;
									}
									el.style[prop] = (isOpera) ? value : 'auto'; 

							} else if (current.indexOf('px') > -1) {
								 return current; 
							} else { 
									if (!el.style[pixel] && !el.style[prop]) {
											el.style[prop] = current; 
									}
									value = el.style[pixel];
							}
							return value;
					};

			}
			else if (document.defaultView && document.defaultView.getComputedStyle) { 
					return function(el, prop) {
							return document.defaultView.getComputedStyle(el, '')[prop];
					};
			} else { 
					return function(el, prop) {
							var capped = prop.charAt(0).toUpperCase() + prop.substr(1); 
							return el[offset + capped];
					}
			}
	}(),
	
	getElementPosition: function(obj) { 
		var pos_left = curtop = 0;
		if (obj.offsetParent) {
			pos_left = obj.offsetLeft
			pos_top = obj.offsetTop
			while (obj = obj.offsetParent) {
				pos_left += obj.offsetLeft
				pos_top += obj.offsetTop
			}
		}
		return {left: pos_left, top: pos_top};
	},
	
	getWindowSize: function() {
		
		var window_width = 	window.innerWidth || 
												document.body.clientWidth || 
												document.documentElement.clientWidth;
		var window_height = window.innerHeight || 
												document.body.clientHeight || 
												document.documentElement.clientHeight;

		window_height +=	(window.pageYOffset)?window.pageYOffset:0;

		return {height: window_height, width: window_width};
		
	},
	
	getEvent: function(event) {
		var event_object = event || window.event;
		
		return e = {	object: event_object,
									type: event_object.type,
									target: event_object.target || event_object.srcElement,
									_x: function() {
												mx = event_object.clientX || event_object.pageX;
												mx += window.pageXOffset || 
															document.body.scrollLeft || 
															document.documentElement.scrollLeft;
												return mx;
											}(),
									_y: function() {
												my = event_object.clientY || event_object.pageY;
												my += window.pageYOffset || 
															document.body.scrollTop || 
															document.documentElement.scrollTop;
												return my;
											}()};
	},
	
	prepImages: function() {
		this.images = this.getImages();
		this.core.addListener("onmouseout", this.core.get("image-results"), this.hoverStop);
		this.core.addListener("onmouseover", this.hover_overlay_object, function() { this.style.visibility="visible"; });
		this.core.addListener("onmouseout", this.hover_overlay_object, function() { this.style.visibility="hidden"; });
		this.core.addListener("onclick", this.hover_overlay_button, this.showPreview);
		for (img_ind=0;img_ind<this.images.length;img_ind++) {
			this.images[img_ind].core = this.core;
			this.core.addListener("onmouseover", this.images[img_ind], this.hoverPreview);
			this.core.addListener("onmouseout", this.images[img_ind], this.hoverStop);
		}
	},
	
	hoverPreview: function(e) {
		FlickrSC.effects.is_over = true;
		var _event = FlickrSC.effects.getEvent(e);
		
		target_thumb = _event.target;
		target_overlay = FlickrSC.effects.hover_overlay_object;
		target_image = FlickrSC.effects.hover_overlay_image;
		target_preview_window_image = FlickrSC.effects.preview_window_image;
		target_image.src = _event.target.src.replace("_s.", "_m.");
		target_preview_window_image.selected_image_url = _event.target.src;
		target_preview_window_image.selected_id = _event.target.id;
		target_image.target_thumb = target_thumb;
		target_image.target_overlay = target_overlay;
		target_h = parseInt(FlickrSC.effects.getPixelSize(target_overlay, "height")); 
		target_w = parseInt(FlickrSC.effects.getPixelSize(target_overlay, "width"));
		target_thumb_pos = FlickrSC.effects.getElementPosition(target_thumb);

		_top = (target_thumb_pos.top + 25) - (target_h + 10);
		_left = (target_thumb_pos.left + 25) - (target_w / 2);
	
		target_overlay.style.top = _top + "px";
		target_overlay.style.left = _left + "px";
		
		target_image.onload = function() {
			target_h = parseInt(FlickrSC.effects.getPixelSize(this.target_overlay, "height")); 
			target_w = parseInt(FlickrSC.effects.getPixelSize(this.target_overlay, "width"));
			
			target_thumb_pos = FlickrSC.effects.getElementPosition(this.target_thumb);
	
			_top = (target_thumb_pos.top + 25) - (target_h + 10);
			_left = (target_thumb_pos.left + 25) - (target_w / 2);
		
			this.target_overlay.style.top = _top + "px";
			this.target_overlay.style.left = _left + "px";

			if (FlickrSC.effects.is_over) {
				this.target_overlay.style.visibility = "visible";
			}
		}
		
	},
	
	hoverStop: function(e) {
		FlickrSC.effects.is_over = false;
		FlickrSC.effects.hover_overlay_object.style.visibility = "hidden";
	},

	hoverMove: function(e) {
		
		var _event = FlickrSC.effects.getEvent(e);
		target = FlickrSC.effects.hover_overlay_object;
		
		target_h = parseInt(FlickrSC.effects.getPixelSize(target, "height")); 
		target_w = parseInt(FlickrSC.effects.getPixelSize(target, "width")); 

		_top = _event._y - (target_h + 10);
		_left = _event._x - (target_w / 2);
	
		target.style.top = _top + "px";
		target.style.left = _left + "px";
		
	},
	
	showPreview: function(e) {
		
		var _event = FlickrSC.effects.getEvent(e);
		var _size = FlickrSC.effects.getWindowSize();
		
		if (!FlickrSC.get("flickr-preview-overlay-mask")) {
			po = FlickrSC.create("div", {attributes: {id:'flickr-preview-overlay-mask'}});
			document.body.appendChild(po);
			po.style.top = 0;
			po.style.left = 0;
			po.style.position = 'absolute';
			po.style.zIndex = 1200;
			po.style.height = _size.height+'px';
			po.style.width = _size.width+'px';
			po.style.backgroundColor = '#000000';
			po.style.opacity = '.8';
			po.style.filter = 'alpha(opacity=80)';
			FlickrSC.effects.setSelections();
			FlickrSC.addListener("onclick", FlickrSC.get("preview-button-cancel"), FlickrSC.effects.closePreview);
			FlickrSC.addListener("onresize", window, FlickrSC.effects.resizePreviewOverlay);
			FlickrSC.addListener("onscroll", window, FlickrSC.effects.resizePreviewOverlay);
			
		}else{
			po = FlickrSC.get('flickr-preview-overlay-mask');
			po.style.visibility = "visible";
		}
		pw = FlickrSC.get('flickr-preview-window');
		pw.replace_id = false;
		pw.style.position = "absolute";
		pw.style.visibility = "visible";
		pw.style.top = 40 +	(	window.pageYOffset || 
															document.body.scrollTop || 
															document.documentElement.scrollTop) + "px";
		pwi = FlickrSC.get('flickr-preview-window-image');
		pwi.current_image_url = pwi.selected_image_url;
		pwi_image_string = "<img src=\"" + pwi.selected_image_url + "\" />"; 
		pwi.innerHTML = pwi_image_string;
		FlickrSC.get('square').checked = true;
		getOwnerInfo(pwi.selected_id);
	},
	
	setSelections: function() {
		var radios = this.core.get("flickr-preview-window-options").getElementsByTagName("input");
		
		for (rad_ind=0;rad_ind<radios.length;rad_ind++) {
			this.core.addListener("onclick", radios[rad_ind], this.swapPreviewImage);
		}
	},
	
	swapPreviewImage: function(e) {
		
		pwi = FlickrSC.get("flickr-preview-window-image");
		target_size = pwi.firstChild.src.substring(pwi.firstChild.src.length - 6, pwi.firstChild.src.length);
		target_ext = target_size.split(".")[1];
		target_size = target_size.split(".")[0];

		switch(target_size) {
			case "_s":
				current_size = pwi.firstChild.src.replace(target_size+".", this.value);
				size_label = "square";
				break;
			case "_t":
				current_size = pwi.firstChild.src.replace(target_size+".", this.value);
				size_label = "thumbnail";
				break;
			case "_m":
				current_size = pwi.firstChild.src.replace(target_size+".", this.value);
				size_label = "small";
				break;
			default:
				current_size = pwi.firstChild.src.replace("."+target_ext, this.value+target_ext);
				size_label = "medium";
				break;
		}
		
		
		pwi.current_image_url = current_size;
		pwi_image_string = "<img src=\"" + pwi.current_image_url + "\" />"; 
		pwi.innerHTML = pwi_image_string;
		
	},
	
	closePreview: function(e) {
		FlickrSC.get("preview-button-submit").src = FlickrSC.path + "/yahoo-shortcuts/images/button-submit-disabled.gif";
		FlickrSC.get("flickr-preview-window").style.visibility = "hidden";
		FlickrSC.get("flickr-preview-overlay-mask").style.visibility = "hidden";
		FlickrSC.effects.cancel_retry = true;
		FlickrSC.effects.cancelSetUserInfo();
		return false;
	},
	
	chooseImage: function(e) {
		pw = FlickrSC.get("flickr-preview-window");
		pw.style.visibility = "hidden";
		FlickrSC.get("flickr-preview-overlay-mask").style.visibility = "hidden";
		pwi = FlickrSC.get('flickr-preview-window-image');
		FlickrSC.effects.image_count++;

		if(!pw.replace_id) {
			FlickrSC.effects.image_count = FlickrSC.attribution.checkNumberedId("yfsc_"+FlickrSC.effects.image_count+"_"+pwi.selected_id, true);
			pwi_image = FlickrSC.create("img", {"attributes": {"id": FlickrSC.attribution.checkNumberedId("yfsc_"+FlickrSC.effects.image_count+"_"+pwi.selected_id), "src": pwi.current_image_url}});
			pwi_image.className = "yfsc_image";
			pr = FlickrSC.get('preview_rendering');
			pr.appendChild(pwi_image);
			aw = FlickrSC.attribution.attributeImage(FlickrSC.effects.image_count+"_"+pwi.selected_id, FlickrSC.effects.user_image_info.person.username._content, FlickrSC.effects.user_image_info.person.photosurl._content);
			
			_menu = FlickrSC.create("div", {"attributes": {"id": pwi_image.id + "_menu", "title": "Open Menu"}});
			_menu.menu = new FlickrSC.menu(FlickrSC, _menu);
			_menu.className = "yfsc_image_menu_tab";
			FlickrSC.addListener("onclick", _menu, _menu.menu.open);
			
			aw.appendChild(_menu);
			aw.insertBefore(_menu, aw.firstChild);
			pr.insertBefore(aw, pr.firstChild);
		}else{
			target_replace = FlickrSC.get(pw.replace_id);
			target_replace.firstChild.nextSibling.src = pwi.firstChild.src;
			
			assign_w = function() {
				this.parentNode.style.width = FlickrSC.effects.getPixelSize(this, "width");
			};
			
			FlickrSC.addListener("onload", target_replace.firstChild.nextSibling, assign_w);
		}
		
		FlickrSC.removeListener("onclick", FlickrSC.get("preview-button-submit"));
		FlickrSC.get("preview-button-submit").style.cursor = "default";
		FlickrSC.get("preview-button-submit").src = FlickrSC.path + "/yahoo-shortcuts/images/button-submit-disabled.gif";
		FlickrSC.effects.user_image_info = false;
		
		return false;
	},
	
	resizePreviewOverlay: function() {
		var _size = FlickrSC.effects.getWindowSize();
		target = FlickrSC.get("flickr-preview-overlay-mask");
		
		target.style.height = _size.height+"px";
		target.style.width = _size.width+"px";
	}

};
