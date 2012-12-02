<?php
    class Plugin_cms_iterator {
        public function __construct() {
            Editor::LoadJS('_cms/plugins/cms_iterator/cms_iterator.js');
        }
        
        public function On_Node_BuildTag_CMS_ITERATOR($a_data) {
            $iterator = $a_data->node;
            $name = $iterator->getAttribute("name");
            
            // create copy, php sucks...
            $iterator_ser = serialize($iterator);
            
            // Container for iterators
            $iterator_container = new Template_ContainerNode();
            $iterator->parent()->addChild($iterator_container, $iterator);
            
            $result = Database::Query("SELECT * FROM `" . DB_TBL_DATA . "` WHERE `type` = 'itr' AND `owner` = '" . $a_data->ownerid . "' AND `name` = '" . $name .  "' ORDER BY `id` ASC");
            if ($result->HasData()) {
                do {
                    // Get Iterator id
                    $iterator_data = $result->GetRow();
                    $id = $iterator_data['id'];
                    
                    // Clone template
                    $tmpl = unserialize($iterator_ser);
                    
                    $node = new Node($a_data->moduleid, -$id);
                    $node->Build($tmpl);
                    
                    // add to container
                    $iterator_container->addChild($tmpl);
                    
                    // link
                    $child_data[] = $id;
                } while ($result->NextRow());
            }
            
            // Add data
            $data = array();
            $data['ownerid'] = $a_data->ownerid;
            $data['type'] = "iterator";
            $data['name'] = $name;
            $data['content'] = $child_data;
            $data['template'] = $iterator->root()->template_file;
            Editor::AddData(DATA_MODULE_DATA, $data);
            
            // Remove "template"
            $iterator->remove();
        }
        
        function On_Editor_SaveModuleFragmentObject($a_data) {
            $object = $a_data->object;
            
            // Save
            if ($object['type'] == "iterator" && $object['childs']) {
                foreach ($object['childs'] as $childs) {
                    Database::Query("INSERT INTO `" . DB_TBL_DATA . "` (`type`, `name`, `owner`, `moduleid`) VALUES ('itr', '" . $object['name'] . "', '" . $a_data->owner . "', '" . $a_data->moduleid . "')");
                    $id = Database::GetLastIncrId();
                    Editor::SaveModuleFragment($childs, -($id));
                }
            }
        }
        
        function On_PostAction_iterator_fetch($a_data) {
            // Find iterator in template
            $doc = new Template_Document($a_data->post['iterator_template']);
            function search_func($node, $args) {
                if ($node instanceof Template_TagNode) {
                    return ($node->getAttribute("name") == $args);
                } else
                    return false;
            }
            $itrs = $doc->getElementsByFunc(search_func, $a_data->post['iterator_name']);
            $iterator = $itrs[0];
            
            // Generate id
            $id = Time("U") . substr((string)microtime(), 2, 6);
            
            // Build iterator template
            $node = new Node(-$id);
            $node->Build($iterator);
            
            // Pack JSON data
            $data = array();
            $data['id'] = $id;
            $data['content'] = Editor::$m_data['module_data'][-$id];
            
            print json_encode($data);
        }
    }
?>