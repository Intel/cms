<?php
	// ###################
	// Global Class Loader
	// ###################

	if (!FAKE)
		exit;
	
    // Main definitions
    include("defines.php");
    
    // Config
    include("config.php");
    
    // Helper functions
    include("utils.php");
    
    // Main classes
    include("class_template.php");
    include("class_database_mysqli.php");
    include("class_account.php");
    include("class_locales.php");
    include("class_compiler.php");
    include("class_content.php");
    include("class_editor.php");
    include("class_pluginmgr.php");
    include("class_ajax_comm.php");
    
    class ObjMgr {
        private static $m_account;
        private static $m_pluginmgr;
        
        public static function Initialize() {
            // Database
			Database::Initialize();
            Database::Query("SET NAMES UTF8");
            
            // Locales
            Locales::Initialize();
            
            // Acount
			self::$m_account = new Account();
            
            // Plugins
            self::$m_pluginmgr = new PluginMgr(PLUGIN_DIR);
        }
        
        public static function GetPluginMgr() {
            return self::$m_pluginmgr;
        }
        
        public static function GetAccount() {
            return self::$m_account;
        }
    }
?>