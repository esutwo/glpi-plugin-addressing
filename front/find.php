<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
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

include ('../../../inc/includes.php');

require_once(__DIR__ . '/../vendor/autoload.php');


// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('ipam') || !$plugin->isActivated('ipam')) {
   Html::displayNotFoundError();
}else{
    $PluginIpamAddressing = new PluginIpamAddressing();
    // check for permissions
    if($PluginIpamAddressing->canView() || Session::haveRight("config", UPDATE)) {
        Html::header(PluginIpamAddressing::getTypeName(2), '', "tools", "pluginipamaddressing");
        
        $ipaddr = "";
        $params = [];
        $errorclass = "";
        $errormsg = "";

        if (isset($_REQUEST['ipaddr']) && !empty($_REQUEST['ipaddr'])){

            // check valid ip
            if(!filter_var($_REQUEST['ipaddr'], FILTER_VALIDATE_IP)){
                $errorclass = " is-invalid";
                $errormsg = "<div class='invalid-feedback'>Invalid IP address</div>";
            }

            $ipaddr = $_REQUEST['ipaddr'];

            // get subnets from db
            global $DB;
            $query = "SELECT `id`,`begin_ip`,`cidr` FROM glpi_plugin_ipam_addressings WHERE `is_deleted` = 0";
            $results = [];
            foreach ($DB->request($query) as $id => $row) {
                // get ip and cidr
                $ip = $row['begin_ip'];
                $cidr = $row['cidr'];
                $sub = new IPv4\SubnetCalculator($ip, $cidr);

                // check if ip is in subnet
                if($sub->isIPAddressInSubnet($_REQUEST['ipaddr'])){
                    $results[] = $row;
                }
            }

            // build data for search
            $params = [
                'is_deleted' => 0, // item is not deleted
                'sort' => 1 // sort by name
            ];

            foreach ($results as $result) {
                $params['criteria'][] = [
                    'field' => 1, // id
                    'searchtype' => 'equals',
                    'value' => $result['id'],
                    'link' => 'OR'
                ];
            }
        }

        echo '
        <form name="findipamsubnet" class="search-form-container" method="get" action="/plugins/ipam/front/find.php">
            <div class="search-form card card-sm mb-4">
                <div class="list-group list-group-flush list-group-hoverable criteria-list pt-2">
                    <div class="p-2 border-0 normalcriteria headerRow">
                        <div class="row g-1" style="padding: 10px">
                            <div class="col-3">
                                <h2>Find Subnets by IP Address</h2>
                                <i>Enter an IP address to find the subnet it belongs to.</i>
                            </div>
                        
                        
                            <div class="col-auto">
                                <input type="text" class="form-control' . $errorclass . '" size="13" name="ipaddr" value="' . $ipaddr . '">' . $errormsg . '
                            </div>
                            <div class="col-auto">
                                <input class="btn btn-primary me-2" type="submit" value="Submit">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>';

        Search::showList('PluginIpamAddressing', $params);

        Html::footer();
    }
    else {
        Html::displayRightError();
    }
}