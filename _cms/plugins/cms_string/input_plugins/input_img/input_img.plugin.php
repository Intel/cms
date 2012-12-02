<?php
    class Plugin_input_img {
        public function __construct() {
            // Load JS
            Editor::LoadJS('_cms/plugins/cms_string/input_plugins/input_img/input_img.js');
        }
        
        public function GetContent($a_attr) {
            $string_id = $a_attr['id'];
            $data = Locales::ReadData($string_id);
            return '<img data-hash="'.$data['hash'].'" '.$a_attr['attr'].' src="_cms/static/?hash=' . $data['hash'] . ($a_attr['width'] ? "&w=" . $a_attr['width'] : "") . ($a_attr['height'] ? "&h=" . $a_attr['height'] : "") . '" />';
        }
        
        public function GenEditorData($a_attr) {
            $data = array();
            $data['ownerid'] = $a_attr['ownerid'];
            $data['type'] = "input_img";
            $data['name'] = $a_attr['name'];
            $data['width'] = $a_attr['width'];
            $data['height'] = $a_attr['height'];
			
            $unn = Locales::ReadData($a_attr['id']);
			$data['hash'] = $unn['hash'];
			
            $data['title'] = Locales::getStringOrJSONLocale($a_attr['title']);
            
            Editor::AddData(DATA_MODULE_DATA, $data);
        }
        
        public function SaveObject($a_data) {
            Locales::WriteData($a_data->data_id, array('hash' => $a_data->object['hash']));
        }
    }
?>