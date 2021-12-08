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

define('PLUGIN_VIP_VERSION', '1.8.0');

// Init the hooks of the plugins -Needed
function plugin_init_vip() {

   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['vip'] = true;

   Plugin::registerClass('PluginVipProfile', ['addtabon' => ['Profile']]);
   $PLUGIN_HOOKS['change_profile']['vip'] = ['PluginVipProfile', 'changeProfile'];

   if (Session::haveRight('plugin_vip', UPDATE)) {
      Plugin::registerClass('PluginVipGroup', ['addtabon' => ['Group']]);
      $PLUGIN_HOOKS['use_massive_action']['vip'] = 1;
      Plugin::registerClass('PluginVipTicket');
   }

   if (class_exists('PluginMydashboardMenu')) {
      $PLUGIN_HOOKS['mydashboard']['vip'] = ["PluginVipDashboard"];
   }

   if (Session::haveRight('plugin_vip', READ)
   && isset($_SESSION["glpiactiveprofile"]["interface"])
   && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
      $PLUGIN_HOOKS['add_javascript']['vip'][] = 'vip.js';
      $PLUGIN_HOOKS['javascript']['vip']       = [
          Plugin::getWebDir('vip') . "/vip.js",
      ];
      if (class_exists('PluginVipTicket')) {
         foreach (PluginVipTicket::$types as $item) {
            if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], strtolower($item) . ".form.php") !== false) {
               $PLUGIN_HOOKS['add_javascript']['vip'][] = 'vip_load_scripts.js';
               $PLUGIN_HOOKS['javascript']['vip']       = [
                  Plugin::getWebDir('vip') . "/vip_load_scripts.js",
               ];
            }
         }
      }
   }
   if (isset($_SESSION["glpiactiveprofile"]["interface"])
   && $_SESSION["glpiactiveprofile"]["interface"] != "helpdesk") {
      $PLUGIN_HOOKS['pre_show_item']['vip'] = ['PluginVipTicket', 'showVIPInfos'];
   }
   $PLUGIN_HOOKS['item_add']['vip']    = ['User' => ['PluginVipVip', 'afterAdd']];
   $PLUGIN_HOOKS['item_update']['vip'] = ['User' => ['PluginVipVip', 'afterUpdate']];

   Plugin::registerClass('PluginVipRuleVipCollection', [
       'rulecollections_types' => true
   ]);
}

function plugin_version_vip() {

   return ['name'           => "VIP",
           'version'        => PLUGIN_VIP_VERSION,
           'author'         => 'Probesys & <a href="http://blogglpi.infotel.com">Infotel</a>',
           'license'        => 'GPLv2+',
           'homepage'       => 'https://github.com/InfotelGLPI/vip',
           'requirements'   => [
              'glpi' => [
                 'min' => '10.0',
                 'max' => '11.0',
                 'dev' => false
              ]
           ]
   ];
}
