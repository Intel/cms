<?php
    class Plugin_input_richtext {
        public function __construct() {
            // Load JS
            Editor::LoadJS('_cms/plugins/cms_string/input_plugins/input_richtext/input_richtext.js');
			Editor::LoadCSS('_cms/js/cleditor/jquery.cleditor.css');
			Editor::LoadJS('_cms/js/cleditor/jquery.cleditor.min.js');
        }
        
        public function GetContent($a_attr) {
            $string_id = $a_attr['id'];
            $data = Locales::ReadData($string_id);
            return $data['textbox'][Locales::GetLocale()];
        }
        
        public function GenEditorData($a_attr) {
            $data = array();
            $data['ownerid'] = $a_attr['ownerid'];
            $data['type'] = "input_richtext";
            $data['name'] = $a_attr['name'];
            $data['title'] = Locales::getStringOrJSONLocale($a_attr['title']);
            
            $locdata = Locales::ReadData($a_attr['id']);
            $data['locales'] = $locdata['textbox'];
            
            Editor::AddData(DATA_MODULE_DATA, $data);
        }
        
        public function SaveObject($a_data) {
            $object = $a_data->object;
            
            Locales::WriteData($a_data->data_id, array('textbox' => $object['locales']));
        }
    }
?>