<?php

use Glpi\Event;

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

class PluginSinglesignonProvider extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;
   static $rightname = 'config';

   /**
    * @var array
    */
   static $default = null;

   /**
    *
    * @var string
    */
   protected $_code = null;

   /**
    *
    * @var null|string
    */
   protected $_token = null;

   /**
    *
    * @var null|array
    */
   protected $_resource_owner = null;

   public $debug = false;

   private $mappings = [];
   private $user_data = [];

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

   // Should return the localized name of the type
   static function getTypeName($nb = 0) {
      return __sso('Single Sign-on Provider');
   }

   /**
    * @see CommonGLPI::getMenuName()
    * */
   static function getMenuName() {
      return __sso('Single Sign-on');
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      // Add a custom tab for fields mapping
      $this->addStandardTab('PluginSinglesignonProvider_Mapping', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
  }

   function post_getEmpty() {
      $this->fields["type"] = 'generic';
      $this->fields["is_active"] = 1;
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      if (empty($this->fields["type"])) {
         $this->fields["type"] = 'generic';
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      echo Html::input("name", ['value' => $this->fields["name"], 'class' => 'form-control']);
      echo "</td>";
      echo "<td>" . __('Comments') . "</td>";
      echo "<td>";
      echo "<textarea name='comment' >" . $this->fields["comment"] . "</textarea>";
      echo "</td></tr>";

      $on_change = 'var _value = this.options[this.selectedIndex].value; $(".sso_url").toggle(_value == "generic");';

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso('SSO Type') . "</td><td>";
      self::dropdownType('type', ['value' => $this->fields["type"], 'on_change' => $on_change]);
      echo "<td>" . __('Active') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("is_active", $this->fields["is_active"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso('Client ID') . "</td>";
      echo "<td><input type='text' style='width:96%' name='client_id' value='" . $this->fields["client_id"] . "'></td>";
      echo "<td>" . __sso('Client Secret') . "</td>";
      echo "<td><input type='text' style='width:96%' name='client_secret' value='" . $this->fields["client_secret"] . "'></td>";
      echo "</tr>\n";

      $url_style = "";

      if ($this->fields["type"] != 'generic') {
         $url_style = 'style="display: none;"';
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso('Scope') . "</td>";
      echo "<td><input type='text' style='width:96%' name='scope' value='" . $this->getScope() . "'></td>";
      echo "<td>" . __sso('Extra Options') . "</td>";
      echo "<td><input type='text' style='width:96%' name='extra_options' value='" . $this->fields["extra_options"] . "'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1 sso_url' $url_style>";
      echo "<td>" . __sso('Authorize URL') . "</td>";
      echo "<td colspan='3'><input type='text' style='width:96%' name='url_authorize' value='" . $this->getAuthorizeUrl() . "'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1 sso_url' $url_style>";
      echo "<td>" . __sso('Access Token URL') . "</td>";
      echo "<td colspan='3'><input type='text' style='width:96%' name='url_access_token' value='" . $this->getAccessTokenUrl() . "'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1 sso_url' $url_style>";
      echo "<td>" . __sso('Resource Owner Details URL') . "</td>";
      echo "<td colspan='3'><input type='text' style='width:96%' name='url_resource_owner_details' value='" . $this->getResourceOwnerDetailsUrl() . "'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso('Logout URL') . "</td>";
      echo "<td colspan='3'><input type='text' style='width:96%' name='url_logout' value='" . $this->fields["url_logout"] . "'></td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso('Is Default', 'singlesignon') . "</td><td>";
      Dropdown::showYesNo("is_default", $this->fields["is_default"]);
      echo "<td>" . __sso('PopupAuth') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("popup", $this->fields["popup"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso('SplitDomain') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("split_domain", $this->fields["split_domain"]);
      echo "</td>";
      echo "<td>" . __sso('AuthorizedDomains');
      echo "&nbsp;";
      Html::showToolTip(nl2br(__sso('AuthorizedDomainsTooltip')));
      echo "</td>";
      echo "<td><input type='text' style='width:96%' name='authorized_domains' value='" . $this->fields["authorized_domains"] . "'></td>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso("Use Email as Login") . "<td>";
      Dropdown::showYesNo("use_email_for_login", $this->fields["use_email_for_login"]);
      echo "</td>";
      echo "<td>" . __sso('Split Name') . "<td>";
      Dropdown::showYesNo("split_name", $this->fields["split_name"]);
      echo "</td>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __sso('Use Single Logout') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("use_single_logout", $this->fields["use_single_logout"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<th colspan='4'>" . __('Personalization') . "</th>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Background color') . "</td>";
      echo "<td>";
      Html::showColorField(
         'bgcolor',
         [
            'value'  => $this->fields['bgcolor'] ?? '',
         ]
      );
      echo "&nbsp;";
      echo Html::getCheckbox([
         'title' => __('Clear'),
         'name'  => '_blank_bgcolor',
         'checked' => empty($this->fields['bgcolor']),
      ]);
      echo "&nbsp;" . __('Clear');
      echo "</td>";
      echo "<td>" . __('Color') . "</td>";
      echo "<td>";
      Html::showColorField(
         'color',
         [
            'value'  => $this->fields['color'] ?? '',
         ]
      );
      echo "&nbsp;";
      echo Html::getCheckbox([
         'title' => __('Clear'),
         'name'  => '_blank_color',
         'checked' => empty($this->fields['color']),
      ]);
      echo "&nbsp;" . __('Clear');
      echo "</td>";
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Picture') . "</td>";
      echo "<td colspan='3'>";
      if (!empty($this->fields['picture'])) {
         echo Html::image(PluginSinglesignonToolbox::getPictureUrl($this->fields['picture']), [
            'style' => '
               max-width: 100px;
               max-height: 100px;
               background-image: linear-gradient(45deg, #b0b0b0 25%, transparent 25%), linear-gradient(-45deg, #b0b0b0 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #b0b0b0 75%), linear-gradient(-45deg, transparent 75%, #b0b0b0 75%);
               background-size: 10px 10px;
               background-position: 0 0, 0 5px, 5px -5px, -5px 0px;',
            'class' => 'picture_square'
         ]);
         echo "&nbsp;";
         echo Html::getCheckbox([
            'title' => __('Clear'),
            'name'  => '_blank_picture'
         ]);
         echo "&nbsp;" . __('Clear');
      } else {
         echo Html::file([
            'name'       => 'picture',
            'onlyimages' => true,
         ]);
      }
      echo "</td>";
      echo "</tr>\n";

      echo '<script type="text/javascript">
      $("[name=bgcolor]").on("change", function (e) {
         $("[name=_blank_bgcolor]").prop("checked", false).attr("checked", false);
      });
      $("[name=color]").on("change", function (e) {
         $("[name=_blank_color]").prop("checked", false).attr("checked", false);
      });
      </script>';

      if ($ID) {
         echo "<tr class='tab_bg_1'>";
         echo "<th colspan='4'>" . __('Test') . "</th>";
         echo "</tr>\n";

         $url = PluginSinglesignonToolbox::getCallbackUrl($ID);
         $fullUrl = $this->getBaseURL() . $url;
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . __sso('Callback URL') . "</td>";
         echo "<td colspan='3'><a id='singlesignon_callbackurl' href='$fullUrl' data-url='$url'>$fullUrl</a></td>";
         echo "</tr>\n";

         $options['addbuttons'] = ['test_singlesignon' => __sso('Test Single Sign-on')];
      }

      $this->showFormButtons($options);

      if ($ID) {
         echo '<script type="text/javascript">
         $("[name=test_singlesignon]").on("click", function (e) {
            e.preventDefault();

            // Im not sure why /test/1 is added here, I got a problem with the redirect_uri because its added after /provider/id
            var url   = $("#singlesignon_callbackurl").attr("data-url"); // + "/test/1";
            var left  = ($(window).width()/2)-(600/2);
            var top   = ($(window).height()/2)-(800/2);
            var newWindow = window.open(url, "singlesignon", "width=600,height=800,left=" + left + ",top=" + top);
            if (window.focus) {
               newWindow.focus();
            }
         });
         </script>';
      }

      return true;
   }

   function prepareInputForAdd($input) {
      return $this->prepareInput($input);
   }

   function prepareInputForUpdate($input) {
      return $this->prepareInput($input);
   }

   function cleanDBonPurge() {
      PluginSinglesignonToolbox::deletePicture($this->fields['picture']);
      $this->deleteChildrenAndRelationsFromDb(
         [
            'PluginSinglesignonProvider_User',
         ]
      );
   }

   /**
    * Prepares input (for update and add)
    *
    * @param array $input Input data
    *
    * @return array
    */
   private function prepareInput($input) {
      $error_detected = [];

      $type = '';
      //check for requirements
      if (isset($input['type'])) {
         $type = $input['type'];
      }

      if (!isset($input['name']) || empty($input['name'])) {
         $error_detected[] = __sso('A Name is required');
      }

      if (empty($type)) {
         $error_detected[] = __('An item type is required');
      } else if (!isset(static::getTypes()[$type])) {
         $error_detected[] = sprintf(__sso('The "%s" is a Invalid type'), $type);
      }

      if (!isset($input['client_id']) || empty($input['client_id'])) {
         $error_detected[] = __sso('A Client ID is required');
      }

      if (!isset($input['client_secret']) || empty($input['client_secret'])) {
         $error_detected[] = __sso('A Client Secret is required');
      }

      if ($type === 'generic') {
         if (!isset($input['url_authorize']) || empty($input['url_authorize'])) {
            $error_detected[] = __sso('An Authorize URL is required');
         } else if (!filter_var($input['url_authorize'], FILTER_VALIDATE_URL)) {
            $error_detected[] = __sso('The Authorize URL is invalid');
         }

         if (!isset($input['url_access_token']) || empty($input['url_access_token'])) {
            $error_detected[] = __sso('An Access Token URL is required');
         } else if (!filter_var($input['url_access_token'], FILTER_VALIDATE_URL)) {
            $error_detected[] = __sso('The Access Token URL is invalid');
         }

         if (!isset($input['url_resource_owner_details']) || empty($input['url_resource_owner_details'])) {
            $error_detected[] = __sso('A Resource Owner Details URL is required');
         } else if (!filter_var($input['url_resource_owner_details'], FILTER_VALIDATE_URL)) {
            $error_detected[] = __sso('The Resource Owner Details URL is invalid');
         }
      }

      if (count($error_detected)) {
         foreach ($error_detected as $error) {
            Session::addMessageAfterRedirect(
               $error,
               true,
               ERROR
            );
         }
         return false;
      }

      if (isset($input["_blank_bgcolor"]) && $input["_blank_bgcolor"]) {
         $input['bgcolor'] = '';
      }

      if (isset($input["_blank_color"]) && $input["_blank_color"]) {
         $input['color'] = '';
      }

      if (isset($input["_blank_picture"]) && $input["_blank_picture"]) {
         $input['picture'] = '';

         if (array_key_exists('picture', $this->fields)) {
            PluginSinglesignonToolbox::deletePicture($this->fields['picture']);
         }
      }

      if (isset($input["_picture"])) {
         $picture = array_shift($input["_picture"]);

         if ($dest = PluginSinglesignonToolbox::savePicture(GLPI_TMP_DIR . '/' . $picture)) {
            $input['picture'] = $dest;
         } else {
            Session::addMessageAfterRedirect(__('Unable to save picture file.'), true, ERROR);
         }

         if (array_key_exists('picture', $this->fields)) {
            PluginSinglesignonToolbox::deletePicture($this->fields['picture']);
         }
      }

      // single logout
      if (isset($input['url_logout'])) {
         $input['url_logout'] = trim($input['url_logout']);
      }

      if (isset($input['use_single_logout'])) {
         $input['use_sso_logout'] = (int)$input['use_single_logout'];
      }

      return $input;
   }

   function getSearchOptions() {
      // For GLPI <= 9.2
      $options = [];
      foreach ($this->rawSearchOptions() as $opt) {
         if (!isset($opt['id'])) {
            continue;
         }
         $optid = $opt['id'];
         unset($opt['id']);
         if (isset($options[$optid])) {
            $message = "Duplicate key $optid ({$options[$optid]['name']}/{$opt['name']}) in " . get_class($this) . " searchOptions!";
            Toolbox::logDebug($message);
         }
         foreach ($opt as $k => $v) {
            $options[$optid][$k] = $v;
         }
      }
      return $options;
   }

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id' => 'common',
         'name' => __('Characteristics'),
      ];

      $tab[] = [
         'id' => 1,
         'table' => $this->getTable(),
         'field' => 'name',
         'name' => __('Name'),
         'datatype' => 'itemlink',
      ];

      $tab[] = [
         'id' => 2,
         'table' => $this->getTable(),
         'field' => 'type',
         'name' => __('Type'),
         'searchtype' => 'equals',
         'datatype' => 'specific',
      ];

      $tab[] = [
         'id' => 3,
         'table' => $this->getTable(),
         'field' => 'client_id',
         'name' => __sso('Client ID'),
         'datatype' => 'text',
      ];

      $tab[] = [
         'id' => 4,
         'table' => $this->getTable(),
         'field' => 'client_secret',
         'name' => __sso('Client Secret'),
         'datatype' => 'text',
      ];

      $tab[] = [
         'id' => 5,
         'table' => $this->getTable(),
         'field' => 'scope',
         'name' => __sso('Scope'),
         'datatype' => 'text',
      ];

      $tab[] = [
         'id' => 6,
         'table' => $this->getTable(),
         'field' => 'extra_options',
         'name' => __sso('Extra Options'),
         'datatype' => 'specific',
      ];

      $tab[] = [
         'id' => 7,
         'table' => $this->getTable(),
         'field' => 'url_authorize',
         'name' => __sso('Authorize URL'),
         'datatype' => 'weblink',
      ];

      $tab[] = [
         'id' => 8,
         'table' => $this->getTable(),
         'field' => 'url_access_token',
         'name' => __sso('Access Token URL'),
         'datatype' => 'weblink',
      ];

      $tab[] = [
         'id' => 9,
         'table' => $this->getTable(),
         'field' => 'url_resource_owner_details',
         'name' => __sso('Resource Owner Details URL'),
         'datatype' => 'weblink',
      ];

      $tab[] = [
         'id' => 10,
         'table' => $this->getTable(),
         'field' => 'is_active',
         'name' => __('Active'),
         'searchtype' => 'equals',
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id' => 11,
         'table' => $this->getTable(),
         'field' => 'use_email_for_login',
         'name' => __('Use email field for login'),
         'searchtype' => 'equals',
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id' => 12,
         'table' => $this->getTable(),
         'field' => 'split_name',
         'name' => __('Split name field for First & Last Name'),
         'searchtype' => 'equals',
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id' => 13,
         'table' => $this->getTable(),
         'field' => 'url_logout',
         'name' => __sso('Logout URL'),
         'datatype' => 'text',
      ];

      $tab[] = [
         'id' => 14,
         'table' => $this->getTable(),
         'field' => 'use_single_logout',
         'name' => __sso('Use Single Logout'),
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id' => 30,
         'table' => $this->getTable(),
         'field' => 'id',
         'name' => __('ID'),
         'datatype' => 'itemlink',
      ];

      return $tab;
   }

   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'type':
            return self::getTicketTypeName($values[$field]);
         case 'extra_options':
            return '<pre>' . $values[$field] . '</pre>';
      }
      return '';
   }

   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'type':
            $options['value'] = $values[$field];
            return self::dropdownType($name, $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * Get ticket types
    *
    * @return array of types
    * */
   static function getTypes() {

      $options['generic'] = __sso('Generic');
      $options['azure'] = __sso('Azure');
      $options['facebook'] = __sso('Facebook');
      $options['github'] = __sso('GitHub');
      $options['google'] = __sso('Google');
      $options['instagram'] = __sso('Instagram');
      $options['linkedin'] = __sso('LinkdeIn');

      return $options;
   }

   /**
    * Get ticket type Name
    *
    * @param $value type ID
    * */
   static function getTicketTypeName($value) {
      $tab = static::getTypes();
      // Return $value if not defined
      return (isset($tab[$value]) ? $tab[$value] : $value);
   }

   /**
    * Dropdown of ticket type
    *
    * @param $name            select name
    * @param $options   array of options:
    *    - value     : integer / preselected value (default 0)
    *    - toadd     : array / array of specific values to add at the begining
    *    - on_change : string / value to transmit to "onChange"
    *    - display   : boolean / display or get string (default true)
    *
    * @return string id of the select
    * */
   static function dropdownType($name, $options = []) {

      $params['value'] = 0;
      $params['toadd'] = [];
      $params['on_change'] = '';
      $params['display'] = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $items = [];
      if (count($params['toadd']) > 0) {
         $items = $params['toadd'];
      }

      $items += self::getTypes();

      return Dropdown::showFromArray($name, $items, $params);
   }

   //////////////////////////////
   ////// SPECIFIC MODIF MASSIVE FUNCTIONS ///////

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
    * */
   function getSpecificMassiveActions($checkitem = null) {

      $actions = parent::getSpecificMassiveActions($checkitem);

      $actions['Document_Item' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'] = _x('button', 'Add a document');         // GLPI core one

      return $actions;
   }

   static function getIcon() {
      return "fas fa-user-lock";
   }

   public static function getDefault($type, $key, $default = null) {
      if (static::$default === null) {
         $content = file_get_contents(dirname(__FILE__) . '/../providers.json');
         static::$default = json_decode($content, true);
      }

      if (isset(static::$default[$type]) && static::$default[$type][$key]) {
         return static::$default[$type][$key];
      }

      return $default;
   }

   public function getClientType() {
      $value = "generic";

      if (isset($this->fields['type']) && !empty($this->fields['type'])) {
         $value = $this->fields['type'];
      }

      return $value;
   }

   public function getClientId() {
      $value = "";

      if (isset($this->fields['client_id']) && !empty($this->fields['client_id'])) {
         $value = $this->fields['client_id'];
      }

      return $value;
   }

   public function getClientSecret() {
      $value = "";

      if (isset($this->fields['client_secret']) && !empty($this->fields['client_secret'])) {
         $value = $this->fields['client_secret'];
      }

      return $value;
   }

   public function getScope() {
      $type = $this->getClientType();

      $value = static::getDefault($type, "scope");

      $fields = $this->fields;

      if (!isset($fields['scope']) || empty($fields['scope'])) {
         $fields['scope'] = $value;
      }

      $fields = Plugin::doHookFunction("sso:scope", $fields);

      return $fields['scope'];
   }

   public function getAuthorizeUrl() {
      $type = $this->getClientType();

      $value = static::getDefault($type, "url_authorize");

      $fields = $this->fields;

      if (!isset($fields['url_authorize']) || empty($fields['url_authorize'])) {
         $fields['url_authorize'] = $value;
      }

      $fields = Plugin::doHookFunction("sso:url_authorize", $fields);

      return $fields['url_authorize'];
   }

   public function getAccessTokenUrl() {
      $type = $this->getClientType();

      $value = static::getDefault($type, "url_access_token");

      $fields = $this->fields;

      if (!isset($fields['url_access_token']) || empty($fields['url_access_token'])) {
         $fields['url_access_token'] = $value;
      }

      $fields = Plugin::doHookFunction("sso:url_access_token", $fields);

      return $fields['url_access_token'];
   }

   public function getResourceOwnerDetailsUrl($access_token = null) {
      $type = $this->getClientType();

      $value = static::getDefault($type, "url_resource_owner_details", "");

      $fields = $this->fields;
      $fields['access_token'] = $access_token;

      if (!isset($fields['url_resource_owner_details']) || empty($fields['url_resource_owner_details'])) {
         $fields['url_resource_owner_details'] = $value;
      }

      $fields = Plugin::doHookFunction("sso:url_resource_owner_details", $fields);

      $url = $fields['url_resource_owner_details'];

      if (!IS_NULL($access_token)) {
         $url = str_replace("<access_token>", $access_token, $url);
         $url = str_replace("<appsecret_proof>", hash_hmac('sha256', $access_token, $this->getClientSecret()), $url);
      }

      return $url;
   }

   /**
    * Get current URL without query string
    * @return string
    */
   private function getBaseURL() {
      $baseURL = "";
      if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
         $baseURL = ($_SERVER["HTTP_X_FORWARDED_PROTO"] == "https") ? "https://" : "http://";
      } else if (isset($_SERVER["HTTPS"])) {
         $baseURL = ($_SERVER["HTTPS"] == "on") ? "https://" : "http://";
      } else {
         $baseURL = "http://";
      }
      if (isset($_SERVER["HTTP_X_FORWARDED_HOST"])) {
         $baseURL .= $_SERVER["HTTP_X_FORWARDED_HOST"];
      } else if (isset($_SERVER["HTTP_X_FORWARDED_HOST"])) {
         $baseURL .= $_SERVER["HTTP_X_FORWARDED_HOST"];
      } else {
         $baseURL .= $_SERVER["SERVER_NAME"];
      }

      $port = $_SERVER["SERVER_PORT"];
      if (isset($_SERVER["HTTP_X_FORWARDED_PORT"])) {
         $port = $_SERVER["HTTP_X_FORWARDED_PORT"];
      }

      if ($port != "80" && $port != "443") {
         $baseURL .= ":" . $_SERVER["SERVER_PORT"];
      }
      return $baseURL;
   }

   /**
    * Get current URL without query string
    * @return string
    */
   private function getCurrentURL() {
      $currentURL = $this->getBaseURL();

      // $currentURL .= $_SERVER["REQUEST_URI"];
      // Ignore Query String
      if (isset($_SERVER["SCRIPT_NAME"])) {
         $currentURL .= $_SERVER["SCRIPT_NAME"];
      }
      if (isset($_SERVER["PATH_INFO"])) {
         $currentURL .= $_SERVER["PATH_INFO"];
      }
      return $currentURL;
   }

   /**
    *
    * @return boolean|string
    */
   public function checkAuthorization() {

      if (isset($_GET['error'])) {

         $error_description = isset($_GET['error_description']) ? $_GET['error_description'] : __("The action you have requested is not allowed.");

         Html::displayErrorAndDie(__($error_description), true);
      }

      if (!isset($_GET['code'])) {
         $state = Session::getNewCSRFToken();
         if (isset($_SESSION['redirect'])) {
            $state .= "&redirect=" . $_SESSION['redirect'];
         }
         $params = [
            'client_id' => $this->getClientId(),
            'scope' => $this->getScope(),
            'state' => $state,
            'response_type' => 'code',
            'approval_prompt' => 'auto',
            'redirect_uri' => $this->getCurrentURL(),
         ];

         $params = Plugin::doHookFunction("sso:authorize_params", $params);

         $url = $this->getAuthorizeUrl();

         $glue = strstr($url, '?') === false ? '?' : '&';
         $url .= $glue . http_build_query($params);

         header('Location: ' . $url);
         exit;
      }

      if (isset($_GET['state']) && is_integer(strpos($_GET['state'], "&redirect="))) {
         $pos_redirect  = strpos($_GET['state'], "&redirect=");
         $state         = substr($_GET['state'], 0, $pos_redirect);
         $_GET['state'] = substr($_GET['state'], $pos_redirect);
      } else {
         $state = isset($_GET['state']) ? $_GET['state'] : '';
      }
      // Check given state against previously stored one to mitigate CSRF attack
      Session::checkCSRF([
         '_glpi_csrf_token' => $state,
      ]);

      $this->_code = $_GET['code'];

      return $_GET['code'];
   }

   /**
    *
    * @return boolean|string
    */
   public function getAccessToken() {
      if ($this->_token !== null) {
         return $this->_token;
      }

      if ($this->_code === null) {
         return false;
      }

      $params = [
         'client_id' => $this->getClientId(),
         'client_secret' => $this->getClientSecret(),
         'redirect_uri' => $this->getCurrentURL(),
         'grant_type' => 'authorization_code',
         'code' => $this->_code,
      ];

      $params = Plugin::doHookFunction("sso:access_token_params", $params);

      $url = $this->getAccessTokenUrl();

      $content = Toolbox::callCurl($url, [
         CURLOPT_HTTPHEADER => [
            "Accept: application/json",
         ],
         CURLOPT_POST => true,
         CURLOPT_POSTFIELDS => http_build_query($params),
         CURLOPT_SSL_VERIFYHOST => false,
         CURLOPT_SSL_VERIFYPEER => false,
      ]);

      if ($this->debug) {
         print_r("\ngetAccessToken:\n");
      }

      try {
         $data = json_decode($content, true);
         if ($this->debug) {
            print_r($data);
         }
         if (!isset($data['access_token'])) {
            return false;
         }
         $this->_token = $data['access_token'];
      } catch (\Exception $ex) {
         if ($this->debug) {
            print_r($content);
         }
         return false;
      }

      return $this->_token;
   }

   /**
    *
    * @return boolean|array
    */
   public function getResourceOwner() {
      if ($this->_resource_owner !== null) {
         return $this->_resource_owner;
      }

      $token = $this->getAccessToken();
      if (!$token) {
         return false;
      }

      $url = $this->getResourceOwnerDetailsUrl($token);

      $headers = [
         "Accept:application/json",
         "Authorization:Bearer $token",
      ];

      $headers = Plugin::doHookFunction("sso:resource_owner_header", $headers);

      $content = Toolbox::callCurl($url, [
         CURLOPT_HTTPHEADER => $headers,
         CURLOPT_SSL_VERIFYHOST => false,
         CURLOPT_SSL_VERIFYPEER => false,
      ]);

      if ($this->debug) {
         print_r("\ngetResourceOwner:\n");
      }

      try {
         $data = json_decode($content, true);
         if ($this->debug) {
            print_r($data);
         }
         $this->_resource_owner = $data;
      } catch (\Exception $ex) {
         if ($this->debug) {
            print_r($content);
         }
         return false;
      }

      if ($this->getClientType() === "linkedin") {
         if ($this->debug) {
            print_r("\nlinkedin:\n");
         }
         $email_url = "https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))";
         $content = Toolbox::callCurl($email_url, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
         ]);

         try {
            $data = json_decode($content, true);
            if ($this->debug) {
               print_r($content);
            }

            $this->_resource_owner['email-address'] = $data['elements'][0]['handle~']['emailAddress'];
         } catch (\Exception $ex) {
            return false;
         }
      }

      return $this->_resource_owner;
   }

   public function findUser() {
      global $DB;
      
      $resource_array = $this->getResourceOwner();

      if (!$resource_array) {
         return false;
      }
      // get mappings
      $this->mappings = $this->getMappings();
      $mappings = $this->mappings;
      $this->user_data = $resource_array;

      $user = new User();

      // First: check linked user
      $id = Plugin::doHookFunction("sso:find_user", $resource_array);
      if (is_numeric($id) && $user->getFromDB($id)) {
         return $user;
      }

      $remote_id = false;
      $remote_id_fields = ['id', 'username', 'sub'];

      // first non empty remote_id field will be used
      foreach ($remote_id_fields as $field) {
         if (isset($resource_array[$field]) && !empty($resource_array[$field])) {
            $remote_id = $resource_array[$field];
            break;
         }
      }

      // find user_id within our stored remote_ids and provider_ids (if any)
      if ($remote_id) {
         $link = new PluginSinglesignonProvider_User();
         $condition = "`remote_id` = '{$remote_id}' AND `plugin_singlesignon_providers_id` = {$this->fields['id']}";
         if (version_compare(GLPI_VERSION, '9.4', '>=')) {
            $condition = [$condition];
         }
         $links = $link->find($condition);
         if (!empty($links) && $first = reset($links)) {
            $id = $first['users_id'];
         }

         $remote_id;
      }

      // get the user if we have an id
      if (is_numeric($id) && $user->getFromDB($id)) {
         return $user;
      }

      $split = $this->fields['split_domain'];
      $authorizedDomainsString = $this->fields['authorized_domains'];
      $authorizedDomains = [];
      if (isset($authorizedDomainsString)) {
         $authorizedDomains = explode(',', $authorizedDomainsString);
      }

      // check email first
      $email = false;

      // use mapped email field if it exists
      if (isset($mappings['email']) && isset($resource_array[$mappings['email']])) {
         $email = $resource_array[$mappings['email']];
         $email_fields = [$mappings['email']];
      } else {
         $email_fields = ['email', 'e-mail', 'email-address', 'mail'];
      }

      foreach ($email_fields as $field) {
         if (isset($resource_array[$field]) && is_string($resource_array[$field])) {
            $email = $resource_array[$field];
            $isAuthorized = empty($authorizedDomains);
            foreach ($authorizedDomains as $authorizedDomain) {
               if (preg_match("/{$authorizedDomain}$/i", $email)) {
                  $isAuthorized = true;
               }
            }
            if (!$isAuthorized) {
               return false;
            }
            if ($split) {
               $emailSplit = explode("@", $email);
               $email = $emailSplit[0];
            }
            break;
         }
      }

      $login = false;
      $use_email = $this->fields['use_email_for_login'];
      if ($email && $use_email) {
         $login = $email;
      } else {

         // if mappings are set we use the "name" mapping as the login. doesnt apply if use_email_for_login is set
         if (isset($mappings['name']) && isset($resource_array[$mappings['name']])) {
            $login_fields = [$mappings['name']];
         } else {
            $login_fields = ['userPrincipalName', 'login', 'username', 'id', 'name', 'displayName'];
         }

         foreach ($login_fields as $field) {
            if (isset($resource_array[$field]) && is_string($resource_array[$field])) {
               $login = $resource_array[$field];
               $isAuthorized = empty($authorizedDomains);
               foreach ($authorizedDomains as $authorizedDomain) {
                  if (preg_match("/{$authorizedDomain}$/i", $login)) {
                     $isAuthorized = true;
                  }
               }

               if (!$isAuthorized) {
                  return false;
               }
               if ($split) {
                  $loginSplit = explode("@", $login);
                  $login = $loginSplit[0];
               }
               break;
            }
         }
      }

      // look for the user in the database by name
      
      if ($login && $user->getFromDBbyName($login)) {
         
         return $user;
      }

      $default_condition = '';

      if (version_compare(GLPI_VERSION, '9.3', '>=')) {
         $default_condition = [];
      }

      // handle duplicate emails
      if ($email) {
         $users_table = getTableForItemType('User');
         $email_table = 'glpi_useremails';
         
         $query = "SELECT COUNT(DISTINCT u.id) as count 
                   FROM $users_table u 
                   JOIN $email_table e ON e.users_id = u.id 
                   WHERE e.email = '$email'";
                   
         $result = $DB->query($query);
         $count = $DB->result($result, 0, 'count');
         
         if ($count > 1) {
            Event::log(0, "singlesignon", 3, "provider",
            sprintf(__sso('Reconciliation failed: Multiple users found with email %s'), $email));
         }
         
         if ($user->getFromDBbyEmail($email, $default_condition)) {
            return $user;
         }
      }
      $bOk = false;

      // nb: mappings are implemented for generic providers only

      // If the user does not exist in the database and the provider is google
      if (static::getClientType() == "google" && !$bOk) {
         // Generates an api token and a personal token... probably not necessary
         $tokenAPI = base_convert(hash('sha256', time() . mt_rand()), 16, 36);
         $tokenPersonnel = base_convert(hash('sha256', time() . mt_rand()), 16, 36);

         $realname = '';
         if (isset($resource_array['family_name'])) {
            $realname = $resource_array['family_name'];
         }
         $firstname = '';
         if (isset($resource_array['given_name'])) {
            $firstname = $resource_array['given_name'];
         }
         $useremail = $email;
         if (isset($resource_array['email'])) {
            $useremail = $resource_array['email'];
         }

         $userPost = [
            'name' => $login,
            'add' => 1,
            'realname' => $realname,
            'firstname' => $firstname,
            //'picture' => $resource_array['picture'] ?? '',
            'picture' => $resource_array['picture'],
            'api_token' => $tokenAPI,
            'personal_token' => $tokenPersonnel,
            'is_active' => 1
         ];
         $userPost['_useremails'][-1] = $useremail;
         $user->add($userPost);
         return $user;
      }

      // If the user does not exist in the database and the provider is generic (Ex: azure ad without common tenant)
      if (static::getClientType() == "generic" && !$bOk) {
         try {
            // Generates an api token and a personal token... probably not necessary
            $tokenAPI = base_convert(hash('sha256', time() . mt_rand()), 16, 36);
            $tokenPersonnel = base_convert(hash('sha256', time() . mt_rand()), 16, 36);

            // if split name is enabled
            $splitname = $this->fields['split_name'];
            $firstLastArray = ['', ''];
            if ($splitname) {
               if (isset($resource_array['name']) && !empty($resource_array['name'])) {
                  $firstLastArray = preg_split('/ /', $resource_array['name'], 2);
               } elseif (isset($resource_array['displayName']) && !empty($resource_array['displayName'])) {
                  $firstLastArray = preg_split('/ /', $resource_array['displayName'], 2);
               }
            }

            // if mappings are set for first and last name, we overwrite the splitname with the mappings
            if(isset($mappings['family_name']) && !empty($mappings['family_name']) && isset($resource_array[$mappings['family_name']]) 
               && isset($mappings['given_name']) && !empty($mappings['given_name']) && isset($resource_array[$mappings['given_name']])) {
               $firstLastArray = [$resource_array[$mappings['given_name']], $resource_array[$mappings['family_name']]];
            }

            // process the remaining mappings before user creation
            // image
            $image = '';
            if (isset($mappings['picture']) && isset($resource_array[$mappings['picture']])) {
               $image = $resource_array[$mappings['picture']];
            }

            // locale (language)
            $locale = '';
            if (isset($mappings['locale']) && isset($resource_array[$mappings['locale']])) {
               $locale = $resource_array[$mappings['locale']];
            }

            // phone number
            $phone = '';
            if (isset($mappings['phone_number']) && isset($resource_array[$mappings['phone_number']])) {
               $phone = $resource_array[$mappings['phone_number']];
            }

            $group = '';
            if (isset($mappings['group']) && isset($resource_array[$mappings['group']])) {
               $group = $resource_array[$mappings['group']];
            }

            $userPost = [
               'name' => $login,
               'add' => 1,
               'realname' => $firstLastArray[1],
               'firstname' => $firstLastArray[0],
               'api_token' => $tokenAPI,
               'personal_token' => $tokenPersonnel,
               'is_active' => 1,
               'picture' => $image,
               'language' => $locale,
               'phone' => $phone,
            ];

            // Set the office location from Office 365 user as entity for the GLPI new user if they names match
            if (isset($resource_array['officeLocation'])) {
               global $DB;
               foreach ($DB->request('glpi_entities') as $entity) {
                  if ($entity['name'] == $resource_array['officeLocation']) {
                     $userPost['entities_id'] = $entity['id'];
                     break;
                  }
               }
            }

            if ($email) {
               $userPost['_useremails'][-1] = $email;
            }

            // adding the user
            $newID = $user->add($userPost);

            $profils = 0;
            // Verification default profiles exist in the entity
            // If no default profile exists, the user will not be able to log in.
            // In this case, we retrieve a profile and an entity and assign these values ​​to it.
            // The administrator can change these values ​​later.
            if (0 == Profile::getDefault()) {
               // No default profiles
               // Profile recovery and assignment
               global $DB;

               $datasProfiles = [];
               foreach ($DB->request('glpi_profiles') as $data) {
                  array_push($datasProfiles, $data);
               }
               $datasEntities = [];
               foreach ($DB->request('glpi_entities') as $data) {
                  array_push($datasEntities, $data);
               }
               if (count($datasProfiles) > 0 && count($datasEntities) > 0) {
                  $profils = $datasProfiles[0]['id'];
                  $entitie = $datasEntities[0]['id'];

                  $profile   = new Profile_User();
                  $userProfile['users_id'] = intval($user->fields['id']);
                  $userProfile['entities_id'] = intval($entitie);
                  $userProfile['is_recursive'] = 0;
                  $userProfile['profiles_id'] = intval($profils);
                  $userProfile['add'] = "Ajouter";
                  $profile->add($userProfile);
               } else {
                  return false;
               }
            }

            if ($group) {
               $this->processGroups($group, $newID);
            }

            return $user;
         } catch (\Exception $ex) {
            Toolbox::logDebug("Exception during user creation: " . $ex->getMessage());
            return false;
         }
      }

      return false;
   }

   public function login() {
      $user = $this->findUser();
      if (!$user) {
         return false;
      }

      $this->updateUserData($user->getID());

      $auth = new Auth();
      $auth->user = $user;
      $auth->auth_succeded = true;
      $auth->extauth = 1;
      $auth->user_present = 1;
      $auth->user->fields['authtype'] = Auth::DB_GLPI;

      Session::init($auth);

      // Return false if the profile is not defined in Session::init($auth)
      return $auth->auth_succeded;
   }

   /**
    * Link a user to the provider
    */
   public function linkUser($user_id) {
      $user = new User();

      if (!$user->getFromDB($user_id)) {
         return false;
      }

      $resource_array = $this->getResourceOwner();

      if (!$resource_array) {
         return false;
      }

      $remote_id = false;
      $id_fields = ['id', 'sub', 'username'];

      foreach ($id_fields as $field) {
         if (isset($resource_array[$field]) && !empty($resource_array[$field])) {
            $remote_id = $resource_array[$field];
            break;
         }
      }

      if (!$remote_id) {
         return false;
      }

      $link = new PluginSinglesignonProvider_User();

      // check if user is already linked with the same identifier
      $existing_links = $link->find([
         'plugin_singlesignon_providers_id' => $this->fields['id'],
         'remote_id' => $remote_id,
         'users_id' => $user_id
      ]);

   
      // user is linked with the same identifier, no action needed
      if (!empty($existing_links)) {
         return true;
      }

      // Unlink from another user
      $link->deleteByCriteria([
         'plugin_singlesignon_providers_id' => $this->fields['id'],
         'remote_id' => $remote_id,
      ],
      false,
      false
      );

      return $link->add([
         'plugin_singlesignon_providers_id' => $this->fields['id'],
         'users_id' => $user_id,
         'remote_id' => $remote_id,
      ],
      false,
      false
      );
   }

   /**
    * Fetch the mappings for the provider
    *
    * @return array
    */
   public function getMappings() {
      global $DB;

      $query = "SELECT * FROM glpi_plugin_singlesignon_providers_mappings WHERE plugin_singlesignon_providers_id = " . intval($this->fields['id']);
      $result = $DB->query($query);

      $mappings = $DB->fetchAssoc($result);
      
      return $mappings;
   }

    /**
     * Process groups for the user
     *
     * @param string $group
     * @param int $user_id
     */
    private function processGroups($group, $user_id) {
        global $DB;

        if (is_array($group)) {
            $group = implode(',', $group);
        }

        $group_names = explode(',', $group);

        // check if user is already associated with the group
        $query = "SELECT `glpi_groups_users`.`groups_id`, `completename` FROM `glpi_groups_users` LEFT JOIN `glpi_groups` ON `glpi_groups_users`.`groups_id` = `glpi_groups`.`id` WHERE `users_id` = '$user_id'";
        $result = $DB->query($query);
        $existing_groups = [];
        if ($result) {
            while ($data = $DB->fetchAssoc($result)) {
                $existing_groups[] = $data['completename'];
            }
        }

        // add the user to the group if they are not already in it
        foreach ($group_names as $group_name) {
            $group_name = trim($group_name);
            if (in_array($group_name, $existing_groups)) {
               // skip if the user is already in the group
                continue;
            }

            // check if the group exists
            $request = $DB->request('glpi_groups', ['completename' => $group_name]);
            if ($data = $request->next()) {
                $id_group_create = $data['id'];
            } else {
                // create the group if it doesn't exist
                $query = "INSERT IGNORE INTO `glpi_groups` (`name`, `completename`) VALUES ('$group_name', '$group_name')";
                $DB->queryOrDie($query);
                $query = "SELECT `id` FROM `glpi_groups` WHERE `completename` = '$group_name'";
                $result = $DB->query($query);
                $id_group_create = $DB->fetchAssoc($result)['id'];
            }

            // link the user to the group
            $query = "INSERT IGNORE INTO `glpi_groups_users` (`users_id`, `groups_id`) VALUES ('$user_id', '$id_group_create')";
            $DB->queryOrDie($query);
        }
    }

   /**
    * Single Logout implementation
    */
   public function singleLogout() {
      global $DB;
      // need to identify the provider linked to the user
      $user = Session::getLoginUserID();
      $provider = new PluginSinglesignonProvider();
      $query = "SELECT * FROM glpi_plugin_singlesignon_providers_users WHERE users_id = " . intval($user);
      $result = $DB->query($query);
      $data = $DB->fetchAssoc($result);

      // check if we found a provider link
      if (!$data) {
         Toolbox::logDebug("No provider entry found for user {$user}");
         return;
      }

      $provider_id = $data['plugin_singlesignon_providers_id'];

      if (!$provider->getFromDB($provider_id)) {
         Toolbox::logDebug("Failed to load provider {$provider_id}");
         return;
      }

      $sign_out_endpoint = $provider->fields['url_logout'];

      // if no sign-out URL is provided for the provider
      if (empty($sign_out_endpoint)) {
         Toolbox::logDebug("No sign-out URL provided for provider {$provider->fields['name']}");
      }

      if (isset($provider->fields['use_single_logout']) && $provider->fields['use_single_logout'] == 1) {
         // destroying session and cookies
         Session::destroy();
         Auth::setRememberMeCookie('');

         // redirect to provider's sign out
         header("Location: $sign_out_endpoint");
         exit();
      }
   }

   /**
    * Update user data from provider, will be called after login
    */
   function updateUserData($user_id) {
      global $DB;
      $user = new User();
      $user->getFromDB($user_id);

      $resource_array = $this->user_data;
      $mappings = $this->mappings;

      // name
      if (isset($mappings['name']) && !empty($mappings['name']) && isset($resource_array[$mappings['name']])) {
         $user->fields['name'] = $resource_array[$mappings['name']];
      }

      // email
      if (isset($mappings['email']) && !empty($mappings['email']) && isset($resource_array[$mappings['email']])) {
         $user->fields['_useremails'][-1] = $resource_array[$mappings['email']];
         $querry = "INSERT IGNORE INTO `glpi_useremails` (`id`, `users_id`, `is_default`, `is_dynamic`, `email`) VALUES ('0', '$user_id', '1', '0', '{$resource_array[$mappings['email']]}')";
         $DB->queryOrDie($querry);
      }  

      // if split name is enabled
      $splitname = $this->fields['split_name'];
      $firstLastArray = ['', ''];
      if ($splitname) {
         if (isset($resource_array['name']) && !empty($resource_array['name'])) {
            $firstLastArray = preg_split('/ /', $resource_array['name'], 2);
         } elseif (isset($resource_array['displayName']) && !empty($resource_array['displayName'])) {
            $firstLastArray = preg_split('/ /', $resource_array['displayName'], 2);
         }
      }

      // if mappings are set for first and last name, we overwrite the splitname with the mappings
      if(isset($mappings['family_name']) && !empty($mappings['family_name']) && isset($resource_array[$mappings['family_name']]) 
         && isset($mappings['given_name']) && !empty($mappings['given_name']) && isset($resource_array[$mappings['given_name']])) {
         $firstLastArray = [$resource_array[$mappings['given_name']], $resource_array[$mappings['family_name']]];
      }
      // realname (split name)
      if (!empty($firstLastArray[1])) {
         $user->fields['realname'] = $firstLastArray[1];
      }
      // firstname (split name)
      if (!empty($firstLastArray[0])) {
         $user->fields['firstname'] = $firstLastArray[0];
      }
      // picture
      if (isset($mappings['picture']) && !empty($mappings['picture']) && isset($resource_array[$mappings['picture']])) {
         $user->fields['picture'] = $resource_array[$mappings['picture']];
      }

      // language
      if (isset($mappings['locale']) && !empty($mappings['locale']) && isset($resource_array[$mappings['locale']])) {
         $user->fields['language'] = $resource_array[$mappings['locale']];
      }

      // phone
      if (isset($mappings['phone_number']) && !empty($mappings['phone_number']) && isset($resource_array[$mappings['phone_number']])) {
         $user->fields['phone'] = $resource_array[$mappings['phone_number']];
      }
      // group
      if (isset($mappings['group']) && !empty($mappings['group']) && isset($resource_array[$mappings['group']])) {
         $this->processGroups($resource_array[$mappings['group']], $user_id);
      }
      $isOk = $user->update($user->fields);

      if (!$isOk) {
         Toolbox::logDebug("Failed to update user data with provider data for user {$user->fields['name']}");
      }
   }

   /**
    * User deletion hook to remove linked users from the singlesignon table
    * 
    * @param User $user The user being deleted
    */
   public static function deleteUser(User $user) {
      global $DB;
      
      if (!isset($user->fields['id'])) {
         return;
      }

      $user_id = $user->fields['id'];
      
      // remove all SSO links for this user across all providers
      $link = new PluginSinglesignonProvider_User();
      $link->deleteByCriteria(
         ['users_id' => $user_id],
         false,
         false
      );
   }

}
