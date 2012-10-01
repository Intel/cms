<?
    // #########################
    // Plugin Management Class
    // #########################

    if (!FAKE)
        exit;
    
    class PluginMgr {
        public $plugins = array();
        
        public function __construct($a_dir) {
            // Load from dirs
            $plugins = find_all_dirs($a_dir);
            if ($plugins) {
                foreach ($plugins as $plugin) {
                    $this->AppendPlugin($a_dir . "/" . $plugin, $plugin);
                }
            }
            
            // Load files  
            $plugins = find_all_files($a_dir);
            if ($plugins) {
                foreach ($plugins as $plugin) {
                    $this->AppendPlugin($a_dir, str_replace(PLUGIN_EXT, "", $plugin));
                }
            }
        }
        
        public function AppendPlugin($plugin_dir, $plugin_name) {
            $plugin_file = $plugin_name . PLUGIN_EXT;
            $plugin_namespace = 'Plugin_' . $plugin_name;
            
            require_once($plugin_dir . "/" . $plugin_file);
            
            if (!class_exists($plugin_namespace))
                die('PluginMgr::AppendPlugin(): Plugin class does not exist for file "' . $plugin_name . '"');
            
            $this->plugins[$plugin_name] = new $plugin_namespace();
        }
        
        public function ExecuteHook($a_hook, $a_params = NULL) {
            foreach ($this->plugins as $plugin) {
                if (method_exists($plugin, $a_hook))
                    $plugin->$a_hook($a_params);
            }
        }
        
        public function GetPlugin($a_name) {
            if (isset($this->plugins[$a_name]))
                return $this->plugins[$a_name];
            else
                return false;
        }
    }
    
    abstract class PluginTemplate {
        // #######
        //  HOOKS
        // #######
        
        // Executed on Node::Build()
        // $a_data->doc - Template_Document
        // $a_data->moduleid - Id of the module
        // $a_data->ownerid - Id of the owner (positive for moduleid, negative for reference)
        abstract function On_Node_BuildTemplate($a_data);
        
        // Executed before template displayed
        // $a_data->doc - Template_Document
        abstract function On_PrepareTemplate($a_data);
        
        // Executed when module is saved (for each object of module fragment)
        // $a_data->object - the processed object
        // $a_data->owner - id of the object owner
        abstract function On_Editor_SaveModuleFragmentObject($a_data);
        
        // Executed when template is built in Compiler::BuildTemplate
        // $a_data->pageid - Id of the page whose template is built
        // $a_data->doc - Template_Document
        abstract function On_Compiler_BuiltTemplate($a_data);
    }
?>