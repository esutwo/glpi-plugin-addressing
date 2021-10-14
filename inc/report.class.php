<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 addressing plugin for GLPI
 Copyright (C) 2009-2016 by the addressing Development Team.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginAddressingReport
 */
class PluginAddressingReport extends CommonDBTM {

   /**
    * @param      $type
    * @param bool $odd
    *
    * @return string
    */
   function displaySearchNewLine($type, $odd = false) {

      $out = "";
      switch ($type) {
         case Search::PDF_OUTPUT_LANDSCAPE : //pdf
         case Search::PDF_OUTPUT_PORTRAIT :
            global $PDF_TABLE;
            $style = "";
            if ($odd) {
               $style = " style=\"background-color:#DDDDDD;\" ";
            }
            $PDF_TABLE .= "<tr nobr=\"true\" $style>";
            break;

         case Search::SYLK_OUTPUT : //sylk
            //       $out="\n";
            break;

         case Search::CSV_OUTPUT : //csv
            //$out="\n";
            break;

         default :
            $class=" class='tab_bg_2' ";
            if ($odd) {
               switch ($odd) {
                  case "double" : //double
                     $class = " class='plugin_addressing_ip_double'";
                     break;

                  case "free" : //free
                     $class = " class='plugin_addressing_ip_free'";
                     break;

                  case "reserved" : //free
                     $class = " class='plugin_addressing_ip_reserved'";
                     break;

                  case "ping_on" : //ping_on
                     $class = " class='plugin_addressing_ping_on'";
                     break;

                  case "ping_off" : //ping_off
                     $class = " class='plugin_addressing_ping_off'";
                     break;

                  default :
                     $class = " class='tab_bg_1' ";
               }
            }
            $out = "<tr $class>";
            break;
      }
      return $out;
   }


   /**
    * @param $result
    * @param $PluginAddressingAddressing
    *
    * @return int
    */
   function displayReport(&$result, $PluginAddressingAddressing) {
      global $CFG_GLPI;

      $ping = $PluginAddressingAddressing->fields["use_ping"];

      // Get config
      $PluginAddressingConfig = new PluginAddressingConfig();
      $PluginAddressingConfig->getFromDB('1');
      $system                 = $PluginAddressingConfig->fields["used_system"];

      // Set display type for export if define
      $output_type = Search::HTML_OUTPUT;

      if (isset($_GET["display_type"])) {
         $output_type = $_GET["display_type"];
      }

      $header_num    = 1;
      $nbcols        = 6;
      $ping_response = 0;
      $row_num       = 1;

      // Column headers
      echo Search::showHeader($output_type, 1, $nbcols, 1);
      echo $this->displaySearchNewLine($output_type);
      echo Search::showHeaderItem($output_type, __('IP'), $header_num);
      echo Search::showHeaderItem($output_type, __('Connected to'), $header_num);
      echo Search::showHeaderItem($output_type, _n('User', 'Users', 1), $header_num);
      echo Search::showHeaderItem($output_type, __('MAC address'), $header_num);
      echo Search::showHeaderItem($output_type, __('Item type'), $header_num);
      echo Search::showHeaderItem($output_type, __('Free Ip', 'addressing'), $header_num);
      echo Search::showEndLine($output_type);

      $user = new User();

      foreach ($result as $num => $lines) {
         $ip = self::string2ip(substr($num, 2));

         if (count($lines)) {
            if (count($lines) > 1) {
               $disp = $PluginAddressingAddressing->fields["double_ip"];
            } else {
               $disp = $PluginAddressingAddressing->fields["alloted_ip"];
            }
            if ($disp) {
               foreach ($lines as $line) {
                  $row_num++;
                  $item_num = 1;
                  $name     = $line["dname"];
                  $namep    = $line["pname"];
                  // IP
                  if ($PluginAddressingAddressing->fields["reserved_ip"] && strstr($line["pname"],
                                                                                   "reserv")) {
                     echo $this->displaySearchNewLine($output_type, "reserved");
                  } else {
                     echo $this->displaySearchNewLine($output_type,
                        (count($lines) > 1 ? "double" : $row_num % 2));
                  }
                  echo Search::showItem($output_type, $ip, $item_num, $row_num);

                  // Device
                  $item = new $line["itemtype"]();
                  $link = Toolbox::getItemTypeFormURL($line["itemtype"]);
                  if ($line["itemtype"] != 'NetworkEquipment') {
                     if ($item->canView()) {
                        $output_iddev = "<a href='".$link."?id=".$line["on_device"]."'>".$name.
                           (empty($name) || $_SESSION["glpiis_ids_visible"] ? " (".$line["on_device"].")" : "")."</a>";
                     } else {
                        $output_iddev = $name.(empty($name) || $_SESSION["glpiis_ids_visible"] ? " (".$line["on_device"].")" : "");
                     }
                  } else {
                     if ($item->canView()) {
                        if (empty($namep)) {
                           $linkp = '';
                        } else {
                           $linkp = $namep." - ";
                        }
                        $output_iddev = "<a href='".$link."?id=".$line["on_device"]."'>".$linkp.$name.
                           (empty($name) || $_SESSION["glpiis_ids_visible"] ? " (".$line["on_device"].")" : "")."</a>";
                     } else {
                        $output_iddev = $namep." - ".$name.(empty($name) || $_SESSION["glpiis_ids_visible"] ? " (".$line["on_device"].")" : "");
                     }
                  }
                  echo Search::showItem($output_type, $output_iddev, $item_num, $row_num);

                  // User
                  if ($line["users_id"] && $user->getFromDB($line["users_id"])) {
                     $dbu = new DbUtils();
                     $username = $dbu->formatUserName($user->fields["id"], $user->fields["name"],
                                                      $user->fields["realname"], $user->fields["firstname"]);

                     if ($user->canView()) {
                        $output_iduser = "<a href='".$CFG_GLPI["root_doc"]."/front/user.form.php?id=".
                           $line["users_id"]."'>".$username."</a>";
                     } else {
                        $output_iduser = $username;
                     }
                     echo Search::showItem($output_type, $output_iduser, $item_num, $row_num);
                  } else {
                     echo Search::showItem($output_type, " ", $item_num, $row_num);
                  }

                  // Mac
                  if ($line["id"]) {
                     if ($item->canView()) {
                        $output_mac = "<a href='".$CFG_GLPI["root_doc"]."/front/networkport.form.php?id=".
                           $line["id"]."'>".$line["mac"]."</a>";
                     } else {
                        $output_mac = $line["mac"];
                     }
                     echo Search::showItem($output_type, $output_mac, $item_num, $row_num);
                  } else {
                     echo Search::showItem($output_type, " ", $item_num, $row_num);
                  }
                  // Type
                  echo Search::showItem($output_type, $item::getTypeName(), $item_num, $row_num);

                  // Reserved
                  if ($PluginAddressingAddressing->fields["reserved_ip"] && strstr($line["pname"], "reserv")) {
                     echo Search::showItem($output_type, __('Reserved Address', 'addressing'), $item_num, $row_num);
                  } else {
                     echo Search::showItem($output_type, " ", $item_num, $row_num);
                  }

                  // End
                  echo Search::showEndLine($output_type);
               }
            }

         } else if ($PluginAddressingAddressing->fields["free_ip"]) {
            $row_num++;
            $item_num = 1;
            $content  = "";

            $rand   = mt_rand();
            $params = ['id_addressing' => $PluginAddressingAddressing->getID(),
                       'ip'            => trim($ip),
                       'root_doc'      => $CFG_GLPI['root_doc'],
                       'rand'          => $rand,
                       'width'         => 1000,
                       'height'        => 550];

            if (!$ping) {
               echo $this->displaySearchNewLine($output_type, "free");
               echo Search::showItem($output_type, $ip, $item_num, $row_num);
               echo Search::showItem($output_type, " ", $item_num, $row_num);

               if ($output_type == Search::HTML_OUTPUT) {
                  $content = "<a href=\"#\" onClick='plugaddr_loadForm(\"showForm\", \"plugaddr_form\", 
                  " . json_encode($params) . ");'> " . __("Reserve") . "</a>";
               } else {
                  $content = "";
               }
            } else {
               if ($output_type == Search::HTML_OUTPUT) {
                  Html::glpi_flush();
               }

               $plugin_addressing_pinginfo = new PluginAddressingPinginfo();
               if($plugin_addressing_pinginfo->getFromDBByCrit(['plugin_addressing_addressings_id'=>$PluginAddressingAddressing->getID(),'ipname'=>$num])) {
                  $ping_value = $plugin_addressing_pinginfo->fields['ping_response'];
               } else {
                  $ping_value = $this->ping($system, $ip);
                  $data = [];
                  $data['plugin_addressing_addressings_id'] = $PluginAddressingAddressing->getID();
                  $data['ipname'] = $num;
                  $data['ping_response'] = $ping_value;
                  $data['ping_date'] = date('Y-m-d H:i:s');;
                  $plugin_addressing_pinginfo->add($data);
               }
               $plugin_addressing_pinginfo->getFromDBByCrit(['plugin_addressing_addressings_id'=>$PluginAddressingAddressing->getID(),'ipname'=>$num]);
               if ($ping_value) {
                  $ping_response++;
                  echo $this->displaySearchNewLine($output_type, "ping_off");
                  echo Search::showItem($output_type, $ip, $item_num, $row_num);
                  echo Search::showItem($output_type, __('Ping: got a response - used Ip', 'addressing'),
                                        $item_num, $row_num);
                  $content = __("last ping : ",'addressing').Html::convDateTime($plugin_addressing_pinginfo->fields['ping_date']);
               } else {
                  echo $this->displaySearchNewLine($output_type, "ping_on");
                  echo Search::showItem($output_type, $ip, $item_num, $row_num);
                  echo Search::showItem($output_type, __('Ping: no response - free Ip', 'addressing'),
                                        $item_num, $row_num);
                  if ($output_type == Search::HTML_OUTPUT) {
                     $content = __("last ping : ",'addressing').Html::convDateTime($plugin_addressing_pinginfo->fields['ping_date'])." <br/><a href=\"#\" onClick='plugaddr_loadForm(\"showForm\", \"plugaddr_form\", 
                     " . json_encode($params) . ");'> " . __("Reserve") . "</a>";

                  } else {
                     $content = "";
                  }
               }
            }
            echo Search::showItem($output_type, " ", $item_num, $row_num);
            echo Search::showItem($output_type, " ", $item_num, $row_num);
            echo Search::showItem($output_type, " ", $item_num, $row_num);
            echo Search::showItem($output_type, "$content ", $item_num, $row_num);
            echo Search::showEndLine($output_type);
         }
      }
      if ($output_type == Search::HTML_OUTPUT) {
         //div for the modal
         echo "<div id=\"plugaddr_form\"  style=\"display:none;text-align:center\"></div>";
      }
      // Display footer
      echo Search::showFooter($output_type, $PluginAddressingAddressing->getTitle());

      return $ping_response;
   }

   /**
    * Converts an (IPv4) Internet network address into a string in Internet standard dotted format
    * @link http://php.net/manual/en/function.long2ip.php
    * problem with 32-bit architectures: https://bugs.php.net/bug.php?id=74417&edit=1
    *
    * @param $s
    *
    * @return string
    */
   static function string2ip($s) {
      if ($s > PHP_INT_MAX) {
         $s = 2 * PHP_INT_MIN + $s;
      }
      return long2ip($s);
   }

   /**
    * @param $system
    * @param $ip
    *
    * @return bool
    */
   function ping($system, $ip) {

      $list ='';
      switch ($system) {
         case 0 :
            // linux ping
             exec("ping -c 1 -w 100 ".$ip, $list);
            $nb = count($list);
            if (isset($nb)) {
               for ($i=0; $i<$nb; $i++) {
                  if (strpos($list[$i], "ttl=")>0) {
                     return true;
                  }
               }
            }
            break;

         case 1 :
            //windows
            exec("ping.exe -n 1 -w 100 -i 64 ".$ip, $list);
            $nb = count($list);
            if (isset($nb)) {
               for ($i=0; $i<$nb; $i++) {
                  if (strpos($list[$i], "TTL")>0) {
                     return true;
                  }
               }
            }
            break;

         case 2 :
            //linux fping
            exec("fping -r1 -c1 -t100 ".$ip, $list);
            $nb = count($list);
            if (isset($nb)) {
               for ($i=0; $i<$nb; $i++) {
                  if (strpos($list[$i], "bytes")>0) {
                     return true;
                  }
               }
            }
            break;

         case 3 :
            // *BSD ping
            exec("ping -c 1 -W 1 ".$ip, $list);
            $nb = count($list);
            if (isset($nb)) {
               for ($i=0; $i<$nb; $i++) {
                  if (strpos($list[$i], "ttl=")>0) {
                     return true;
                  }
               }
            }
            break;

         case 4 :
            // MacOSX ping
            exec("ping -c 1 -t 1 ".$ip, $list);
            $nb = count($list);
            if (isset($nb)) {
               for ($i=0; $i<$nb; $i++) {
                  if (strpos($list[$i], "ttl=")>0) {
                     return true;
                  }
               }
            }
            break;
      }
   }
}
