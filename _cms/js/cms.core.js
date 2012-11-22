$(function() {
    CMS.DebugLevel = 2;
    CMS.Init();
});

var CMS = {
    PluginSystem: false,
    ToolBar: false,
    Comm: false,
    Data: false,
    DebugLevel: 0,
    
    Init: function() {
        // Check for dependencies
        if (!this.Comm)
            return CMS.OutError("CMS.Init(): Communication module not loaded");
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
        
        // Initialize communication module
        this.Comm.Init();
        // Initialize plugins
        this.PluginSystem.Init();
        // Initialize toolbar
        this.ToolBar.Init();
        
        // We're ready to go!
        CMS.OutDebug("CMS.Init(): Loaded!");
    },
    
    OutError: function(error) {
        if (CMS.DebugLevel < 1)
            return;
        
        console.log("[CMS_ERROR]" + error);
    },
    
    OutDebug: function(msg) {
        if (CMS.DebugLevel < 2)
            return;
        
        console.log("[CMS_DEBUG]" + msg);
    }
};
