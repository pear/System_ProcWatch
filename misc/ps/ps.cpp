/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// {{{ license
// +----------------------------------------------------------------------+
// | ps                                                                   |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is available at http://www.php.net/license/3_0.txt              |
// | If you did not receive a copy of the PHP license and are unable      |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 Michael Wallner <mike@iworks.at>                  |
// +----------------------------------------------------------------------+
// }}}
// $Id$

// {{{ header
/**
* ps for WinNT
*
* @author        Michael Wallner <mike@php.net>
* @package       System_ProcWatch
* @categroy      System
*
* Example output:
* ===============
* <code>
*  PID    %MEM      VSZ      RSS    COMMAND
* 1304     0.3     4145     2684    C:\WINNT\system32\stisvc.exe
* 1376     0.3     4773     3660    C:\WINNT\system32\tlntsvr.exe
* 1428     0.1     1440      628    C:\WINNT\System32\WBEM\WinMgmt.exe
* 1444     0.2     2432     1916    C:\WINNT\System32\MsPMSPSv.exe
* 1456     0.5     8605     5216    C:\WINNT\system32\svchost.exe
* 1468     0.9    15293     9444    C:\WINNT\System32\inetsrv\inetinfo.exe
* 1920     0.4    18618     4688    C:\WINNT\Explorer.EXE
* ...
* </code>
*/
// }}}

// {{{ includes
#include <windows.h>
#include <stdio.h>
#include "psapi.h"
// }}}

// {{{ namespace PS
namespace PS
{
    // {{{ ProcInfo(DWORD processID, int &mem, int &full, char file[])
    int ProcInfo(DWORD processID, int &mem, int &full, char file[])
    {
        HANDLE 
            handle;

        PROCESS_MEMORY_COUNTERS 
            pmc;

        HMODULE 
            mods[1];

        DWORD 
            needed;

        handle = OpenProcess(
            PROCESS_QUERY_INFORMATION | PROCESS_VM_READ,
            false,
            processID
        );

        if ( NULL == handle ) {
            return 0;
        }

        if (!GetProcessMemoryInfo( handle, &pmc, sizeof(pmc))) {
            CloseHandle(handle);
            return 0;
        }
        if (!EnumProcessModules( handle, mods, sizeof(HMODULE), &needed)) {
            CloseHandle(handle);
            return 0;
        }
    
        if (!GetModuleFileNameEx( handle, mods[0], file, sizeof(char[128]))) {
            CloseHandle(handle);
            return 0;
        }

        mem  = pmc.WorkingSetSize;
        full = (pmc.PagefileUsage + mem) - pmc.QuotaNonPagedPoolUsage;

        CloseHandle(handle);
        return 1;
    }
    // }}}

    // {{{ int getTotalMem()
    int getTotalMem()
    {
        MEMORYSTATUS status;
        GlobalMemoryStatus(&status);
        return status.dwTotalPhys;
    }
    // }}}

    // {{{ void print()
    void print()
    {
        DWORD 
            procs[128], 
            needed;
    
        int 
            totalmem, 
            mem, 
            full, 
            actual, 
            i;
    
        char 
            file[128];

        if (!EnumProcesses(procs, sizeof(procs), &needed)) {
            return;
        }

        actual   = needed / sizeof(DWORD);
        totalmem = PS::getTotalMem();

        printf("  PID    %%MEM      VSZ      RSS    COMMAND\n");
    
        for (i = 0; i < actual; i++) {
    
            if ( procs[i] == 8 || procs[i] == 0) {
                continue;
            }

            PS::ProcInfo(procs[i], mem, full, file);

            printf("%5u    ",   procs[i]);
            printf("%4.1f  ",   (mem * 100.0 / totalmem));
            printf("%7u  ",     full / 1024);
            printf("%7u    ",   mem / 1024);
            printf("%s",        file);
            printf("\n");
        }

    }
    // }}}
}
// }}}

// {{{ void main()
void main()
{
    PS::print();
}
// }}}
