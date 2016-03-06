<?php

/*Script name:  generate_motd.sh
# Version:      v0.04.160306
# Created on:   10/02/2014
# Author:       Willem D'Haese
# Purpose:      Bash script that will dynamically generate a message
#               of they day for users logging in.
# On GitHub:    https://github.com/willemdh/check_wordpress_update
# On OutsideIT: https://outsideit.net/check-wordpress-update
# Recent History:
#   05/03/16 => Inital creation
#   06/03/16 => Better output and logging
# Copyright:
# This program is free software: you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by the Free
# Software Foundation, either version 3 of the License, or (at your option)
# any later version. This program is distributed in the hope that it will be
# useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
# Public License for more details. You should have received a copy of the
# GNU General Public License along with this program.  If not, see
# <http://www.gnu.org/licenses/>.*/

$Verbose = 0;

function WriteLog($Log, $Severity, $Msg) {
    global $Verbose;
    if ($Verbose == 1) {
        $DateTime = date('Y-m-d H:i:s');    
        $MicroTime = round(microtime(true) * 1000);
        list($MicroSec, $Sec) = explode(" ", microtime());
        $FullDateTime = date("Y-m-d H:i:s,",$Sec) . intval(round($MicroSec*1000)) . ' ';
        $fd = fopen($Log, 'a');
        date_default_timezone_set('Europe/Brussels');
        $FullMessage = $FullDateTime . $Severity . ': ' . $Msg;
        fwrite($fd, $FullMessage . "\n");
        fclose($fd);
    }    
}


$allowed_ips = array('127.0.0.1','212.71.234.84','2a01:7e00::f03c:91ff:fe18:6141');
$remote_ip = $_SERVER['REMOTE_ADDR'];
WriteLog('outsideit_Wp.log', 'Info', "Address: $remote_ip");
if (! in_array($remote_ip, $allowed_ips)) {
    echo "CRITICAL: IP $remote_ip not allowed.";
    exit;
}
WriteLog('outsideit_Wp.log', 'Info', "Require wp-load.php");
require_once('wp-load.php');
global $wp_version;
$core_updates = FALSE;
$plugin_updates = FALSE;
WriteLog('outsideit_Wp.log', 'Info', "Running wp_version_check");
wp_version_check();
WriteLog('outsideit_Wp.log', 'Info', "Running wp_update_plugins");
wp_update_plugins();
WriteLog('outsideit_Wp.log', 'Info', "Running wp_update_themes");
wp_update_themes();
$counts = array( 'core' => 0, 'plugins' => 0, 'themes' => 0 );
$CountStr = implode(",",$counts);
WriteLog('outsideit_Wp.log', 'Info', "Counts: $CountStr");
// $update_wordpress = get_core_updates( array('dismissed' => false) );
//if ( ! empty( $update_wordpress ) && ! in_array( $update_wordpress[0]->response, array('development', 'latest') ) ) {
//   $counts['wordpress'] = 1;
//}
//else {
//    $counts['wordpress'] = 11;
//}
if ( false === ( $core = get_transient( 'update_core' ) ) ) {
    WriteLog('outsideit_Wp.log', 'Info', "Core transient equals false. Trying site...");
    if ( false === ( $core = get_site_transient( 'update_core' ) ) ) {
        WriteLog('outsideit_Wp.log', 'Info', "Core site transient also equals false.");
    }
    else {
//        WriteLog('outsideit_Wp.log', 'Info', "Serializing core object.");
//        $coretext = serialize($core);
//        WriteLog('outsideit_Wp.log', 'Info', "coretext: $coretext");
//        $counts['core'] = count( $core->response );
//        $CountStr = implode(",",$counts);
//        WriteLog('outsideit_Wp.log', 'Info', "Counts: $CountStr");
        foreach ($core->updates as $core_update) {
            if ($core_update->current != $wp_version) {
                WriteLog('outsideit_Wp.log', 'Info', "Core updates not equal to $wp_version");
                $counts['core'] = 1;
            }
       }
    }
}
else {

}

if ( false === ( $plugins = get_transient( 'update_plugins' ) ) ) {
    WriteLog('outsideit_Wp.log', 'Info', "Plugin transient equals false. Trying site...");
    if ( false === ( $plugins = get_site_transient( 'update_plugins' ) ) ) {
        WriteLog('outsideit_Wp.log', 'Info', "Plugin site transient also equals false.");
    }
    else {
        WriteLog('outsideit_Wp.log', 'Info', "Serializing plugins object.");
        $pluginstext = serialize($plugins);
        WriteLog('outsideit_Wp.log', 'Info', "pluginstext: $pluginstext");
        $counts['plugins'] = count( $plugins->response );
        $CountStr = implode(",",$counts);
        WriteLog('outsideit_Wp.log', 'Info', "Counts: $CountStr");
    }
}
else {
      
}
if ( false === ( $themes = get_transient( 'update_themes' ) ) ) {
    WriteLog('outsideit_Wp.log', 'Info', "Theme transient equals false. Trying site...");
    if ( false === ( $plugins = get_site_transient( 'update_themes' ) ) ) {
        WriteLog('outsideit_Wp.log', 'Info', "Theme site transient also equals false.");
    }
    else {
        WriteLog('outsideit_Wp.log', 'Info', "Serializing plugins object.");
        $themestext = serialize($themes);
        WriteLog('outsideit_Wp.log', 'Info', "themestext: $themestext");
        $counts['themes'] = count( $themes->response );
        $CountStr = implode(",",$counts);
        WriteLog('outsideit_Wp.log', 'Info', "Counts: $CountStr");
    }
}
else {

}
$status = 'UNKNOWN: ';
if ($counts['core'] >= 1) {
    $status = 'CRITICAL';
}
elseif ($counts['plugins'] >= 1 || $counts['themes'] >= 1 ) {
    $status = 'WARNING';
}
elseif ($counts['plugins'] == 0 && $counts['themes'] == 0 && $counts['core'] == 0) {
    $status = 'OK';
}
else {
    $status = 'UNKNOWN';
}
$text = $status . ': ' . $counts['core'] . ' core update. ' . $counts['plugins'] . ' plugin updates. ' . $counts['themes'] . ' theme updates. ';
echo $text;
