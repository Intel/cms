var Plugin_cms_iterator = {
    Generate: function(object_data) {
        var iterator = $('<ul class="editor-iterator"></ul>');
        
        // Container
        iterator.append('<div></div>');
        var container = iterator.children("div");
        container.sortable({tolerance: 'pointer'});

        // Button to add iterator
        iterator.append('<button id="editor-button-itr-add-new">Add new ' + object_data.name + '</button>');
        var button = iterator.children('#editor-button-itr-add-new');
        button.button({ icons: {primary:'ui-icon-plusthick'}});
        button.click(function(event) {
            $.ajax({
                type: "POST",
                url: "index.php",
                data: { action: 'iterator_fetch', iterator_name: object_data.name, iterator_template: object_data.template },
            }).done(function( msg ) {
                var responseData = JSON.parse(msg);
                object_data.content.push(responseData.id);
                editorData.module_data[responseData.id] = responseData.content;
                var content = Plugin_cms_iterator.CreateContent(responseData.content);
                container.append(content);
                
                content.find('*').trigger("showup");
            });
        });
        
        // Add iterators
        if (object_data.content) {
            jQuery.each(object_data.content, function(index, value) {
                container.append(Plugin_cms_iterator.CreateContent(editorData.module_data[-value]));
            });
        } else {
            object_data.content = new Array();
        }
        
        // Add to container
        return iterator;
    },
    
    Save: function(iterator) {
        var object_data = GetObjectData(iterator);
        
        // Save childs
        var datar = new Array;
        iterator.children('div').children().each(function(index) {
            var data = SaveObjects($(this));
            datar.push(data);
        });
        object_data.childs = datar;
        return object_data;
    },
    
    CreateContent: function(data) {
        var container = $('<li class="ui-state-default"><span style="float: right;" class="ui-icon ui-icon-closethick" onclick="$(this).parent().remove();"></span></li>');
        GenerateEditableContent(data, container);
        return container;
    }
};

PluginMgr.Hook("EditableObject_Generate", Plugin_cms_iterator.Generate, "iterator");
PluginMgr.Hook("EditableObject_Save", Plugin_cms_iterator.Save, "iterator");
