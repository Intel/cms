<?
    class Plugin_input_link {
        public function __construct() {
            // Load JS
            Editor::LoadJS('_cms/plugins/cms_string/input_plugins/input_link/input_link.js');
        }
        
        public function GetContent($a_attr) {
            $string_id = $a_attr['id'];
            $data = Locales::ReadData($string_id);
            return '<a '.$a_attr['attr'].' href="'.$data['link_url'].'">'.$data['link_title'][Locales::GetLocale()].'</a>';
        }   

        public function GenEditorData($a_attr) {
            $data = array();
            $data['ownerid'] = $a_attr['ownerid'];
            $data['type'] = "input_link";
            $data['name'] = $a_attr['name'];
            $data['tooltip_url'] = Locales::getStringOrJSONLocale($a_attr['tooltip_url']);
            $data['tooltip_title'] = Locales::getStringOrJSONLocale($a_attr['tooltip_title']);
            $data['title'] = Locales::getStringOrJSONLocale($a_attr['title']);
            
            $locdata = Locales::ReadData($a_attr['id']);
            $data['link_url'] = $locdata['link_url'];
            $data['link_title'] = $locdata['link_title'];
            /*foreach (Locales::$m_locales as $loc) {
                $link_data = Locales::ReadStringData($a_attr['id'], $loc);
                $data['link_url'] = $link_data['link_url'];               
                $data['link_title'][$loc] = $link_data['link_title'];               
            }*/
            
            Editor::AddData(DATA_MODULE_DATA, $data);
        }   

        public function SaveObject($a_data) {
            $object = $a_data->object;
            
            $data = array('link_url' => $object['link_url'],
                            'link_title' => $object['link_title']);
            
            Locales::WriteData($a_data->data_id, $data);
            /*$link_url = $object['link_url'];
            
            // Iterate over locales
            foreach (Locales::$m_locales as $loc) {
            
                $link_title = $object['link_title'][$loc];
            
                // Create Data array
                $data = array("link_url" => $link_url, "link_title" => $link_title);
                // Save Data
                Locales::WriteStringData($a_data->data_id, $a_data->moduleid, $loc, $data);
            }*/
        }        
    }
?>