<?
    class Plugin_cms_string {
        public $m_pluginmgr = NULL;
        
        public function __construct() {
            // Initialize local plugin mgr
            $this->m_pluginmgr = new PluginMgr(dirname(__FILE__) . "/input_plugins");
        }
        
        public function On_PrepareTemplate($a_data) {
            $doc = $a_data->doc;
            
            // Load all strings
            $strings = $doc->getElementsByTag('CMS_STRING');
            
            foreach ($strings as $string) {
                $html = "";
                
                switch($string->getAttribute('type'))
                {
                    case "const":
                        $html = Locales::GetConstString($string->getAttribute("name"));
                        break;
                    default:
                        // Process data in plugin
                        $plugin = $this->m_pluginmgr->GetPlugin("input_" . $string->getAttribute('type'));
                        
                        if (!$plugin)
                            die('Plugin_cms_string::On_Editor_LoadedPageTemplate(): No valid generators found for string type "' . $string->getAttribute('type') . '".');
                        
                        $html = $plugin->GetContent($string->attributes());
                        break;
                }
                
                $string->replaceWith(new Template_TextNode($html));
            }
        }
        
        // Convert string names to id's (optimization)
        public function On_Node_BuildTag_CMS_STRING($a_data) {
            $string = $a_data->node;
            
            // Skip const strings
            if ($string->getAttribute("type") == "const")
                return;
            
            // Only process db strings without id
            if ($string->hasAttribute("id"))
                return;
            
            $string_name = $string->getAttribute("name");
            $string_type = $string->getAttribute("type");
            $string_id = 0;
            
            // Load  string
            $result = Database::Query("SELECT `id` FROM `" . DB_TBL_DATA . "` WHERE `owner` = '" . $a_data->ownerid . "' AND `name` = '" . $string_name . "'");
            
            if ($result->HasData())
                $string_id = $result->GetValue('id');
            
            // Add
            $string->setAttribute("id", $string_id);
            // Set owner
            $string->setAttribute("ownerid", $a_data->ownerid);
            
            // Editor data
            if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                $plugin = $this->m_pluginmgr->GetPlugin("input_" . $string_type);
                
                if (!$plugin)
                    die('Plugin_cms_string::On_Node_BuildTag_CMS_STRING(): Plugin type "' . $string_type . '" not found.');
                
                $plugin->GenEditorData($string->attributes());
            }
        }
        
        function On_Editor_SaveModuleFragmentObject($a_data) {
            $object = $a_data->object;
            
            // Save if we have such a plugin
            if ($plugin = $this->m_pluginmgr->GetPlugin($object['type'])) {
                Database::Query("INSERT INTO `" . DB_TBL_DATA . "` (`type`, `name`, `owner`, `moduleid`) VALUES ('string', '" . $object['name'] . "', '" . $a_data->owner . "', '" . $a_data->moduleid . "')");
                $id = Database::GetLastIncrId();
                $a_data->data_id = $id;
                $plugin->SaveObject($a_data);
            }
        }
    }
?>