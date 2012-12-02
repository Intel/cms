<?php
    class Plugin_cms_langbar {
        function On_Compiler_BuiltTemplate($a_data) {
            // Get Template_Document from event data
            $doc = $a_data->doc;
            
            // Find body container
            $nodes = $doc->getElementsByTag('CMS_BODY');
            
            // There is only one body node (unless someone messed with templates) so get first element
            $node = $nodes[0];
            
            // Iterate over all locales
            $langs = '';
            foreach(Locales::$m_locales as $locale) {
                $langs .= '<a style="margin-left: 5px;" href="?locale=' . $locale . '"><img src="' . Locales::GetConstString('ICO', $locale) . '"/></a>';
            }
            
            // Add container div
            $langbar = '<div style="position: absolute; right: 0; top: 0; padding: 5px; z-index: 10000;">' . $langs . '</div>';
            
            // Replace
            $node->addChild(new Template_TextNode($langbar));
        }
    }
?>