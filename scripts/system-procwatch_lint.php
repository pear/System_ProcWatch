#!@PHP-BIN@
<?php
// +----------------------------------------------------------------------+
// | procwatch-lint                                                       |
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
* Shell script for validation of System_ProcWatch' config files
* 
* @author       Michael Wallner <mike@php.net>
* @link         http://pear.php.net/package/System_ProcWatch
* @package      System_ProcWatch
* @category     System
* @version      $Revision$
*/

/**
* Requires XML::DTD::XmlValidator
*/
require_once('XML/DTD/XmlValidator.php');

/**
* Requires Console::Getopt
*/
require_once('Console/Getopt.php');

/**
* Set error handling
*/
PEAR::setErrorHandling(PEAR_ERROR_CALLBACK, '_show_usage');

/**
* Command line options
*/
$options = array(
    'c:'    => 'conf=',
    'd:'    => 'dtd=',
    'v'     => 'verbose',
    'h'     => 'help'
);

$arglist = Console_Getopt::readPHPArgv();
$srcname = array_shift($arglist);

/**
* Get command line arguments
*/
$cl_args = _prepare_args(
    array_shift(
        Console_Getopt::getopt(
            $arglist,                           // ARGV
            implode('', array_keys($options)),  // short options
            array_values($options)              // long options
        )
    )
);

/**
* Show usage if:
*   o requested with -h or --help
*   o -c or --conf was not specified
*/
if (
    isset($cl_args['h'])    ||
    isset($cl_args['help']) ||
    (!isset($cl_args['c'])  && !isset($cl_args['conf']))
)
{
    _show_usage();
}

/**
* Get path to config file
*/
$xmlfile = (isset($cl_args['c']) ? $cl_args['c'] : $cl_args['conf']);

/**
* Show usage with error message if path to config file is invalid
* PEAR::raiseError() will call _show_usage() and exit
*/
if (!is_file($xmlfile)) {
    PEAR::raiseError("File '$xmlfile' doesn't exist");
}

/**
* Get path to dtd file if set
*/
$dtdfile =  (isset($cl_args['d'])       ? $cl_args['d'] :
            (isset($cl_args['dtd'])     ? $cl_args['dtd'] : 
            '@DATADIR@/System_ProcWatch/procwatch-1_0.dtd'));

/**
* Show usage with error message if path to dtd file is invalid
* PEAR::raiseError() will call _show_usage() and exit
*/
if (!is_file($dtdfile)) {
    PEAR::raiseError("File '$dtdfile' doesn't exist");
}


/**
* Init XML::DTD::XmlValidator
*/
$xmlvalid = &new XML_DTD_XmlValidator;

/**
* Validate the supplied XML configuration file
*/
if ($xmlvalid->isValid($dtdfile, $xmlfile)) {

    $str =  "\nCONGRATS!\n\nConfiguration from $xmlfile seems " .
            "valid according to $dtdfile\n\n";

} else {

    // echo errors to STDERR if (-v|--verbose) isset
    if (isset($cl_args['v']) || isset($cl_args['verbose'])) {
        fputs(STDERR, $xmlvalid->getMessage());
    }

    $str =  "\nERRRRR...\n\nConfiguration from $xmlfile seems ".
            "NOT valid according to $dtdfile\n\n";

}

echo wordwrap($str, 79);


// ------------------ script end ------------------ //

/**
* Prepare args array from Console_Getopt::getopt()
* 
* @return   array
* @param    array
*/
function _prepare_args($array)
{
    $args = array();
    foreach ($array as $option) {
        $key        = preg_replace('/^-+/', '', $option[0]);
        $args[$key] = empty($option[1]) ?  1 : $option[1];
    }
    return $args;
}

/**
* Function which shows usage of this script
* 
* @return   void
* @param    object PEAR_Error
*/
function _show_usage($error = null)
{
    if (isset($error)) {

        fputs(STDERR, "\nError: " . $error->getMessage() . "\n\n");
        exit(-1);

    } else {

        echo <<<_SHOW_USAGE
#
# procwatch-lint v@VERSION@, @DATE@ by Michael Wallner <mike@php.net>
# For further information visit http://pear.php.net/package/System_ProcWatch
#

USAGE:
\$ {$GLOBALS['srcname']} -c <file> [-d <dtd>] [-v]

OPTIONS:
    -c | --conf=        path to XML configuration file
    -d | --dtd=         path to DTD
    
    -v | --verbose      verbose output on errors

    -h | --help         this help message

EXAMPLE:
    \$ {$GLOBALS['srcname']} -c /etc/procwatch.xml

    This command will validate the configuration file '/etc/procwatch.xml'
    using the DTD at '@DATADIR@/System_ProcWatch/procwatch-1.0.dtd'

_SHOW_USAGE;
    
        exit(0);

    }
}
?>