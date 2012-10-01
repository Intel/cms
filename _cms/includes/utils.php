<?
    function BuildString($a_format, $a_vars)
    {
        $count = 1;
        foreach($a_vars as $var) {
            $a_format = str_replace("%s" . $count, $var, $a_format);
            $count++;
        }
        return $a_format;
    }

    function find_all_files($dir)
    {
        $root = scandir($dir);
        $result = array();
        foreach($root as $value)
        {
            if ($value == '.' || $value == '..')
                continue;
            
            if (is_file("$dir/$value"))
                $result[]= $value;
        }
        return $result;
    }
	
	function find_all_dirs($dir)
    {
        $root = scandir($dir);
        $result = array();
        foreach($root as $value)
        {
            if ($value == '.' || $value == '..')
                continue;
            
            if (!is_file("$dir/$value"))
                $result[]= $value;
        }
        return $result;
    }
    
    function GetRelativePath($path)
    {
        $npath = str_replace('\\', '/', $path);
        return str_replace(dirname($_SERVER['SCRIPT_FILENAME']) . '/', '', $npath);
    } 
    /*function SaveXML($a_doc)
    {
        $xml = $a_doc->saveXML($a_doc, LIBXML_NOEMPTYTAG);
        $xml = str_replace('<br></br>', '<br/>', $xml);
            
        // Hack to remove xml header
        return substr($xml, strpos($xml, '?>') + 2);
    }*/
    
    function GetContentAsString($node) {   
        $st = "";
        foreach ($node->childNodes as $cnode)
            if ($cnode->nodeType==XML_TEXT_NODE)
                $st .= $cnode->nodeValue;
            else if ($cnode->nodeType==XML_ELEMENT_NODE) {
                $st .= "<" . $cnode->nodeName;
                if ($attribnodes=$cnode->attributes) {
                    $st .= " ";
                    foreach ($attribnodes as $anode)
                        $st .= $anode->nodeName . "=\"" . htmlentities($anode->nodeValue) . "\" ";
                }   
                $nodeText = GetContentAsString($cnode);
                if (empty($nodeText) && !$attribnodes)
                    $st .= " />";        // unary
                else
                    $st .= ">" . $nodeText . "</" . $cnode->nodeName . ">";
            }
        return $st;
    }
    
    function SToMs($sec) {
        return round($sec * 1000, 3);
    }
    
    class StopWatch
    {
        static $start_time;
        static $total_time = 0;
        
        function ResetTotal() {
            self::$total_time = 0;
        }
        
        function GetTotal() {
            return self::$total_time;
        }
        
        function Start() {
            self::$start_time = microtime(true);
        }
        
        function Stop() {
            self::$total_time += SToMs(microtime(true) - self::$start_time);
            return SToMs(microtime(true) - self::$start_time);
        }
    }
    
    function get_execution_time($reset = false)
    {
        static $microtime_start = null;
        return (($microtime_start === null) || $reset) ? $microtime_start = microtime(true) : microtime(true) - $microtime_start;
    }
    
    function DOMAttributesToArray($a_attributes) {
        $attr_array = array();
        for ($itr = 0; $itr < $a_attributes->length; $itr++) {
            $attr = $a_attributes->item($itr);
            $attr_array[$attr->nodeName] = $attr->nodeValue;
        }
        return $attr_array;
    }
    
    // Cookies
    
    function UGetCookie($a_cookie)
    {
        return (isset($_COOKIE['cms_' . $a_cookie]) ? $_COOKIE['cms_' . $a_cookie] : NULL);
    }
    
    function USetCookie($a_cookie, $a_val, $a_time = NULL)
    {
        setcookie('cms_' . $a_cookie, $a_val, $a_time);
    }
    
    function UDelCookie($a_cookie)
    {
        setcookie('cms_' . $a_cookie, "", time() - 60*60*24);
    }
?>