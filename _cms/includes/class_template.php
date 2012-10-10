<?
	// #######################
	// Template Compiler Class
	// #######################

	if (!FAKE)
		exit;
    
    $template_non_recursive = array('CMS_ITERATOR');
    
    // Search over all nodes
    define("TEMPLATE_SEARCHMODE_RECURSIVE",             0);
    // Searches only in children (level 1 recursion)
    define("TEMPLATE_SEARCHMODE_CHILDREN",              1);
    // Finds first occurance (returns element instead of array)
    define("TEMPLATE_SEARCHMODE_FIRST",                 2);
    // Recursive, but doesn't search in matched node children
    define("TEMPLATE_SEARCHMODE_UNMATCHED_RECURSIVE",   3);
    
    abstract class Template_Node {
        protected $parent;
        protected $root;

        public function setParent(Template_ContainerNode $parent=null)
        {
            $this->parent = $parent;

            if ($parent)
                $this->root = $parent->root();
        }

        public function parent()
        {
            return $this->parent;
        }

        public function getHtml()
        {
            return null;
        }

        public function root()
        {
            return $this->root;
        }

        public function findParentByTag($tag)
        {
            $node = $this->parent();

            while($this->parent() != null && !$node instanceof SBBCodeParser_Document)
            {
                if($node->tag() === $tag)
                    return $node;

                $node = $node->parent();
            }

            return null;
        }
    }
    
    class Template_ContainerNode extends Template_Node
    {
        protected $children = array();
        protected $guid_itr = 1;
        public $guid;
        
        public function addChild($childs, $after = null)
        {
            if (!is_array($childs))
                $childs = array($childs);
            
            foreach ($childs as $child)
                $child->setParent($this);
                
            if ($after) {
                if ($key = array_search($after, $this->children))
                    array_splice($this->children, $key+1, 0, $childs);
                else
                    die("Template_ContainerNode::addChild(): Failed to find node key");
            } else
                $this->children = array_merge($this->children, $childs);
        }

        public function replaceChild(Template_Node $what, $with)
        {
            $replace_key = array_search($what, $this->children);

            if ($replace_key === false)
                return false;

            if (is_array($with)) {
                foreach ($with as $child)
                    $child->setParent($this);
                
                array_splice($this->children, $replace_key, 1, $with);
            } else
                array_splice($this->children, $replace_key, 1, array($with));

            return true;
        }

        public function removeChild(Template_Node $child)
        {
            $key = array_search($child, $this->children);

            if ($key === false)
                return false;

            $this->children[$key]->setParent();
            unset($this->children[$key]);
            return true;
        }

        public function children()
        {
            return $this->children;
        }

        public function getHtml($include_tags = false)
        {
            $html = '';

            foreach ($this->children as $child)
                $html .= $child->getHtml($include_tags);
            
            if ($include_tags && $this instanceof Template_TagNode) {
                if ($html !== '')
                    return '[' . $this->tag() . ' ' . $this->attributesToString() . ']' . $html . '[/' . $this->tag() . ']';
                else
                    return '[' . $this->tag() . ' ' . $this->attributesToString() . '/]';
            } else
                return $html;
        }

        public function getText()
        {
            $text = '';

            foreach($this->children as $child)
                $text .= $child->getText();

            return $text;
        }
        
        public function getElementsByTag($pattern, $mode = TEMPLATE_SEARCHMODE_RECURSIVE)
        {
            $template_non_recursive = array('CMS_ITERATOR');
            $elements = array();
            
            foreach($this->children as $child) {
                if ($child instanceof Template_ContainerNode) {
                    switch ($mode) {
                        case TEMPLATE_SEARCHMODE_RECURSIVE:
                        case TEMPLATE_SEARCHMODE_FIRST:
                            $elements = array_merge($elements, $child->getElementsByTag($pattern, TEMPLATE_SEARCHMODE_RECURSIVE));
                            if ($child instanceof Template_TagNode && fnmatch($pattern, $child->tag()))
                                $elements[] = $child;
                            break;
                        case TEMPLATE_SEARCHMODE_CHILDREN:
                            if ($child instanceof Template_TagNode && fnmatch($pattern, $child->tag()))
                                $elements[] = $child;
                            break;
                        case TEMPLATE_SEARCHMODE_UNMATCHED_RECURSIVE:
                            if ($child instanceof Template_TagNode && fnmatch($pattern, $child->tag())) {
                                $elements[] = $child;
                                if (!in_array($child->tag(), $template_non_recursive)) 
                                    $elements = array_merge($elements, $child->getElementsByTag($pattern, $mode));
                            }
                    }
                }
            }
            
            if ($mode == TEMPLATE_SEARCHMODE_FIRST)
                return $elements[0];
            else
                return $elements;
        }
        
        public function getElementsByFunc($func, $args)
        {
            $elements = array();
            
            foreach($this->children as $child) {
                if ($func($child, $args))
                    $elements[] = $child;
                
                if ($child instanceof Template_ContainerNode)
                    $elements = array_merge($elements, $child->getElementsByFunc($func, $args));
            }
            
            return $elements;
        }
    }
    
    class Template_TextNode extends Template_Node
    {
        protected $text;

        public function __construct($text)
        {
            $this->text = $text;
        }

        public function getHtml()
        {
            return $this->text;
            //return htmlentities($this->text, ENT_QUOTES | ENT_IGNORE, "UTF-8");
        }

        public function getText()
        {
            return $this->text;
        }
    }

    class Template_TagNode extends Template_ContainerNode
    {
        protected $tag;
        protected $attribs;

        public function __construct($tag, $attribs)
        {
            $this->tag = $tag;
            $this->attribs = $attribs;
        }

        public function tag()
        {
            return $this->tag;
        }

        public function attributes()
        {
            return $this->attribs;
        }
        
        public function getAttribute($key)
        {
            return $this->attribs[$key];
        }
        
        public function hasAttribute($key)
        {
            return isset($this->attribs[$key]);
        }
        
        public function setAttribute($key, $value)
        {
            $this->attribs[$key] = $value;
        }
        
        public function attributesToString()
        {
            $str = "";
            
            foreach ($this->attribs as $index=>$attr)
                $str .= $index . '="' . htmlentities($attr, ENT_QUOTES | ENT_IGNORE, 'UTF-8') . '" ';
            
            return $str;
        }
        
        public function replaceWith($node)
        {
            $this->parent()->replaceChild($this, $node);
        }
        
        public function remove()
        {
            return $this->parent()->removeChild($this);
        }
    }
	
	class Template_Document extends Template_ContainerNode {
        public $current_tag;
        public $template_file;
        
        public function __construct($a_data = "") {
            // If it's file get content
			if (file_exists(COMPILER_TEMPLATES_DIR . '/' . $a_data)) {
                $this->template_file = $a_data;
                $a_data = file_get_contents(COMPILER_TEMPLATES_DIR . '/' . $a_data);
            }
            
            $this->root = $this;
            $this->parse($a_data);
		}
        
        public function parse($str) {
            //$str = preg_replace('/[\r\n|\r]/', "\n", $a_str);
            $len = strlen($str);
            $tag_open = false;
            $tag_text = '';
            $tag = '';
            
            // set the document as the current tag.
            $this->current_tag = $this;
            
            for ($itr = 0; $itr < $len; ++$itr)
            {
                // Escape character
                if ($str[$itr] === '@') {
                    ++$itr;
                    continue;
                }
                
                if ($str[$itr] === '[' && !$tag_open) {
                    $tag_open = true;
                    $tag = '';
                } else if ($str[$itr] === ']' && $tag_open) {
                    if ($tag !== '') {
                        $bits = preg_split('/([ =])/', trim($tag), 2, PREG_SPLIT_DELIM_CAPTURE);
                        $tag_attrs = (isset($bits[2]) ? $bits[1] . $bits[2] : '');
                        $tag_closing = ($bits[0][0] === '/');
                        $tag_name = ($bits[0][0] === '/' ? substr($bits[0], 1) : $bits[0]);
                        $self_closing = ($str[$itr-1] === '/');

                        $this->tagText($tag_text);
                        $tag_text = '';

                        if ($tag_closing)
                            $this->tagClose($tag_name);
                        else
                            $this->tagOpen($tag_name, $this->parseAttribs($tag_attrs), $self_closing);
                    }
                    else
                        $tag_text .= '[]';

                    $tag_open = false;
                    $tag = '';
                } else if ($tag_open)
                    $tag .= $str[$itr];
                else
                    $tag_text .= $str[$itr];
            }
            
            $this->tagText($tag_text);
        }
        
        private function tagOpen($tag, $attrs, $self_closing = false) {
            $node = new Template_TagNode($tag, $attrs);
            
            $this->current_tag->addChild($node);

            if(!$self_closing)
                $this->current_tag = $node;
            
            return true;
        }
        
        private function tagClose($tag) {
            if ($this->current_tag instanceof Template_Document)
                return false;
            else
                $this->current_tag = $this->current_tag->parent();
            
            return true;
        }
        
        private function tagText($text) {
            if ($text !== '')
                $this->current_tag->addChild(new Template_TextNode($text));
        }
        
        private function parseAttribs($attribs) {
            $ret = array();
            $attribs = trim($attribs);

            if($attribs == '')
                return $ret;

            preg_match_all('/([a-z0-9_]+)\s*=\s*([\"\'])(.*?)\2/is', $attribs, $matches, PREG_SET_ORDER);

            foreach($matches as $match)
                $ret[$match[1]] = $match[3];

            return $ret;
        }
    }
?>