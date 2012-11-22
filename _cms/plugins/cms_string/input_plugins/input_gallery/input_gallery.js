CMS.PluginSystem.LoadPlugin('input_gallery', {
    PluginInit: function(Hooks) {
        Hooks["EditableObject_Generate"] = this.Generate;
        Hooks["EditableObject_Save"] = this.Save;
    },
    
    Generate: function(object_data) {
        var tmpl = jQuery.templates('<label><span style="float: left; margin-right: 5px;" class="ui-icon ui-icon-grip-diagonal-se"></span>{{>title}}</label></br> \
                                     <ul class="ui-widget-content" style="list-style-type: none; margin: 0; padding: 0; width: 450px;"></ul></br> \
                                     <input type="file" class="text ui-widget-content ui-corner-all" multiple>');
        
        // Render template
        var gallery = $(tmpl.render(object_data));
        var container = gallery.filter("ul");
        
        function AppendImage(hash) {
            var image = jQuery('<li class="ui-state-default" style="margin: 3px 3px 3px 0; padding: 1px; float: left; width: 100px; height: 100px; position: relative;"> \
                                    <img data-hash="' + hash + '" src="_cms/static/?hash=' + hash + '&w=100&h=100" /> \
                                    <span style="position: absolute; right: 0; top: 0;" class="ui-icon ui-icon-trash"></span> \
                                </li>');
            
            image.find("span").click(function() {
                image.remove();
            });
            
            container.append(image);
        }
        
        if (object_data.images) {
            jQuery.each(object_data.images, function(index, image) {
                AppendImage(image.hash);
            });
        } else
            object_data.images = new Object;
        
        // Sortable
        container.sortable();
        container.disableSelection();
        
        // Uploading
        gallery.filter('input').change(function() {
            if (this.files.length) {
                jQuery.each(this.files, function(index, file) {
                    IMGUpload(file, function(xhr) {
                        var hash = JSON.parse(xhr.responseText).hash;
                        AppendImage(hash);
                    });
                });
            }
        });	
        
        // Add to container
        return gallery;
    },
    
    Save: function(gallery) {
        var container = gallery.filter("ul");
        
        var data = GetObjectData(gallery);
        data.images = new Array;
        
        container.children().each(function() {
            var image = new Object;
            image.hash = $(this).find("img").attr("data-hash");
            data.images.push(image);
        });
        
        // Send all data, we'll process it in php
        return data;
    }
});
