<?php
    // ###################
	// Global Definitions
	// ###################

	if (!FAKE)
		exit;
    
    // Debug masks
    class DebugMask {
        const NONE                          = 0;
        // JS
        const JS_CORE                       = 1;
        const JS_COMM                       = 2;
        const JS_LOCALES                    = 4;
        const JS_TOOLBAR                    = 8;
        const JS_PLUGINSYSTEM               = 16;
        const JS_OBJMGR                     = 32;
        // PHP
        const PHP_CORE                      = 1024;
        const PHP_COMPILER                  = 2048;
        const PHP_AJAX_COMM                 = 4096;
    }
    
    // Tables
    define("DB_PREFIX",                     "cms_");
    define("DB_TBL_ACCOUNT",                DB_PREFIX . "account");
    define("DB_TBL_ACCOUNT_SESSIONS",       DB_PREFIX . "account_sessions");
    define("DB_TBL_PAGES",                  DB_PREFIX . "pages");
    define("DB_TBL_MODULE_TEMPLATE",        DB_PREFIX . "module_template");
    define("DB_TBL_MODULE",                 DB_PREFIX . "module");
    define("DB_TBL_DATA",                   DB_PREFIX . "data");
    
    // Compiler
    define("COMPILER_MODE_FRONTEND",        0);
    define("COMPILER_MODE_EDITOR",          1);
    
    // Editor
    define("DATA_CONTAINER",                0);
    define("DATA_MODULE",                   1);
    define("DATA_PAGE",                     2);
    define("DATA_LOCALES",                  3);
    define("DATA_STRINGS",                  4);
	define("DATA_TEMPLATES",                5);
    define("DATA_MODULE_DATA",              6);
    define("DATA_JAVASCRIPT",               7);
    define("DATA_DEFINES",                  8);
    
    // Ajax
    define("AJAX_URL",                      "index.php");
    
?>