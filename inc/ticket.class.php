<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 vip plugin for GLPI
 Copyright (C) 2009-2016 by the vip Development Team.

 https://github.com/InfotelGLPI/vip
 -------------------------------------------------------------------------

 LICENSE

 This file is part of vip.

 vip is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 vip is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with vip. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginVipTicket extends CommonDBTM {

   static $types = array('Ticket');

   static function isUserVip($uid) {
      global $DB;

      $vipquery = "SELECT count(*) AS nb
                   FROM glpi_groups_users
                   JOIN glpi_plugin_vip_groups
                     ON glpi_plugin_vip_groups.id = glpi_groups_users.groups_id
                   WHERE glpi_plugin_vip_groups.isvip = 1
                   AND glpi_groups_users.users_id = " . $uid;

      $vipresult = $DB->query($vipquery);
      $isvip     = mysqli_fetch_object($vipresult)->nb;

      if ($isvip) {
         return true;
      }
      return false;
   }

   static function isTicketVip($ticketid) {
      global $DB;

      $isvip = false;

      $userquery  = "SELECT users_id
                     FROM glpi_tickets_users
                     WHERE type = " . CommonITILActor::REQUESTER . "
                     AND tickets_id = " . $ticketid;
      $userresult = $DB->query($userquery);

      while ($uids = mysqli_fetch_object($userresult)) {
         foreach ($uids as $uid) {
            $isuservip = self::isUserVip($uid);
            if ($isuservip) {
               $isvip = true;
            }
         }
      }
      return $isvip;
   }

}
