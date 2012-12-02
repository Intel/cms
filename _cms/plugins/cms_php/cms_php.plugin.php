<?php
    class Plugin_cms_php {
        function On_Compiler_BuiltTemplate($a_data) {
            // Get Template_Document from event data
            $doc = $a_data->doc;
            
            // Find body container
            $nodes = $doc->getElementsByTag('CMS_PHP');
            
            foreach ($nodes as $node)
            {
                // We do it the hard way
                ob_start();
                eval($node->getAttribute("code"));
                $text = ob_get_contents();
                ob_end_clean();
                
                $node->replaceWith(new Template_TextNode($text));
            }
        }
    }
?>