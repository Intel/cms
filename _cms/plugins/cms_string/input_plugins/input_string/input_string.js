var Plugin_input_string = {
    Generate: function(object_data) {
        var tmpl = jQuery.templates('<label for="{{>name}}">{{>title}}</label></br> \
                                     <input type="text" title="{{>tooltip}}"  class="text ui-widget-content ui-corner-all">');
        
        // Render template
        var string = $(tmpl.render(object_data));
        
        // Add Tooltips
        string.tipsy();
        
        // Add to container
        return string;
    },
    
    LoadLocale: function(string, new_locale, old_locale) {
        // since string is array of children, we need to use filter
        var input = string.filter('input');
        
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
        var input = string.filter('input');
        object_data.locales[editorData.locales.current] = input.val();
        
        // Send all data, we'll process it in php
        return object_data;
    }
};

PluginMgr.Hook("EditableObject_Generate", Plugin_input_string.Generate, "input_string");
PluginMgr.Hook("EditableObject_LoadLocale", Plugin_input_string.LoadLocale, "input_string");
PluginMgr.Hook("EditableObject_Save", Plugin_input_string.Save, "input_string");