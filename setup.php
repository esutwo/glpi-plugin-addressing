<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 ipam plugin for GLPI
 Copyright (c) 2023 by ESU2

 https://github.com/esutwo/glpi-plugin-ipam

 ** Special thanks to original creators of addressing ***
 
 addressing plugin for GLPI
 Copyright (C) 2009-2022 by the addressing Development Team.

 https://github.com/pluginsGLPI/addressing
 -------------------------------------------------------------------------

 LICENSE

 This file is part of addressing.

 addressing is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 addressing is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with addressing. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('PLUGIN_IPAM_VERSION', '0.0.1');

if (!defined("PLUGIN_IPAM_DIR")) {
    define("PLUGIN_IPAM_DIR", Plugin::getPhpDir("ipam"));
    define("PLUGIN_IPAM_DIR_NOFULL", Plugin::getPhpDir("ipam", false));
    define("PLUGIN_IPAM_WEBDIR", Plugin::getWebDir("ipam"));
}

// Init the hooks of the plugins -Needed
function plugin_init_ipam()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['ipam'] = true;

    $PLUGIN_HOOKS['change_profile']['ipam'] = ['PluginIpamProfile', 'initProfile'];

    Plugin::registerClass(
        'PluginIpamProfile',
        ['addtabon' => ['Profile']]
    );

    if (Session::getLoginUserID()) {
        if (Session::haveRight('plugin_ipam', READ)) {
            $PLUGIN_HOOKS["menu_toadd"]['ipam'] = ['tools'  => 'PluginIpamAddressing'];
        }

        if (Session::haveRight('plugin_ipam', UPDATE)) {
            $PLUGIN_HOOKS['use_massive_action']['ipam']   = 1;
        }

        // Config page
        if (Session::haveRight("config", UPDATE)) {
            $PLUGIN_HOOKS['config_page']['ipam'] = 'front/config.php';
        }

        // Add specific files to add to the header : javascript or css
        if (isset($_SESSION['glpiactiveprofile']['interface'])
            && $_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $PLUGIN_HOOKS['add_css']['ipam']        = "ipam.css";
            $PLUGIN_HOOKS["javascript"]['ipam']     = [PLUGIN_IPAM_DIR_NOFULL."/ipam.js"];
            $PLUGIN_HOOKS['add_javascript']['ipam'] = 'ipam.js';
        }
    }
}


// Get the name and the version of the plugin - Needed
function plugin_version_ipam()
{
    return [
       'name'           => _n('IPAM', 'IPAM', 2, 'ipam'),
       'version'        => PLUGIN_IPAM_VERSION,
       'author'         => 'ESU2',
       'license'        => 'GPLv2+',
       'homepage'       => 'https://github.com/esutwo/glpi-plugin-ipam',
       'requirements'   => [
          'glpi' => [
             'min' => '10.0',
             'max' => '11.0',
          ]
       ]];
}
