CMS.PluginSystem = {
    Plugins: new Array,
    
    Init: function() {
        this.types = ["EditableObject_Generate", "EditableObject_LoadLocale", "EditableObject_Save"];
        this.hooks = new Array;
        
        for (var itr = 0; itr < this.types.length; ++itr)
        {
            this.hooks[this.types[itr]] = new Array;
        }
        
        // Hook Plugins
        this.HookAll();
        
        // Ready!
        CMS.OutDebug("CMS.PluginSystem.Init(): Loaded!", CMS.Debug.JS_PLUGINSYSTEM);
    },
    
    LoadPlugin: function(name, object) {
        if (this.Plugins[name])
            return CMS.OutError("CMS.PluginSystem.LoadPlugin(): Plugin '" + name + "' is already loaded!");
        
        if (typeof(object) != "object")
            return CMS.OutError("CMS.PluginSystem.LoadPlugin(): Param 2 Type missmatch for plugin '" + name + "'. Expected 'object' got '" + typeof(object) + "'");
        
        this.Plugins[name] = object;
    },
    
    HookAll: function() {
        for (var name in this.Plugins) {
            var plugin = this.Plugins[name];
            CMS.OutDebug("CMS.PluginSystem.HookAll(): Loading plugin '" + name + "'", CMS.Debug.JS_PLUGINSYSTEM);
            
            // Init Hooks Array
            var Hooks = new Array;
            
            if (!plugin.PluginInit)
                return CMS.OutError("CMS.PluginSystem.HookAll(): Plugin '" + name + "' doesn't have PluginInit handler");
            
            plugin.PluginInit(Hooks);
            
            for (var hook_type in Hooks) {
                CMS.OutDebug("CMS.PluginSystem.HookAll(): Hooking '" + hook_type + "' for plugin '" + name + "'", CMS.Debug.JS_PLUGINSYSTEM);
                
                // Hook
                CMS.PluginSystem.Hook(hook_type, Hooks[hook_type], name);
            }
        }
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
        CMS.OutDebug("CMS.PluginSystem.Execute(): Executing '" + type + "' hooks", CMS.Debug.JS_PLUGINSYSTEM);
        var args = Array.prototype.slice.call(arguments, 1);
        $.each(this.hooks[type], function (index, data) {
            CMS.OutDebug("CMS.PluginSystem.HookAll(): Executing '" + type + "' for plugin '" + data.name + "'", CMS.Debug.JS_PLUGINSYSTEM);
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