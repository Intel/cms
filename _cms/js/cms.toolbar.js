CMS.ToolBar = {
    Locked: false,
    Opened: false,
    
    Init: function() {
        var tmpl = jQuery.templates('<div id="cms-toolbar-wrapper" class="k-block k-shadow">\
                                        <div class="k-header" style="text-align: center;">ToolBar</div>\
                                        <div id="cms-toolbar-container">\
                                            <div id="cms-toolbar-treeview"></div>\
                                        </div>\
                                        <span id="cms-toolbar-lock" class="k-icon" style="position: absolute; top: 6px; left: 6px;"></span>\
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
        if (this.Opened)
            this.Open(0);
        else
            this.Close(0);
        
        // Lock
        if (this.Locked)
            $('#cms-toolbar-lock').addClass("k-i-cancel");
        else
            $('#cms-toolbar-lock').addClass("k-i-tick");
        
        // Slide Locking
        $("#cms-toolbar-lock").click(function() {
            if (CMS.ToolBar.Locked) {
                $(this).addClass("k-i-tick");
                $(this).removeClass("k-i-cancel");
            } else {
                $(this).removeClass("k-i-tick");
                $(this).addClass("k-i-cancel");
            }
            CMS.ToolBar.Locked = !CMS.ToolBar.Locked;
        });
        
        // Initialize TreeView
        this.TreeView.Create();
        
        // Ready!
        CMS.OutDebug("CMS.ToolBar.Init(): Loaded!", CMS.Debug.JS_TOOLBAR);
    },
    
    TreeView: {
        Create: function() {
            // Initialize Kendo Tree View
            CMS.ToolBar.TreeViewContainer.kendoTreeView();
            this.KendoTreeView = CMS.ToolBar.TreeViewContainer.data("kendoTreeView");
            
            // Disable selection
            this.KendoTreeView.bind("select", function(e) {
                e.preventDefault();
            });
            
            // Disable text selection
            CMS.ToolBar.TreeViewContainer.attr('unselectable', 'on').css('user-select', 'none').on('selectstart', false);
            
            // Main Sections
            this.NodeSettings = this.AddNode('<span class="k-sprite k-icon" style="background: url(\'_cms/images/icons16/control_equalizer.png\')"></span>Settings');
            this.NodePages = this.AddNode('<span class="k-sprite k-icon" style="background: url(\'_cms/images/icons16/page_copy.png\')"></span>Pages');
            this.NodeModules = this.AddNode('<span class="k-sprite k-icon" style="background: url(\'_cms/images/icons16/bricks.png\')"></span>Modules');
            
            // Fill Everything
            this.FillPages();
            this.FillModules();
            
            // Collapse All Nodes
            this.KendoTreeView.collapse(".k-item");
            
            // Events
            this.KendoTreeView.bind("dragstart", this.DragStart);
            this.KendoTreeView.bind("drag", this.Drag);
        },
        
        FillPages: function() {
            for (var pageid in CMS.Data.Page.Pages) {
                var page = CMS.Data.Page.Pages[pageid];
                var name = page.Name[CMS.Locales.Current];
                
                CMS.OutDebug("CMS.ToolBar.TreeView.FillPages(): Adding Page '" + name +"'", CMS.Debug.JS_TOOLBAR);
                
                var PageNode = this.AddNode('<span class="k-sprite k-icon" style="background: url(\'_cms/images/icons16/page.png\')"></span>' + name + ' <span style="font-size: 6px">' + pageid + '</span><span class="k-sprite k-icon k-i-custom">', "page", this.NodePages);
                
                PageNode.click(function() {
                    
                });
            }
            this.AddNode('<span class="k-sprite k-icon" style="background: url(\'_cms/images/icons16/add.png\')"></span>Create Page', "", this.NodePages);
        },
        
        FillModules: function() {
            jQuery.each(CMS.Data.Modules, function(moduleid) {
                var module = CMS.Data.Modules[moduleid];
                
                CMS.OutDebug("CMS.ToolBar.TreeView.FillModules(): Adding Module '" + module.name +"'", CMS.Debug.JS_TOOLBAR);
                
                var ModuleNode = CMS.ToolBar.TreeView.AddNode('<span class="k-sprite k-icon" style="background: url(\'_cms/images/icons16/brick.png\')"></span>' + module.name + ' <span style="font-size: 6px">' + module.id + '</span><span class="k-sprite k-icon k-i-custom">', "module", CMS.ToolBar.TreeView.NodeModules);
                ModuleNode.hover(
                    function () {
                        CMS.ObjMgr.GetModule(module.id).Objects.append('<div class="cms-module-highlight k-block k-success-colored"></div>');
                    },
                    function () {
                        CMS.ObjMgr.GetModule(module.id).Objects.find('.cms-module-highlight').remove();
                    }
                );
                ModuleNode.disableSelection();
            });
            this.AddNode('<span class="k-sprite k-icon" style="background: url(\'_cms/images/icons16/add.png\')"></span>Create Module', "", this.NodeModules);
        },
        
        AddNode: function(html, type, parent) {
            if (parent)
                this.KendoTreeView.append({text: "uberl33thackfix"}, parent);
            else
                this.KendoTreeView.append({text: "uberl33thackfix"});
            
            // Get Node
            var node = this.KendoTreeView.findByText("uberl33thackfix");
            
            // Set Html
            node.html('<div class="k-top k-bot"><span class="k-in">' + html + '</span></div>');
            
            // Set Type
            node.data("NodeType", type);
            
            return node;
        },
        
        DragStart: function(e) {
            if ($(e.sourceNode).data("NodeType") != "module")
                e.preventDefault();
        },
        
        Drag: function(e) {
            e.setStatusClass("k-denied");
        }
    },
    
    Open: function(duration = 100) {
        CMS.ToolBar.Wrapper.stop();
        CMS.ToolBar.Wrapper.animate({"left": "0px"}, duration);
        this.Opened = true;
    },
    
    Close: function(duration = 100) {
        if (!CMS.ToolBar.Locked) {
            CMS.ToolBar.Wrapper.stop();
            CMS.ToolBar.Wrapper.animate({"left": (10 - CMS.ToolBar.Wrapper.width())}, duration);
            this.Opened = false;
        }
    }
};