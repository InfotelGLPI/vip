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

class PluginVipGroup extends CommonDBTM {

   static $rightname = "plugin_vip";

   /**
    * Configuration form
    * */
   function showForm($id, $options = array()) {

      $target = $this->getFormURL();
      if (isset($options['target'])) {
         $target = $options['target'];
      }

      if (!Session::haveRight("plugin_vip", READ)) {
         return false;
      }

      $canedit = Session::haveRight("plugin_vip", UPDATE);
      $prof    = new Profile();

      if ($id) {
         $this->getFromDB($id);
         $prof->getFromDB($id);
      }

      echo "<form action='" . $target . "' method='post'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2' class='center b'>" . __('VIP management', 'vip') . " : " . Dropdown::getDropdownName("glpi_groups", $this->fields["id"]);
      echo "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('VIP group', 'vip') . "</td><td>";
      Dropdown::showYesNo("isvip", $this->fields["isvip"]);
      echo "</td></tr>";

      if ($canedit) {
         echo "<tr class='tab_bg_2'>";
         echo "<td class='center' colspan='2'>";
         echo "<input type='hidden' name='id' value=$id>";
         echo "<input type='submit' name='update_vip_group' value='" . __('Update') . "' class='submit'>";
         echo "</td></tr>";
      }
      echo "</table>";

      Html::closeForm();
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Group'
          && Session::haveRight("plugin_vip", UPDATE)) {
         return __('VIP', 'vip');
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Group') {
         $grp = new self();
         $ID  = $item->getField('id');
         if (!$grp->getFromDB($ID)) {
            $grp->createVip($ID);
         }
         $grp->showForm($ID);
      }
      return true;
   }

   function createVip($ID) {

      $this->add(array('id' => $ID));
   }

   function getVipUsers() {

      $groups = $this->find("`isvip` = 1");
      if (isset($groups[0])) {
         unset($groups[0]);
      }

      $vip = array();
      if (count($groups) > 0) {
         $restrict = "`groups_id` IN (" . implode(',', array_keys($groups)) . ")";
         $managers = getAllDatasFromTable('glpi_groups_users', $restrict);


         foreach ($managers as $manager) {
            $vip[]['id'] = $manager['users_id'];
         }
      }

      return $vip;
   }

   /**
    * Massive actions available for infocom types
    * @return type
    */
   function massiveActions() {
      return array("PluginVipGroup:isvip" => __('Update') . " " . __('VIP group', 'vip'));
   }

   function getAddSearchOptions() {

      $sopt = array();

      if (Session::getCurrentInterface() == 'central' && Session::haveRight('plugin_vip', READ)) {
         $rng1                         = 150;
         $sopt[$rng1]['table']         = 'glpi_plugin_vip_groups';
         $sopt[$rng1]['field']         = 'isvip';
         $sopt[$rng1]['linkfield']     = 'id';
         $sopt[$rng1]['name']          = 'Vip';
         $sopt[$rng1]['datatype']      = 'bool';
         $sopt[$rng1]['massiveaction'] = false;
      }

      return $sopt;
   }

   /**
    * @see CommonDBTM::showMassiveActionsSubForm()
    * */
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      Dropdown::showYesNo('isvip');
      echo "<br><br>" . Html::submit(_x('button', 'Save'), array('name' => 'massiveaction'));
      return true;
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      $vip = new self();
      //We check if it's really a massive action of vip
      if (strpos($ma->getAction(), "plugin_vip_update") == -1) {
         $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
      } else {
         if ($vip->canCreate()) {
            $input = $ma->getInput();
            foreach ($ids as $id) {
               //Item has alreaddy
               if ($vip->getFromDB($id)) {
                  $update = array(
                     "id"    => $id,
                     "isvip" => $input['isvip']
                  );
                  $vip->update($update);
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               } else { //Item has no vip yet
                  $update = array(
                     "isvip" => $input['isvip']
                  );
                  $vip->add($update);
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
               }
            }
         } else {
            $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_NORIGHT);
         }
      }
      return $ma;
   }

}
