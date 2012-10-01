<?
	// #######################
	// Content Builder Class
	// #######################

	if (!FAKE)
		exit;
	
	class Content {
        public static $m_pagename;
        
        public static function GetDefaultPageId() {
            $result = Database::Query("SELECT `id` FROM `" . DB_TBL_PAGES . "` WHERE `default` = '1'");
            
            if (!$result->HasData())
                die('No default page defined!');
            
            return $result->GetValue('id');
        }
        
        /*public static function GetPageId($a_name) {
            $name = mysql_real_escape_string($a_name);
            $result = Database::Query("SELECT `id` FROM `" . DB_TBL_PAGES . "` WHERE `name` = '" . $name . "'");
            
            if (!$result->HasData())
                die('Unknown page id');
            
            return $result->GetValue('id');
        }*/
        
        public static function GetPage($a_id) {
            $id = mysql_real_escape_string($a_id);
            $result = Database::Query("SELECT * FROM `" . DB_TBL_PAGES . "` WHERE `id` = '" . $id . "'");
            
            if (!$result->HasData())
                die('Unknown page id');
            
            self::$m_pagename = unserialize($result->GetValue('name'));
            $doc = unserialize($result->GetValue('compiled'));
            
            if (!$doc) {
                $compiler = new Compiler();
                $doc = $compiler->CompilePage($id);
            }
            
            // Plugin Hook
            $data_object = new stdClass();
            $data_object->doc = $doc;
            ObjMgr::GetPluginMgr()->ExecuteHook("On_PrepareTemplate", $data_object);
            
            // Title
            Content::AddTitle($doc, Locales::GetConstString("PAGE_TITLE", NULL, self::$m_pagename[Locales::$m_locale]));
            
            return $doc->getHtml();
        }
        
        public static function AddTitle($a_doc, $a_title) {
            $head = $a_doc->getElementsByTag("CMS_HEAD", TEMPLATE_SEARCHMODE_FIRST);
            $title = new Template_TextNode("<title>" . $a_title . "</title>");
            $head->addChild($title);
        }
        
        /*public static function ProcessStrings($a_node) {
            
            // Load all strings
            $strings = $a_node->getElementsByTagName('CMS_STRING');
            
            while ($string = $strings->item(0)) {
                self::ProcessString($string);
            }
        }
        
        public static function ProcessString($a_node) {
            // Get String type
            $type = $a_node->getAttribute("type");
            $name = $a_node->getAttribute("name");
            $title = Locales::getStringOrJSONLocale($a_node->getAttribute("title"));
            $tooltip = Locales::getStringOrJSONLocale($a_node->getAttribute("tooltip"));
            $converted_string;
            $locale_strings = array();
            
            switch ($type)
            {
                case "const":
                    // Get string from locales
                    $converted_string = Locales::GetConstString($name);
                    break;
                case "php":
                    $php_pagename = Content::$m_pagename;
                    $converted_string = "{${$name}}";
                    break;
                case "string":
                    // Get string from db locales
                    $string_id = $a_node->attributes->getNamedItem("id")->nodeValue;
                    $data = Locales::GetDBData($string_id);
                    $converted_string = $data['string'];
                    
                    // Load all langs
                    if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                        foreach (Locales::$m_locales as $loc) {
                            $data = Locales::GetDBData($string_id, $loc);
                            $locale_strings[$loc] = $data['string'];
                        }
                    }
                    break;
                case "link":
                    // 'string' - link, 'string2' - name
                    $link_id = $a_node->attributes->getNamedItem("id")->nodeValue;
                    $data = Locales::GetDBData($link_id);
                    $link_url = $data['string'];
                    $link_title = $data['string2'];
                    $attr = ($a_node->hasAttribute("attr") ? html_entity_decode($a_node->getAttribute("attr")) : '');
                    $converted_string = "<a href=\"" . $link_url . "\" " . $attr . ">" . $link_title . "</a>";
                    
                    // Load all langs
                    if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                        foreach (Locales::$m_locales as $loc) {
                            $data = Locales::GetDBData($link_id, $loc);
                            $locale_strings[$loc] = $data['string2'];
                        }
                    }
                    break;
				case "richtext":
					// Get string from db locales
                    $string_id = $a_node->attributes->getNamedItem("id")->nodeValue;
                    $data = Locales::GetDBData($string_id);
                    $converted_string = $data['string'];
                    
                    // Load all langs
                    if (Compiler::$Mode == COMPILER_MODE_EDITOR) {
                        foreach (Locales::$m_locales as $loc) {
                            $data = Locales::GetDBData($string_id, $loc);
                            $locale_strings[$loc] = $data['string'];
                        }
                    }
					break;
                case "img":
                    // Get img url
                    $string_id = $a_node->attributes->getNamedItem("id")->nodeValue;
                    $data = Locales::GetDBData($string_id);
                    $attr = ($a_node->hasAttribute("attr") ? html_entity_decode($a_node->getAttribute("attr")) : '');
					$img_url = STATIC_URL . "?hash=" . $data['string'] . "&w=" . $a_node->getAttribute("width") . "&h=" . $a_node->getAttribute("height");
                    $converted_string = '<img src="' . htmlentities($img_url) . '" data-hash="' . $data['string'] . '" ' . $attr . ' />';
                    break;
                default:
                    die("Content::ProcessString: Unknown string type '" . $type . "' for string '" . $a_node->getAttribute("name") . "'");
            }
            
            if (Compiler::$Mode == COMPILER_MODE_EDITOR && $type != "const" && $type != "php") {
                $compiled_attr = '';
                $attributes = $a_node->attributes;
                if(!is_null($attributes)) {
                    foreach ($attributes as $attr) {
                        $compiled_attr .= 'data-' . $attr->nodeName . '="' . htmlspecialchars(Locales::getStringOrJSONLocale($attr->nodeValue)) . '" ';
                    }
                }
                
                $converted_string = "<var " . $compiled_attr . " data-locales=\"" . htmlentities(htmlentities(json_encode($locale_strings), ENT_QUOTES)) . "\">" . $converted_string . "</var>";
            }
            
            // Create DOMNode
            $fragment = $a_node->ownerDocument->createDocumentFragment();
            $fragment->appendXML($converted_string);

            // Replace CMS tag with string
            $parent = $a_node->parentNode;
            $parent->replaceChild($fragment, $a_node);
        }*/
    }
?>