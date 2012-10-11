function IMGUpload(file, onload) {
   // file is from a <input> tag or from Drag'n Drop
   // Is the file an image?
   if (!file || !file.type.match('image.*')) return;

   // It is!
   // Let's build a FormData object
   var fd = new FormData();
   fd.append("image", file); // Append the file
   fd.append("key", "b7ea18a4ecbda8e92203fa4968d10660"); // Get your own key: http://api.imgur.com/

   // Create the XHR (Cross-Domain XHR FTW!!!)
   var xhr = new XMLHttpRequest();
   xhr.open("POST", "_cms/static/upload.php"); // Boooom!

   xhr.onload = function() { onload(xhr); }

   // Ok, I don't handle the errors. An exercice for the reader.
   // And now, we send the formdata
   xhr.send(fd);
}

function GetImg(hash, w, h)
{
	var url = "_cms/static/?hash=" + hash;
	
	if (w)
		url += "&w=" + w;
	if (h)
		url += "&h=" + h;
	
	return url;
}		

var Plugin_input_img = {
    Generate: function(object_data) {
        var tmpl = jQuery.templates('<label for="{{>name}}">{{>title}}</label></br> \
                                     <input type="file" title="Peržiūros failas bus automatiškai sumažintas iki {{>width}}x{{>height}} px"  class="text ui-widget-content ui-corner-all">\
									 <span title="<img src=\'\' width=200 height=200>">Peržiūra</span><br/>\
									 ');
        
        // Render template
        var string = $(tmpl.render(object_data));
       
		// Load image that's already there
		string.filter('span').attr('title', '<img src="'+GetImg(object_data.hash, 200, 200)+'" width=200 height=200>');
        
        // Add Tooltips
        string.filter('input').tipsy({html: true, gravity: 's'});
        string.filter('span').tipsy({html: true, gravity: 'w', opacity: 1.0 });
        
		// Perform activities
		string.filter('input').change(function() {
            IMGUpload(this.files[0], function(xhr) {
				var hash = JSON.parse(xhr.responseText).hash;
                string.filter('span').attr('title', '<img src="'+GetImg(hash, 200, 200)+'" width=200 height=200>');
                string.filter('span').trigger('mouseover').delay(3500, "qq").queue("qq", function(){ 
                  string.filter('span').trigger('mouseout');
                }).dequeue("qq");                
				object_data.hash = hash;
            });
        });		
		
        // Add to container
        return string;	
    },
    
    Save: function(string) {
        var object_data = GetObjectData(string);
        
        // Send all data, we'll process it in php
        return object_data;
    }
};

PluginMgr.Hook("EditableObject_Generate", Plugin_input_img.Generate, "input_img");
PluginMgr.Hook("EditableObject_Save", Plugin_input_img.Save, "input_img");