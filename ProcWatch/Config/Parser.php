<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: ProcWatch :: Config :: Parser                      |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is available at http://www.php.net/license/3_0.txt              |
// | If you did not receive a copy of the PHP license and are unable      |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 Michael Wallner <mike@iworks.at>                  |
// +----------------------------------------------------------------------+
//
// $Id$

/**
* Requires XML::Parser
*/
require_once('XML/Parser.php');

/** 
* System_ProcWatch_Config_Parser
* 
* Parses an XML configuration string into a configuration array.
*
* @author       Michael Wallner <mike@php.net>
* @package      System_ProcWatch
* @category     System
* 
* @version      $Revision$
* @access       protected
*/
class System_ProcWatch_Config_Parser extends XML_Parser
{
    /**
    * Parsed Configuration
    *
    * @access   private
    * @var      array
    */
    var $_conf = array();

    /**
    * Current Job
    *
    * @access   private
    * @var      reference
    */
    var $_cur = null;
    
    /**
    * Current Job Name
    *
    * @access   private
    * @var      string
    */
    var $_job = '';
    
    /**
    * Current Position
    *
    * @access   private
    * @var      reference
    */
    var $_pos = null;
    
	/**
    * Constructor
    *
    * @access   protected
    * @return   object
    */
    function System_ProcWatch_Config_Parser()
    {
        $this->__construct();
    }

    /**
    * Constructor (ZE2)
    *
    * @access   protected
    * @return   object
    */
    function __construct()
    {
        $this->XML_Parser(null, 'func', null);
    }

    /**
    * Get copy (ZE2)
    *
    * @access   public
    * @return   object
    */
    function __clone()
    {
        return $this;
    }

    /**
    * Reset
    *
    * @access   public
    * @return   void
    */
    function reset()
    {
        $this->_conf    = array();
        $this->_cur     = null;
        $this->_pos     = null;
    }
    
    /**
    * Get Configuration from XML data
    *
    * @access   public
    * @return   mixed
    * @param    string  $xml    XML configuration data
    */
    function getConf($xml)
    {
        $this->reset();

        $error = $this->parseString($xml);
        if (PEAR::isError($error)) {
            return $error;
        }
        
        return $this->_conf;
    }
    
    /**
    * CDATA handler
    *
    * @access   protected
    * @return   void
    */
    function cdataHandler($p, $d)
    {
        $cdata = trim($d);
        if (!empty($cdata) || $cdata === '0') {
            $this->_pos .= trim($d, "\r\n");
        }
    }
    
    /**
    * Watch
    *
    * @access   protected
    * @return   void
    */
    function xmltag_watch($p, $e, $a)
    {
        $job = $a['NAME'];
        
        $this->_conf[$job] = array(
            'pattern'   => array(),
            'condition' => array(),
            'execute'   => array('shell' => array(), 'php' => array())
        );

        $this->_job = $job;
        $this->_cur = &$this->_conf[$job];
    }
    
    /**
    * Pattern
    *
    * @access   protected
    * @return   void
    */
    function xmltag_pattern($p, $e, $a)
    {
        $this->_pos = &$this->_cur['pattern'][$a['MATCH']];
    }
    
    /**
    * Condition
    *
    * @access   protected
    * @return   void
    */
    function xmltag_condition($p, $e, $a)
    {
        $type = strToLower(trim($a['TYPE']));

        if ($type == 'presence') {

            $this->_cur['condition']['presence'] = array();
            $this->_cur = &$this->_cur['condition']['presence'];

        } elseif ($type == 'attr') {

            $this->_cur['condition']['attr'] = array();
            $this->_cur = &$this->_cur['condition']['attr'][$a['ATTR']];

        }
    }
    
    /**
    * _Condition
    *
    * @access   protected
    * @return   mixed
    */
    function xmltag_condition_($p, $e)
    {
        $this->_cur = &$this->_conf[$this->_job];
    }
    
    /**
    * Execute
    *
    * @access   protected
    * @return   void
    */
    function xmltag_execute($p, $e, $a)
    {
        $count = count($this->_cur['execute'][$a['TYPE']]);
        $this->_pos = &$this->_cur['execute'][$a['TYPE']][$count];
    }

    /**
    * Max
    *
    * @access   protected
    * @return   void
    */
    function xmltag_max($p, $e, $a)
    {
        $this->_pos = &$this->_cur['max'];
    }
    
    /**
    * Min
    *
    * @access   protected
    * @return   void
    */
    function xmltag_min($p, $e, $a)
    {
        $this->_pos = &$this->_cur['min'];
    }
    
    /**
    * Sum
    *
    * @access   protected
    * @return   void
    */
    function xmltag_sum($p, $e, $a)
    {
        $this->_pos = &$this->_cur['sum'];
    }
    
    /**
    * Is
    *
    * @access   protected
    * @return   void
    */
    function xmltag_is($p, $e, $a)
    {
        $this->_pos = &$this->_cur['is'];
    }
    
    /**
    * IsNot
    *
    * @access   protected
    * @return   void
    */
    function xmltag_isnot($p, $e, $a)
    {
        $this->_pos = &$this->_cur['isnot'];
    }
}
?>