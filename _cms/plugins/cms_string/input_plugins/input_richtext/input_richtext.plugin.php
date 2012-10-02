<?
    class Plugin_input_richtext {
        public function __construct() {
            // Load JS
            Editor::LoadJS(GetRelativePath(dirname(__FILE__)) . '/input_richtext.js');
			Editor::LoadCSS('_cms/js/cleditor/jquery.cleditor.css');
			Editor::LoadJS('_cms/js/cleditor/jquery.cleditor.min.js');
        }
        
        public function GetContent($a_attr) {
            $string_id = $a_attr['id'];
            $data = Locales::ReadStringData($string_id);
            return $data['textbox'];
        }
        
        public function GenEditorData($a_attr) {
            $data = array();
            $data['ownerid'] = $a_attr['ownerid'];
            $data['type'] = "input_richtext";
            $data['name'] = $a_attr['name'];
         // $data['tooltip'] = Locales::getStringOrJSONLocale($a_attr['tooltip']);
            $data['title'] = Locales::getStringOrJSONLocale($a_attr['title']);
            
            foreach (Locales::$m_locales as $loc) {
                $richtext_content = Locales::ReadStringData($a_attr['id'], $loc);
                $data['locales'][$loc] = addslashes($richtext_content['textbox']);
            }
            
            Editor::AddData(DATA_MODULE_DATA, $data);
        }
        
        public function SaveObject($a_data) {
            $object = $a_data->object;
            // Iterate over locales
            foreach ($object['locales'] as $locale=>$string) {			
                // Create Data array
                $data = array('textbox' => $string);
                // Save Data
                Locales::WriteStringData($a_data->data_id, $a_data->moduleid, $locale, $data);
            }
        }
    }
?>