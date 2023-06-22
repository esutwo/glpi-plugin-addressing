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

function plugin_ipam_install()
{
    global $DB;

    include_once(PLUGIN_IPAM_DIR . "/inc/profile.class.php");

    $update = false;
    if (!$DB->tableExists("glpi_plugin_ipam_display")
        && !$DB->tableExists("glpi_plugin_ipam")
        && !$DB->tableExists("glpi_plugin_ipam_configs")) {
        $DB->runFile(PLUGIN_IPAM_DIR . "/sql/empty-0.0.1.sql");
    } else {
        /*if (!$DB->tableExists("glpi_plugin_ipam_profiles")
            && $DB->tableExists("glpi_plugin_ipam_display")
            && !$DB->fieldExists("glpi_plugin_ipam_display", "ipconf1")) {//1.4
            $update = true;
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-1.4.sql");
        }

        if (!$DB->tableExists("glpi_plugin_ipam")
            && $DB->tableExists("glpi_plugin_ipam_display")
            && $DB->fieldExists("glpi_plugin_ipam_display", "ipconf1")) {
            $update = true;
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-1.5.sql");
        }

        if ($DB->tableExists("glpi_plugin_ipam_display")
            && !$DB->fieldExists("glpi_plugin_ipam", "ipdeb")) {
            $update = true;
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-1.6.sql");
        }

        if ($DB->tableExists("glpi_plugin_ipam_profiles")
            && $DB->fieldExists("glpi_plugin_ipam_profiles", "interface")) {
            $update = true;
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-1.7.0.sql");
        }

        if (!$DB->tableExists("glpi_plugin_ipam_configs")) {
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-1.8.0.sql");
            $update = true;
        }

        if ($DB->tableExists("glpi_plugin_ipam_profiles")
            && !$DB->fieldExists("glpi_plugin_ipam_profiles", "use_ping_in_equipment")) {
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-1.9.0.sql");
            $update = true;
        }
        //Version 2.4.0
        if (!$DB->tableExists("glpi_plugin_ipam_filters")) {
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-2.4.0.sql");
        }

        //Version 2.5.0
        if (!$DB->fieldExists("glpi_plugin_ipam_addressings", "locations_id")
            && !$DB->fieldExists("glpi_plugin_ipam_addressings", "fqdns_id")) {
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-2.5.0.sql");
        }
        //Version 2.9.1
        if (!$DB->tableExists("glpi_plugin_ipam_pinginfos")) {
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-2.9.1.sql");
        }
        //Version 3.0.1
        if (!$DB->fieldExists("glpi_plugin_ipam_addressings", "vlans_id")) {
            $DB->runFile(PLUGIN_IPAM_DIR . "/sql/update-3.0.1.sql");
        }*/
    }

    if ($update) {
        $query_  = "SELECT *
                  FROM `glpi_plugin_ipam_profiles` ";
        $result_ = $DB->query($query_);

        if ($DB->numrows($result_) > 0) {
            while ($data = $DB->fetchArray($result_)) {
                $query  = "UPDATE `glpi_plugin_ipam_profiles`
                      SET `profiles_id` = '" . $data["id"] . "'
                      WHERE `id` = '" . $data["id"] . "'";
                $result = $DB->query($query);
            }
        }

        if ($DB->fieldExists("glpi_plugin_ipam_profiles", "name")) {
            $query  = "ALTER TABLE `glpi_plugin_ipam_profiles`
                    DROP `name` ";
            $result = $DB->query($query);
        }

        Plugin::migrateItemType(
            [5000 => 'PluginIpamAddressing',
                                 5001 => 'PluginIpamReport'],
            ["glpi_savedsearches", "glpi_savedsearches_users",
             "glpi_displaypreferences", "glpi_documents_items",
             "glpi_infocoms", "glpi_logs", "glpi_items_tickets"]
        );
    }

    //0.85 : new profile system
    PluginIpamProfile::migrateProfiles();
    //Add all rights for current user profile
    PluginIpamProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    //Drop old profile table : not used anymore
    $migration = new Migration("2.5.0");
    $migration->dropTable('glpi_plugin_ipam_profiles');
    //CronTask::Register(PluginIpamPinginfo::class, 'UpdatePing', DAY_TIMESTAMP);

    return true;
}


/**
 * @return bool
 */
function plugin_ipam_uninstall()
{
    global $DB;

    include_once(PLUGIN_IPAM_DIR . "/inc/profile.class.php");

    $migration = new Migration("2.5.0");
    $tables    = ["glpi_plugin_ipam_addressings",
                  "glpi_plugin_ipam_configs",
                  "glpi_plugin_ipam_filters",
                  //"glpi_plugin_ipam_pinginfos",
                  "glpi_plugin_ipam_ipcomments"];

    foreach ($tables as $table) {
        $migration->dropTable($table);
    }

    $itemtypes = ['DisplayPreference', 'SavedSearch'];
    foreach ($itemtypes as $itemtype) {
        $item = new $itemtype;
        $item->deleteByCriteria(['itemtype' => 'PluginIpamAddressing']);
    }

    //Delete rights associated with the plugin
    $profileRight = new ProfileRight();

    foreach (PluginIpamProfile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }

    //Remove rigth from $_SESSION['glpiactiveprofile'] if exists
    PluginIpamProfile::removeRightsFromSession();

    PluginIpamAddressing::removeRightsFromSession();
    CronTask::unregister("ipam");
    return true;
}


/**
 * Define database relations
 *
 * @return array
 */
function plugin_ipam_getDatabaseRelations()
{
    if (Plugin::isPluginActive("ipam")) {
        return ["glpi_networks"  => ["glpi_plugin_ipam_addressings" => "networks_id"],
                "glpi_vlans"     => ["glpi_plugin_ipam_addressings" => "vlans_id"],
                "glpi_fqdns"     => ["glpi_plugin_ipam_addressings" => "fqdns_id"],
                "glpi_locations" => ["glpi_plugin_ipam_addressings" => "locations_id"],
                "glpi_entities"  => ["glpi_plugin_ipam_addressings" => "entities_id"]];
    }
    return [];
}

/**
 * @param $itemtype
 *
 * @return array
 */
function plugin_ipam_getAddSearchOptions($itemtype)
{
    $sopt = [];

    if (in_array($itemtype, PluginIpamAddressing::getTypes(true))) {
        /*if (Session::haveRight("plugin_ipam", READ)) {
            $sopt[5000]['table']         = 'glpi_plugin_ipam_pinginfos';
            $sopt[5000]['field']         = 'ping_response';
            $sopt[5000]['name']          = __('Ping result', 'ipam');
            $sopt[5000]['forcegroupby']  = true;
            $sopt[5000]['linkfield']     = 'id';
            $sopt[5000]['massiveaction'] = false;
            $sopt[5000]['joinparams']    = ['beforejoin' => ['table'      => 'glpi_plugin_ipam_pinginfos',
                                                             'joinparams' => ['jointype' => 'itemtype_item']]];
        }*/
    }
    return $sopt;
}

/**
 * @param $type
 * @param $ID
 * @param $data
 * @param $num
 *
 * @return string
 */
function plugin_ipam_giveItem($type, $ID, $data, $num)
{
    global $DB;

    $dbu = new DbUtils();

    $searchopt =& Search::getOptions($type);
    $table     = $searchopt[$ID]["table"];
    $field     = $searchopt[$ID]["field"];
    $out       = "";
    /*if (in_array($type, PluginIpamAddressing::getTypes(true))) {
        switch ($table . '.' . $field) {
            case "glpi_plugin_ipam_pinginfos.ping_response":
                if ($data[$num][0]['name'] == "1") {
                    $out .= "<i class=\"fas fa-check-square fa-2x\" style='color: darkgreen'></i><br>" . __('Last ping OK', 'ipam');
                } elseif ($data[$num][0]['name'] == "0") {
                    $out .= "<i class=\"fas fa-window-close fa-2x\" style='color: darkred'></i><br>" . __('Last ping KO', 'ipam');
                } else {
                    $out .= "<i class=\"fas fa-question fa-2x\" style='color: orange'></i><br>" . __("Ping informations not available", 'ipam');
                }
                return $out;
                break;
        }
    }*/
    return "";
}

/**
 * Do special actions for dynamic report
 *
 * @param $params
 *
 * @return bool
 */
function plugin_ipam_dynamicReport($params)
{
    $PluginIpamAddressing = new PluginIpamAddressing();

    if ($params["item_type"] == 'PluginIpamReport'
        && isset($params["id"])
        && isset($params["display_type"])
        && $PluginIpamAddressing->getFromDB($params["id"])) {
        $PluginIpamReport = new PluginIpamReport();
        $PluginIpamAddressing->getFromDB($params['id']);

        $addressingFilter = new PluginIpamFilter();
        if (isset($params['filter']) && $params['filter'] > 0) {
            if ($addressingFilter->getFromDB($params['filter'])) {
                $ipdeb  = sprintf("%u", ip2long($addressingFilter->fields['begin_ip']));
                $ipfin  = sprintf("%u", ip2long($addressingFilter->fields['end_ip']));
                $result = $PluginIpamAddressing->compute($params["start"], ['ipdeb'       => $ipdeb,
                                                                                  'ipfin'       => $ipfin,
                                                                                  'entities_id' => $addressingFilter->fields['entities_id'],
                                                                                  'type_filter' => $addressingFilter->fields['type']]);
            }
        } else {
            $ipdeb  = sprintf("%u", ip2long($PluginIpamAddressing->fields["begin_ip"]));
            $ipfin  = sprintf("%u", ip2long($PluginIpamAddressing->fields["end_ip"]));
            $result = $PluginIpamAddressing->compute($params["start"], ['ipdeb' => $ipdeb,
                                                                              'ipfin' => $ipfin]);
        }
        $PluginIpamReport->displayReport($result, $PluginIpamAddressing);

        return true;
    }

    // Return false if no specific display is done, then use standard display
    return false;
}

/**
 * @param $itemtype
 * @param $ID
 * @param $order
 * @param $key
 *
 * @return string
 */
function plugin_ipam_addOrderBy($itemtype, $ID, $order, $key)
{
    if ($itemtype == "PluginIpamAddressing"
        && ($ID == 1000 || $ID == 1001)) {
        return "ORDER BY INET_ATON(ITEM_$key) $order";
    }
}

function plugin_ipam_postinit()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['item_purge']['ipam'] = [];

    foreach (PluginIpamAddressing::getTypes() as $type) {
        $PLUGIN_HOOKS['item_purge']['ipam'][$type]
           = ['cleanForItem'];
    }
}