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
include ('../vendor/autoload.php');

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

if (isset($_GET["start"])) {
   $start = $_GET["start"];
} else {
   $start = 0;
}

$ipam = new PluginIpamAddressing();

if (isset($_POST["add"])) {
   $ipam->check(-1, CREATE, $_POST);
   if (!empty($_POST["name"])
      && !empty($_POST["begin_ip"])
         && !empty($_POST["cidr"])) {
      
      // check if ip is valid
      if(!filter_var($_POST['begin_ip'], FILTER_VALIDATE_IP)){
         Session::addMessageAfterRedirect(__('Invalid IP address', 'ipam'),
                                          false, ERROR);
         Html::back();
      }
      // check if cidr is valid
      if(!filter_var($_POST['cidr'], FILTER_VALIDATE_INT) || $_POST['cidr'] < 0 || $_POST['cidr'] > 32){
         Session::addMessageAfterRedirect(__('Invalid CIDR', 'ipam'),
                                          false, ERROR);
         Html::back();
      }

      // Change Begin IP & End IP to match CIDR
      $sub = new IPv4\SubnetCalculator($_POST['begin_ip'], $_POST['cidr']);
      [$_POST['begin_ip'], $_POST['end_ip']] = $sub->getIPAddressRange();

      $newID = $ipam->add($_POST);

   } else {
      Session::addMessageAfterRedirect(__('Problem when adding, required fields are not here', 'ipam'),
                                       false, ERROR);
   }
   if ($_SESSION['glpibackcreated']) {
      Html::redirect($ipam->getFormURL()."?id=".$newID);
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $ipam->check($_POST['id'], DELETE);
   $ipam->delete($_POST);
   $ipam->redirectToList();

} else if (isset($_POST["restore"])) {
   $ipam->check($_POST['id'], PURGE);
   $ipam->restore($_POST);
   $ipam->redirectToList();

} else if (isset($_POST["purge"])) {
   $ipam->check($_POST['id'], PURGE);
   $ipam->delete($_POST, 1);
   $ipam->redirectToList();

} else if (isset($_POST["update"])) {
   $ipam->check($_POST['id'], UPDATE);
   if (!empty($_POST["name"])
      && !empty($_POST["begin_ip"])
         && !empty($_POST["end_ip"])) {
      $ipam->update($_POST);
   } else {
      Session::addMessageAfterRedirect(__('Problem when adding, required fields are not here', 'ipam'),
                                       false, ERROR);
   }
   Html::back();

} else if (isset($_POST["search"])) {

   $ipam->checkGlobal(READ);
   Html::header(PluginIpamAddressing::getTypeName(2), '', "tools", "pluginipamaddressing");
   $ipam->display($_POST);
   Html::footer();
} else {
   $ipam->checkGlobal(READ);
   Html::header(PluginIpamAddressing::getTypeName(2), '', "tools", "pluginipamaddressing");
   $ipam->display($_GET);
   Html::footer();
}
