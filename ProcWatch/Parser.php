<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: ProcWatch :: Parser                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is available at http://www.php.net/license/3_0.txt              |
// | If you did not receive a copy of the PHP license and are unable      |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003-2004 Michael Wallner <mike@iworks.at>             |
// +----------------------------------------------------------------------+
//
// $Id$

/**
* System_ProcWatch_Parser
* 
* Fetches output from `ps` and parses it into an associative array
* 
* Usage:
* <code>
* $ps = &new System_ProcWatch_Parser();
* $pd = &$ps->getParsedData();
* </code>
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_ProcWatch
* @category     System
*
* @version      $Revision$
* @access       public
*/
class System_ProcWatch_Parser
{
    /**
    * ps' args
    *
    * @access   private
    * @var      string
    */
    var $_args = 'aux';
    
    /**
    * Processes
    *
    * @access   private
    * @var      array
    */
    var $_procs = array();
    
	/**
    * Constructor
    *
    * @access   protected
    * @return   object  System_ProcWatch_Parser
    * @param    string  $ps_args    ps' args
    */
    function System_ProcWatch_Parser($ps_args = 'aux')
    {
        $this->__construct($ps_args);
    }
    
    /**
    * Constructor (ZE2)
    *
    * @access   protected
    * @return   object  System_ProcWatch_Parser
    * @param    string  $ps_args    ps' args
    */
    function __construct($ps_args = 'aux')
    {
        $this->_args = $ps_args;
    }
    
    /**
    * Fetch ps' data
    *
    * @access   public
    * @return   string  ps' output
    * @param    string  $ps_args    ps' args
    */
    function fetch($ps_args = '')
    {
        if (empty($ps_args)) {
            $ps_args = $this->_args;
        }
        return shell_exec("ps $ps_args");
    }
    
    /**
    * Parse
    *
    * @access   public
    * @return   array
    * @param    string  $data
    */
    function &parse($data)
    {
        $lines = explode("\n", trim($data));
        $heads = preg_split('/\s+/', strToLower(trim(array_shift($lines))));
        $count = count($heads);
        $procs = array();

        foreach($lines as $i => $line){
            $parts = preg_split('/\s+/', trim($line), $count);
            foreach ($heads as $j => $head) {
                $procs[$i][$head] = str_replace('"', '\"', $parts[$j]);
            }
        }

        return $procs;        
    }
    
    /**
    * Get parsed data
    *
    * @access   public
    * @return   array
    * @param    string  $ps_args    ps' arguments
    * @param    bool    $refresh    whether to refresh our data
    */
    function &getParsedData($ps_args = 'aux', $refresh = false)
    {
        if ($refresh || empty($this->_procs)) {
            $this->_procs = &$this->parse($this->fetch());
        }
        
        return $this->_procs;
    }

    /**
    * Get info about a process by its PID
    *
    * @access   public
    * @return   array
    * @param    int     $pid    the PID of the process
    */
    function getProcByPid($pid)
    {
        $procs = &$this->getParsedData();
        
        foreach ($procs as $proc) {
            if ($proc['pid'] == $pid) {
                return $proc;
            }
        }
        return array();
    }

    /**
    * Get information about processes
    *
    * @access   public
    * @return   array
    * @param    string  $pattern    PCRE to match for process
    * @param    string  $search     the ps field to search for
    */
    function getProcInfo($pattern, $search)
    {
        $procs  = &$this->getParsedData();
        $result = array();
        
        foreach ($procs as $p) {
            if (preg_match($pattern, @$p[$search])) {
                $result[] = $p;
            }
        }
        
        return $result;
    }    
}
?>