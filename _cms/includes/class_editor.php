<?
	// #######################
	//   Layout Editor Class
	// #######################

	if (!FAKE)
		exit;
	
	class Editor {
        public static $m_data;
        public static $m_pageid;
        public static $m_moduleid;
        public static $m_extra_head;
        
		public static function CreateModule($a_type, $a_template, $a_name) {
			if (!is_file(COMPILER_TEMPLATES_DIR . "/modules/" . $a_type . "/" . $a_template . ".tmpl"))
				die("Editor::CreateModule: Template not found: " . $a_type . ":" . $a_template);
			
			$type = Database::Escape($a_type);
			$template = Database::Escape($a_template);
			$name = Database::Escape($a_name);
			
			Database::Query("INSERT INTO `" . DB_TBL_MODULE_TEMPLATE . "` (`type`, `template`, `name`) VALUES ('" . $type . "', '" . $template . "', '" . $name . "')");
			
			$id = Database::GetLastIncrId();
			
			$data = self::GenerateModuleData($id);
			
			print (json_encode($data));
		}
		
		public static function DeleteModule($a_id, $a_force = false) {
			$id = Database::Escape($a_id);
			$result = Database::Query("SELECT * FROM `" . DB_TBL_MODULE_TEMPLATE . "` WHERE `id` = '" . $id . "'");
			
			if (!$result->HasData())
				die("Editor::DeleteModule: Module #" . $a_id . " not found");
			
			$module_template = $result->GetRow();
			
			if ($module_template['type'] == "static" && !$a_force)
				die("Editor::DeleteModule: Can't delete static module #" . $a_id);
			
			// Delete
			Database::Query("DELETE FROM `" . DB_TBL_MODULE_TEMPLATE . "` WHERE `id` = '" . $id . "'");
			Database::Query("DELETE FROM `" . DB_TBL_MODULE . "` WHERE `id` = '" . $id . "'");
			Database::Query("DELETE FROM `" . DB_TBL_DATA . "` WHERE `moduleid` = '" . $id . "'");
			Database::Query("DELETE FROM `" . DB_TBL_STRINGS . "` WHERE `moduleid` = '" . $id . "'");
			
			print 'ok';
		}
		
		public static function CreatePage($a_data) {
			if (!is_file(COMPILER_TEMPLATES_DIR . '/' . $a_data['template'] . ".tmpl"))
				die("Editor::CreatePage: Template not found: " . $a_data['template']);
			
			$template = Database::Escape($a_data['template']);
			$name = serialize($a_data['name']);
			
			Database::Query("INSERT INTO `" . DB_TBL_PAGES . "` (`name`, `template`) VALUES ('" . $name . "', '" . $template . "')");
			
			$id = Database::GetLastIncrId();
			
			$page_data = array();
			$page_data['id'] = $id;
			$page_data['name'] = $a_data['name'];
			$page_data['template'] = $a_template;
			$page_data['default'] = 0;
			
			print json_encode($page_data);
		}
		
        public static function SavePage($a_data) {
            self::$m_pageid = Database::Escape($a_data['pageid']);
            
            Database::Query("DELETE FROM `" . DB_TBL_MODULE . "` WHERE `pageid` = '" . self::$m_pageid . "' AND `container` != 'global'");
            
            if (isset($a_data['containers'])) {
                foreach ($a_data['containers'] as $container) {
                    if (!isset($container['modules']))
                        continue;
                    
                    foreach ($container['modules'] as $module) {
                        Database::Query("INSERT INTO `" . DB_TBL_MODULE . "` (`id`, `pageid`, `container`, `slot`) VALUES ('" . $module['id'] . "', '" . self::$m_pageid . "', '" . $container['name'] . "', '" . $module['slot'] . "')");
                    }
                }
            }
            
            $compiler = new Compiler();
            $compiler->CompilePage(self::$m_pageid);
        }
		
		public static function DeletePage($a_id) {
			$id = Database::Escape($a_id);
			
			Database::Query("DELETE FROM `" . DB_TBL_MODULE . "` WHERE `pageid` = '" . $id . "'");
			Database::Query("DELETE FROM `" . DB_TBL_PAGES . "` WHERE `id` = '" . $id . "'");
			
			$result = Database::Query("SELECT `id` FROM `" . DB_TBL_PAGES . "` WHERE `default` = '1'");
			if (!$result->HasData())
				Database::Query("UPDATE `" . DB_TBL_PAGES . "` SET `default` = '1' LIMIT 1");
		}
		
		public static function SetDefaultPage($a_id) {
			$id = Database::Escape($a_id);
			
			Database::Query("UPDATE `" . DB_TBL_PAGES . "` SET `default` = '0'");
			Database::Query("UPDATE `" . DB_TBL_PAGES . "` SET `default` = '1' WHERE `id` = '" . $id . "'");
		}
        
        public static function GeneratePageData() {
            $page_data = array();
            $page_data['id'] = self::$m_pageid;
            $page_data['pages'] = array();
            
            $result = Database::Query("SELECT `id`, `name`, `template`, `default` FROM `" . DB_TBL_PAGES . "`");
            
            do {
                $row = $result->GetRow();
                $page_data['pages'][$row['id']] = $row;
                $page_data['pages'][$row['id']]['name'] = unserialize($row['name']);
            } while ($result->NextRow());
            
            self::AddData(DATA_PAGE, $page_data);
			
			// Generate template data
			// Page templates
			$page_templates = find_all_files(COMPILER_TEMPLATES_DIR);
			foreach ($page_templates as $page_template) {
				$templates_data['page'][] = substr($page_template, 0, -5);
			}
			// Module templates
			$module_types = find_all_dirs(COMPILER_TEMPLATES_DIR . '/modules');
			foreach ($module_types as $module_type) {
				$module_templates = find_all_files(COMPILER_TEMPLATES_DIR . '/modules/' . $module_type);
				foreach ($module_templates as $module_template) {
					$templates_data['modules'][$module_type][] = substr($module_template, 0, -5);
				}
			}
			self::AddData(DATA_TEMPLATES, $templates_data);
        }
		
		public static function GenerateModulesData() {
			$result = Database::Query("SELECT * FROM `" . DB_TBL_MODULE_TEMPLATE . "`");
			
			if (!$result->HasData())
				return;
			
			do {
				$module_template = $result->GetRow();
				
                // Add data
                $data = array();
                $data['id'] = $module_template['id'];
                $data['type'] = $module_template['type'];
                $data['template'] = $module_template['template'];
				$data['name'] = $module_template['name'];
                
                //$iterators = Module::GenerateIteratorStructures($module_template);
                if (count($iterators))
                    $data['iterators'] = $iterators;
                Editor::AddData(DATA_MODULE, $data);
			} while ($result->NextRow());
		}
		
		public static function GenerateModuleData($a_id) {
			$id = Database::Escape($a_id);
			
			$result = Database::Query("SELECT * FROM `" . DB_TBL_MODULE_TEMPLATE . "` WHERE `id` = '" . $id . "'");
			
			if (!$result->HasData())
				die("Editor::GenerateModuleData: module #" . $a_id . " not found");
			
			$module_template = $result->GetRow();
			
			// Add data
            $data = array();
            $data['id'] = $module_template['id'];
            $data['type'] = $module_template['type'];
            $data['template'] = $module_template['template'];
			$data['name'] = $module_template['name'];
               
            //$iterators = Module::GenerateIteratorStructures($module_template);
            if (count($iterators))
                $data['iterators'] = $iterators;
			
			return $data;
		}
        
        public static function SaveModule($a_data) {
            //print_r($_POST);
            self::$m_pageid = Database::Escape($_POST['page_id']);
            self::$m_moduleid = Database::Escape($_POST['module_id']);
            
            // Delete old data
            Database::Query("DELETE FROM `" . DB_TBL_DATA . "` WHERE `moduleid` = '" . self::$m_moduleid . "'");
            Database::Query("DELETE FROM `" . DB_TBL_STRINGS . "` WHERE `moduleid` = '" . self::$m_moduleid . "'");
            
            self::SaveModuleFragment($a_data, self::$m_moduleid);
            
            $module = new Module(self::$m_moduleid);
            $doc = $module->Build(); 
            
            // Plugin Hook
            $data_object = new stdClass();
            $data_object->doc = $doc;
            ObjMgr::GetPluginMgr()->ExecuteHook("On_PrepareTemplate", $data_object);
            
            print json_encode(array("moduleid" => self::$m_moduleid, "content" => $doc->getHtml(), "module_data" => self::$m_data['module_data']));
            
            // Page needs recompiling
            $result = Database::Query("SELECT `pageid` FROM `" . DB_TBL_MODULE . "` WHERE `id` = '" . self::$m_moduleid . "' GROUP BY `pageid`");
            if ($result->HasData()) {
                do {
                    Database::Query("UPDATE `" . DB_TBL_PAGES . "` SET `compiled` = '' WHERE `id` = '" . $result->GetValue('pageid') . "'");
                } while ($result->NextRow());
            }
        }
        
        public static function SaveModuleFragment($a_fragment, $a_owner) {
            foreach ($a_fragment as $object)
            {
                // Plugin Hook
                $data_object = new stdClass();
                $data_object->object = $object;
                $data_object->owner = $a_owner;
                $data_object->moduleid = self::$m_moduleid;
                ObjMgr::GetPluginMgr()->ExecuteHook("On_Editor_SaveModuleFragmentObject", $data_object);
                
                /*$name = Database::Escape($object['name']);
                
                switch ($object['type'])
                {
                    case 'itr':
                        Database::Query("INSERT INTO `" . DB_TBL_DATA . "` (`type`, `name`, `owner`, `moduleid`) VALUES ('itr', '" . $object['name'] . "', '" . $a_parent . "', '" . self::$m_moduleid . "')");
                        $id = Database::GetLastIncrId();
                        self::SaveModuleFragment($object['data'], -($id));
                        break;
                    case 'string':
                        Database::Query("INSERT INTO `" . DB_TBL_DATA . "` (`type`, `name`, `owner`, `moduleid`) VALUES ('string', '" . $object['name'] . "', '" . $a_parent . "', '" . self::$m_moduleid . "')");
                        $id = Database::GetLastIncrId();
                        // Iterate over locales
                        foreach ($object['value'] as $locale=>$string) {
                            $string = Database::Escape($string);
                            Database::Query("INSERT INTO `" . DB_TBL_STRINGS . "` (`id`, `moduleid`, `locale`, `string`) VALUES ('" . $id . "', '" . self::$m_moduleid . "', '" . $locale . "', '" . $string . "')");
                        }
                        break;
                    case 'link':
                        Database::Query("INSERT INTO `" . DB_TBL_DATA . "` (`type`, `name`, `owner`, `moduleid`) VALUES ('string', '" . $object['name'] . "', '" . $a_parent . "', '" . self::$m_moduleid . "')");
                        $id = Database::GetLastIncrId();
                        $url = Database::Escape($object['link_url']);
                        // Iterate over locales
                        foreach ($object['link_title'] as $locale=>$string) {
                            $string = Database::Escape($string);
                            Database::Query("INSERT INTO `" . DB_TBL_STRINGS . "` (`id`, `moduleid`, `locale`, `string`, `string2`) VALUES ('" . $id . "', '" . self::$m_moduleid . "', '" . $locale . "', '" . $url . "', '" . $string . "')");
                        }
                        break;
					case 'richtext':
						Database::Query("INSERT INTO `" . DB_TBL_DATA . "` (`type`, `name`, `owner`, `moduleid`) VALUES ('string', '" . $object['name'] . "', '" . $a_parent . "', '" . self::$m_moduleid . "')");
                        $id = Database::GetLastIncrId();
                        // Iterate over locales
                        foreach ($object['value'] as $locale=>$string) {
                            $string = str_replace('<br>', '<br></br>', $string);
                            $string = Database::Escape($string);
                            Database::Query("INSERT INTO `" . DB_TBL_STRINGS . "` (`id`, `moduleid`, `locale`, `string`) VALUES ('" . $id . "', '" . self::$m_moduleid . "', '" . $locale . "', '" . $string . "')");
                        }
                        break;
                    case 'img':
                        Database::Query("INSERT INTO `" . DB_TBL_DATA . "` (`type`, `name`, `owner`, `moduleid`) VALUES ('string', '" . $object['name'] . "', '" . $a_parent . "', '" . self::$m_moduleid . "')");
                        $id = Database::GetLastIncrId();
                        $img_src = Database::Escape($object['img_src']);
                        Database::Query("INSERT INTO `" . DB_TBL_STRINGS . "` (`id`, `moduleid`, `string`) VALUES ('" . $id . "', '" . self::$m_moduleid . "', '" . $img_src . "')");
                        break;
                }*/
            }
        }
        
        public static function GetPage($a_id) {
            self::$m_pageid = $a_id;
            
            $result = Database::Query("SELECT * FROM `" . DB_TBL_PAGES . "` WHERE `id` = '" . Database::Escape($a_id) . "'");
            
            if (!$result->HasData())
                die("Page with id #" . $a_id . " not found!");
            
            Content::$m_pagename = unserialize($result->GetValue('name'));
            
            $compiler = new Compiler();
            $doc = $compiler->CompilePage(self::$m_pageid, COMPILER_MODE_EDITOR);
            
            // Plugin Hook
            $data_object = new stdClass();
            $data_object->doc = $doc;
            ObjMgr::GetPluginMgr()->ExecuteHook("On_PrepareTemplate", $data_object);
            
            //Content::ProcessStrings($doc);
            
            // Add neccessary data
            $locale_list = array();
            foreach (Locales::$m_locales as $locale)
            {
                $loc_data = array();
                $loc_data['name'] = $locale;
                $loc_data['ico'] = Locales::GetConstString('ICO', $locale);
                $locale_list[] = $loc_data;
            }
			
            self::AddData(DATA_LOCALES, array('default' => Locales::$m_locale, 'list' => $locale_list));
            self::AddData(DATA_STRINGS, Locales::$m_const_strings[Locales::$m_locale]);
            self::GeneratePageData();
			self::GenerateModulesData();
            
            self::InsertHeadContent($doc);
            self::GenerateToolBar($doc);
            
            // Title
            Content::AddTitle($doc, Locales::GetConstString("PAGE_TITLE", NULL, Content::$m_pagename[Locales::$m_locale]));
            
            return $doc->getHtml();
        }
		
		public static function GetModule($a_id) {
			$id = Database::Escape($a_id);
			$module = new Module($id);
			
			$doc = $module->Build();
			
			// Plugin Hook
            $data_object = new stdClass();
            $data_object->doc = $doc;
            ObjMgr::GetPluginMgr()->ExecuteHook("On_PrepareTemplate", $data_object);
            
            $data = array();
            $data['html'] = $doc->getHtml();
            $data['module_data'] = self::$m_data['module_data'];
			
			return json_encode($data);
		}
        
        public static function AddData($a_type, $a_data)
        {
            switch ($a_type)
            {
                case DATA_CONTAINER:
                    self::$m_data['containers'][] = $a_data;
                    break;
                case DATA_MODULE:
                    self::$m_data['modules'][$a_data['id']] = $a_data;
                    break;
                case DATA_MODULE_DATA:
                    self::$m_data['module_data'][$a_data['ownerid']][] = $a_data;
                    break;
                case DATA_PAGE:
                    self::$m_data['page'] = $a_data;
                    break;
                case DATA_LOCALES:
                    self::$m_data['locales'] = $a_data;
                    break;
                case DATA_STRINGS:
                    self::$m_data['strings'] = $a_data;
                    break;
				case DATA_TEMPLATES:
					self::$m_data['templates'] = $a_data;
					break;
                case DATA_JAVASCRIPT:
                    self::$m_extra_head .= '<script type="text/javascript" src="' . $a_data . '"></script>';
                    break;
                default:
                    die('Unknown data type passed to Editor::AddData');
            }
        }
        
        public static function GenerateToolBar($a_doc) {
            $toolbar_html = '<div id="editor-toolbar-container">
                                <div id="editor-toolbar" class="ui-state-default ui-corner-bottom">
                                    <div id="editor-toolbar-content">
                                        <div id="editor-toolbar-actions" class="ui-state-default ui-corner-all"></div><br/>
                                        <div id="editor-toolbar-pages" class="ui-state-default ui-corner-all"></div><br/>
                                        <div id="editor-toolbar-modules" class="ui-state-default ui-corner-all">
                                            <div id="editor-toolbar-modules-content">
                                            </div>
                                        </div>
                                    </div>
                                    <div id="editor-toolbar-tempeklis" class="ui-state-default ui-corner-bottom"><span class="ui-icon ui-icon-wrench"></span></div>
                                </div>
                            </div>';
            
            $bodys = $a_doc->getElementsByTag('CMS_BODY');
            $body = $bodys[0];
            
            // Add
            $body->addChild(new Template_TextNode($toolbar_html));
        }
        
        public static function LoadJS($a_file) {
            if (!file_exists($a_file))
                die('Editor::LoadJS(): File "' . $a_file . '" not found');
            
            self::$m_extra_head .= '<script type="text/javascript" src="' . GetRelativePath($a_file) . '"></script>';
        }
        
        public static function LoadCSS($a_file) {
            if (!file_exists($a_file))
                die('Editor::LoadCSS(): File "' . $a_file . '" not found');
            
            self::$m_extra_head .= '<link rel="stylesheet" type="text/css" href="' . GetRelativePath($a_file) . '"/>';
        }
        
        public static function InsertHeadContent($a_doc) {
            // Get <HEAD> tag
            $heads = $a_doc->getElementsByTag('CMS_HEAD');
            $head = $heads[0];
            
            $head_html = '  <link rel="stylesheet" type="text/css" href="_cms/css/editor.css" media="all" />
                            <link rel="stylesheet" type="text/css" href="_cms/css/ui-lightness/jquery-ui-1.8.21.custom.css" />
                            <link rel="stylesheet" type="text/css" href="_cms/css/tipsy.css" />
                            <script type="text/javascript" src="_cms/js/jquery-1.7.2.min.js"></script>
                            <script type="text/javascript" src="_cms/js/jquery-ui-1.8.21.custom.min.js"></script>
                            <script type="text/javascript" src="_cms/js/jquery.jsrender.min.js"></script>
                            <script type="text/javascript" src="_cms/js/jquery.tipsy.js"></script>
                            <script type="text/javascript" src="_cms/js/editor.js"></script>
                            <link rel="stylesheet" type="text/css" href="_cms/js/cleditor/jquery.cleditor.css" />
                            <script type="text/javascript" src="_cms/js/cleditor/jquery.cleditor.js"></script>
                            <script type="text/javascript">var editorData = JSON.parse(\'' . json_encode(self::$m_data) . '\');</script>
                            ' . self::$m_extra_head;
            
            // Add
            $head->addChild(new Template_TextNode($head_html));
        }
    }
?>