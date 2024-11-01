// JavaScript Document
function YFSC_Menu(core_obj, obj) {
	this.core = core_obj;
	this.menu_target = FlickrSC.get(obj);
	this.menu_object = this.createMenu();
}

YFSC_Menu.prototype = {
	
	menu_items: {},
	
	setMenuItems: function() {
		this.menu_items = {	hide: ["Hide this menu", "menuarrowright.png", this.hide], 
												remove: ["Remove", "menudelete.png", this.remove], 
												change_size: ["Change size", "menuchangesize.gif", this.changeSize]	};
	},
	
	createMenu: function() {
		
		this.setMenuItems();
		var container = this.core.create("div", {"attributes": {"id": this.menu_target.id + "_items"}});
		container.className = "yfsc_menu yfsc_embed_menu_open";
		var menu_list = this.core.create("ul");
		
		for (i in this.menu_items) {
			var li  = this.core.create("li");
			var li_img = this.core.create("img", {"attributes": {"src": FlickrSC.path + "/yahoo-shortcuts/images/" + this.menu_items[i][1]}});
			li.appendChild(li_img);
			menu_list.appendChild(li);
			li.innerHTML += "<span>"+this.menu_items[i][0]+"</span>";
			this.core.addListener("onclick", li, this.menu_items[i][2]);
		}
		
		container.appendChild(menu_list);
		return container;

	},
	
	open: function() {
		
		var m = this.menu.core.get(this.menu.menu_target.id); 
		m.appendChild(this.menu.menu_object);
	
	},
	
	hide: function() {
		this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);
		
	},
	
	remove: function() {
		this.parentNode.parentNode.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode.parentNode.parentNode);
	},
	
	changeSize: function(e) {
		target = this.parentNode.parentNode.parentNode.nextSibling;
		target_src = target.src;
		target_size = target_src.substring(target_src.length - 6, target_src.length);
		target_size = target_size.split(".")[0];
		
		pwi = FlickrSC.get("flickr-preview-window-image");
		pwi.selected_image_url = target_src;
		pwi.selected_id = target.id.split("_")[2];
		FlickrSC.effects.showPreview(e);
		
		switch(target_size) {
			case "_s":
				size_label = "square";
				break;
			case "_t":
				size_label = "thumbnail";
				break;
			case "_m":
				size_label = "small";
				break;
			default:
				size_label = "medium";
				break;
		}
		
		FlickrSC.get(size_label).checked = true;
		
		pw = FlickrSC.get('flickr-preview-window');
		pw.replace_id = target.parentNode.id;
		pw.style.visibility = "visible";
		this.parentNode.parentNode.parentNode.removeChild(this.parentNode.parentNode);
		
	}
	
};