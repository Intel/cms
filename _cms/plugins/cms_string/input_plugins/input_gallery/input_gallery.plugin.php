<?
    class Plugin_input_gallery {
        public function __construct() {
            // Load JS
            Editor::LoadJS('_cms/plugins/cms_string/input_plugins/input_gallery/input_gallery.js');
        }
        
        public function GetContent($a_attr) {
            $string_id = $a_attr['id'];
            $data = Locales::ReadData($string_id);
            
            $html = "";
            if ($data['images']) {
                foreach($data['images'] as $image) {
                    $opts = array(STATIC_URL . "?hash=" . $image['hash']); // %s1 - url
                    $html .= BuildString($a_attr['format'], $opts);
                }
            }
            
            return $html;
        }
        
        public function GenEditorData($a_attr) {
            $data = array();
            $data['ownerid'] = $a_attr['ownerid'];
            $data['type'] = "input_gallery";
            $data['name'] = $a_attr['name'];
            $data['title'] = Locales::getStringOrJSONLocale($a_attr['title']);
            
            $locdata = Locales::ReadData($a_attr['id']);
            $data['images'] = $locdata['images'];
            
            Editor::AddData(DATA_MODULE_DATA, $data);
        }
        
        public function SaveObject($a_data) {
            $object = $a_data->object;
            
            Locales::WriteData($a_data->data_id, array('images' => $object['images']));
        }
    }
?>