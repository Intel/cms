var Plugin_input_string = {
    Generate: function(object_data) {
        var tmpl = jQuery.templates('<label for="{{>name}}"><span style="float: left; margin-right: 5px;" class="ui-icon ui-icon-grip-diagonal-se"></span>{{>title}}</label> \
                                     <div class="editor-dialog-input-container"> \
                                        <span class="ui-icon ui-icon-pencil"></span> \
                                        <input type="text" size="{{>width}}" title="{{>tooltip}}"  class="text ui-widget-content ui-corner-all"> \
                                     </div>');
        
        // Render template
        var string = $(tmpl.render(object_data));
        
        // Add Tooltips
        string.find('input').tipsy();
        
        if(object_data.datepicker) {
            string.find('input').datepicker();
            string.find('input').datepicker( "option", "dateFormat", "yy-mm-dd");
            string.find('input').tipsy({ gravity: 's' });
        }
        
        // Initialize datatype if object is new
        if (!object_data.locales)
            object_data.locales = new Object;
        
        // Add to container
        return string;
    },
    
    LoadLocale: function(string, new_locale, old_locale) {
        var input = string.find('input');
        
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
        var input = string.find('input');
        object_data.locales[editorData.locales.current] = input.val();
        
        // Send all data, we'll process it in php
        return object_data;
    }
};

PluginMgr.Hook("EditableObject_Generate", Plugin_input_string.Generate, "input_string");
PluginMgr.Hook("EditableObject_LoadLocale", Plugin_input_string.LoadLocale, "input_string");
PluginMgr.Hook("EditableObject_Save", Plugin_input_string.Save, "input_string");