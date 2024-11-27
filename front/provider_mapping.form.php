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

use Glpi\Event;

include ('../../../inc/includes.php');

Session::checkRight("config", UPDATE);

if (!isset($_GET["id"])) {
   $_GET["id"] = "";
}

$mapping = new PluginSinglesignonProvider_Mapping();
if (isset($_POST["update"])) {
   if ($mapping->handleFormSubmission()) {
      Event::log($_GET["id"], "singlesignon", 4, "mapping",
            sprintf(__('%1$s updates the item %2$s'), $_SESSION["glpiname"], $_POST["name"]));
      Html::redirect($mapping->getLinkURL());
   }
   Html::back();
} else {
   if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
      Html::header(__sso('Field Mapping'), $_SERVER['PHP_SELF'], "config", "pluginsinglesignonprovider", "");
   } else {
      Html::helpHeader(__sso('Field Mapping'), $_SERVER['PHP_SELF']);
   }

   $mapping->display($_GET);
}

Html::footer();