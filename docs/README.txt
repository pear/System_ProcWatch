################################################################################
#                                                                              #
#                README of System::ProcWatch - Process Monitor                 #
#                                                                              #
################################################################################
$Id$

Contents of this File:
=======================
    1.  Intro
    2.  Classes
    3.  Configuration
    
    
1. Intro
=========


1.1 Structure
--------------

At the moment System::ProcWatch comes with the following structure:

System/
    +- ProcWatch.php        public      System::ProcWatch
    +- ProcWatch/
        +- Parser.php       public      System::ProcWatch::Parser
        +- Config.php       public      System::ProcWatch::Config
        +- Config/
            +- Parser.php   protected   System::ProcWatch::Config::Parser

docs/
    +- System_ProcWatch/
        +- README.txt       this file
        +- EXAMPLE.txt      describes how to use example.xml
        +- example.xml      config example - ready to run an well commented
        +- example.ini      config example

data/
    +- System_ProcWatch/
        +- procwatch-1_0.dtd    DTD to validate ones config files
        

    Furthermore you will find two shellscripts in your bin (usually /usr/bin):
        o procwatch         a ready to run process monitoring utility
        o procwatch-lint    a utility to validate your config files by the DTD


2. Classes
===========
    
    This section tries to give a short insight into the ProcWatch classes.


2.1 System::ProcWatch
----------------------

    This is the main class which handles the specified actions when the defined
    condition applies to the matching processes. The ProcWatch object can be
    configured through an XML configuration file or string and an native PHP
    array (see 3. Configuration).
    
    It provides two main executive methods. That are run() and daemon().
    

2.1.1 System::ProcWatch::run([$ps_args='aux'])

    The run() method executes once. With (string) $ps_args you can define 
    the command line arguments passed to ps (Unix' procps), which implies that
    your configuration must base upon the column headers of the output of ps.
    

2.1.2 System::ProcWatch::daemon($interval[, $ps_args='aux'])

    The daemon() method executes the run() method in an infinite loop, sleeping
    the defined (int) $interval seconds between the single runs. The (string)
    $ps_args will get passed through to the run() method.
    

2.2 System::ProcWatch::Parser
------------------------------

    This class executes ps (Unix' procps) on the command line, fetches its 
    output and parses it into an associative array. The column headers of
    ps' output will be taken as keys for the array.
    
    The main method of an System_ProcWatch_Parser object is &getParsedData().


2.2.1 System::ProcWatch::Parser::getParsedData([$ps_args='aux'[, $refresh=true])

    This method returns a two dimensional array - first dimension indexed,
    second dimension associative. It contains all information about processes
    fetched from the output of ps.
    
    If the outptut of ps looks like

        PID TTY          TIME CMD
        346 pts/0    00:00:00 bash
        349 pts/0    00:00:00 ps
        
    the corresponding array will look like

        array(
            array(
                'pid'   => '346',
                'tty'   => 'pts/0',
                'time'  => '00:00:00',
                'cmd'   => 'bash'
            ),
            array(
                ...
            )
        )
  

2.3 System::ProcWatch::Config
------------------------------

    The System_ProcWatch_Config class provides four static methods to retrieve
    a configuration array from the following methods: (see 3. Configuration)
        o array fromXml     ((string) $xml )
        o array fromXmlFile ((string) $file )
        o array fromIniFile ((string) $file )
        o array fromArray   ((array)  $array )

    The latter does in fact a sanity check on your configuration array, 
    if you rather want to configure ProcWatch through an PHP array than 
    an XML file or string. I think it should only be used for testing
    purposes.

    An example of an array to configure System::Procwatch could look like this:
    
        array(
            'job1' =>    array(
                'pattern'   => array(
                    'command' => '/httpd/'
                ),
                'condition' => array(
                    'presence' => array()
                ),
                'execute'   => array(
                    'shell' => array(
                        'echo $msg >> /var/log/procwatch'
                    )
                    'php'   => array(
                        'echo($msg);'
                    )
                )
            )
        )

    This will configure System::ProcWatch for a single job named "job1".
    It will search the output of ps for presence of the PCRE "/httpd/" in
    ps' column "COMMAND" and log the count of found processes to
    /var/log/procwatch through a shell command and echos the same message
    through php.
    

2.4 System::ProcWatch::Config::Parser
--------------------------------------

    This class is used internally by System_ProcWatch_Config for parsing
    XML configuration strings (and files).


3. Configuration
=================

    There are four methods to configure System::ProcWatch
        o PHP array
        o XML string
        o XML file
        o INI file


3.1 Configuration through PHP arrays
-------------------------------------

    See 2.3 System::ProcWatch::Config


3.2 Configuration through XML strings/files
--------------------------------------------

    The only method to configure the procwatch system utiliy is by supplying
    appropriate XML configuration files. An example configuration can be found
    in example.xml, which is also well commented.
    
    Simple example:
    ________________________________________

    <procwatch>

      <!-- Here we define a single job: -->
      <watch name="Look for crappy ZOMBIES!">

        <!-- The PCRE should look for a "Z" in ps' column "STAT" -->
        <pattern match="stat">/Z/</pattern>

        <!-- If any got found... -->
        <condition type="presence" />

        <!-- ...execute 'oh_my_god_zombies_have_been_found.sh' on the shell -->
        <execute type="shell">oh_my_god_zhombies_have_been_found.sh</execute>

      </watch>
    </procwatch>
    ________________________________________
    
    Really easy, isn't it?
    
    Now let's have a look at another one:
    ________________________________________

    <procwatch>

      <!-- Our job is now named "httpd" -->
      <watch name="httpd">

        <!-- Look for PCRE "/httpd/" in ps' column "COMMAND" -->
        <pattern match="command">/httpd/</command>

        <!-- If less than 10 httpd processes have been found... -->
        <condition type="presence">
          <min>10</min>
        </condition>

        <!-- ...mail the root! -->
        <execute type="php">
            $mail = 'root@localhost';
            $subj = 'httpd's dead!';
            mail($mail, $subj, $msg);
        </execute>
      </watch>
    </procwatch>
    ________________________________________
    
    Not less easy right?
    
    Hm, what? I didn't define $msg? Well, you're right - see 3.4 :)


3.3 Configuration through INI files
------------------------------------

    You also can configure System_ProcWatch through INI files. The layout
    is somewhat compareable to XML configuration files,
    
        BUT YOU CAN ONLY DEFINE SHELL EXECUTES
        
    in INI configuration files.
    
    Have a look at "example.ini" for an example configuration.


3.4 Special Variables
----------------------

    System_ProcWatch supplies the following special variables,
    which will be replaced in the string to execute:
  
    $msg    - This contains a general message, what has happened.
              It is quoted in single quotes for save usage and
              is available in shell and php executes.
    
    $pids   - This contains all PIDs of the processes that have been
              found. They are enclosed by single quotes and parenthesis.
              Example: '(433, 444, 455, 466)'

    $procs  - This is a somewhat serialized php array in string format
              containing all information gained from ps and looks like:
              array(array('pid' => 344, 'command' => '/usr/sbin/httpd' ...))
              It is only available in php executes and can easily be used
              in function callbacks:
              <execute type="php">get_procs($procs);</execute>


################################################################################
