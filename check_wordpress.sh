#!/bin/bash
# Script name:  check_wordpress_updates.sh
# Version:      v1.04.160413
# Created on:   10/11/2015
# Author:       Willem D'Haese
# Purpose:      Checks Wordpress website for updates.
# On GitHub:    https://github.com/willemdh/check_wordpress_updates
# On OutsideIT: https://outsideit.net/check-wordpress-updates
# Recent History:
#   05/03/16 => Inital creation, based on Kong Jin Jie's WP plugin
#   06/03/16 => Better output and logging
#   07/03/16 => Better parameters and tests
#   13/04/16 => Cleanup for release, core testing
# Copyright:
# This program is free software: you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by the Free
# Software Foundation, either version 3 of the License, or (at your option)
# any later version. This program is distributed in the hope that it will be
# useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
# Public License for more details. You should have received a copy of the
# GNU General Public License along with this program.  If not, see
# <http://www.gnu.org/licenses/>.

Verbose=0
Exitcode=3
ReturnString="UNKNOWN: Something unusual happened. Please debug."

WriteLog () {
    if [ -z "$1" ] ; then
        echo "WriteLog: Log parameter #1 is zero length. Please debug..."
        exit 1
    else
        if [ -z "$2" ] ; then
            echo "WriteLog: Severity parameter #2 is zero length. Please debug..."
            exit 1
        else
            if [ -z "$3" ] ; then
                echo "WriteLog: Message parameter #3 is zero length. Please debug..."
                exit 1
            fi
        fi
    fi
    Now=$(date '+%Y-%m-%d %H:%M:%S,%3N')
    ScriptName="$($(which basename) ${0})"
    if [ "${1,,}" = "verbose" -a $Verbose = 1 ] ; then
        echo "$Now: $ScriptName: $2: $3"
    elif [ "${1,,}" = "verbose" -a $Verbose = 0 ] ; then
        :
    elif [ "${1,,}" = "output" ] ; then
        echo "${Now}: $ScriptName: $2: $3"
    elif [ -f $1 ] ; then
        echo "${Now}: $ScriptName: $2: $3" >> $1
    fi
}

DisplayHelp ()  {
    echo "Usage: check_wordpress_updates.sh"
    echo "1) Add the IP addresses of the Nagios servers that need access to the Allowed array in check_wordpress_updates.php"
    echo "2) Put check_wordpress_updates.php in the root of you Wordpress installation"
    echo "3) Put check_wordpress_updates.sh in you nagios plugin folder and call it from Nagios interface"
    echo "Arguments: "
    echo "    -h |--help        Displays this help option"
    echo "    -U |--Url         Mandatory URL on which to check for Wordpress updates"
    exit 3
}

while :; do
    case "$1" in
        -h|--help)
            DisplayHelp ;;
        -U|--Url)
            shift ; Url="$1" ; shift ; ;;
        -*)
            echo "you specified a non-existant option. Please debug." ; exit 2 ; ;;
        *)
            break ; ;;
    esac
done

if [[ -z $Url ]] ; then
    ReturnString="CRITICAL: Mandatory parameter \"--Url\" is missing."
    Exitcode=2
else
    CurlPath="$(which curl)"
    if [[ -z $CurlPath ]] ; then
        ReturnString="ERROR: Curl is not installed"
        Extcode=2
    else
        CurlOptions='--user-agent check_wordpress_updates.sh'
        WriteLog Verbose Info "Command: $CurlPath -s $Url"
        CurlResult="$($CurlPath -s $Url/wp-version.php)"
        CommandResult=$?
        if [ $CommandResult != 0 ] ; then
            ReturnString="CRITICAL: Problem with curl or ip address in php incorrect. Wesbite: $url Result: $CurlResult"
            Exitcode=2
        else
            WriteLog Verbose Info "CurlResult: $CurlResult"
            if [[ ! -z $CurlResult ]] ; then
                ReturnString=$CurlResult
                if [[ "$CurlResult" =~ "CRITICAL" ]]; then
                    Exitcode=2
                elif [[ "$CurlResult" =~ "WARNING" ]]; then
                    Exitcode=1
                elif [[ "$CurlResult" =~ "OK" ]]; then        
                    Exitcode=0
                fi
            else
                ReturnString="ERROR: Curl Results is empty. Something went wrong."
                Extcode=2
            fi
        fi
    fi
fi
echo $ReturnString
exit $Exitcode


