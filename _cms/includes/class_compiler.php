<?
	// #######################
	// Template Compiler Class
	// #######################

	if (!FAKE)
		exit;
	
	class Compiler {  
        // Data
        public $m_pageid;
        public $m_pagename;
        // enduser or editor mode
        public static $Mode;
        
        public function CompilePage($a_id, $a_mode = COMPILER_MODE_FRONTEND) {
            self::$Mode = $a_mode;
            
            $result = Database::Query("SELECT * FROM `" . DB_TBL_PAGES . "` WHERE `id` = '" . $a_id . "'");
            
            if (!$result->HasData())
                die("Page with id #" . $a_id . " not found!");
            
            $this->m_pageid = $result->GetValue('id');
            $this->m_pagename = $result->GetValue('name');
            
            $compiled = $this->BuildTemplate($result->GetValue('template') . ".tmpl");
            
            if ($a_mode == COMPILER_MODE_FRONTEND) {
                Database::Query("UPDATE `" . DB_TBL_PAGES . "` SET `compiled` = '" . Database::Escape(serialize($compiled)) . "' WHERE `id` = '" . $a_id . "'");
                return $compiled;
            } else
                return $compiled; // Do not insert editor pages into db
        }
        
        public function BuildTemplate($a_template) {
            // Load template
            $doc = new Template_Document($a_template);
            
            // Load all static modules
            $modules = $doc->getElementsByTag('CMS_MODULE');
            foreach ($modules as $module) {
                // Build Module
                $c_module = new StaticModule($module, $this->m_pageid);
                $module_doc = $c_module->Build();
                
                $module->replaceWith($module_doc);
            }
            
            // Load all modules
            $containers = $doc->getElementsByTag('CMS_CONTAINER');
            foreach ($containers as $container) {
                $c_container = new Container($container, $this->m_pageid);
                $c_container->Build();
            }
            
            // Plugin Hook
            $data_object = new stdClass();
            $data_object->pageid = $this->m_pageid;
            $data_object->doc = $doc;
            ObjMgr::GetPluginMgr()->ExecuteHook("On_Compiler_BuiltTemplate", $data_object);
            
            return $doc;
        }
    }
    
    class Container {
        public $m_container;
        public $m_pageid;
        
        public function __construct($a_container, $a_pageid) {
            $this->m_container = $a_container;
            $this->m_pageid = $a_pageid;
        }
        
        public function Build() {
            // Get container type and define string
            $type = $this->m_container->getAttribute("type");
            $name = $this->m_container->getAttribute("name");
            $title = $this->m_container->getAttribute("title");
            $slots = $this->m_container->getAttribute("slots");
            
            // wrapepr for js
            if (Compiler::$Mode == COMPILER_MODE_EDITOR)
                $this->m_container->addChild(new Template_TextNode('<div class="editor-container editor-container-' . $type . '" id="editor-container-' . $name . '" data-type="' . $type . '">'));
            
            for ($slot = 0; $slot < $slots; $slot++) {
                // Load module in slot
                $result = Database::Query("SELECT * FROM `" . DB_TBL_MODULE . "` WHERE `container` = '" . $name . "' AND `pageid` = '" . $this->m_pageid . "' AND `slot` = '" . $slot . "'");
                
                if ($result->HasData()) {
                    $row = $result->GetRow();
                    // Build Module
                    $module = new Module($row['id']);
                    $module_tmpl = $module->Build();
                    // insert after iterator
                    $this->m_container->addChild($module_tmpl);
                } /*else { // Place holder
                    // Create DOM object
                    $doc = new DOMDocument();
                    // Load template
                    if (Compiler::$Mode == COMPILER_MODE_FRONTEND)
                        $doc->load(COMPILER_TEMPLATES_DIR . '/modules/' . $type . '/placeholder.tmpl');
                    else
                        $doc->load(COMPILER_TEMPLATES_DIR . '/modules/' . $type . '/placeholder-editor.tmpl');
                    
                    $container_html .= $doc->saveHTML();
                }*/
            }
            
            if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                // Add container wrapper for js
                $this->m_container->addChild(new Template_TextNode('</div>'));
                
                // Add data
                $data = array();
                $data['name'] = $name;
                $data['title'] = Locales::getStringOrJSONLocale($title);
                $data['type'] = $type;
                $data['slots'] = $slots;
                Editor::AddData(DATA_CONTAINER, $data);
            }

            //$this->m_container->remove();
        }
    }
    
    class Node {
        public $m_moduleid;
        public $m_ownerid;
        
        public function __construct($a_moduleid, $a_ownerid = null) {
            $this->m_moduleid = $a_moduleid;
            $this->m_ownerid = (isset($a_ownerid) ? $a_ownerid : $a_moduleid);
        }
        
        public function Build($a_node) {
            // Get all elements
            $nodes = $a_node->getElementsByTag('*', TEMPLATE_SEARCHMODE_UNMATCHED_RECURSIVE);
            foreach ($nodes as $node)
            {
                // Plugin Hook
                $data_object = new stdClass();
                $data_object->node = $node;
                $data_object->moduleid = $this->m_moduleid;
                $data_object->ownerid = $this->m_ownerid;
                ObjMgr::GetPluginMgr()->ExecuteHook("On_Node_BuildTag_" . $node->tag(), $data_object);
            }
        }
    };
    
    class Module {
        public $m_id;
        
        public static function Create($a_type, $a_template, $a_name, $a_flags = "") {
            // Insert module
            Database::Query("INSERT INTO `" . DB_TBL_MODULE_TEMPLATE . "` (`type`, `template`, `name`, `flags`) VALUES ('" . $a_type . "', '" . $a_template . "', '" . $a_name . "', '" . $a_flags . "')");
            
            // Get new id
            $id = Database::GetLastIncrId();
            
            return new Module($id);
        }
        
        public function __construct($a_id) {
            $this->m_id = $a_id;
        }
        
        public function Build() {
            // Load module data
            $result = Database::Query("SELECT * FROM `" . DB_TBL_MODULE_TEMPLATE . "` WHERE `id` = '" . $this->m_id . "'");
            
            if (!$result->HasData())
                die("Module::Build(): Module #" . $this->m_id . " not found!");
            
            $module_template = $result->GetRow();
            
            // Load template
            $doc = new Template_Document('modules/' . $module_template['type'] . '/' . $module_template['template'] . '.tmpl');
            
            $node = new Node($this->m_id);
            $node->Build($doc);
            
            if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                $container = new Template_Document();
                $container->addChild(new Template_TextNode('<div data-moduleid="' . $this->m_id . '">'));
                $container->addChild($doc);
                $container->addChild(new Template_TextNode('</div>'));
                return $container;
            } else
                return $doc;
        }
        
        /*public function ParseIterators($a_doc)
        {
            $iterators = $a_doc->getElementsByTag('CMS_ITERATOR');
            foreach ($iterators as $iterator) {
                if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                    
                    $iterator_data = array();
                    //$iterator_data['id']
                    $this->m_iterators[] = $iterator_data;
                }
                
                // Create iterator handler object
                $itr_hndl = new IteratorHandler($iterator, $this->m_id);
                
                // replace
                $iterator->replaceWith(new Template_TextNode($itr_hndl->Build()));
            }
        }
        
        public static function GenerateIteratorStructures($a_module_data) {
            $doc = new DOMDocument();
            $doc->load(COMPILER_TEMPLATES_DIR . '/modules/' . $a_module_data['type'] . '/' . $a_module_data['template'] . '.tmpl');
            
            $itr_data = array();
            
            $iterators = $doc->getElementsByTagName('CMS_ITERATOR');
            foreach ($iterators as $iterator) {
                $iterator = $iterator->cloneNode(true);
                
                $citerators = $iterator->getElementsByTagName('CMS_ITERATOR');
                while ($citerator = $citerators->item(0)) {
                    // Create new xml node
                    $fragment = $citerator->ownerDocument->createDocumentFragment();
                    $fragment->appendXML("<var data-type=\"mainitr\" data-name=\"" . $citerator->attributes->getNamedItem("name")->nodeValue . "\"></var>");
                     
                    // Replace with new string xml
                    $parent = $citerator->parentNode;
                    $parent->replaceChild($fragment, $citerator);
                }
                
                $strings = $iterator->getElementsByTagName('CMS_STRING');
                while ($string = $strings->item(0)) {
                    $compiled_attr = '';
                    $attributes = $string->attributes;
                    if(!is_null($attributes))
                    {
                        foreach ($attributes as $attr)
                        {
                            $compiled_attr .= 'data-' . $attr->nodeName . '="' . htmlspecialchars(Locales::getStringOrJSONLocale($attr->nodeValue)) . '" ';
                        }
                    }
                    
                    $cstring = "<var " . $compiled_attr . "data-locales=\"{}\"></var>";
                    
                    // Create new xml node
                    $fragment = $string->ownerDocument->createDocumentFragment();
                    $fragment->appendXML($cstring);
                     
                    // Replace with new string xml
                    $parent = $string->parentNode;
                    $parent->replaceChild($fragment, $string);
                }
                
                $itr_data[$iterator->getAttribute("name")] = "<![CDATA[" . htmlspecialchars("<var data-type=\"itr\" data-name=\"" . $iterator->attributes->getNamedItem("name")->nodeValue . "\">" . str_replace("\n", "", GetContentAsString($iterator)) . "</var>") . " ]]>";
            }
            
            return $itr_data;
        }*/
    }
    
    class StaticModule extends Module {
        public $m_node;
        public $m_pageid;
        public $m_template;
        public $m_flags;
        
        public function __construct($a_node, $a_pageid) {
            $this->m_node = $a_node;
            
            $this->m_template = $this->m_node->getAttribute("template");
            $this->m_name = $this->m_node->getAttribute("name");
            $this->m_flags = $this->m_node->getAttribute("flags");
            
            if ($this->m_flags == "_global")
                $this->m_pageid = "0";
            else
                $this->m_pageid = $a_pageid;   
            
            $query = "SELECT `tmpl`.`id` FROM `" . DB_TBL_MODULE_TEMPLATE . "` AS `tmpl` LEFT JOIN `" . DB_TBL_MODULE . "` AS `module` ON `tmpl`.`id` = `module`.`id` WHERE `tmpl`.`template` = '" . $this->m_template . "' AND `tmpl`.`name` = '" . $this->m_name . "' AND `module`.`pageid` = '" . $this->m_pageid . "' AND `module`.`container` = 'global'";
            $result = Database::Query($query);
            
            // Create module if it doesn't exist
            if (!$result->HasData()) {
                // Create module template if it doesn't exist
                $result = Database::Query("SELECT * FROM `" . DB_TBL_MODULE_TEMPLATE . "` WHERE `type` = 'static' AND `template` = '" . $this->m_template . "' AND `flags` = '_global'");
                if ($result->HasData()) {
                    $this->m_id = $result->GetValue('id');
                } else {
                    $module = Module::Create("static", $this->m_template, $this->m_name, $this->m_flags);
                    $this->m_id = $module->m_id;
                }
                
                Database::Query("INSERT INTO `" . DB_TBL_MODULE . "` (`id`, `pageid`, `container`) VALUES ('" . $this->m_id . "', '" . $this->m_pageid . "', 'global')");
            } else {
                $module_data = $result->GetRow();
                $this->m_id = $module_data['id'];
            }
        }
    }
    
    /*class IteratorHandler {
        public $m_node;
        public $m_ref;
        public $m_content;
        public $m_module_id;
        public $m_editor_data;
        
        public function __construct($a_node, $a_ref)
        {
            $this->m_node = $a_node;
            $this->m_ref = $a_ref;
        }
        
        public function Build()
        {
            $name = $this->m_node->getAttribute("name");
            
            $result = Database::Query("SELECT * FROM `" . DB_TBL_DATA . "` WHERE `type` = 'itr' AND `owner` = '" . $this->m_ref . "' AND `name` = '" . $name .  "' ORDER BY `id` ASC");
            $output = '';
            // Parse nested iterators
            if ($result->HasData()) {
                do {
                    $doc = clone $this->m_node;
                    
                    $iterator_data = $result->GetRow();
                    $id = $iterator_data['id'];
                    
                    $iterators = $doc->getElementsByTag('CMS_ITERATOR');
                    foreach ($iterators as $iterator) {
                        // Change sign as referenced iterators reference is negative
                        $itr_hndl = new IteratorHandler($iterator, -($id));
                        
                        $iterator->replaceWith(new Template_TextNode($itr_hndl->Build()));
                    }
                    
                    // Convert named strings to ids
                    Module::ConvertStrings($doc, 0, $id);
                    
                    $html = $doc->getHtml();
                    
                    if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                        $html = "<var data-type=\"itr\" data-name=\"" . $name . "\" data-id=\"" . $id . "\">" . $html . "</var>";
                    }
                    
                    $output .= $html;
                } while ($result->NextRow());
            }
            
            if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                $output = "<var data-type=\"mainitr\" data-name=\"" . $name . "\">" . $output . "</var>";
            }

            return $output;
        }
        
        public function GetEditorData() {
            return $this->m_editor_data;
        }
    }*/
?>