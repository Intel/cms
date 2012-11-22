CMS.PluginSystem.LoadPlugin("input_richtext", {
    PluginInit: function(Hooks) {
        Hooks["EditableObject_Generate"] = this.Generate;
        Hooks["EditableObject_LoadLocale"] = this.LoadLocale;
        Hooks["EditableObject_Save"] = this.Save;
    },
    
    Generate: function(object_data) {
        var tmpl = jQuery.templates('<label for="{{>name}}">{{>title}}</label></br> \
									 <textarea id="richtext_box"></textarea> \
									 ');
        
        // Render template
        var string = $(tmpl.render(object_data));
        
        // Add Tooltips
        string.tipsy();
        
        // Initialize datatype if object is new
        if (!object_data.locales)
            object_data.locales = new Object;
		
		// Load richtext editor
        string.filter("textarea").bind("showup", function() {
            $.cleditor.defaultOptions.controls = "bold italic underline strikethrough | color highlight removeformat | bullets numbering | alignleft center alignright justify | undo redo | link unlink | source";
            $(this).cleditor();
        });
		  
        // Add to container
        return string;
    },
    
    LoadLocale: function(string, new_locale, old_locale) {
        var input = string.find('textarea');
        
        // Retrieves object data from object (function is in editor.js)
        var object_data = GetObjectData(string);
        
        // Save value (if we already had locale set)
        if (old_locale)
            object_data.locales[old_locale] = input.val();
        
        // Set new value
        input.val(object_data.locales[new_locale]);
        
        // Trigger cleditor update
        input.blur();
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
});
