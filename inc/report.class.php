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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginIpamReport
 */
class PluginIpamReport extends CommonDBTM {
   static $rightname = "plugin_ipam";

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
            $class = " class='tab_bg_2' ";
            if ($odd) {
               switch ($odd) {
                  case "double" : //double
                     $class = " class='plugin_ipam_ip_double'";
                     break;

                  case "free" : //free
                     $class = " class='plugin_ipam_ip_free'";
                     break;

                  case "reserved" : //free
                     $class = " class='plugin_ipam_ip_reserved'";
                     break;

                  case "ping_on" : //ping_on
                     $class = " class='plugin_ipam_ping_on'";
                     break;

                  case "ping_off" : //ping_off
                     $class = " class='plugin_ipam_ping_off'";
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
    * @param $PluginIpamAddressing
    *
    * @return int
    */
   function displayReport(&$result, $PluginIpamAddressing) {
      global $CFG_GLPI;

      

      // Get config
      $PluginIpamConfig = new PluginIpamConfig();
      $PluginIpamConfig->getFromDB('1');
      $system = $PluginIpamConfig->fields["used_system"];

      // Set display type for export if define
      $output_type = Search::HTML_OUTPUT;

      if (isset($_GET["display_type"])) {
         $output_type = $_GET["display_type"];
      }

      $header_num    = 1;
      $nbcols        = 8;
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
      /*if ($ping == 1) {
         echo Search::showHeaderItem($output_type, __('Ping result', 'ipam'), $header_num);
      }*/
      echo Search::showHeaderItem($output_type, __('Reservation', 'ipam'), $header_num);
      echo Search::showHeaderItem($output_type, __('Comments'), $header_num);
      echo Search::showHeaderItem($output_type, "", $header_num);
      echo Search::showEndLine($output_type);

      $user = new User();

      foreach ($result as $num => $lines) {
         $ip = self::string2ip(substr($num, 2));

         if (count($lines)) {
            if (count($lines) > 1) {
               $disp = $PluginIpamAddressing->fields["double_ip"];
            } else {
               $disp = $PluginIpamAddressing->fields["alloted_ip"];
            }
            if ($disp) {
               foreach ($lines as $line) {
                  $row_num++;
                  $item_num = 1;
                  $name     = $line["dname"];
                  $namep    = $line["pname"];
                  // IP
                  if ($PluginIpamAddressing->fields["reserved_ip"] && strstr($line["pname"],
                                                                                   "reserv")) {
                     echo $this->displaySearchNewLine($output_type, "reserved");
                  } else {
                     echo $this->displaySearchNewLine($output_type,
                        (count($lines) > 1 ? "double" : $row_num % 2));
                  }
                  $rand = mt_rand();
                  $params = ['ip' => trim($ip),
                             'width'         => 450,
                             'height'        => 300,
                             'dialog_class'  => 'modal-sm'];
                  
                  /*echo Search::showItem($output_type, "$ping_link ", $item_num, $row_num, "class='center'");
                  if (isset($params) && count($params) > 0) {
                     echo Ajax::createIframeModalWindow('ping' . $rand,
                                                        PLUGIN_IPAM_WEBDIR . "/ajax/addressing.php?action=ping&ip=" . $params['ip'],
                                                        ['title' => __s('IP ping', 'ipam'),
                                                         'display' => false]);
                  }*/
                  echo Search::showItem($output_type, $ip, $item_num, $row_num);

                  // Device
                  $item = new $line["itemtype"]();
                  $link = Toolbox::getItemTypeFormURL($line["itemtype"]);
                  if ($line["itemtype"] != 'NetworkEquipment') {
                     if ($item->canView()) {
                        $output_iddev = "<a href='" . $link . "?id=" . $line["on_device"] . "'>" . $name .
                                        (empty($name) || $_SESSION["glpiis_ids_visible"] ? " (" . $line["on_device"] . ")" : "") . "</a>";
                     } else {
                        $output_iddev = $name . (empty($name) || $_SESSION["glpiis_ids_visible"] ? " (" . $line["on_device"] . ")" : "");
                     }
                  } else {
                     if ($item->canView()) {
                        if (empty($namep)) {
                           $linkp = '';
                        } else {
                           $linkp = $namep . " - ";
                        }
                        $output_iddev = "<a href='" . $link . "?id=" . $line["on_device"] . "'>" . $linkp . $name .
                                        (empty($name) || $_SESSION["glpiis_ids_visible"] ? " (" . $line["on_device"] . ")" : "") . "</a>";
                     } else {
                        $output_iddev = $namep . " - " . $name . (empty($name) || $_SESSION["glpiis_ids_visible"] ? " (" . $line["on_device"] . ")" : "");
                     }
                  }
                  echo Search::showItem($output_type, $output_iddev, $item_num, $row_num);

                  // User
                  if ($line["users_id"] && $user->getFromDB($line["users_id"])) {
                     $dbu      = new DbUtils();
                     $username = $dbu->formatUserName($user->fields["id"], $user->fields["name"],
                                                      $user->fields["realname"], $user->fields["firstname"]);

                     if ($user->canView()) {
                        $output_iduser = "<a href='" . $CFG_GLPI["root_doc"] . "/front/user.form.php?id=" .
                                         $line["users_id"] . "'>" . $username . "</a>";
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
                        $output_mac = "<a href='" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php?id=" .
                                      $line["id"] . "'>" . $line["mac"] . "</a>";
                     } else {
                        $output_mac = $line["mac"];
                     }
                     echo Search::showItem($output_type, $output_mac, $item_num, $row_num);
                  } else {
                     echo Search::showItem($output_type, " ", $item_num, $row_num);
                  }
                  // Type
                  echo Search::showItem($output_type, $item::getTypeName(), $item_num, $row_num);

                  // Ping
                  
                  echo Search::showItem($output_type, " ", $item_num, $row_num, "style='background-color:#e0e0e0' class='center'");
                  

                   $rand    = mt_rand();
                   $comment = new PluginIpamIpcomment();
                   $comment->getFromDBByCrit(['ipname' => $num, 'plugin_ipam_addressings_id' => $PluginIpamAddressing->getID()]);
                   $comments = $comment->fields['comments'] ?? '';
                   $comments = Toolbox::stripslashes_deep($comments);
                   //                  echo Search::showItem($output_type, '<textarea id="comment'.$num.'"
                   //                      rows="5" cols="33">'.$comments.'</textarea>', $item_num, $row_num, "style='background-color:#e0e0e0' class='center' onChange='updateFA$rand()'");
                   if ($output_type == Search::HTML_OUTPUT) {
                       echo Search::showItem($output_type, '<input type="text" id="comment' . $num . '" 
                      value="' . $comments . '">', $item_num, $row_num, "style='background-color:#e0e0e0' class='center' onChange='updateFA$rand()'");
                   } else {
                       echo Search::showItem($output_type, $comments, $item_num, $row_num);
                   }
                   if ($output_type == Search::HTML_OUTPUT) {
                       echo Search::showItem($output_type, '<i id="save' . $num . '" class="fas fa-save fa-2x center pointer" style="color:forestgreen"></i>', $item_num, $row_num, "style='background-color:#e0e0e0' class='center' onClick='updateComment$rand()'");
                       echo "<script>
                    
                       
                      function updateComment$rand() {
                          
                          $('#ajax_loader').show();
                          $.ajax({
                             url: '" . $CFG_GLPI["root_doc"] . PLUGIN_IPAM_DIR_NOFULL . "/ajax/ipcomment.php',
                                type: 'POST',
                                data:
                                  {
                                    addressing_id:" . $PluginIpamAddressing->getID() . ",
                                    ipname: \"" . $num . "\",
                                    contentC: $('#comment" . $num . "').val(),
                    
                                  },
                                success: function(response){
                                    $('#save" . $num . "').css('color','');
                                    $('#save" . $num . "').css('color','forestgreen');
                                    $('#ajax_loader').hide();
                                    
                                 },
                                error: function(xhr, status, error) {
                                   console.log(xhr);
                                   console.log(status);
                                   console.log(error);
                                 } 
                             });
                       };
                       
                      function updateFA$rand() {
                          $('#save" . $num . "').css('color','');
                          $('#save" . $num . "').css('color','orange');
    
                       };
                     </script>";
                   }
                   //                  echo '<td><textarea id="tre" name="story"
                   //          rows="5" cols="33"></textarea></td>';
                  // End
                  echo Search::showEndLine($output_type);
               }
            }

         } else if ($PluginIpamAddressing->fields["free_ip"]) {
            $row_num++;
            $item_num = 1;
            $content  = "";

            $rand   = mt_rand();
            $params = ['id_addressing' => $PluginIpamAddressing->getID(),
                       'ip'            => trim($ip),
                       //               'root_doc' => $CFG_GLPI['root_doc'],
                       'rand'          => $rand,
                       //               'width' => 1000,
                       //               'height' => 550
            ];

            echo $this->displaySearchNewLine($output_type, "free");
            $rand = mt_rand();
            $params = ['ip' => trim($ip),
                        'width'         => 450,
                        'height'        => 300,
                        'dialog_class'  => 'modal-sm'];
            
            echo Search::showItem($output_type, $ip, $item_num, $row_num);
            echo Search::showItem($output_type, " ", $item_num, $row_num);
            echo Search::showItem($output_type, " ", $item_num, $row_num);

               $rand    = mt_rand();
               $comment = new PluginIpamIpcomment();
               $comment->getFromDBByCrit(['ipname' => $num, 'plugin_ipam_addressings_id' => $PluginIpamAddressing->getID()]);
               $comments = $comment->fields['comments'] ?? '';
               $comments = Toolbox::stripslashes_deep($comments);

            if ($output_type == Search::HTML_OUTPUT) {
               $content = "";
               $params = ['id_addressing' => $PluginIpamAddressing->getID(),
                           'ip' => trim($ip),
                           //                                 'root_doc' => $CFG_GLPI['root_doc'],
                           'rand' => $rand,
                           //                                 'width' => 1000,
                           //                                 'height' => 550
               ];
               $reserv  = "<a href=\"#\" data-bs-toggle='modal' data-bs-target='#reservation$rand'><i class='fas fa-clipboard fa-2x pointer' style='color: #d56f15' title='" . __("Reserve IP", 'ipam') . "'></i></a>";
               if (isset($params) && count($params) > 0) {
                  echo Ajax::createIframeModalWindow('reservation'.$rand,
                                                      PLUGIN_IPAM_WEBDIR . "/ajax/addressing.php?action=showForm&ip=" . $params['ip'] . "&id_addressing=" . $params['id_addressing'] . "&rand=" . $params['rand'],
                                                      ['title'   => __s('IP reservation', 'ipam'),
                                                      'display' => false, 'reloadonclose' => true]);
               }
            } else {
               $content = "";
               $reserv  = "";
            }
            echo Search::showItem($output_type, " ", $item_num, $row_num);
            echo Search::showItem($output_type, " ", $item_num, $row_num);
            echo Search::showItem($output_type, "$reserv ", $item_num, $row_num, "style='background-color:#e0e0e0' class='center'");

               $rand    = mt_rand();
               $comment = new PluginIpamIpcomment();
               $comment->getFromDBByCrit(['ipname' => $num, 'plugin_ipam_addressings_id' => $PluginIpamAddressing->getID()]);
               $comments = $comment->fields['comments'] ?? '';
               $comments = Toolbox::stripslashes_deep($comments);
               //               echo Search::showItem($output_type, '<textarea id="comment'.$num.'"
               //                      rows="5" cols="33">'.$comments.'</textarea>', $item_num, $row_num, "style='background-color:#e0e0e0' class='center' onChange='updateFA$rand()'");
               if ($output_type == Search::HTML_OUTPUT) {
                  echo Search::showItem($output_type, '<input type="text" id="comment' . $num . '" 
                     value="' . $comments . '">', $item_num, $row_num, "style='background-color:#e0e0e0' class='center' onChange='updateFA$rand()'");
               } else {
                  echo Search::showItem($output_type, $comments, $item_num, $row_num);
               }
               if ($output_type == Search::HTML_OUTPUT) {
                  echo Search::showItem($output_type, '<i id="save' . $num . '" class="fas fa-save fa-2x center pointer" style="color:forestgreen"></i>', $item_num, $row_num, "style='background-color:#e0e0e0' class='center' onClick='updateComment$rand()'");
                  echo "<script>
                  
                     
                     function updateComment$rand() {
                        
                        $('#ajax_loader').show();
                        $.ajax({
                           url: '" . $CFG_GLPI["root_doc"] . PLUGIN_IPAM_DIR_NOFULL . "/ajax/ipcomment.php',
                              type: 'POST',
                              data:
                                 {
                                 addressing_id:" . $PluginIpamAddressing->getID() . ",
                                 ipname: \"" . $num . "\",
                                 contentC: $('#comment" . $num . "').val(),
                  
                                 },
                              success: function(response){
                                 $('#save" . $num . "').css('color','');
                                 $('#save" . $num . "').css('color','forestgreen');
                                 $('#ajax_loader').hide();
                                 
                              },
                              error: function(xhr, status, error) {
                                 console.log(xhr);
                                 console.log(status);
                                 console.log(error);
                              } 
                           });
                     };
                     
                     function updateFA$rand() {
                        $('#save" . $num . "').css('color','');
                        $('#save" . $num . "').css('color','orange');
   
                     };
                  </script>";
               }
            echo Search::showEndLine($output_type);
         
         }
      }

      if ($output_type == Search::HTML_OUTPUT) {
         //div for the modal
         echo "<div id=\"plugaddr_form\"  style=\"display:none;text-align:center\"></div>";
      }
      // Display footer
      echo Search::showFooter($output_type, $PluginIpamAddressing->getTitle());

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

   static function ip2string($s) {
      if ($s > PHP_INT_MAX) {
         $s = 2 * PHP_INT_MIN + $s;
      }
      return ip2long($s);
   }
}
