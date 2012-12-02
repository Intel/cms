CMS.ObjMgr = {
    Containers: new Array,
    Modules: new Array,
    Init: function() {
        this.LoadContainers();
        this.LoadModules();
        // Ready!
        CMS.OutDebug("CMS.ObjMgr.Init(): Loaded!", CMS.Debug.JS_OBJMGR);
    },
    LoadContainers: function() {
        for (var idx in CMS.Data.Containers) {
			var containerData = CMS.Data.Containers[idx];
			var containerObj = $("#editor-container-" + containerData.name);
			
            CMS.OutDebug("CMS.ObjMgr.LoadContainers(): Loaded Container '" + containerData.name + "'", CMS.Debug.JS_OBJMGR);
		}
    },
    LoadModules: function() {
        for (var idx in CMS.Data.Modules) {
            var moduleData = CMS.Data.Modules[idx];
            
            var module = new CMS.Module(moduleData.id, moduleData.name, moduleData.template, moduleData.type);
            
            $('div[data-moduleid="' + moduleData.id + '"]').each(function() {
                module.AddObject($(this));
            });
            
            this.Modules.push(module);
            
            CMS.OutDebug("CMS.ObjMgr.LoadModules(): Loaded Module '" + moduleData.name + "'", CMS.Debug.JS_OBJMGR);
        }
    },
    GetModule: function(id) {
        for (var idx in this.Modules) {
            if (this.Modules[idx].id == id)
                return this.Modules[idx];
        }
        return false;
    }
};

CMS.Module = function(id, name, template, type) {
    this.id = id;
    this.name = name;
    this.template = template;
    this.type = type;
    
    // Containers
    this.Objects = $();
    
    // Functions
    this.AddObject = function(obj) {
        if (obj.length > 1)
            return CMS.OutDebug("CMS.Module.AddObject(): Expected single object.", CMS.Debug.JS_OBJMGR);
        
        if (!obj.length)
            return CMS.OutDebug("CMS.Module.AddObject(): Object is empty!", CMS.Debug.JS_OBJMGR);
        
        // Fix shit
        obj.css('position', 'relative');
        obj.append('<div style="clear: both;"></div>');
        
        // Add Overlay
        obj.append('<div class="cms-module-overlay"><div class="cms-module-overlay-bg k-block k-shadow k-success-colored"></div></div>');
        obj.find('.cms-module-overlay').hide();
        obj.hover(
            function () {
                $(this).find('.cms-module-overlay').show();
            },
            function () {
                $(this).find('.cms-module-overlay').hide();
            }
        );
        
        this.Objects = this.Objects.add(obj);
    }
}

var test = new CMS.Module(5, 9);
console.log(test.id);
