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


// function to convert netmask to cidr
function mask2cidr($mask)
{
    if($mask[0]=='0')
        return 0;
    $long = ip2long($mask);
    $base = ip2long('255.255.255.255');
    return 32-log(($long ^ $base)+1,2);
}

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('ipam') || !$plugin->isActivated('ipam')) {
   Html::displayNotFoundError();
}else{
    $PluginIpamAddressing = new PluginIpamAddressing();
    // check for permissions
    if($PluginIpamAddressing->canView() || Session::haveRight("config", UPDATE)) {

        Html::header(PluginIpamAddressing::getTypeName(2), '', "tools", "pluginipamaddressing");

        $stringReport = '';
        if(isset($_REQUEST['ip']) && isset($_REQUEST['cidr'])){
            // check if ip is valid
            if(!filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP)){
                $stringReport = 'Invalid IP address';
            }

            // check if cidr is actually a netmask, then convert it to cidr
            if(filter_var($_REQUEST['cidr'], FILTER_VALIDATE_IP)){
                $_REQUEST['cidr'] = (int) mask2cidr($_REQUEST['cidr']);
            }
            elseif(!filter_var($_REQUEST['cidr'], FILTER_VALIDATE_INT)){
                $stringReport = 'Invalid CIDR / Netmask';
            }
            else {
                // check if cidr is between 0 and 32
                if($_REQUEST['cidr'] < 0 || $_REQUEST['cidr'] > 32){
                    $stringReport = 'Invalid CIDR / Netmask';
                }
            }

            // if no errors, calculate subnet
            if($stringReport == ''){
                $sub = new IPv4\SubnetCalculator($_REQUEST['ip'], $_REQUEST['cidr']);
                $stringReport = $sub->getPrintableReport();
                $stringReport = $stringReport.PHP_EOL.'Broadcast Address: '.$sub->getBroadcastAddress();
            }
        }

        // if stringReport is empty, add a subnet mask cheat sheet for fun
        if($stringReport == ''){
            $stringReport = 'Quick Subnet Mask Cheat Sheet - Thanks to https://www.aelius.com/njh/subnet_sheet.html

            CIDR Addresses  Hosts   Netmask         Amount of a Class C
            ---- ---------  -----   -------         ------------------
            /30	 4          2       255.255.255.252 1/64
            /29	 8          6       255.255.255.248 1/32
            /28	 16         14      255.255.255.240 1/16
            /27	 32         30      255.255.255.224 1/8
            /26	 64         62      255.255.255.192 1/4
            /25	 128        126     255.255.255.128 1/2
            /24	 256        254     255.255.255.0   1
            /23	 512        510     255.255.254.0   2
            /22	 1024       1022    255.255.252.0   4
            /21	 2048       2046    255.255.248.0   8
            /20	 4096       4094    255.255.240.0   16
            /19	 8192       8190    255.255.224.0   32
            /18	 16384      16382   255.255.192.0   64
            /17	 32768      32766   255.255.128.0   128
            /16	 65536      65534   255.255.0.0     256';
        }

        $stringReport = "<pre><code style='white-space: pre-wrap;'>$stringReport</code></pre>";
        

        echo '
            <div class="d-flex flex-column">
                <div class="row">
                    <div class="col">
                        <div class="d-flex card-tabs flex-column horizontal">
                        <ul class="nav nav-tabs flex-row d-none d-md-flex" id="tabspanel" role="tablist">
                            <li class="nav-item ">
                                <a class="nav-link justify-content-between active" data-bs-toggle="tab" title="Calculator" href="#" data-bs-target="#tab-Glpi_Inventory_Conf_1-926907961">Calculator</a>
                            </li>
                        </ul>

                        <div class="tab-content p-2 flex-grow-1 card " style="min-height: 150px">
                            <div class="tab-pane fade active show" role="tabpanel" id="tab-Glpi_Inventory_Conf_1-926907961">
                                <form name="form" action="/plugins/ipam/front/calculator.php" method="get">
                                    <div class="center spaced" id="tabsbody">
                                        <table class="tab_cadre_fixe"><tbody>
                                            <tr class="">
                                                <td><label>IP Address</label></td>
                                                <td width="360"><input type="text" title="" class="form-text-input" id="ip" name="ip"></td>
                                                <td><label>CIDR / Netmask</label></tD>
                                                <td width="360"><input type="text" title="" class="form-text-input" id="cidr" name="cidr"></td>
                                                <td><input class="btn btn-primary me-2" type="submit" value="Submit"></td>
                                            </tr>
                                                
                                        </tbody></table>
                                    </div>
                                    
                                </form><br>'.$stringReport . '
                            </div>
                        </div>
                    </div>
                </div>
            </div>';

        Html::footer();
    }
    else {
        Html::displayRightError();
    }
}