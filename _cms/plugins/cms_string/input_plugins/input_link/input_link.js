var Plugin_input_link = {
    Generate: function(link_data) {
        var tmpl = jQuery.templates('<label><span style="float: left; margin-right: 5px;" class="ui-icon ui-icon-grip-diagonal-se"></span>{{>title}}</label></br> \
                                     <div class="editor-dialog-input-container"> \
                                        <span class="ui-icon ui-icon-tag"></span> \
                                        <input type="text" name="link_title" title="{{>tooltip_title}}" class="ui-widget-content ui-corner-all"> \
                                     </div> \
                                     <div class="editor-dialog-input-container"> \
                                        <span class="ui-icon ui-icon-extlink"></span> \
                                        <input type="text" name="link_url" value="{{>link_url}}" title="{{>tooltip_url}}" class="ui-widget-content ui-corner-all"> \
                                     </div>');
        
        // Render template
        var string = $(tmpl.render(link_data));
        
        // Add Tooltips
        string.tipsy();
        
        // Copy page link
        string.find('input[name="link_url"]').each(function(index) {
            var input = $(this);
            var select = $('<select style="margin-left: 5px" class="ui-widget-content ui-corner-all"><option/></select>');
            select.insertAfter(input);
            jQuery.each(editorData.page.pages, function(index, page) {
                select.append('<option id="' + page.id + '">' + page.name[editorData.locales.default] + '</option>');
            });
            select.change(function() {
                input.val('?page=' + $(this).find("option:selected").attr("id"));
            });
        });
        
        // Add to container
        return string;
    },
    
    LoadLocale: function(string, new_locale, old_locale) {
        // find title input
        var link_title = string.find('input[name="link_title"]');
        
        // Retrieves object data from object (function is in editor.js)
        var link_data = GetObjectData(string);
        
        // Save value (if we already had locale set)
        if (old_locale)
            link_data.link_title[old_locale] = link_title.val();
        
        // Set new value
        link_title.val(link_data.link_title[new_locale]);
    },

    Save: function(string) {
        var link_data = GetObjectData(string);
        
        // Save locale
        var link_title = string.find('input[name="link_title"]');
        link_data.link_title[editorData.locales.current] = link_title.val();

        // Save others
        var link_url = string.find('input[name="link_url"]');
        link_data.link_url = link_url.val();        
        
        // Send all data, we'll process it in php
        return link_data;
    }
};

PluginMgr.Hook("EditableObject_Generate", Plugin_input_link.Generate, "input_link");
PluginMgr.Hook("EditableObject_LoadLocale", Plugin_input_link.LoadLocale, "input_link");
PluginMgr.Hook("EditableObject_Save", Plugin_input_link.Save, "input_link");