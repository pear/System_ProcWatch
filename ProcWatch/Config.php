<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: ProcWatch :: Config                                |
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
* Requires PEAR.
*/
require_once('PEAR.php');

/** 
* System_ProcWatch_Config
* 
* Build a configuration array for System_ProcWatch
* 
* Usage:
* <code>
* $cf = System_ProcWatch_Config::fromXmlFile('/etc/procwatch.xml');
* $pw = &new System_ProcWatch($cf);
* </code>
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_ProcWatch
* @category     System
* 
* @version      $Revision$
* @access       public
*/
class System_ProcWatch_Config
{
    /**
    * Get config array from XML file
    *
    * @throws   PEAR_Error
    * @static
    * @access   public
    * @return   mixed
    * @param    string  $file   path to XML file
    */
    function fromXmlFile($file)
    {
        if (!is_file($file)) {
            return PEAR::raiseError("File '$file' doesn't exist");
        }
        
        return System_ProcWatch_Config::fromXml(implode('', file($file)));
    }

    /**
    * Get config array from XML string
    *
    * @throws   PEAR_Error
    * @static
    * @access   public
    * @return   mixed
    * @param    string  $xml    XML string
    */
    function fromXml($xml)
    {
        include_once('System/ProcWatch/Config/Parser.php');
        
        $config = &new System_ProcWatch_Config_Parser();
        $result = $config->getConf($xml);

        unset($config);

        return  $result;
    }
    
    /**
    * Get config array from INI file
    *
    * @throws   PEAR_Error
    * @static
    * @access   public
    * @return   mixed
    * @param    string  $file   path to INI file
    */
    function fromIniFile($file)
    {
        if (!is_file($file)) {
            return PEAR::raiseError("File '$file' doesn't exist");
        }
        
        $jobs   = parse_ini_file($file, true);
        $result = array();
        
        foreach ($jobs as $job => $c) {

            $result[$job] = array();
            
            $result[$job]['pattern'][$c['match']] = $c['pattern'];
            $result[$job]['condition'][$c['condition']] = array();
            
            // set a refernce for easier use
            if ($c['condition'] == 'attr') {
                $result[$job]['condition']['attr'] = array();
                $reference = &$result[$job]['condition']['attr'][$c['attr']];
            } elseif($c['condition'] == 'presence') {
                $reference = &$result[$job]['condition'][$c['condition']];
            } else {
                continue;
            }
            
            // (min|max|sum|is|isnot) conditions
            if (isset($c['min'])) {
                $reference['min'] = $c['min'];
            }
            if (isset($c['max'])) {
                $reference['max'] = $c['max'];
            }
            if (isset($c['sum'])) {
                $reference['sum'] = $c['sum'];
            }
            if (isset($c['is'])) {
                $reference['is'] = $c['is'];
            }
            if (isset($c['isnot'])) {
                $reference['isnot'] = $c['isnot'];
            }
            
            $result[$job]['execute'] = array('shell' => array($c['execute']));
        }
        unset($jobs);
        
        return $result;
    }
    
    /**
    * Get config array from an array :)
    *
    * This method in fact does a sanity check on the supplied config array
    * and should only be used for testing purposes.
    * 
    * @throws   PEAR_Error
    * @static
    * @access   public
    * @return   mixed
    * @param    array   $array  config array to check
    */
    function fromArray($array)
    {
        foreach ($array as $job) {
            if (
                !isset($job['pattern'])                         OR
                !isset($job['condition'])                       OR
                !isset($job['execute'])                         OR
                
                !is_array($job['pattern'])                      OR
                !is_array($job['condition'])                    OR
                !is_array($job['execute'])                      OR
                
                (   (
                    !isset($job['execute']['shell'])            AND
                    !isset($job['execute']['php'])
                    )                                           OR
                    (
                    !is_array(@$job['execute']['shell'])        AND
                    !is_array(@$job['execute']['php'])
                    )
                )                                               OR
                (   (
                    !isset($job['condition']['presence'])       AND
                    !isset($job['condition']['attr'])
                    )                                           OR
                    (
                    !is_array(@$job['condition']['presence'])   AND
                    !is_array(@$job['condition']['attr'])
                    )
                )
            ) {
                return PEAR::raiseError('Invalid configuration array supplied');
            }
        }
        return $array;
    }
}
?>