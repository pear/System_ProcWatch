<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: ProcWatch                                          |
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
* Requires System::ProcWatch::Parser
*/
require_once('System/ProcWatch/Parser.php');

/**
* Constants
*/
define('PHP_PROCWATCH_PRESENCE',        100);
define('PHP_PROCWATCH_PRESENCE_MIN',    101);
define('PHP_PROCWATCH_PRESENCE_MAX',    102);
define('PHP_PROCWATCH_IS',              103);
define('PHP_PROCWATCH_ISNOT',           104);
define('PHP_PROCWATCH_MIN',             105);
define('PHP_PROCWATCH_MAX',             106);
define('PHP_PROCWATCH_SUM',             107);

/** 
* System_ProcWatch
* 
* Monitor processes
* 
* Usage:
* <code>
* $cf = System_ProcWatch_Config::fromXmlFile('/etc/procwatch.xml');
* $pw = &new System_ProcWatch($cf);
* $pw->run();
* </code>
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_ProcWatch
* @category     System
* 
* @version      $Revision$
* @access       public
*/
class System_ProcWatch
{
    /**
    * Parser
    *
    * @access   public
    * @var      object System_ProcWatch_Parser
    */
    var $parser = null;
    
    /**
    * Patterns
    *
    * @access   private
    * @var      array
    */
    var $_patt = array();
    
    /**
    * Conditions
    *
    * @access   private
    * @var      array
    */
    var $_cond = array();
    
    /**
    * Executes
    *
    * @access   private
    * @var      array
    */
    var $_exec = array();
    
    /**
    * Selected Processes
    *
    * @access   private
    * @var      array
    */
    var $_procs = array();
    
	/**
    * Constructor
    *
    * @access   protected
    * @return   object  System_ProcWatch
    * @param    array   $config     config array from System_ProcWatch_Config
    */
    function System_ProcWatch($config)
    {
        $this->__construct($config);
    }
    
    /**
    * Constructor (ZE2)
    *
    * @access   protected
    * @return   object      System_ProcWatch
    * @param    string      $config         path to configuration file
    */
    function __construct($config)
    {
        $this->parser = &new System_ProcWatch_Parser;
        $this->setConfig($config);
    }
    
    /**
    * Run
    *
    * @access   public
    * @return   void
    * @param    string  $ps_args    ps' args
    */
    function run($ps_args = 'aux')
    {
        // get actual process' data
        $this->_procs = array();
        $procs = &$this->parser->getParsedData($ps_args, true);
        
        // fetch needed data
        foreach ($this->_jobs as $job) {
        
            // get the pattern to search for
            $search = array_shift(array_keys($this->_patt[$job]));
            $pattern= $this->_patt[$job][$search];
            
            foreach ($procs as $p) {
            
                // search for the line we need
                if (!preg_match($pattern, @$p[$search])) {
                    continue;
                }
                
                // save the data
                $this->_procs[$job][] = $p;
            }

            // check for presence
            if (isset($this->_cond[$job]['presence'])) {
                $this->_handlePresence($job);
            }
            
            // check for attribute
            if (isset($this->_cond[$job]['attr'])) {
                $this->_handleAttr($job);
            }
        }
    }
    
    /**
    * Daemon mode
    *
    * @access   public
    * @return   void
    * @param    int     $interval   seconds to sleep
    * @param    string  $ps_args    ps' arguments
    */
    function daemon($interval, $ps_args = 'aux')
    {
        while(true) {
            $this->run($ps_args);
            sleep($interval);
        }
    }
    
    /**
    * Handle presence
    *
    * @access   private
    * @return   void
    * @param    string  $job
    */
    function _handlePresence($job)
    {
        $presence   = @count($this->_procs[$job]);
        $condition  = $this->_cond[$job]['presence'];
        list($name) = array_keys($this->_patt[$job]);

        if (!isset($condition['max']) && !isset($condition['min'])) {

            $this->_execute(
                $job, 
                $presence, 
                PHP_PROCWATCH_PRESENCE, 
                array('name' => $name)
            );

        } else {

            if (isset($condition['max']) && ($condition['max'] < $presence)) {
                $this->_execute(
                    $job, 
                    $presence, 
                    PHP_PROCWATCH_PRESENCE_MAX, 
                    array('name' => $name, 'max' => $condition['max'])
                );
            }
            
            if (isset($condition['min']) && ($condition['min'] > $presence)) {
                $this->_execute(
                    $job, 
                    $presence, 
                    PHP_PROCWATCH_PRESENCE_MIN,
                    array('name' => $name, 'min' => $condition['min'])
                );
            }
        }
        
    }
    
    /**
    * Handle attributes
    *
    * @access   private
    * @return   void
    */
    function _handleAttr($job)
    {
        $name = array_shift(array_keys($this->_cond[$job]['attr']));
        $attr = $this->_cond[$job]['attr'][$name];
        $proc = isset($this->_procs[$job]) ? $this->_procs[$job] : array();

        $attr['name'] = $name;

        // SUM
        if (isset($attr['sum'])) {
            $sum = 0.0;
            foreach ($proc as $p) {
                $sum  += @$p[$name];
            }
            if ($sum > $attr['sum']) {
                $this->_execute($job, $sum, PHP_PROCWATCH_SUM, $attr);
            }
        
        } else {

            $sum = 0;
            
            // MAX
            if (isset($attr['max'])) {
                $const = PHP_PROCWATCH_MAX;
                foreach ($proc as $p) {
                    if ($p[$name] > $attr['max']) {
                        ++$sum;
                    }
                }
                
            // MIN
            } elseif (isset($attr['min'])) {
                $const = PHP_PROCWATCH_MIN;
                foreach ($proc as $p) {
                    if ($p[$name] < $attr['min']) {
                        ++$sum;
                    }
                }
            
            // IS
            } elseif (isset($attr['is'])) {
                $const = PHP_PROCWATCH_IS;
                foreach ($proc as $p) {
                    if ($p[$name] == $attr['is']) {
                        ++$sum;
                    }
                }
            
            // ISNOT
            } elseif (isset($attr['isnot'])) {
                $const = PHP_PROCWATCH_ISNOT;
                foreach ($proc as $p) {
                    if ($p[$name] != $attr['isnot']) {
                        ++$sum;
                    }
                }
            }
            
            if ($sum) {
                $this->_execute($job, $sum, $const, $attr);
            }
        }
    }
    
    /**
    * Execute
    *
    * @access   private
    * @return   void
    * @param    string  $job
    * @param    mixed   $sum
    * @param    int     $mode
    * @param    mixed   $info
    */
    function _execute($job, $sum, $mode, $info = null)
    {
        $event_msg  = $this->_getMsg($job, $sum, $mode, $info);
        $event_pids = $this->_getPids($job);
        $shell_exec = @$this->_exec[$job]['shell'];
        $php_exec   = @$this->_exec[$job]['php'];
        
        
        if (is_array($shell_exec)) {
            foreach ($shell_exec as $e) {
                shell_exec(
                    str_replace(
                        '$pids',
                        $event_pids,
                        str_replace('$msg', $event_msg, $e)
                    )
                );
            }
        }
        
        if (is_array($php_exec)) {
            $procs = 'unserialize(\'' . serialize(@$this->_procs[$job]) . '\'';
            foreach ($php_exec as $e) {
                eval(
                    str_replace(
                        '$procs',
                        $procs,
                        str_replace(
                            '$pids', 
                            $event_pids, 
                            str_replace('$msg', $event_msg, $e)
                        )
                    )
                );
            }
        }
    }
    
    /**
    * Get processes' PIDs of a certain job
    *
    * @access   public
    * @return   string
    * @param    string  $job
    */
    function _getPids($job)
    {
        $str = '';
        if (isset($this->_procs[$job])) {
            foreach ($this->_procs[$job] as $proc) {
                $str .= $proc['pid'] . ', ';
            }
        }
        return '\'(' . trim($str, ', ') . ')\'';
    }
    
    /**
    * Get alert message
    *
    * @access   private
    * @return   string
    * @param    string  $job
    * @param    int     $c
    * @param    int     $mode
    * @param    array   $a
    */
    function _getMsg($job, $sum, $mode, $a)
    {
        $w = $a['name'];
        
        switch($mode){

            case PHP_PROCWATCH_IS: 
                $is = $a['is'];
                $m  = "$sum procs where $w is $is";
                break;

            case PHP_PROCWATCH_ISNOT: 
                $isnot  = $a['isnot'];
                $m      = "$sum procs where $w is not $isnot";
                break;

            case PHP_PROCWATCH_MAX: 
                $max = $a['max'];
                $m   = "$sum procs where $w exceeds $max";
                break;

            case PHP_PROCWATCH_MIN: 
                $min = $a['min'];
                $m   = "$sum procs where $w under-runs $min";
                break;

            case PHP_PROCWATCH_PRESENCE: 
                $match  = $this->_patt[$job][$w];
                $m      = "$sum procs where $w matches $match";
                break;
                
            case PHP_PROCWATCH_PRESENCE_MIN: 
                $min    = $a['min'];
                $match  = $this->_patt[$job][$w];
                $m      = "$sum (min $min) procs where $w matches $match";
                break;

            case PHP_PROCWATCH_PRESENCE_MAX: 
                $max    = $a['max'];
                $match  = $this->_patt[$job][$w];
                $m      = "$sum (max $max) procs where $w matches $match";
                break;
                
            case PHP_PROCWATCH_SUM: 
                $s  = $a['sum'];
                $c  = count($this->_procs[$job]);
                $m  = "$c procs which sum of $w ($sum) exceeds $s";
                break;
        }

        return '\'' . date('r') . ' - '. $job . ': Found ' . 
                str_replace('\'', '\\\'', $m) . '\'';
    }
    
    
    /**
    * Set configuration
    *
    * @access   public
    * @return   void
    * @param    array   $config     config array from System_ProcWatch_Config
    */
    function setConfig($config)
    {
        $this->_jobs = array();
        $this->addConfig($config);
    }
    
    /**
    * Add configuration
    *
    * @access   public
    * @return   void
    * @param    array   $config     config array from System_ProcWatch_Config
    */
    function addConfig($config)
    {
        foreach ($config as $job => $arrays) {
            $this->_jobs[]      = $job;
            $this->_patt[$job]  = $arrays['pattern'];
            $this->_cond[$job]  = $arrays['condition'];
            $this->_exec[$job]  = $arrays['execute'];
        }
    }
}
?>