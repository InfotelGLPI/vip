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

function plugin_vip_install() {
   global $DB;
   // Création de la table uniquement lors de la première installation
   if (!$DB->tableExists("glpi_plugin_vip_groups")) {
      $DB->runFile(GLPI_ROOT . "/plugins/vip/install/sql/empty-1.5.0.sql");
   }

   if ($DB->tableExists('glpi_plugin_vip_tickets')) {
      $tables = ["glpi_plugin_vip_tickets"];

      foreach ($tables as $table) {
         $DB->query("DROP TABLE IF EXISTS `$table`;");
      }
   }
   include_once(GLPI_ROOT . "/plugins/vip/inc/profile.class.php");
   PluginVipProfile::initProfile();
   PluginVipProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);

   return true;
}

function plugin_vip_uninstall() {
   global $DB;

   $tables = ["glpi_plugin_vip_profiles",
              "glpi_plugin_vip_groups",
              "glpi_plugin_vip_tickets"];

   foreach ($tables as $table)
      $DB->query("DROP TABLE IF EXISTS `$table`;");

   $tables_glpi = ["glpi_displaypreferences",
                   "glpi_documents_items",
                   "glpi_savedsearches",
                   "glpi_logs",
                   "glpi_items_tickets",
                   "glpi_contracts_items",
                   "glpi_notepads",
                   "glpi_dropdowntranslations"];

   foreach ($tables_glpi as $table_glpi)
      $DB->query("DELETE FROM `$table_glpi` WHERE `itemtype` LIKE 'PluginVip%';");

   //drop rules
   $Rule    = new Rule();
   $a_rules = $Rule->find(['sub_type' => 'PluginVipRuleVip']);
   foreach ($a_rules as $data) {
      $Rule->delete($data);
   }

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();
   foreach (PluginVipProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }

   PluginVipProfile::removeRightsFromSession();
   return true;
}

function plugin_vip_getPluginsDatabaseRelations() {

   $plugin = new Plugin();
   if ($plugin->isActivated("vip"))
      return [
         "glpi_groups" => ["glpi_plugin_vip_groups" => "id"]
      ];
   else
      return [];
}

function plugin_vip_getAddSearchOptions($itemtype) {

   $sopt = [];

   if (Session::getCurrentInterface() == 'central'
       && Session::haveRight('plugin_vip', READ)) {
      switch ($itemtype) {
         case 'Ticket':
            $rng1                         = 10100;
            $sopt[$rng1]['table']         = 'glpi_plugin_vip_groups';
            $sopt[$rng1]['field']         = 'isvip';
            $sopt[$rng1]['name']          = 'Vip';
            $sopt[$rng1]['datatype']      = 'bool';
            $sopt[$rng1]['massiveaction'] = false;
            break;
         case 'Group':
            $rng1                         = 150;
            $sopt[$rng1]['table']         = 'glpi_plugin_vip_groups';
            $sopt[$rng1]['field']         = 'isvip';
            $sopt[$rng1]['linkfield']     = 'id';
            $sopt[$rng1]['name']          = 'Vip';
            $sopt[$rng1]['datatype']      = 'bool';
            $sopt[$rng1]['massiveaction'] = false;
            break;
      }
   }

   return $sopt;
}

function plugin_vip_MassiveActions($type) {
   if ($type == 'Group') {
      $vip = new PluginVipGroup();
      return $vip->massiveActions();
   }
   return [];
}

function plugin_vip_addLeftJoin($type, $ref_table, $new_table, $linkfield, &$already_link_tables) {
   if ($ref_table == 'glpi_tickets') {
      switch ($new_table) {
         case "glpi_plugin_vip_groups" :
            $out = " LEFT JOIN `glpi_tickets_users` ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = " . CommonITILActor::REQUESTER . ") ";
            $out .= " LEFT JOIN `glpi_groups_users` ON (`glpi_tickets_users`.`users_id` = `glpi_groups_users`.`users_id`)";
            $out .= " LEFT JOIN `glpi_plugin_vip_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_plugin_vip_groups`.`id`)";

            return $out;
      }
   }

   return "";
}

function plugin_vip_giveItem($type, $ID, $data, $num) {
   global $CFG_GLPI, $DB;

   $searchopt = &Search::getOptions($type);
   $table     = $searchopt[$ID]["table"];
   $field     = $searchopt[$ID]["field"];
   switch ($type) {
      case 'Ticket':
         switch ($table . '.' . $field) {
            case "glpi_plugin_vip_groups.isvip" :
               if (PluginVipTicket::isTicketVip($data["id"])) {
                  return "<img src=\"" . $CFG_GLPI['root_doc'] . "/plugins/vip/pics/vip.png\" alt='vip' ><p style='display:none'>1</p>";
               }
               break;
         }
         break;
      case 'Group':
         switch ($table . '.' . $field) {
            case "glpi_plugin_vip_groups.isvip" :
               if ($data[$num][0]['name']) {
                  return "<img src=\"" . $CFG_GLPI['root_doc'] . "/plugins/vip/pics/vip.png\" alt='vip' ><p style='display:none'>1</p>";
               }
               break;
         }
         break;
   }

   return " ";
}

function plugin_vip_executeActions($options) {
   $vip = new PluginVipRuleVip();
   return $vip->executeActions($options['action'], $options['output'], $options['params']);
}
