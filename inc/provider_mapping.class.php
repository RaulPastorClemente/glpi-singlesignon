<?php

/**
 * ---------------------------------------------------------------------
 * SingleSignOn is a plugin which allows to use SSO for auth
 * ---------------------------------------------------------------------
 * Copyright (C) 2022 Edgard
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @copyright Copyright © 2021 - 2022 Edgard
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/edgardmessias/glpi-singlesignon/
 * ---------------------------------------------------------------------
 */

class PluginSinglesignonProvider_Mapping extends CommonDBTM {

    static $rightname = 'config';

    public static function canCreate() {
        return static::canUpdate();
     }
  
     public static function canDelete() {
        return static::canUpdate();
     }
  
     public static function canPurge() {
        return static::canUpdate();
     }
  
     public static function canView() {
        return static::canUpdate();
     }

    static function getTypeName($nb = 0) {
        return __sso('Field Mapping');
    }

    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        return __sso('Field Mapping');
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        if ($item->getType() == 'PluginSinglesignonProvider') {
            $id = $item->getID();
            $provider_mapping = new self();
            $provider_mapping->showForm($id);
        }
    }

    function showForm($ID, $options = []) {
        global $DB;
        $this->showFormHeader();
        // fetch existing mappings for the provider
        $query = "SELECT * FROM glpi_plugin_singlesignon_providers_mappings WHERE plugin_singlesignon_providers_id = $ID";
        $result = $DB->query($query);
        if($result) {
            $mapping = $DB->fetchAssoc($result);
        } else {
            $mapping = [];
        }

        // display form fields
        echo "<form method='post' action=''>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td><input type='text' name='name' value='" . Html::cleanInputText($mapping['name'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __sso('Given Name') . "</td>";
        echo "<td><input type='text' name='given_name' value='" . Html::cleanInputText($mapping['given_name'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __sso('Family Name') . "</td>";
        echo "<td><input type='text' name='family_name' value='" . Html::cleanInputText($mapping['family_name'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Picture') . "</td>";
        echo "<td><input type='text' name='picture' value='" . Html::cleanInputText($mapping['picture'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Email') . "</td>";
        echo "<td><input type='text' name='email' value='" . Html::cleanInputText($mapping['email'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Locale') . "</td>";
        echo "<td><input type='text' name='locale' value='" . Html::cleanInputText($mapping['locale'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __sso('Phone Number') . "</td>";
        echo "<td><input type='text' name='phone_number' value='" . Html::cleanInputText($mapping['phone_number'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Group') . "</td>";
        echo "<td><input type='text' name='group' value='" . Html::cleanInputText($mapping['group'] ?? '') . "'></td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='4' class='center'>";
        echo "<input type='hidden' name='plugin_singlesignon_providers_id' value='" . Html::cleanInputText($ID) . "'>";
        echo "<input type='submit' name='update' class='submit' value=\"" . _sx('button', 'Save') . "\">";
        echo "</td></tr>";
        echo "</form>";

        return true;
    }
        
    function handleFormSubmission() {
        global $DB;
        $plugin_singlesignon_providers_id = $_POST['plugin_singlesignon_providers_id'];
        $name = $_POST['name'];
        $given_name = $_POST['given_name'];
        $family_name = $_POST['family_name'];
        $picture = $_POST['picture'];
        $email = $_POST['email'];
        $locale = $_POST['locale'];
        $phone_number = $_POST['phone_number'];
        $group = $_POST['group'];

        // check if mapping already exists (unique to provider)
        $query = "SELECT * FROM glpi_plugin_singlesignon_providers_mappings WHERE plugin_singlesignon_providers_id = $plugin_singlesignon_providers_id";
        $result = $DB->query($query);
        if($result) {
            $mapping = $DB->fetchAssoc($result);
        } else {
            $mapping = [];
        }

        if(empty($mapping)) {
            // create new mapping
            $query = "INSERT INTO glpi_plugin_singlesignon_providers_mappings (plugin_singlesignon_providers_id, name, given_name, family_name, picture, email, locale, phone_number, `group`, date_mod)
                    VALUES ($plugin_singlesignon_providers_id, '$name', '$given_name', '$family_name', '$picture', '$email', '$locale', '$phone_number', '$group', NOW())";
        } else {
            // update existing mapping
            $query = "UPDATE glpi_plugin_singlesignon_providers_mappings
                    SET name = '$name', given_name = '$given_name', family_name = '$family_name', picture = '$picture', email = '$email', locale = '$locale', phone_number = '$phone_number', `group` = '$group', date_mod = NOW()
                    WHERE plugin_singlesignon_providers_id = $plugin_singlesignon_providers_id";
        }

        $DB->query($query) or die("Error saving mapping: " . $DB->error());
    }
}