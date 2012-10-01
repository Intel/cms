// ###############
//      UTILS
// ###############

function GetLocaleString(key) {
    var string = editorData.strings[key];
    for (var itr = 1; itr < arguments.length; ++itr) {
        string = string.replace("%s" + itr, arguments[itr]);
    }
    return string;
}

function PluginSystem()
{
    this.types = ["EditableObject_Generate", "EditableObject_LoadLocale", "EditableObject_Save"];
    this.hooks = new Array;
    
    for (var itr = 0; itr < this.types.length; ++itr)
    {
        this.hooks[this.types[itr]] = new Array;
    }
}

PluginSystem.prototype.Hook = function(type, func, name)
{
    if (typeof func != "function") {
        func = new Function(func);
    }
    
    if ($.inArray(type, this.types) == -1)
        alert('PluginSystem.Hook(): Failed. Undefined hook type "' + type + '"');
    
    var data = new Object;
    data.func = func;
    data.name = name;
    
    this.hooks[type].push(data);
}

PluginSystem.prototype.Execute = function(type)
{
    var args = Array.prototype.slice.call(arguments, 1);
    $.each(this.hooks[type], function (index, data) {
        data.func.apply(null, args);
    });
}

PluginSystem.prototype.ExecuteByName = function(type, name)
{
    var args = Array.prototype.slice.call(arguments, 2);
    var returnobj;
    
    $.each(this.hooks[type], function (index, data) {
        if (data.name != name)
            return;
        
        returnobj = data.func.apply(null, args);
    });
    
    return returnobj;
}

// Global scope
var PluginMgr = new PluginSystem();

// Retrieves object data of a child
function GetObjectData(obj)
{
    var parent = obj.closest('fieldset[data-index]');
    if (parent.length)
        return parent.data('objdata');
    else
        alert("GetObjectData(): Failed to find parent container");
}

// Retrives module id that is being edited
function GetModuleidFromEditContainer(obj)
{
    var parent = obj.parents('div[data-m_moduleid]');
    if (parent.length)
        return parent.attr('data-m_moduleid');
    else
        alert("GetModuleidFromEditContainer(): Failed to find parent container");
}

// ###############
//     EVENTS
// ###############

function Event_ContainerSorted(event, ui) {
	// Add last column class
	$(this).children("div:not(:last)").removeClass("last_column");
    $(this).children("div:last").addClass("last_column");
	
	if ($(ui.item).hasClass('editor-toolbar-modules-module')) {
		$.ajax({
			type: "POST",
			url: "index.php",
			context: ui.item,
			data: { action: 'module_query', module_id: $(ui.item).attr('data-mid') },
		}).done(function( msg ) {
            var responseData = JSON.parse(msg);
            
            // Add module data
            editorData.module_data = $.extend({}, editorData.module_data, responseData.module_data); 
            
			// Create module object
			var module = $(responseData.html);
			// Replace old one
			$(this).replaceWith(module);
			// Initialize
			InitModule(module);
		});
	}
	
	// Remove container highlighting
	var type = $(this).attr('data-type');
	$('.editor-container-' + type).removeClass('ui-state-default');
}

function Event_ContainerSortStart(event, ui) {
	// Add container highlighting
	var type = $(this).attr('data-type');
	$('.editor-container-' + type).addClass('ui-state-default'); 
}

// ###############
//   INITIALIZE
// ###############

$(function() {
    // Initialize Containers
	if (editorData.containers) {
		for (var itr = 0; itr < editorData.containers.length; itr++) {
			var containerData = editorData.containers[itr];
			var container = $("#editor-container-" + containerData.name);
			
			container.sortable({
				connectWith: [".editor-container-" + containerData.type],
				stop: Event_ContainerSorted,
				start: Event_ContainerSortStart,
				receive: function(event, ui) {  if ($(this).children().length > containerData.slots)
													$(ui.sender).sortable('cancel');
											  },
				tolerance: 'pointer'
			});
			container.disableSelection();
			container.children("div:last").addClass("last_column");
		}
	}
    
    // Initialize Modules
	$('[data-moduleid]').each(function(index) {
		InitModule(this);
	});
    
    // Toolbar
    InitToolbar();
});

// ###############
//     TOOLBAR
// ###############

function InitToolbar() {
    $('#editor-toolbar-tempeklis').hover(function() { $(this).toggleClass('ui-state-hover'); });
    $('#editor-toolbar-tempeklis').click(function() {
        // Make it pretty
        $('#editor-toolbar-tempeklis').slideUp('fast', function() { 
            $('#editor-toolbar-content').slideDown('slow');
			// Fix for scrollbar
			sizeScrollbar();
        });
    });
    $('#editor-toolbar-content').mouseleave(function() {
		// Prevent closing when dragging
		if ($('.ui-draggable-dragging').length)
			return;
		
        $('#editor-toolbar-content').slideUp('slow', function() {
            $('#editor-toolbar-tempeklis').slideDown('fast');
        });
    });
    // Button for each page
    jQuery.each(editorData.page.pages, function(index, page) {
        AddToolbarPage(page);
    });
	
    // Add page button
    $('#editor-toolbar-pages').append('<button style="margin: 5px; float: right;" id="editor-button-add-page">' + GetLocaleString('EDITOR_ADD_NEW_PAGE') + '</button>');
    $('#editor-button-add-page').button({ icons: {primary:'ui-icon-plusthick'}}).click(function() { AddPageDialog(); });
    
    // ACTION BUTTONS
    // Logout
    $('#editor-toolbar-actions').append('<button style="margin-left: 5px; margin-top: 5px;" id="editor-button-logout">' + GetLocaleString('EDITOR_LOGOUT') + '</button>');
    $('#editor-button-logout').button().click(function() { window.location.href = "?logout=1"; });
    $('#editor-toolbar-actions').append('<button style="margin-left: 5px; margin-top: 5px;" id="editor-button-save-page">' + GetLocaleString('EDITOR_SAVE_PAGE') + '</button>');
    $('#editor-button-save-page').button().click(function() { SavePage(); });
	$('#editor-toolbar-actions').append('<button style="margin-left: 5px; margin-top: 5px;" id="editor-button-delete-page">' + GetLocaleString('EDITOR_DELETE_PAGE') + '</button>');
    $('#editor-button-delete-page').button().click(function() { DeletePage(editorData.page.id); });
	$('#editor-toolbar-actions').append('<button style="margin-left: 5px; margin-top: 5px;" id="editor-button-set-default-page">' + GetLocaleString('EDITOR_SET_DEFAULT_PAGE') + '</button>');
    $('#editor-button-set-default-page').button().click(function() { SetDefaultPage(editorData.page.id); });
    $('#editor-toolbar-actions').append('<button style="margin-left: 5px; margin-top: 5px;" id="editor-button-page-settings">' + GetLocaleString('EDITOR_PAGE_SETTINGS') + '</button>');
    $('#editor-button-page-settings').button().click(function() { alert('unfinished'); });
    
    // module container
    //scrollpane parts
    var scrollPane = $("#editor-toolbar-modules");
    var scrollContent = $("#editor-toolbar-modules-content");
		
    //build slider
    var scrollbar = $("#editor-toolbar-modules-scrollbar").slider({
        slide: function( event, ui ) {
            if ( scrollContent.width() > scrollPane.width() ) {
                scrollContent.css( "margin-left", Math.round(ui.value / 100 * ( scrollPane.width() - scrollContent.width() )) + "px" );
            } else {
                scrollContent.css( "margin-left", 0 );
            }
        }
    });
		
    //append icon to handle
    var handleHelper = scrollbar.find(".ui-slider-handle")
    .mousedown(function() {
        scrollbar.width( handleHelper.width() );
    })
    .mouseup(function() {
        scrollbar.width( "100%" );
    })
    .append( "<span class='ui-icon ui-icon-grip-dotted-vertical'></span>" )
    .wrap( "<div class='ui-handle-helper-parent'></div>" ).parent();

    //change overflow to hidden now that slider handles the scrolling
    scrollPane.css( "overflow", "hidden" );

    //size scrollbar and handle proportionally to scroll distance
    function sizeScrollbar() {
        var remainder = scrollContent.width() - scrollPane.width();
		if (remainder < 0)
			remainder = 3;
        var proportion = remainder / scrollContent.width();
        var handleSize = scrollPane.width() - ( proportion * scrollPane.width() );
        scrollbar.find( ".ui-slider-handle" ).css({
            width: handleSize,
            "margin-left": -handleSize / 2
        });
        handleHelper.width( "" ).width( scrollbar.width() - handleSize );
    }
    
    //reset slider value based on scroll content position
    function resetValue() {
        var remainder = scrollPane.width() - scrollContent.width();
        var leftVal = scrollContent.css( "margin-left" ) === "auto" ? 0 :
            parseInt( scrollContent.css( "margin-left" ) );
        var percentage = Math.round( leftVal / remainder * 100 );
        scrollbar.slider( "value", percentage );
    }
    
    //if the slider is 100% and window gets larger, reveal content
    function reflowContent() {
        var showing = scrollContent.width() + parseInt( scrollContent.css( "margin-left" ), 10 );
        var gap = scrollPane.width() - showing;
        if ( gap > 0 ) {
            scrollContent.css( "margin-left", parseInt( scrollContent.css( "margin-left" ), 10 ) + gap );
        }
    }
    
    //change handle position on window resize
    $( window ).resize(function() {
        resetValue();
        sizeScrollbar();
        reflowContent();
    });
    //init scrollbar size
    setTimeout( sizeScrollbar, 10 );//safari wants a timeout
	
	// hide
	$('#editor-toolbar-content').hide();
	
	// Init modules
	if (editorData.containers) {
		for (var itr = 0; itr < editorData.containers.length; itr++) {
			var containerData = editorData.containers[itr];
			
			// More than one container may have the same type
			if ($('.editor-toolbar-modules-container-' + containerData.type).length > 0)
				continue;
			
			// Create container
			$('#editor-toolbar-modules-content').append('<div class="editor-toolbar-modules-container ui-widget-content editor-toolbar-modules-container-' + containerData.type + '" data-type="' + containerData.type + '"><span class="editor-toolbar-modules-container-header">' + containerData.title + '<button style="margin-left: 10px;"><span class="ui-icon ui-icon-plusthick" /></button></span><div></div></div>');
			$('.editor-toolbar-modules-container-' + containerData.type + ' button').button().click(function() { AddModuleDialog($(this).parents('.editor-toolbar-modules-container').attr('data-type')); });
		}
	}
	
	jQuery.each(editorData.modules, function(index, moduleData) {
		AddToolbarModule(moduleData);
	});
}

function AddToolbarPage(pageData) {
	$('#editor-toolbar-pages').append('<button style="margin: 5px; margin-right: 0px;" id="editor-button-page-' + pageData.id + '">' + pageData.name[editorData.locales.default] + '</button>');
    var button = $('#editor-button-page-' + pageData.id);
    var icon = null;
    var disabled = false;
    if (pageData['default'] == 1)
        icon = 'ui-icon-home';
    if (pageData.id == editorData.page.id)
        disabled = true;
    button.button({icons: {primary:icon}, disabled: disabled});
    button.click(function() {
        $(window.location).attr('href', '?page=' + pageData.id);
    });
}

function AddToolbarModule(moduleData) {
	if (moduleData.type == "static")
		return;
	
	var container = $('.editor-toolbar-modules-container-' + moduleData.type);
	
	// append
	var module = $('<div class="editor-toolbar-modules-module ui-state-default" data-mid="' + moduleData.id + '" data-type="' + moduleData.type + '">' + moduleData.name + '<button style="margin-left: 10px;"><span class="ui-icon ui-icon-trash" /></button></div>');
	container.children('div').append(module);
	
	// delete button
	module.find('button').button().click(function() { DeleteModule($(this).parent().attr('data-mid')) });
	
	// draggable
	module.draggable({
		connectToSortable: ".editor-container-" + moduleData.type,
		helper: "clone",
		revert: "invalid",
		start: function(event, ui) {// Add container highlighting
									$('.editor-container-' + moduleData.type).addClass('ui-state-default'); },
		stop: function(event, ui) {	// Fix for toolbar sliding
									$('#editor-toolbar-content').slideUp('slow', function() {
										$('#editor-toolbar-tempeklis').slideDown('fast');
									});
									// Remove container highlighting
									$('.editor-container-' + moduleData.type).removeClass('ui-state-default'); },
	});
}

function AddModuleDialog(type) {
    function AddModule(type, template, name) {
        $.ajax({
            type: "POST",
            url: "index.php",
            data: { action: 'module_create', module_type: type, module_template: template, module_name: name },
        }).done(function( msg ) {
            var moduleData = JSON.parse(msg);
            
            editorData.modules[moduleData.id] = moduleData;
            
            AddToolbarModule(moduleData);
        });
    }
    
	var dialog = $('<div id="editor-dialog"></div>');
	
	// title
	dialog.attr('title', GetLocaleString('EDITOR_DIALOG_ADD_MODULE_TITLE', type));
	
	dialog.append('<div class="ui-state-default" style="padding: 10px"><fieldset><label>' + GetLocaleString('EDITOR_DIALOG_ADD_MODULE_NAME') + '</label><br/><input type="text" name="module_name"/></fieldset><br/><fieldset><label>' + GetLocaleString('EDITOR_DIALOG_ADD_MODULE_SELECT_TEMPLATE') + '</label><br/><select></select></fieldset></div>');
	
	var select = dialog.find('select');
	
	jQuery.each(editorData.templates.modules[type], function(index, template) {
		select.append('<option value="' + template + '">' + template + '</option>');
	});
	
    //dialog.find('input').tipsy();
    
    // Open
    dialog.dialog({ modal: true,
                    resizable: false,
                    draggable: false,
                    show: "slide",
                    hide: "slide",
                    minWidth: 600,
                    close: function(event, ui) { dialog.remove(); }
                  });
                  
    var buttons = new Object();
    buttons[GetLocaleString('COMMON_CANCEL')] = function() { dialog.remove() };
    buttons[GetLocaleString('COMMON_CREATE')] = function() { AddModule(type, select.val(), dialog.find('input').val()); $('#editor-toolbar-tempeklis').trigger('click'); dialog.remove(); };
    dialog.dialog("option", "buttons", buttons);
}

function DeleteModule(id) {
	$.ajax({
        type: "POST",
        url: "index.php",
        data: { action: 'module_delete', module_id: id},
    }).done(function( msg ) {
		if (msg != "ok") {
			alert('Error deleting module #' + id);
			return;
		}
		
		// remove DOM
		$('.editor-toolbar-modules-module[data-mid="' + id + '"]').remove();
		$('div[data-moduleid="' + id + '"]').remove();
		
		// remove data
		editorData.modules.splice(id, 1);
    });
}

function AddPageDialog() {
    function AddPage(name, template) {
        var page_data = new Object;
        page_data.name = name;
        page_data.template = template;
        console.log(page_data);
        $.ajax({
            type: "POST",
            url: "index.php",
            data: { action: 'page_create', data: page_data},
        }).done(function( msg ) {
            var pageData = JSON.parse(msg);
            
            editorData.page.pages[pageData.id] = pageData;
            
            AddToolbarPage(pageData);
        });
    }
    
    var dialog = $('<div id="editor-dialog"></div>');
	
	// title
	dialog.attr('title', GetLocaleString('EDITOR_DIALOG_ADD_PAGE_TITLE'));
    
    // Add locale selection
    var locale_html = '<div id="editor-locales" class="ui-state-highlight ui-corner-all" style="padding: 5px;">';
    for (var itr = 0; itr < editorData.locales.list.length; itr++)
    {
        var locale = editorData.locales.list[itr];
        var checked = '';
        if (locale.name == editorData.locales['default'])
            checked = 'checked="checked"';
        locale_html += '<input type="radio" id="' + locale.name + '" name="editor-locales" ' + checked + '/><label for="' + locale.name + '"><img src="' + locale.ico + '" /></label>';
    }
    locale_html += '</div><br/>';
    dialog.append(locale_html);
	
	dialog.append('<div class="ui-state-default" style="padding: 10px"><fieldset><label>' + GetLocaleString('EDITOR_DIALOG_ADD_PAGE_NAME') + '</label><br/><input type="text" name="page_name"/></fieldset><br/><fieldset><label>' + GetLocaleString('EDITOR_DIALOG_ADD_PAGE_SELECT_TEMPLATE') + '</label><br/><select></select></fieldset></div>');
	
    dialog.find('#editor-locales').buttonset();
    var loc_select = dialog.find('#editor-locales input');
    loc_select.change(function() {
        var old_loc = editorData.locales.current;
        var new_loc = editorData.locales.current = $(this).attr("id");
        
        var page_name = dialog.find('input[name="page_name"]');
        var data;
        
        if (page_name.data("locs"))
            data = page_name.data("locs");
        else
            data = new Object;
        
        data[old_loc] = page_name.val();
        page_name.val(data[new_loc]);
        
        page_name.data("locs", data);
    });
    editorData.locales.current = editorData.locales.default;
    
	var select = dialog.find('select');
	
	jQuery.each(editorData.templates.page, function(index, template) {
		select.append('<option value="' + template + '">' + template + '</option>');
	});
	
    //dialog.find('input').tipsy();
    
    // Open
    dialog.dialog({ modal: true,
                    resizable: false,
                    draggable: false,
                    show: "slide",
                    hide: "slide",
                    minWidth: 600,
                    close: function(event, ui) { dialog.remove(); }
                  });
                  
    var buttons = new Object();
    buttons[GetLocaleString('COMMON_CANCEL')] = function() { dialog.remove(); };
    buttons[GetLocaleString('COMMON_CREATE')] = function() { loc_select.trigger('change'); AddPage(dialog.find('input[name="page_name"]').data('locs'), dialog.find('select').val()); $('#editor-toolbar-tempeklis').trigger('click'); dialog.remove(); };
    dialog.dialog("option", "buttons", buttons);
}

function InitModule(obj) {
    var moduleWrapper = $(obj);
    var moduleData = editorData.modules[moduleWrapper.attr('data-moduleid')];
    var module;
    
    // Unpack module from wrapper
    // Todo: modules without root node
    if (moduleWrapper.children().length == 1) {
        module = moduleWrapper.children(':first');
        module.attr('data-moduleid', moduleWrapper.attr('data-moduleid'));
        module.detach().insertAfter(moduleWrapper);
        moduleWrapper.remove();
    }
       
    // Save for later use
    module.data('json_data', moduleData);
        
    // Fix overlay for some wrappers
    module.css('position', 'relative');
       
    // Add classes and overlays
    module.addClass("editor-module");
    module.append('<div id="editor-module-overlay"><div class="editor-overlay ui-widget-overlay ui-corner-all"></div><button id="editor-button-edit" data-id="' + moduleData.id + '"><span class="ui-icon ui-icon-gear" /></button><button id="editor-button-delete"><span class="ui-icon ui-icon-trash" /></button></div>');
    module.find('#editor-module-overlay').hide();
    module.hover(
        function () {
            $(this).find('#editor-module-overlay').show();
        },
        function () {
            $(this).find('#editor-module-overlay').hide();
        }
    );
        
    // buttons
    module.find('button').button();
    module.find('#editor-button-edit').click( function(event) { EditModule($(this).parents('div[data-moduleid]').attr('data-moduleid')); });
    // static modules cannot be deleted
    if (moduleData.type == "static")
        module.find('#editor-button-delete').remove();
    else
        module.find('#editor-button-delete').click( function(event) { $(this).parents('div[data-moduleid]').remove(); });
}

function EditModule(id) {
    var dialog = $('<div id="editor-dialog"><div id="editor-dialog-container" class="ui-state-default" style="padding: 10px"></div></div>');
    var dialogContainer = dialog.find('#editor-dialog-container');
    var module = $('div[data-moduleid="' + id + '"]:first');
    var moduleData = module.data('json_data');
    
    // Add locale selection
    var locale_html = '<div id="editor-locales" class="ui-state-highlight ui-corner-all" style="padding: 5px;">';
    for (var itr = 0; itr < editorData.locales.list.length; itr++)
    {
        var locale = editorData.locales.list[itr];
        var checked = '';
        if (locale.name == editorData.locales['default'])
            checked = 'checked="checked"';
        locale_html += '<input type="radio" id="' + locale.name + '" name="editor-locales" ' + checked + '/><label for="' + locale.name + '"><img src="' + locale.ico + '" /></label>';
    }
    locale_html += '</div><br/>';
    dialogContainer.append(locale_html);
    
    // Locale switching
    delete editorData.locales.current;
	function switchLocale(new_locale, old_locale) {
        //alert("oldloc: " + oldloc + "\nnewloc:" + $(this).attr('id'));
        /*dialog.find('#editor-settings-container').find('[localized]').each(function(index) {
            var input = $(this); 
            
            if (!input.data('locale_data'))
                input.data('locale_data', JSON.parse(input.attr('data-locales')));
            
            var locale_data = input.data('locale_data');
			
			// switch
			switch(input.prop('tagName')) {
				case 'TEXTAREA':
					locale_data[oldloc] = input.val();
					input.val(locale_data[editorData.locales.current]);
                    input.trigger('change');
					break;
				default:
					locale_data[oldloc] = input.val();
					input.val(locale_data[editorData.locales.current]);
			}
            
            input.data('locale_data', locale_data);
        });*/
        dialog.find('#editor-settings-container').find('fieldset[data-type]').each(function(index) {
            PluginMgr.ExecuteByName("EditableObject_LoadLocale", $(this).attr('data-type'), $(this).children(), new_locale, old_locale);
        });
        editorData.locales.current = new_locale;
	}
    editorData.locales.current = editorData.locales['default'];
    dialogContainer.find('#editor-locales').buttonset();
    dialogContainer.find('#editor-locales input').change(function() {
        switchLocale($(this).attr('id'), editorData.locales.current);
    });
    
    // Set Data
    dialog.attr('data-m_moduleid', moduleData.id);
    dialog.attr('title', GetLocaleString('EDITOR_MODULE_DIALOG_TITLE', moduleData.name, moduleData.id));
    
    // Load content
    dialogContainer.append('<span id="editor-settings-container"></span>');
    var settingsContainer = dialogContainer.find('#editor-settings-container');
    GenerateEditableContent(editorData.module_data[moduleData.id], settingsContainer);
    
    /*// Iterator buttons
    settingsContainer.find('ul').each(function(index) {
        AddItrAddButton($(this));
    });*/
	
	// Initialize locale data
    switchLocale(editorData.locales.default);
    
    //dialogContainer.find('ul').sortable({tolerance: 'pointer'});
    
    // Open
    dialog.dialog({ modal: true,
                    resizable: false,
                    draggable: false,
                    hide: "fold",
                    minWidth: 600,
                    close: function(event, ui) { dialog.remove(); }
                  });
    
    var buttons = new Object();
    buttons[GetLocaleString('COMMON_CANCEL')] = function() { dialog.remove(); };
    buttons[GetLocaleString('COMMON_SAVE')] = function() { SaveModule($(this).find('#editor-settings-container')) };
    dialog.dialog("option", "buttons", buttons);
    
    // trigger event
    dialog.find('*').trigger("showup");
    //tempinitshit(settingsContainer);
}

/*function tempinitshit(container) {
    // tooltips
    container.find('input').tipsy();
    
    // images
    container.find('fieldset[data-type="img"]').each(function(index) {
        var img = $(this).find('img');
        $(this).find('input').change(function() {
            IMGUpload(this.files[0], function(xhr) {
				var hash = JSON.parse(xhr.responseText).hash;
				img.attr('data-hash', hash);
                img.attr('src', GetImg(hash, 100, 100));
            });
        });
	});
    
    // rich text areas
    //tinyMCE.execCommand('mceAddControl', false, 'editor-richtext');
	container.find('fieldset[data-type="richtext"]').each(function(index) {
        var textarea = $(this).find('textarea');
        textarea.cleditor();
        textarea.change(function() {
            $(this).cleditor()[0].updateFrame();
        });
	});
    
    // Hackfix for navbar pages
    moduleData = editorData.modules[container.parents('[data-m_moduleid]').attr('data-m_moduleid')];
    if (moduleData.template == "navbar") {
        container.find('fieldset[data-type="link"]').each(function(index) {
            var fieldset = $(this);
            var input = fieldset.find('input[name="link"]');
            $('<select><option/></select>').insertAfter(input);
            var select = fieldset.find('select');
            jQuery.each(editorData.page.pages, function(index, page) {
                select.append('<option>' + page.name + '</option>');
            });
            select.change(function() {
                input.val('?page=' + $(this).val());
            });
        });
    }
}*/

function GenerateEditableContent(objects, container)
{
    jQuery.each(objects, function(index, object_data) {
        var obj = PluginMgr.ExecuteByName("EditableObject_Generate", object_data.type, object_data);
        if (!obj)
            console.log(objects);
        container.append(obj);
        obj.wrapAll($('<fieldset></fieldset>')
                        .attr('data-name', object_data.name)
                        .attr('data-type', object_data.type)
                        .attr('data-index', index)
                        .data('objdata', object_data));
        container.append('<br/>');
    });
}

/*function ParseStrings(object) {
    var output = '';
    
    object.find('var').each(function(index) {
        $(this).data('parsed', 0);
    });
    
    object.find('var').each(function(index) {
        // prevent parsing same thing multiple times
        if ($(this).data('parsed'))
            return;
            
        switch ($(this).attr('data-type'))
        {
            case 'mainitr':
                output += '<ul class="editor-iterator" data-name="' + $(this).attr('data-name') + '">' + ParseStrings($(this)) + '</ul>';
                break;
            case 'itr':
                output += '<li class="ui-state-default" data-name="' + $(this).attr('data-name') + '"><span style="float: right;" class="ui-icon ui-icon-closethick" onclick="$(this).parent().remove();"></span>' + ParseStrings($(this)) + '</li>';
                break;
            case 'string':
                output += '<fieldset data-name="' + $(this).attr('data-name') + '" data-type="' + $(this).attr('data-type') + '">';
                output += '<label for="' + $(this).attr('data-name') + '">' + $(this).attr('data-title') + '</label></br>';
                output += '<input name="string" localized="true" value="' + $(this).text() + '" title="' + $(this).attr('data-tooltip') + '" data-locales="' + ($(this).attr('data-locales')).replace(/\"/g, "&quot;") + '" class="text ui-widget-content ui-corner-all" /></fieldset><br/>';
                break;
            case 'link':
                //if (!$(this).attr('data-locales'))
                    //$(this).attr('data-locales', new Array())
                output += '<fieldset data-name="' + $(this).attr('data-name') + '" data-type="' + $(this).attr('data-type') + '">';
                output += '<label for="' + $(this).attr('data-name') + '"><span style="float: left; margin-right: 5px;" class="ui-icon ui-icon-grip-diagonal-se"></span>' + $(this).attr('data-title') + '</label></br>';
                output += '<div class="editor-dialog-input-container"><span class="ui-icon ui-icon-tag"></span><input name="title" localized="1" value="' + $(this).find('a').text() + '" title="' + $(this).attr('data-tooltip') + '" data-locales="' + ($(this).attr('data-locales')).replace(/\"/g, "&quot;") + '" class="text ui-widget-content ui-corner-all" /></div>';
                output += '<div class="editor-dialog-input-container"><span class="ui-icon ui-icon-extlink"></span><input name="link" value="' + $(this).find('a').attr('href') + '" title="' + $(this).attr('data-tooltip-link') + '" data-locales="' + ($(this).attr('data-locales')).replace(/\"/g, "&quot;") + '" class="text ui-widget-content ui-corner-all" /></div>';
                output += '</fieldset>';
                break;
			case 'richtext':
				output += '<fieldset data-name="' + $(this).attr('data-name') + '" data-type="' + $(this).attr('data-type') + '">';
                output += '<label for="' + $(this).attr('data-name') + '">' + $(this).attr('data-title') + '</label></br>';
                output += '<textarea localized="1" data-locales="' + ($(this).attr('data-locales')).replace(/\"/g, "&quot;") + '" >' + $(this).html() + '</textarea></fieldset><br/>';
                break;
            case 'img':
                output += '<fieldset data-name="' + $(this).attr('data-name') + '" data-type="' + $(this).attr('data-type') + '">';
                output += '<label for="' + $(this).attr('data-name') + '">' + $(this).attr('data-title') + '</label></br>';
                output += '<input type="file" name="image" /><br/><img src="_cms/static/?hash=' + $(this).find('img').attr('data-hash') + '&w=150&h=100" data-hash="' + $(this).find('img').attr('data-hash')  + '" /></fieldset><br/>';
                break;
        }
        
        $(this).data('parsed', 1);
    });
    
    return output;
}*/

/*function EncodeObject(object) {
    var output = new Object();
    var childs = new Array();
    
    object.find('*:not(ul)').each(function(index) {
        // prevent parsing same thing multiple times
        if ($(this).data('parsed'))
            return;
        
        var obj = EncodeObject($(this))
        if (obj != false)
            childs.push(obj);
        
        $(this).data('parsed', 1);
    });
    
    switch (object.prop('tagName'))
    {
        case 'LI':
            output.type = 'itr';
            output.name = object.attr('data-name');
            output.data = childs;
            break;
        case 'FIELDSET':
            output.type = object.attr('data-type');
            output.name = object.attr('data-name');
            
            switch (object.attr('data-type'))
            {
                case 'string':
                    var datar = object.find('input').data('locale_data');
                    datar[editorData.locales.current] = object.find('input').val();
                    output.value = datar;
                    break;
                case 'link':
                    output.link_url = object.find('input[name="link"]').val();
                    var data = object.find('input[name="title"]').data('locale_data');
                    data[editorData.locales.current] = object.find('input[name="title"]').val();
                    output.link_title = data;
                    break;
				case 'richtext':
					var datar = object.find('textarea').data('locale_data');
                    datar[editorData.locales.current] = object.find('textarea').val();
                    output.value = datar;
                    break;
                case 'img':
                    output.img_src = object.find('img').attr('data-hash');
                    break;
            }
            break;
        default:
            //alert(object.prop('tagName'));
            return false;
    }
    
    return output;
}*/

function SaveObjects(container) {
    var datar = new Array;
    container.children('fieldset[data-index]').each(function(index) {
        var data = PluginMgr.ExecuteByName("EditableObject_Save", $(this).attr('data-type'), $(this).children());
        datar.push(data);
    });
    return datar;
}

function SaveModule(container) {
    var data = SaveObjects(container);
    var moduleid = GetModuleidFromEditContainer(container);
    
    $('#editor-dialog').html('<h2>Please wait...</h2>');
    
    $.ajax({
        type: "POST",
        url: "index.php",
        data: { action: 'module_update', module_data: data, module_id: moduleid, page_id: editorData.page.id}
    }).done(function( msg ) {
        var responseData = JSON.parse(msg);
        
        // Close dialog
        $('#editor-dialog').dialog("close");
        
        // Initialize new module content
        $('div[data-moduleid="' + responseData.moduleid + '"]').replaceWith(responseData.content);
        InitModule($('div[data-moduleid="' + responseData.moduleid + '"]'));
        $('div[data-moduleid="' + responseData.moduleid + '"]').fadeIn('slow');
        
        // Apply module data update
        if (responseData.module_data) {
            jQuery.each(responseData.module_data, function(index, data) {
                editorData.module_data[index] = data;
            });
        }
    });
}

/*function AddItrAddButton(ul) {
    ul.append('<button id="editor-button-itr-add-new">Add new ' + ul.attr('data-name') + '</button>');
    var button = ul.find('#editor-button-itr-add-new');
    button.button({ icons: {primary:'ui-icon-plusthick'}});
    button.click(function(event) {
        var m_container = $(this).parents('ul:first');
        var m_dialog = $('#editor-dialog');
        var m_module = $('div[data-moduleid="' + m_dialog.attr('data-m_moduleid') + '"]');
        var m_moduleData = m_module.data('json_data');
        var html = m_moduleData.iterators[m_container.attr('data-name')];
        html = html.replace("<![CDATA[", "").replace("]]>", "");
        html = $('<textarea/>').html(html).val();
        
        
        var obj = $(ParseStrings($('<div/>').html(html)));
        $(this).before(obj);
        tempinitshit(obj);
        m_container.find('li:last ul').each(function(index) {
            AddItrAddButton($(this));
        });
        
        // hackfix for locale data
        m_dialog.find('#editor-locales #' + editorData.locales.current).trigger('change');
    });
}*/

function SavePage() {
    var data = new Object();
    data['containers'] = new Array();
    
    $('.editor-container').each(function(index) {
        var container_data = new Object();
        container_data['name'] = $(this).attr('id').replace("editor-container-", "");
        container_data['modules'] = new Array();
        
        var modules = $(this).children();
        modules.filter('[data-moduleid]').each(function(index) {
            var module_data = new Object();
            module_data['id'] = $(this).attr('data-moduleid');
            module_data['slot'] = $(this).index();
            container_data['modules'].push(module_data);
        });
        
        data['containers'].push(container_data);
    });
    
    data['pageid'] = editorData.page.id;
    
    $.ajax({
        type: "POST",
        url: "index.php",
        data: { action: 'page_update', page_data: data },
    }).done(function( msg ) {
        MessageDialog(GetLocaleString('EDITOR_DIALOG_PAGE_SAVED_TITLE'), 
					'<span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 0 0;"></span>' + GetLocaleString('EDITOR_DIALOG_PAGE_SAVED_BODY'));
    });
}

function DeletePage(id) {
	$.ajax({
        type: "POST",
        url: "index.php",
        data: { action: 'page_delete', page_id: id},
    }).done(function( msg ) {
		$(window.location).attr('href', 'index.php');
    });
}

function SetDefaultPage(id) {
	$.ajax({
        type: "POST",
        url: "index.php",
        data: { action: 'page_default', page_id: id},
    }).done(function( msg ) {
		$('#editor-toolbar-pages').find('button').button( "option", "icons", {primary:''});
		$('#editor-button-page-' + id).button( "option", "icons", {primary:'ui-icon-home'});
    });
}

function MessageDialog(title, body) {
	// Remove old
	$('#editor-message-dialog').remove();
	
	var dialog = $('<div id="editor-message-dialog" title="' + title + '"><p>' + body + '</p></div>');
	
	dialog.dialog({
		modal: true,
		draggable: false,
		resizable: false
	});
	
	var buttons = new Object();
    buttons[GetLocaleString('COMMON_OK')] = function() { $(this).dialog("close") };
    dialog.dialog("option", "buttons", buttons);
}

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