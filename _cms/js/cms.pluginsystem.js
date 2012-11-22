CMS.PluginSystem = {
    Plugins: new Array,
    
    Init: function() {
        this.types = ["EditableObject_Generate", "EditableObject_LoadLocale", "EditableObject_Save"];
        this.hooks = new Array;
        
        for (var itr = 0; itr < this.types.length; ++itr)
        {
            this.hooks[this.types[itr]] = new Array;
        }
        
        // Hooks
        jQuery.each(this.Plugins, function(name, plugins) {
            CMS.OutDebug("CMS.PluginSystem.Init(): Loading plugin '" + name + "'");
            
            // Init Hooks Array
            var Hooks = new Array;
            
            if (!plugin.PluginInit)
                return CMS.OutError("CMS.PluginSystem.Init(): Plugin '" + name + "' doesn't have PluginInit handler");
            
            object.PluginInit(Hooks);
            
            jQuery.each(Hooks, function(hook_type, func) {
                CMS.OutDebug("CMS.PluginSystem.Init(): Hooking '" + hook_type + "' for plugin '" + name + "'");
                
                // Hook
                CMS.PluginSystem.Hook(hook_type, func, name);
            });
        });
        
        // Ready!
        CMS.OutDebug("CMS.PluginSystem.Init(): Loaded!");
    },
    
    LoadPlugin: function(name, object) {
        if (this.Plugins[name])
            return CMS.OutError("CMS.PluginSystem.LoadPlugin(): Plugin '" + name + "' is already loaded!");
        
        if (typeof(object) != "object")
            return CMS.OutError("CMS.PluginSystem.LoadPlugin(): Param 2 Type missmatch for plugin '" + name + "'. Expected 'object' got '" + typeof(object) + "'");
        
        this.Plugins[name] = object;
    },

    Hook: function(type, func, name) {
        if (typeof func != "function") {
            func = new Function(func);
        }
        
        if ($.inArray(type, this.types) == -1)
            CMS.OutError("CMS.PluginSystem.Hook(): Undefined hook type '" + type + "' for plugin '" + name + "'");
        
        var data = new Object;
        data.func = func;
        data.name = name;
        
        this.hooks[type].push(data);
    },

    Execute: function(type) {
        var args = Array.prototype.slice.call(arguments, 1);
        $.each(this.hooks[type], function (index, data) {
            data.func.apply(null, args);
        });
    },

    ExecuteByName: function(type, name) {
        var args = Array.prototype.slice.call(arguments, 2);
        var returnobj;
        
        $.each(this.hooks[type], function (index, data) {
            if (data.name != name)
                return;
            
            returnobj = data.func.apply(null, args);
        });
        
        return returnobj;
    }
};