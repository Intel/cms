<?
    // ###################
	// Global Definitions
	// ###################

	if (!FAKE)
		exit;
    
    // Database
    define("DB_HOST",                       "localhost");
    define("DB_USER",                       "ten-games_league");
    define("DB_PASS",                       "dSg31Hs6");
    define("DB_DATABASE",                   "www_ten-games_league");
    define("DB_PREFIX",                     "cms_");
    // Tables
    define("DB_TBL_ACCOUNT",                DB_PREFIX . "account");
    define("DB_TBL_ACCOUNT_SESSIONS",       DB_PREFIX . "account_sessions");
    define("DB_TBL_PAGES",                  DB_PREFIX . "pages");
    define("DB_TBL_MODULE_TEMPLATE",        DB_PREFIX . "module_template");
    define("DB_TBL_MODULE",                 DB_PREFIX . "module");
    define("DB_TBL_DATA",                   DB_PREFIX . "data");
	
	// Static data
	define("STATIC_URL",					"_cms/static/");
	define("STATIC_IMG_EXT",				".jpg");
    
    // Compiler
    define("COMPILER_TEMPLATES_DIR",        "./_cms/templates");
    define("COMPILER_MODE_FRONTEND",        0);
    define("COMPILER_MODE_EDITOR",          1);

    // Template Engine
    define("TEMPLATE_ESCAPE_CHARACTER",     '@');
    
    // Editor
    define("DATA_CONTAINER",                0);
    define("DATA_MODULE",                   1);
    define("DATA_PAGE",                     2);
    define("DATA_LOCALES",                  3);
    define("DATA_STRINGS",                  4);
	define("DATA_TEMPLATES",                5);
    define("DATA_MODULE_DATA",              6);
    define("DATA_JAVASCRIPT",               7);
    
    // Locales
    define("LOCALES_DIR",                   "./_cms/locales");
    define("LOCALES_DEFAULT",               "enUS");
    
    // Plugins
    define("PLUGIN_DIR",                    "./_cms/plugins");
    define("PLUGIN_EXT",                    ".plugin.php");
    
?>