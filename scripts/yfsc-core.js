// JavaScript Document
function YFSC() {}

YFSC.prototype = {
	
	images: [],
	count: 0,
		
	attributionCallback: function(response) {
		var yfsc_obj = eval("(" + response.responseText.split("<!--")[0] + ")");
		if (yfsc_obj.id) {
			arguments[1][0].attributeImage(yfsc_obj.id, yfsc_obj.copyright);
		}else{
			arguments[1][0].debug("error: " +yfsc_obj.message);
		}
		this.count++;
	},
	
	create: function(type, definition) {

		var obj = new Object();

		obj = document.createElement(type);
		
		if (definition) {
			if (typeof(definition.attributes) == "object") {
				for (attribute in definition.attributes) {
						obj.setAttribute(attribute,  definition.attributes[attribute]);
				}
			}
			
			if (typeof(definition.properties) == "object") {
				for (property in definition.properties) {
						obj[property] = definition.properties[property];
				}
			}
		}
		
		return obj;
		
	},

	attributeImage: function(id, cc) {
		var target = this.get("yfsc_"+id);
		target_wrapper = this.create("span", {'attributes': {'class': "yfsc_wrapper", 'id': "yfscw_"+id}});
		target_attribute = "&copy; " + cc;
		target_attribute_container = this.create("div", {'attributes': {'class': "yfsc_attribution"}});
		document.body.appendChild(target_wrapper);
		document.body.insertBefore(target, target_wrapper);
		target_wrapper.appendChild(target);
		target_wrapper.appendChild(target_attribute_container);
		target_attribute_container.innerHTML = target_attribute;
	},
	
	get: function(elementRef) {
		
		var element = new Object();
		
		if (typeof(elementRef) == "string") {
			element = document.getElementById(elementRef);
		}else if (typeof(elementRef) == "object") {
			element = elementRef;
		}
		
		return element;
		
	},
	
	getImages: function() {
		return document.getElementsByTagName("img");
	},
	
	
	request: function(method, url, callback) {
	  
	  var request_object = (window.XMLHttpRequest) ? new XMLHttpRequest() : (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : false;
		if (!request_object) return request_object;
		method = method.toLowerCase();
		//places additional arguments to be used by the callback
		//function in an indexed array for later retrieval
		
		var args = [];

		if (method == "get" && arguments.length > 3) {
			for (arg_index=3;arg_index<arguments.length;arg_index++) {
				args.push(arguments[arg_index]);
			}
		}else if (method == "post" && arguments.length > 4) {
			for (arg_index=4;arg_index<arguments.length;arg_index++) {
				args.push(arguments[arg_index]);
			}
		}else{
			args.push(false);
		}

		request_object.onreadystatechange = function() {
			if (request_object.readyState == 4 && request_object.status == 200) {
				if (typeof(callback) == "function")	callback(request_object, args);
			}else{
				//Error
			}
	  }
	  
	  request_object.open(method, url, true);
		request_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		var post_var = "";
		if (method == "post") {
			for (i in arguments[3]) {
				post_var += "&" + i + "=" + arguments[3][i];
			}
			post_var = post_var.substring(1, post_var.length);
		}
		
	  request_object.send(post_var);
			  
	},
	
	debug: function(str) {
		if (!this.get('debug')) {
			nd = this.create("div", {attributes: {id:'debug'}});
			document.body.appendChild(nd);
		}else{
			nd = this.get('debug');
		}
		nd.innerHTML += str+"<br />";
	},
	
	addListener: function(_event, obj, _listener) {
		obj[_event] = _listener;
	},
	
	removeListener: function(_event, obj) {
		obj[_event] = null;
	}

	
};
