<?php
/**
 * Moons over my YAML
 *
 * Moons over my YAML is a really fast YAML parser for PHP4 and up.
 * It is a modified version of the gorgeous new Horde YAML parser by
 * Chris Wanstrath, Chuck Hagenbuch and Mike Naberezny.
 *
 * @author   Brian Hendrickson (brian@structal.net)
 * @author   Chris Wanstrath (chris@ozmm.org)
 * @author   Chuck Hagenbuch (chuck@horde.org)
 * @author   Mike Naberezny (mike@maintainable.com)
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @package  Moons_over_my_YAML
 */

/**
 * Horde YAML parser.
 *
 * This class can be used to read a YAML file and convert its contents
 * into a PHP array. The native PHP parser supports a limited
 * subsection of the YAML spec, but if the syck extension is present,
 * that will be used for parsing.
 *
 * @package  Moons_over_my_YAML
 */
class Horde_Yaml_Node
{
    /**
     * @var string
     */
    var $parent;

    /**
     */
    var $id;

    /**
     * @var mixed
     */
    var $data;

    /**
     * @var integer
     */
    var $indent;

    /**
     * @var bool
     */
    var $children = false;

    /**
     * The constructor assigns the node a unique ID.
     * @return void
     */
    function Horde_Yaml_Node($nodeId)
    {
        $this->id = $nodeId;
    }

}


class Horde_Yaml_Loader
{

    /**
     * List of nodes with references
     * @var array
     */
    var $_haveRefs = array();

    /**
     * All nodes
     * @var array
     */
    var $_allNodes = array();

    /**
     * Array of node parents
     * @var array
     */
    var $_allParent = array();

    /**
     * Last indent level
     * @var integer
     */
    var $_lastIndent = 0;

    /**
     * Last node id
     * @var integer
     */
    var $_lastNode = null;

    /**
     * Is the parser inside a block?
     * @var boolean
     */
    var $_inBlock = false;

    /**
     * @var boolean
     */
    var $_isInline = false;

    /**
     * Next node id to use
     * @var integer
     */
    var $_nodeId = 1;

    /**
     * Last line number parsed.
     * @var integer
     */
    var $_lineNumber = 0;

    /**
     * Create a new YAML parser.
     */
    function Horde_Yaml_Loader()
    {
        $base = new Horde_Yaml_Node($this->_nodeId++);
        $base->indent = 0;
        $this->_lastNode = $base->id;
    }

    /**
     * Return the PHP built from all YAML parsed so far.
     *
     * @return array PHP version of parsed YAML
     */
    function toArray()
    {
        // Here we travel through node-space and pick out references
        // (& and *).
        $this->_linkReferences();

        // Build the PHP array out of node-space.
        return $this->_buildArray();
    }

    /**
     * Parse a line of a YAML file.
     *
     * @param  string           $line  The line of YAML to parse.
     * @return Horde_Yaml_Node         YAML Node
     */
    function parse($line)
    {
        // Keep track of how many lines we've parsed for friendlier
        // error messages.
        ++$this->_lineNumber;

        $trimmed = trim($line);

        // If the line starts with a tab (instead of a space), throw a fit.
        if (preg_match('/^ *(\t) *[^\t ]/', $line)) {
            $msg = "Line {$this->_lineNumber} indent contains a tab.  "
                 . 'YAML only allows spaces for indentation.';
            echo "error 1";
                 //throw new Horde_Yaml_Exception($msg);
        }

        if (!$this->_inBlock && empty($trimmed)) {
            return;
        } elseif ($this->_inBlock && empty($trimmed)) {
            $last =& $this->_allNodes[$this->_lastNode];
            $last->data[key($last->data)] .= "\n";
        } elseif ($trimmed[0] != '#' && substr($trimmed, 0, 3) != '---') {
            // Create a new node and get its indent
            $node = new Horde_Yaml_Node($this->_nodeId++);
            $node->indent = $this->_getIndent($line);

            // Check where the node lies in the hierarchy
            if ($this->_lastIndent == $node->indent) {
                // If we're in a block, add the text to the parent's data
                if ($this->_inBlock) {
                    $parent =& $this->_allNodes[$this->_lastNode];
                    $parent->data[key($parent->data)] .= trim($line) . $this->_blockEnd;
                } else {
                    // The current node's parent is the same as the previous node's
                    if (isset($this->_allNodes[$this->_lastNode])) {
                        $node->parent = $this->_allNodes[$this->_lastNode]->parent;
                    }
                }
            } elseif ($this->_lastIndent < $node->indent) {
                if ($this->_inBlock) {
                    $parent =& $this->_allNodes[$this->_lastNode];
                    $parent->data[key($parent->data)] .= trim($line) . $this->_blockEnd;
                } elseif (!$this->_inBlock) {
                    // The current node's parent is the previous node
                    $node->parent = $this->_lastNode;

                    // If the value of the last node's data was > or |
                    // we need to start blocking i.e. taking in all
                    // lines as a text value until we drop our indent.
                    $parent =& $this->_allNodes[$node->parent];
                    $this->_allNodes[$node->parent]->children = true;
                    if (is_array($parent->data)) {
                        if (isset($parent->data[key($parent->data)])) {
                            $chk = $parent->data[key($parent->data)];
                            if ($chk === '>') {
                                $this->_inBlock = true;
                                $this->_blockEnd = ' ';
                                $parent->data[key($parent->data)] =
                                    str_replace('>','', $parent->data[key($parent->data)]);
                                $parent->data[key($parent->data)] .= trim($line) . ' ';
                                $this->_allNodes[$node->parent]->children = false;
                                $this->_lastIndent = $node->indent;
                            } elseif ($chk === '|') {
                                $this->_inBlock = true;
                                $this->_blockEnd = "\n";
                                $parent->data[key($parent->data)] =
                                    str_replace('|','', $parent->data[key($parent->data)]);
                                $parent->data[key($parent->data)] .= trim($line) . "\n";
                                $this->_allNodes[$node->parent]->children = false;
                                $this->_lastIndent = $node->indent;
                            }
                        }
                    }
                }
            } elseif ($this->_lastIndent > $node->indent) {
                // Any block we had going is dead now
                if ($this->_inBlock) {
                    $this->_inBlock = false;
                    if ($this->_blockEnd = "\n") {
                        $last =& $this->_allNodes[$this->_lastNode];
                        $last->data[key($last->data)] =
                            trim($last->data[key($last->data)]);
                    }
                }

                // We don't know the parent of the node so we have to
                // find it
                foreach ($this->_indentSort[$node->indent] as $n) {
                    if ($n->indent == $node->indent) {
                        $node->parent = $n->parent;
                    }
                }
            }

            if (!$this->_inBlock) {
                // Set these properties with information from our
                // current node
                $this->_lastIndent = $node->indent;

                // Set the last node
                $this->_lastNode = $node->id;

                // Parse the YAML line and return its data
                $node->data = $this->_parseLine($line);

                // Add the node to the master list
                $this->_allNodes[$node->id] = $node;

                // Add a reference to the parent list
                $this->_allParent[intval($node->parent)][] = $node->id;

                // Add a reference to the node in an indent array
                $this->_indentSort[$node->indent][] =& $this->_allNodes[$node->id];

                // Add a reference to the node in a References array
                // if this node has a YAML reference in it.
                $is_array = is_array($node->data);
                $key = key($node->data);
                $isset = isset($node->data[$key]);
                if ($isset) {
                    $nodeval = $node->data[$key];
                }
                if (($is_array && $isset && !is_array($nodeval))
                    && (strlen($nodeval) && ($nodeval[0] == '&' || $nodeval[0] == '*') && $nodeval[1] != ' ')) {
                    $this->_haveRefs[] =& $this->_allNodes[$node->id];
                } elseif ($is_array && $isset && is_array($nodeval)) {
                    // Incomplete reference making code. Needs to be
                    // cleaned up.
                    foreach ($node->data[$key] as $d) {
                        if (!is_array($d) && strlen($d) && (($d[0] == '&' || $d[0] == '*') && $d[1] != ' ')) {
                            $this->_haveRefs[] =& $this->_allNodes[$node->id];
                        }
                    }
                }
            }
        }
    }

    /**
     * Finds and returns the indentation of a YAML line
     *
     * @param  string  $line  A line from the YAML file
     * @return int            Indentation level
     */
    function _getIndent($line)
    {
        if (preg_match('/^\s+/', $line, $match)) {
            return strlen($match[0]);
        } else {
            return 0;
        }
    }

    /**
     * Parses YAML code and returns an array for a node
     *
     * @param  string  $line  A line from the YAML file
     * @return array
     */
    function _parseLine($line)
    {
        $array = array();

        $line = trim($line);
        if (preg_match('/^-(.*):$/', $line)) {
            // It's a mapped sequence
            $key = trim(substr(substr($line, 1), 0, -1));
            $array[$key] = '';
        } elseif ($line[0] == '-' && substr($line, 0, 3) != '---') {
            // It's a list item but not a new stream
            if (strlen($line) > 1) {
                // Set the type of the value. Int, string, etc
                $array[] = $this->_toType(trim(substr($line, 1)));
            } else {
                $array[] = array();
            }
        } elseif (preg_match('/^(.+):/', $line, $key)) {
            // It's a key/value pair most likely
            // If the key is in double quotes pull it out
            if (preg_match('/^(["\'](.*)["\'](\s)*:)/', $line, $matches)) {
                $value = trim(str_replace($matches[1], '', $line));
                $key = $matches[2];
            } else {
                // Do some guesswork as to the key and the value
                $explode = explode(':', $line);
                $key = trim(array_shift($explode));
                $value = trim(implode(':', $explode));
            }

            // Set the type of the value. Int, string, etc
            $value = $this->_toType($value);
            if (empty($key)) {
                $array[] = $value;
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Finds the type of the passed value, returns the value as the new type.
     *
     * @param  string   $value
     * @return mixed
     */
    function _toType($value)
    {
        // Used in a lot of cases.
        $lower_value = strtolower($value);

        if (preg_match('/^("(.*)"|\'(.*)\')/', $value, $matches)) {
            $value = (string)str_replace(array('\'\'', '\\\''), "'", end($matches));
            $value = str_replace('\\"', '"', $value);
        } elseif (preg_match('/^\\[(\s*)\\]$/', $value)) {
            // empty inline mapping
            $value = array();
        } elseif (preg_match('/^\\[(.+)\\]$/', $value, $matches)) {
            // Inline Sequence

            // Take out strings sequences and mappings
            $explode = $this->_inlineEscape($matches[1]);

            // Propogate value array
            $value  = array();
            foreach ($explode as $v) {
                $value[] = $this->_toType($v);
            }
        } elseif (preg_match('/^\\{(\s*)\\}$/', $value)) {
            // empty inline mapping
            $value = array();
        } elseif (strpos($value, ': ') !== false && !preg_match('/^{(.+)/', $value)) {
            // inline mapping
            $array = explode(': ', $value);
            $key = trim($array[0]);
            array_shift($array);
            $value = trim(implode(': ', $array));
            $value = $this->_toType($value);
            $value = array($key => $value);
        } elseif (preg_match("/{(.+)}$/", $value, $matches)) {
            // Inline Mapping

            // Take out strings sequences and mappings
            $explode = $this->_inlineEscape($matches[1]);

            // Propogate value array
            $array = array();
            foreach ($explode as $v) {
                $array = $array + $this->_toType($v);
            }
            $value = $array;
        } elseif ($lower_value == 'null' || $value == '' || $value == '~') {
            $value = null;
        } elseif ($lower_value == '.nan') {
            $value = NAN;
        } elseif ($lower_value == '.inf') {
            $value = INF;
        } elseif ($lower_value == '-.inf') {
            $value = -INF;
        } elseif (ctype_digit($value)) {
            $value = (int)$value;
        } elseif (in_array($lower_value,
                           array('true', 'on', '+', 'yes', 'y'))) {
            $value = true;
        } elseif (in_array($lower_value,
                           array('false', 'off', '-', 'no', 'n'))) {
            $value = false;
        } elseif (is_numeric($value)) {
            $value = (float)$value;
        } else {
            // Just a normal string, right?
            if (($pos = strpos($value, '#')) !== false) {
                $value = substr($value, 0, $pos);
            }
            $value = trim($value);
        }

        return $value;
    }

    /**
     * Used in inlines to check for more inlines or quoted strings
     *
     * @todo  There should be a cleaner way to do this.  While
     *        pure sequences seem to be nesting just fine,
     *        pure mappings and mappings with sequences inside
     *        can't go very deep.  This needs to be fixed.
     *
     * @param  string  $inline  Inline data
     * @return array
     */
    function _inlineEscape($inline)
    {
        $saved_strings = array();

        // Check for strings
        $regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
        if (preg_match_all($regex, $inline, $strings)) {
            $saved_strings = $strings[0];
            $inline = preg_replace($regex, 'YAMLString', $inline);
        }

        // Check for sequences
        if (preg_match_all('/\[(.+)\]/U', $inline, $seqs)) {
            $inline = preg_replace('/\[(.+)\]/U', 'YAMLSeq', $inline);
            $seqs = $seqs[0];
        }

        // Check for mappings
        if (preg_match_all('/{(.+)}/U', $inline, $maps)) {
            $inline = preg_replace('/{(.+)}/U', 'YAMLMap', $inline);
            $maps = $maps[0];
        }

        $explode = explode(', ', $inline);

        // Re-add the sequences
        if (!empty($seqs)) {
            $i = 0;
            foreach ($explode as $key => $value) {
                if (strpos($value, 'YAMLSeq') !== false) {
                    $explode[$key] = str_replace('YAMLSeq', $seqs[$i], $value);
                    ++$i;
                }
            }
        }

        // Re-add the mappings
        if (!empty($maps)) {
            $i = 0;
            foreach ($explode as $key => $value) {
                if (strpos($value, 'YAMLMap') !== false) {
                    $explode[$key] = str_replace('YAMLMap', $maps[$i], $value);
                    ++$i;
                }
            }
        }

        // Re-add the strings
        if (!empty($saved_strings)) {
            $i = 0;
            foreach ($explode as $key => $value) {
                while (strpos($value, 'YAMLString') !== false) {
                    $explode[$key] = preg_replace('/YAMLString/', $saved_strings[$i], $value, 1);
                    ++$i;
                    $value = $explode[$key];
                }
            }
        }

        return $explode;
    }

    /**
     * Builds the PHP array from all the YAML nodes we've gathered
     *
     * @return array
     */
    function _buildArray()
    {
        $trunk = array();
        if (!isset($this->_indentSort[0])) {
            return $trunk;
        }

        foreach ($this->_indentSort[0] as $n) {
            if (empty($n->parent)) {
                $this->_nodeArrayizeData($n);

                // Check for references and copy the needed data to complete them.
                $this->_makeReferences($n);

                // Merge our data with the big array we're building
                $trunk = $this->_array_kmerge($trunk, $n->data);
            }
        }

        return $trunk;
    }

    /**
     * Traverses node-space and sets references (& and *) accordingly
     *
     * @return bool
     */
    function _linkReferences()
    {
        if (is_array($this->_haveRefs)) {
            foreach ($this->_haveRefs as $node) {
                if (!empty($node->data)) {
                    $key = key($node->data);
                    // If it's an array, don't check.
                    if (is_array($node->data[$key])) {
                        foreach ($node->data[$key] as $k => $v) {
                            $this->_linkRef($node, $key, $k, $v);
                        }
                    } else {
                        $this->_linkRef($node, $key);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Helper for _linkReferences()
     *
     * @param  Horde_Yaml_Node  $n   Node
     * @param  string           $k   Key
     * @param  mixed            $v   Value
     * @return void
     */
    function _linkRef(&$n, $key, $k = null, $v = null)
    {
        if (empty($k) && empty($v)) {
            // Look for &refs
            if (preg_match('/^&([^ ]+)/', $n->data[$key], $matches)) {
                // Flag the node so we know it's a reference
                $this->_allNodes[$n->id]->ref = substr($matches[0], 1);
                $this->_allNodes[$n->id]->data[$key] =
                    substr($n->data[$key], strlen($matches[0]) + 1);
                // Look for *refs
            } elseif (preg_match('/^\*([^ ]+)/', $n->data[$key], $matches)) {
                $ref = substr($matches[0], 1);
                // Flag the node as having a reference
                $this->_allNodes[$n->id]->refKey = $ref;
            }
        } elseif (!empty($k) && !empty($v)) {
            if (preg_match('/^&([^ ]+)/', $v, $matches)) {
                // Flag the node so we know it's a reference
                $this->_allNodes[$n->id]->ref = substr($matches[0], 1);
                $this->_allNodes[$n->id]->data[$key][$k] =
                    substr($v, strlen($matches[0]) + 1);
                // Look for *refs
            } elseif (preg_match('/^\*([^ ]+)/', $v, $matches)) {
                $ref = substr($matches[0], 1);
                // Flag the node as having a reference
                $this->_allNodes[$n->id]->refKey = $ref;
            }
        }
    }

    /**
     * Finds the children of a node and aids in the building of the PHP array
     *
     * @param  int    $nid   The id of the node whose children we're gathering
     * @return array
     */
    function _gatherChildren($nid)
    {
        $return = array();
        $node =& $this->_allNodes[$nid];
        if (is_array ($this->_allParent[$node->id])) {
            foreach ($this->_allParent[$node->id] as $nodeZ) {
                $z =& $this->_allNodes[$nodeZ];
                // We found a child
                $this->_nodeArrayizeData($z);

                // Check for references
                $this->_makeReferences($z);

                // Merge with the big array we're returning, the big
                // array being all the data of the children of our
                // parent node
                $return = $this->_array_kmerge($return, $z->data);
            }
        }
        return $return;
    }

    /**
     * Turns a node's data and its children's data into a PHP array
     *
     * @param  array    $node  The node which you want to arrayize
     * @return boolean
     */
    function _nodeArrayizeData(&$node)
    {
        if (is_array($node->data) && $node->children == true) {
            // This node has children, so we need to find them
            $childs = $this->_gatherChildren($node->id);
            // We've gathered all our children's data and are ready to use it
            $key = key($node->data);
            $key = empty($key) ? 0 : $key;
            // If it's an array, add to it of course
            if (isset ($node->data[$key])) {
                if (is_array($node->data[$key])) {
                    $node->data[$key] = $this->_array_kmerge($node->data[$key], $childs);
                } else {
                    $node->data[$key] = $childs;
                }
            } else {
                $node->data[$key] = $childs;
            }
        } elseif (!is_array($node->data) && $node->children == true) {
            // Same as above, find the children of this node
            $childs = $this->_gatherChildren($node->id);
            $node->data = array();
            $node->data[] = $childs;
        }

        // We edited $node by reference, so just return true
        return true;
    }

    /**
     * Traverses node-space and copies references to / from this object.
     *
     * @param  Horde_Yaml_Node  $z  A node whose references we wish to make real
     * @return bool
     */
    function _makeReferences(&$z)
    {
        // It is a reference
        if (isset($z->ref)) {
            $key = key($z->data);
            // Copy the data to this object for easy retrieval later
            $this->ref[$z->ref] =& $z->data[$key];
            // It has a reference
        } elseif (isset($z->refKey)) {
            if (isset($this->ref[$z->refKey])) {
                $key = key($z->data);
                // Copy the data from this object to make the node a real reference
                $z->data[$key] =& $this->ref[$z->refKey];
            }
        }

        return true;
    }

    /**
     * Merges two arrays, maintaining numeric keys. If two numeric
     * keys clash, the second one will be appended to the resulting
     * array. If string keys clash, the last one wins.
     *
     * @param  array  $arr1
     * @param  array  $arr2
     * @return array
     */
    function _array_kmerge($arr1, $arr2)
    {
        while (list($key, $val) = each($arr2)) {
            if (isset($arr1[$key]) && is_int($key)) {
                $arr1[] = $val;
            } else {
                $arr1[$key] = $val;
            }
        }

        return $arr1;
    }

}
class Horde_Yaml
{
    /**
     * Callback used for alternate YAML loader, typically exported
     * by a faster PHP extension.  This function's first argument
     * must accept a string with YAML content.
     *
     * @var callback
     */
     var $loadfunc = 'syck_load';

    /**
     * Load a string containing YAML and parse it into a PHP array.
     * Returns an empty array on failure.
     *
     * @param  string  $yaml   String containing YAML
     * @return array           PHP array representation of YAML content
     */
    function load($yaml)
    {
        if (!is_string($yaml) || !strlen($yaml)) {
            $msg = 'YAML to parse must be a string and cannot be empty.';
            //throw new InvalidArgumentException($msg);
            echo "error 2";
        }

        //if (is_callable($this->$loadfunc)) {
        //    $array = call_user_func($this->$loadfunc, $yaml);
        //    return is_array($array) ? $array : array();
        //}

        if (strpos($yaml, "\r") !== false) {
            $yaml = str_replace(array("\r\n", "\r"), array("\n", "\n"), $yaml);
        }
        $lines = explode("\n", $yaml);
        $loader = new Horde_Yaml_Loader();

        while (list(,$line) = each($lines)) {
            $loader->parse($line);
        }

        return $loader->toArray();
    }

    /**
     * Load a file containing YAML and parse it into a PHP array.
     *
     * If the file cannot be opened, an exception is thrown.  If the
     * file is read but parsing fails, an empty array is returned.
     *
     * @param  string  $filename     Filename to load
     * @return array                 PHP array representation of YAML content
     * @throws IllegalArgumentException  If $filename is invalid
     * @throws Horde_Yaml_Exception  If the file cannot be opened.
     */
    function loadFile($filename)
    {

        return $this->loadStream($filename);
    }

    /**
     * Load YAML from a PHP stream resource.
     *
     * @param  resource  $stream     PHP stream resource
     * @return array                 PHP array representation of YAML content
     */
    function loadStream($stream)
    {

      
        //if (is_callable($this->$loadfunc)) {
        //    $array = call_user_func($this->$loadfunc, file_get_contents($stream));
        //    return is_array($array) ? $array : array();
        //}

        $loader = new Horde_Yaml_Loader();

        $handle = @fopen($stream, "r");
        if ($handle) {
            while (!feof($handle)) {
              $loader->parse(fgets($handle, 4096));
            }
            fclose($handle);
        }
        

        return $loader->toArray();
    }

}
