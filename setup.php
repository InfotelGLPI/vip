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

function plugin_version_vip() {

   return array('name'           => "VIP",
                'version'        => '1.5.1',
                'author'         => 'Probesys & Infotel',
                'license'        => 'GPLv2+',
                'homepage'       => 'https://github.com/InfotelGLPI/vip',
                'minGlpiVersion' => '9.3');
}

function plugin_vip_check_prerequisites() {

   if (version_compare(GLPI_VERSION, '9.3', 'lt')) {
      echo "This plugin requires GLPI >= 9.3";
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded
//may display messages or add to message after redirect
function plugin_vip_check_config() {
   return true;
}

function plugin_init_vip() {

   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['vip'] = true;

   Plugin::registerClass('PluginVipProfile', array('addtabon' => array('Profile')));
   $PLUGIN_HOOKS['change_profile']['vip'] = array('PluginVipProfile', 'changeProfile');

   if (Session::haveRight('plugin_vip', UPDATE)) {
      Plugin::registerClass('PluginVipGroup', array('addtabon' => array('Group')));
      $PLUGIN_HOOKS['use_massive_action']['vip'] = 1;
      Plugin::registerClass('PluginVipTicket');
   }

   if (class_exists('PluginMydashboardMenu')) {
      $PLUGIN_HOOKS['mydashboard']['vip'] = array("PluginVipDashboard");
   }

   if (Session::haveRight('plugin_vip', READ)) {
      $PLUGIN_HOOKS['add_javascript']['vip'][] = 'vip.js';
      $PLUGIN_HOOKS['javascript']['vip']       = array(
         "/plugins/vip/vip.js",
      );
      if (class_exists('PluginVipTicket')) {
         foreach (PluginVipTicket::$types as $item) {
            if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], strtolower($item) . ".form.php") !== false) {
               $PLUGIN_HOOKS['add_javascript']['vip'][] = 'vip_load_scripts.js';
               $PLUGIN_HOOKS['javascript']['vip']       = array(
                  "/plugins/vip/vip_load_scripts.js",
               );
            }
         }
      }
   }

   $PLUGIN_HOOKS['item_add']['vip']    = array('User' => array('PluginVipVip', 'afterAdd'));
   $PLUGIN_HOOKS['item_update']['vip'] = array('User' => array('PluginVipVip', 'afterUpdate'));

   Plugin::registerClass('PluginVipRuleVipCollection', array(
      'rulecollections_types' => true
   ));
}
