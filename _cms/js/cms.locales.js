CMS.Locales = {
    Default: false,
    Current: false,
    
    Init: function() {
        this.Default = CMS.Data.Locales.Default;
        this.Current = CMS.Data.Locales.Current;
        
        // Ready!
        CMS.OutDebug("CMS.Locales.Init(): Loaded!", CMS_DEBUG_LOCALES);
    },
    
    GetString: function(key) {
        CMS.OutDebug("CMS.Locales.GetString(): Fetching String '" + key + "'", CMS_DEBUG_LOCALES);
        var string = CMS.Data.Strings[key];
        for (var itr = 1; itr < arguments.length; ++itr) {
            string = string.replace("%s" + itr, arguments[itr]);
        }
        return string;
    }
};