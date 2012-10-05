<?
    class Plugin_input_string {
        public function __construct() {
            // Load JS
            Editor::LoadJS('_cms/plugins/cms_string/input_plugins/input_string/input_string.js');
        }
        
        public function GetContent($a_attr) {
            $string_id = $a_attr['id'];
            $data = Locales::ReadStringData($string_id);
            return $data['text'];
        }
        
        public function GenEditorData($a_attr) {
            $data = array();
            $data['ownerid'] = $a_attr['ownerid'];
            $data['type'] = "input_string";
            $data['name'] = $a_attr['name'];
			$data['width'] = $a_attr['width'];
            $data['tooltip'] = Locales::getStringOrJSONLocale($a_attr['tooltip']);
            $data['title'] = Locales::getStringOrJSONLocale($a_attr['title']);
            
            foreach (Locales::$m_locales as $loc) {
                $string_data = Locales::ReadStringData($a_attr['id'], $loc);
                $data['locales'][$loc] = $string_data['text'];
            }
            
            Editor::AddData(DATA_MODULE_DATA, $data);
        }
        
        public function SaveObject($a_data) {
            $object = $a_data->object;
            // Iterate over locales
            foreach ($object['locales'] as $locale=>$string) {
                // Create Data array
                $data = array('text' => $string);
                // Save Data
                Locales::WriteStringData($a_data->data_id, $a_data->moduleid, $locale, $data);
            }
        }
    }
?>