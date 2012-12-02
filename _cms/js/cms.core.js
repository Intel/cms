$(function() {
    $.cookie.json = true;
    CMS.Init();
});

var CMS = {
    // Modules
    PluginSystem: false,
    ToolBar: false,
    Comm: false,
    Locales: false,
    ObjMgr: false,
    
    // Internal Data
    Data: false,
    
    // Debug Settings
    DebugMask: 0,
    
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
        else if (!this.ObjMgr)
            return CMS.OutError("CMS.Init(): ObjMgr module not loaded");
        
        // Load CMS Data
        var cms_data = $("#cms-data");
        if (!cms_data.length)
            return CMS.OutError("CMS.Init(): JSON Data not found");
        else
            this.Data = JSON.parse(cms_data.html());
        
        // Initialize Debug
        this.Debug = this.Data.Defines.DebugMaskList;
        this.DebugMask = this.Data.Defines.DebugMask;
        
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
        // Initialize ObjMgr
        this.ObjMgr.Init();
        
        // Events
        $(window).unload(function() {
            CMS.SaveUserData();
        });
        
        // We're ready to go!
        CMS.OutDebug("CMS.Init(): Loaded!", CMS.Debug.JS_CORE);
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
        var UserData = new Object;
        UserData.ToolBar = new Object;
        UserData.ToolBar.Opened = CMS.ToolBar.Opened;
        UserData.ToolBar.Locked = CMS.ToolBar.Locked;
        $.cookie('CMS_USERDATA', UserData);
        
        CMS.OutDebug("CMS.SaveUserData(): Saved", CMS.Debug.JS_CORE);
    },
    
    LoadUserData: function() {
        var UserData = $.cookie('CMS_USERDATA');
        
        if (!UserData)
            return;
        
        CMS.ToolBar.Opened = UserData.ToolBar.Opened;
        CMS.ToolBar.Locked = UserData.ToolBar.Locked;
        
        CMS.OutDebug("CMS.LoadUserData(): Loaded", CMS.Debug.JS_CORE);
    },
    
    GenUniqueID: function() {
        if (!this.UniqueIDPointer)
            return this.UniqueIDPointer = (new Date()).getTime();
        else
            return ++this.UniqueIDPointer;
    }
};
