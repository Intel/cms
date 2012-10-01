var Plugin_input_richtext = {
    Generate: function(object_data) {
        var tmpl = jQuery.templates('<label for="{{>name}}">{{>title}}</label></br> \
									 <textarea id="richtext_box"></textarea> \
									 ');
        
        // Render template
        var string = $(tmpl.render(object_data));
        
        // Add Tooltips
        string.tipsy();
		
		// Load richtext editor
        string.filter("textarea").bind("showup", function() {
            $(this).cleditor();
        });
		  
        // Add to container
        return string;
    },
    
    LoadLocale: function(string, new_locale, old_locale) {
        // since string is array of children, we need to use filter
        var input = string.filter('textarea');
        
        // Retrieves object data from object (function is in editor.js)
        var object_data = GetObjectData(string);
        
        // Save value (if we already had locale set)
        if (old_locale)
            object_data.locales[old_locale] = input.val();
        
        // Set new value
        input.val(object_data.locales[new_locale]);
    },
    
    Save: function(string) {
        var object_data = GetObjectData(string);
        
        // Save locale
        var input = string.find('textarea');
        // Flush html to textarea
        input.cleditor()[0].updateFrame();
        object_data.locales[editorData.locales.current] = input.val();
        
        // Send all data, we'll process it in php
        return object_data;
    }
};

PluginMgr.Hook("EditableObject_Generate", Plugin_input_richtext.Generate, "input_richtext");
PluginMgr.Hook("EditableObject_LoadLocale", Plugin_input_richtext.LoadLocale, "input_richtext");
PluginMgr.Hook("EditableObject_Save", Plugin_input_richtext.Save, "input_richtext");