// JavaScript Document
function YFSC_Attribution(core_obj) {
	this.core = core_obj;	
}

YFSC_Attribution.prototype = {
	
	images: [],
	count: 0,
		
	attributionCallback: function(response) {

		var yfsc_obj = eval("(" + response.responseText.split("<!--")[0] + ")");
		if(yfsc_obj.person) {
		  if (yfsc_obj.person.username._content) {
		    target = arguments[1][0].core.get("yfsc_" + arguments[1][1]);
		    if (arguments[1][2] == "preview") {
		      pw = FlickrSC.get('flickr-preview-window');
		      pw.replace_id = true;
		      pwi = FlickrSC.get('flickr-preview-window-image');
		      pwi.selected_image_url = target.src;
		    }
		    _parent = target.parentNode.parentNode;
				_immediate_parent = target.parentNode;
				if (_immediate_parent.style.textAlign === 'center' && _immediate_parent.nodeName === 'P') {
					_parent.insertBefore(target, _immediate_parent);
					_parent.removeChild(_immediate_parent);
					_align = "center";
				}else{
					_align = "left";
				}
				
				if (target.align === 'left') {
					_align = "left";
				}else if (target.align === 'right') {
					_align = "right";
				}
				
				_proxy = new Image();
		    _proxy.src = FlickrSC.path + "/yahoo-shortcuts/images/proxy.gif";
		    _temp_target = new Image();
		    _temp_target.src = target.src;
		    _temp_target.id = target.id;
		    _temp_target.className = target.className;
				_temp_target._align = _align;
		    _w = (arguments[1][0].getPxSize(target, 'width'));
		    _temp_target._w = _w;
		    target.parentNode.replaceChild(_proxy, target);
		    //pr = FlickrSC.get('preview_rendering');
			aw = arguments[1][0].attributeImage(_temp_target, yfsc_obj.person.username._content, yfsc_obj.person.photosurl._content,yfsc_obj);
		    if (arguments[1][2]) {
		      _menu = FlickrSC.create("div", {"attributes": {"id": FlickrSC.attribution.checkNumberedId("yfsc_" + arguments[1][1] + "_menu"), "title": "Open Menu"}});
		      _menu.menu = new FlickrSC.menu(FlickrSC, _menu);
		      _menu.className = "yfsc_image_menu_tab"
			FlickrSC.addListener("onclick", _menu, _menu.menu.open);
		      
		      aw.appendChild(_menu);
		      aw.insertBefore(_menu, aw.firstChild);

		    }
		    _proxy.parentNode.replaceChild(aw, _proxy);

		  }else{
		    arguments[1][0].core.debug("error: " +yfsc_obj.person.id);
		  }
		  this.count++;
		}
	},
	
	attributeImage: function(id, cc, url, yfsc_obj) {
		if (typeof(id) == "object") {
			var target = id;
			var id_arr = target.id.split("_");
			target_wrapper = this.core.create("div", {'attributes': {'id': this.checkNumberedId("yfscw_"+id_arr[1] + "_" + id_arr[2])}});
		}else{
			var target = this.core.get("yfsc_"+id);
			target_wrapper = this.core.create("div", {'attributes': {'id': this.checkNumberedId("yfscw_"+id)}});
		}
		
		clr =  this.core.create("div", {'attributes': {'style': "clear:both;width:1px;height:1px;line-height:1px;font-size:1px;"}});
		cc_img = this.core.create("img", {'attributes': {'src':FlickrSC.path+"/yahoo-shortcuts/images/icon-sm-cc-2.gif", 'alt':'&copy;', 'title':'&copy;'}});
		cc_text = document.createTextNode(" by ");
		cc_link =  this.core.create("a", {'attributes': {'href':url, 'target':'_blank;', 'title':'&copy;'}});
		cc_link.className = 'yfsc_cc';
		cc_link_text = document.createTextNode(cc);
		cc_link.appendChild(cc_link_text);

		target_attribute_container = this.core.create("div");
		document.body.appendChild(target_wrapper);
		_a = target._align || "left";
		target_wrapper.className = "yfsc_wrapper yfsc_wrapper_" + _a;
		if (target._w) {
			target_wrapper.style.width = target._w;
		}
		
		if (_a === 'center') {
			p = this.core.create("p", {'attributes': {'style':'text-align:center;'}});
			document.body.appendChild(p);
			document.body.insertBefore(p, target_wrapper);
			p.appendChild(target_wrapper);
			p.insertBefore(target, target_wrapper);			
		}else{
			document.body.insertBefore(target, target_wrapper);
		}
		target_id = target.src;

		target_id = target_id.replace(/http\:\/\/.*?\/.*?\//,"");
		target_id = target_id.replace(/_.*/,"");
		target.url = url.replace("photos/","")+target_id;

		target_wrapper.appendChild(target);
		target.style.cursor = 'pointer';
		FlickrSC.addListener("onclick", target, function() { window.open(this.url) });
//		target.align = (_a === 'center')?'middle':_a;
		target_wrapper.appendChild(target_attribute_container);
		target_attribute_container.className = "yfsc yfsc_attribution";
		target_attribute_container.appendChild(cc_img);
		target_attribute_container.appendChild(cc_text);
		target_attribute_container.appendChild(cc_link);
		target_wrapper.appendChild(clr);
		return target_wrapper;
	},

	checkNumberedId: function(id, return_count) {
		
		var id_arr = id.split("_");
		var ret_val = id;
		
		if (this.core.get(id)) {
			while(this.core.get(id_arr[0]+"_"+id_arr[1]+"_"+id_arr[2])) {
				id_arr[1]++;
			}
			ret_val = id_arr.join("_");
		}
		
		if (return_count) {
			ret_val = id_arr[1];
		}
		
		return ret_val;
		
	},
	
	getImages: function() {
		return document.getElementsByTagName("img");
	},
	
	getPxSize: function() { 
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

	stripImages: function() {
		images = document.getElementById("preview_rendering").getElementsByTagName("img");
		for (var i=0;i<images.length;i++) {
			if (images[i].className.match('yfsc_image')) {
				images[i].setAttribute('align', images[i]._align);
				wid = images[i].parentNode.id;
				pid = images[i].parentNode.parentNode.id;
				_parent = images[i].parentNode.parentNode;
				if (images[i].parentNode.className.match('yfsc_wrapper_center')) {
					p = this.core.create("p");
					_parent.appendChild(p);
					p.style.textAlign = 'center';
					FlickrSC.get(wid).parentNode.insertBefore(p, FlickrSC.get(wid));
					p.appendChild(images[i]);
				}else{
					FlickrSC.get(wid).parentNode.insertBefore(images[i], FlickrSC.get(wid));
				}
				_parent.removeChild(FlickrSC.get(wid));
			}
		}

		return true;
		
	},
	
	tagImages: function(id, mode, has_menu) {
		
		this.images = document.getElementsByTagName("img");
		
		for (var i=this.images.length-1;i>=0;i--) {
			if (this.images[i].className.indexOf('yfsc_image') != 1) {
				var id_arr = this.images[i].id.split("_");
				if (id_arr[0] == 'yfsc') {
				  this.core.request("post", (FlickrSC.base_path+"/wp-content/plugins/yahoo-shortcuts/flickr_ajax.php"), 
						    this.attributionCallback, {action: "yfsc_get_user", user_id: id_arr[2], cookie: encodeURIComponent(document.cookie)}, 
						    this, id_arr[1]+"_"+id_arr[2], has_menu, mode);
				}
			}
		}
		
	}
	
};
