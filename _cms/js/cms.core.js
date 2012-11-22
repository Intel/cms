$(function() {
    $.cookie.json = true;
    CMS.DebugMask = CMS_DEBUG_CORE | CMS_DEBUG_TOOLBAR;
    CMS.Init();
});

var CMS = {
    // Modules
    PluginSystem: false,
    ToolBar: false,
    Comm: false,
    Locales: false,
    
    // Internal Data
    Data: false,
    
    // Debug Settings
    DebugLevel: 0,
    
    Init: function() {
        // Check for dependencies
        if (!this.Comm)
            return CMS.OutError("CMS.Init(): Communication module not loaded");
        else if (!this.Locales)
            return CMS.OutError("CMS.Init(): Locales module not loaded");
        else if (!this.PluginSystem)
            return CMS.OutError("CMS.Init(): PluginSystem module not loaded");
        else if (!this.ToolBar)
            return CMS.OutError("CMS.Init(): ToolBar module not loaded");
        
        // Load CMS Data
        var cms_data = $("#cms-data");
        if (!cms_data.length)
            return CMS.OutError("CMS.Init(): JSON Data not found");
        else
            this.Data = JSON.parse(cms_data.html());
        
        // Load User Data
        this.LoadUserData();
        
        // Initialize communication module
        this.Comm.Init();
        // Initialize locales
        this.Locales.Init();
        // Initialize plugins
        this.PluginSystem.Init();
        // Initialize toolbar
        this.ToolBar.Init();
        
        // Events
        $(window).unload(function() {
            CMS.SaveUserData();
        });
        
        // We're ready to go!
        CMS.OutDebug("CMS.Init(): Loaded!", CMS_DEBUG_CORE);
    },
    
    OutError: function(error) {
        if (CMS.DebugLevel < 1)
            return;
        
        console.log("[CMS_ERROR]" + error);
    },
    
    OutDebug: function(msg, type) {
        if (CMS.DebugMask & type)
            console.log("[CMS_DEBUG]" + msg);
    },
    
    SaveUserData: function() {
        CMS.OutDebug("CMS.SaveUserData(): Saving...", CMS_DEBUG_CORE);
        
        var UserData = new Object;
        UserData.ToolBar = new Object;
        UserData.ToolBar.Opened = CMS.ToolBar.Opened;
        UserData.ToolBar.Locked = CMS.ToolBar.Locked;
        $.cookie('CMS_USERDATA', UserData);
    },
    
    LoadUserData: function() {
        CMS.OutDebug("CMS.LoadUserData(): Loading...", CMS_DEBUG_CORE);
        
        var UserData = $.cookie('CMS_USERDATA');
        
        if (!UserData)
            return;
        
        CMS.ToolBar.Opened = UserData.ToolBar.Opened;
        CMS.ToolBar.Locked = UserData.ToolBar.Locked;
    }
};

var CMS_DEBUG_CORE = 1;
var CMS_DEBUG_COMM = 2;
var CMS_DEBUG_LOCALES = 4;
var CMS_DEBUG_TOOLBAR = 8;
var CMS_DEBUG_PLUGINSYSTEM = 16;
