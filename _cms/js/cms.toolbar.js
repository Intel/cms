CMS.ToolBar = {
    Init: function() {
        var tmpl = jQuery.templates('<div id="cms-toolbar-wrapper" class="k-block k-shadow">\
                                        <div class="k-header" style="text-align: center;">ToolBar</div>\
                                        <div id="cms-toolbar-container">\
                                            <div id="cms-toolbar-treeview"></div>\
                                        </div>\
                                    </div>');
        
        // Render template
        var html = $(tmpl.render());
        
        var body = $('body');
        
        if (!body.length)
            return CMS.OutError("CMS.ToolBar.Init(): Body element not found");
        
        // Add to page
        body.append(html);
        
        // Save objects
        this.Wrapper = $('#cms-toolbar-wrapper');
        this.Container = this.Wrapper.find('#cms-toolbar-container');
        this.TreeViewContainer = this.Container.find('#cms-toolbar-treeview');
        
        // Hover
        this.Wrapper.hover(function() {CMS.ToolBar.Open()}, function() {CMS.ToolBar.Close()});
        this.Close(0);
        
        // Initialize TreeView
        this.CreateTreeView();
        
        // Ready!
        CMS.OutDebug("CMS.ToolBar.Init(): Loaded!");
    },
    
    CreateTreeView: function() {
        // Initialize Kendo Tree View
        this.TreeViewContainer.kendoTreeView();
        this.TreeView = this.TreeViewContainer.data("kendoTreeView");
        
        // Disable selection
        this.TreeView.bind("select", function(e) {
            e.preventDefault();
        });
        
        // Settings
        this.TreeView.append({ text: "Settings", spriteCssClass: "k-icon k-i-custom"});
        
        // Pages
        this.TreeView.append({ text: "Pages", spriteCssClass: "k-icon k-i-restore"});
    },
    
    Open: function(duration = 100) {
        CMS.ToolBar.Wrapper.stop();
        CMS.ToolBar.Wrapper.animate({"left": "0px"}, duration);
    },
    
    Close: function(duration = 100) {
        CMS.ToolBar.Wrapper.stop();
        CMS.ToolBar.Wrapper.animate({"left": (10 - CMS.ToolBar.Wrapper.width())}, duration);
    }
};