<?php

/**
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Behaviors plugin for GLPI.
 *
 * Behaviors is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Behaviors is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Behaviors. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   behaviors
 * @author    Infotel, Remi Collet, Nelly Mahu-Lasson
 * @copyright Copyright (c) 2018-2025 Behaviors plugin team
 * @license   AGPL License 3.0 or (at your option) any later version
 * http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://github.com/InfotelGLPI/behaviors/
 * @link      http://www.glpi-project.org/
 * @since     2010
 *
 * --------------------------------------------------------------------------
 */

class PluginBehaviorsCommon extends CommonGLPI
{
    public static $clone_types = [
        'NotificationTemplate' => 'PluginBehaviorsNotificationTemplate',
        'Profile' => 'PluginBehaviorsProfile',
        'RuleImportComputer' => 'PluginBehaviorsRule',
        'RuleImportEntity' => 'PluginBehaviorsRule',
        'RuleMailCollector' => 'PluginBehaviorsRule',
        'RuleRight' => 'PluginBehaviorsRule',
        'RuleSoftwareCategory' => 'PluginBehaviorsRule',
        'RuleTicket' => 'PluginBehaviorsRule',
        'Transfer' => 'PluginBehaviorsCommon',
        'Ticket' => 'PluginBehaviorsTicket',
    ];


    public static function getCloneTypes()
    {
        return self::$clone_types;
    }


    /**
     * Declare that a type is clonable
     *
     * @param $clonetype    String   classe name of new clonable type
     * @param $managertype  String   class name which manage the clone actions (default '')
     *
     * @return Boolean
     **/
    public static function addCloneType($clonetype, $managertype = '')
    {
        if (!isset(self::$clone_types[$clonetype])) {
            self::$clone_types[$clonetype] = ($managertype ? $managertype : $clonetype);
            return true;
        }
        // already registered
        return false;
    }


    /**
     * @return void
     */
    public static function postInit()
    {
        Plugin::registerClass(
            'PluginBehaviorsCommon',
            ['addtabon' => array_keys(PluginBehaviorsCommon::getCloneTypes())]
        );

        PluginBehaviorsTicket::onNewTicket();
    }


    /**
     * @param CommonGLPI $item
     * @param $withtemplate
     * @return array|string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $config = PluginBehaviorsConfig::getInstance();

        if (
            array_key_exists($item->getType(), self::$clone_types)
            && $item->canUpdate()
            && ($config->getField('clone') > 0)
            && (isset($_SESSION['glpiactiveprofile']['interface'])
                && ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk'))
        ) {
            return sprintf(
                __('%1$s (%2$s)'),
                __('Clone', 'behaviors'),
                __('Behaviours', 'behaviors')
            );
        }
        return '';
    }


    /**
     * @param CommonGLPI $item
     * @return void
     */
    public static function showCloneForm(CommonGLPI $item)
    {
        echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "' >";
        echo "<div class='spaced' id='tabsbody'>";
        echo "<table class='tab_cadre_fixe'>";

        echo "<tr><th>" . __('Clone', 'behaviors') . "</th></tr>";

        if ($item->isEntityAssign()) {
            $config = PluginBehaviorsConfig::getInstance();

            if ($config->getField('clone') == 1) {
                $entities_id = $_SESSION['glpiactive_entity'];
            } elseif ($config->getField('clone') == 2) {
                $entities_id = $item->getEntityID();
            }
            echo "<tr class='tab_bg_1'><td class='center'>";
            printf(
                __('%1$s: %2$s'),
                __('Destination entity'),
                "<span class='b'>" . Dropdown::getDropdownName(
                    'glpi_entities',
                    $entities_id
                ) .
                "</span>"
            );
            echo "</td></tr>";
        }

        $name = sprintf(__('%1$s %2$s'), __('Clone of', 'behaviors'), $item->getName());
        echo "<tr class='tab_bg_1'><td class='center'>" . sprintf(__('%1$s: %2$s'), __('Name'), $name);
        echo Html::input('name', [
            'value' => $name,
            'size' => 60,
        ]);
        echo Html::hidden('itemtype', ['value' => $item->getType()]);
        echo Html::hidden('id', ['value' => $item->getID()]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td class='center'>";
        echo Html::submit(__('Clone', 'behaviors'), [
            'name' => '_clone',
            'class' => 'btn btn-primary',
        ]);
        echo "</th></tr>";

        echo "</table></div>";
        Html::closeForm();
    }


    /**
     * @param CommonGLPI $item
     * @param $tabnum
     * @param $withtemplate
     * @return true
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (
            array_key_exists($item->getType(), self::$clone_types)
            && $item->canUpdate()
        ) {
            self::showCloneForm($item);
        }
        return true;
    }


    /**
     * @param array $param
     * @return false|void
     */
    public static function cloneItem(array $param)
    {
        $dbu = new DbUtils();
        // Sanity check
        if (
            !isset($param['itemtype']) || !isset($param['id']) || !isset($param['name'])
            || !array_key_exists($param['itemtype'], self::$clone_types)
            || empty($param['name'])
            || !($item = $dbu->getItemForItemtype($param['itemtype']))
        ) {
            return false;
        }

        // Read original and prepare clone
        $item->check($param['id'], READ);

        $input = ToolBox::addslashes_deep($item->fields);
        $input['name'] = $param['name'];
        $input['_add'] = 1;
        $input['_old_id'] = $input['id'];
        unset($input['id']);
        if ($item->isEntityAssign()) {
            $config = PluginBehaviorsConfig::getInstance();

            if ($config->getField('clone') == 1) {
                $entities_id = $_SESSION['glpiactive_entity'];
            } elseif ($config->getField('clone') == 2) {
                $entities_id = $item->getEntityID();
            }
            $input['entities_id'] = $entities_id;
        }

        // Manage NULL fields in original
        foreach ($input as $k => $v) {
            if (is_null($input[$k])) {
                $input[$k] = "NULL";
            }
        }

        // Specific to itemtype - before clone
        if (method_exists(self::$clone_types[$param['itemtype']], 'preClone')) {
            $input = call_user_func(
                [self::$clone_types[$param['itemtype']], 'preClone'],
                $item,
                $input
            );
        }

        // Clone
        $clone = clone $item;
        $clone->check(-1, CREATE, $input);
        $new = $clone->add($input);

        // Specific to itemtype - after clone
        if (method_exists(self::$clone_types[$param['itemtype']], 'postClone')) {
            call_user_func([self::$clone_types[$param['itemtype']], 'postClone'], $clone, $param['id']);
        }
        Plugin::doHook('item_clone', $clone);

        // History
        if ($clone->dohistory) {
            $changes[0] = '0';
            $changes[1] = '';
            $changes[2] = addslashes(
                sprintf(
                    __('%1$s %2$s'),
                    __('Clone of', 'behaviors'),
                    $item->getNameID(0, true)
                )
            );
            Log::history(
                $clone->getID(),
                $clone->getType(),
                $changes,
                0,
                Log::HISTORY_LOG_SIMPLE_MESSAGE
            );
        }
    }

    /**
     * show warning message
     *
     * @param $params
     *
     * @return string
     **/
    public static function checkWarnings($params)
    {
        global $DB;

        $warnings = [];
        $obj = $params['options']['item'];

        $config = PluginBehaviorsConfig::getInstance();

        // Check is the connected user is a tech
        if (
            !is_numeric(Session::getLoginUserID(false))
            || (!Session::haveRight('ticket', UPDATE)
                && !Session::haveRight('problem', UPDATE)
                && !Session::haveRight('change', UPDATE))
        ) {
            return false; // No check
        }

        // Want to solve/close the ticket
        $dur = ($obj->fields['actiontime'] ?? 0);
        $cat = ($obj->fields['itilcategories_id'] ?? 0);
        $loc = ($obj->fields['locations_id'] ?? 0);

        if ($obj->getType() == 'Ticket') {
            $mandatory_solution = false;
            if ($config->getField('is_ticketrealtime_mandatory')) {
                // for moreTicket plugin
                $plugin = new Plugin();
                if ($plugin->isActivated('moreticket')) {
                    $configmoreticket = new PluginMoreticketConfig();
                    $mandatory_solution = $configmoreticket->isMandatorysolution();
                }

                if (($dur == 0) && ($mandatory_solution == false)) {
                    $warnings[] = __("Duration is mandatory before ticket is solved/closed", 'behaviors');
                }
            }
            if ($config->getField('is_ticketcategory_mandatory')) {
                if ($cat == 0) {
                    $warnings[] = __("Category is mandatory before ticket is solved/closed", 'behaviors');
                }
            }


            if ($config->getField('is_tickettech_mandatory')) {
                if (
                    ($obj->countUsers(CommonITILActor::ASSIGN) == 0)
                    && !$config->getField('ticketsolved_updatetech')
                ) {
                    $warnings[] = __(
                        "Technician assigned is mandatory before ticket is solved/closed",
                        'behaviors'
                    );
                }
            }

            if ($config->getField('is_tickettechgroup_mandatory')) {
                if (($obj->countGroups(CommonITILActor::ASSIGN) == 0)) {
                    $warnings[] = __(
                        "Group of technicians assigned is mandatory before ticket is solved/closed",
                        'behaviors'
                    );
                }
            }

            if ($config->getField('is_ticketlocation_mandatory')) {
                if ($loc == 0) {
                    $warnings[] = __("Location is mandatory before ticket is solved/closed", 'behaviors');
                }
            }

            if ($config->getField('is_tickettasktodo')) {
                foreach (
                    $DB->request(
                        'glpi_tickettasks',
                        ['tickets_id' => $obj->getField('id')]
                    ) as $task
                ) {
                    if ($task['state'] == 1) {
                        $warnings[] = __("You cannot solve/close a ticket with task do to", 'behaviors');
                        break;
                    }
                }
            }
            foreach (
                $DB->request(
                    'glpi_ticketvalidations',
                    ['tickets_id' => $obj->getField('id')]
                ) as $validation
            ) {
                if ($validation['status'] == TicketValidation::WAITING && $config->getField('is_ticketvalidation_mandatory')) {
                    $warnings[] = __("You cannot solve/close a ticket with pending validation", 'behaviors');
                    break;
                }
            }
        }

        if ($obj->getType() == 'Problem') {
            if ($config->getField('is_problemtasktodo')) {
                foreach (
                    $DB->request(
                        'glpi_problemtasks',
                        ['problems_id' => $obj->getField('id')]
                    ) as $task
                ) {
                    if ($task['state'] == 1) {
                        $warnings[] = __("You cannot solve/close a problem with task do to", 'behaviors');
                        break;
                    }
                }
            }
        }

        if ($obj->getType() == 'Change') {
            if ($config->getField('is_changetasktodo')) {
                foreach (
                    $DB->request(
                        'glpi_changetasks',
                        ['changes_id' => $obj->getField('id')]
                    ) as $task
                ) {
                    if ($task['state'] == 1) {
                        $warnings[] = __("You cannot solve/close a change with task do to", 'behaviors');
                        break;
                    }
                }
            }
        }
        return $warnings;
    }


    /**
     * Displaying message solution
     *
     * @param $params
     **/
    public static function messageWarning($params)
    {
        if (isset($params['item'])) {
            $item = $params['item'];
            if ($item->getType() == 'ITILSolution') {
                $warnings = self::checkWarnings($params);
                $config = PluginBehaviorsConfig::getInstance();
                if (
                    (is_array($warnings) && count($warnings))
                    || $config->getField('is_ticketsolution_mandatory')
                    || $config->getField('is_ticketsolutiontype_mandatory')
                ) {
                    echo "<div class='alert alert-warning'>";

                    echo "<div style='display:flex;align-items: center;'>";

                    echo "<div style='margin-right: 20px;'>";
                    echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:orange;vertical-align: top;'></i>";
                    echo "</div>";

                    echo "<div>";
                    if ($config->getField('is_ticketsolution_mandatory') && is_array($warnings) && count($warnings) == 0) {
                        echo "<h4 class='alert-title'>" . __(
                            "You must add a description. it's mandatory",
                            'behaviors'
                        ) . "</h4>";
                    }
                    if ($config->getField('is_ticketsolutiontype_mandatory') && is_array($warnings) && count($warnings) == 0) {
                        echo "<h4 class='alert-title'>" . __(
                            "You must add a solution type. it's mandatory",
                            'behaviors'
                        ) . "</h4>";
                    }
                    if (is_array($warnings) && count($warnings)) {
                        echo "<h4 class='alert-title'>" . __('You cannot resolve the ticket', 'behaviors') . " :</h4>";
                        echo "<div class='text-muted'>" . implode('</div><div>', $warnings) . "</div>";
                    }
                    echo "</div>";

                    echo "</div>";

                    echo "</div>";
                }

            } elseif ($item->getType() == 'TicketTask') {
                $config = PluginBehaviorsConfig::getInstance();
                if ($config->getField('is_tickettaskcategory_mandatory')) {
                    echo "<div class='alert alert-warning'>";

                    echo "<div class='d-flex'>";

                    echo "<div class='me-2'>";
                    echo "<i class='fas fa-exclamation-triangle fa-2x' style='color:orange'></i>";
                    echo "</div>";

                    echo "<div>";
                    echo "<h4 class='alert-title'>" . __(
                        "You must define a category. it's mandatory",
                        'behaviors'
                    ) . "</h4>";
                    echo "</div>";

                    echo "</div>";

                    echo "</div>";
                }
            }
        }
        return $params;
    }


    /**
     * Displaying Add solution button or not
     *
     * @param $params
     *
     * @return array
     **/
    public static function deleteAddSolutionButton($params)
    {
        if (isset($params['item'])) {
            $item = $params['item'];
            if ($item->getType() == 'ITILSolution') {
                //                $options = $params['options'];
                //                $config = PluginBehaviorsConfig::getInstance();
                //                if ($config->getField('is_ticketrealtime_mandatory')) {
                //                    $ticket = $options['item'];
                //                    echo "<div class='row mx-n3 mx-xxl-auto'>";
                //                    echo "<div class='col-12 mb-3'>";
                //                    echo __('Duration');
                //                    echo "&nbsp;<span style='color:red'>*</span>&nbsp;";
                //
                //                    $rand = mt_rand();
                //                    echo "<span id='duration_solution_" . $rand . $ticket->fields['id'] . "'>";
                //                    $toadd = [];
                //                    for ($i = 9; $i <= 100; $i++) {
                //                        $toadd[] = $i * HOUR_TIMESTAMP;
                //                    }
                //                    echo Html::scriptBlock(
                //                        "function showsolutionbutton(){
                //                                 $('.itilsolution').children().find(':submit').show();
                //                              }"
                //                    );
                //
                //                    Dropdown::showTimeStamp("duration_solution", [
                //                        'min' => 0,
                //                        'max' => 8 * HOUR_TIMESTAMP,
                //                        'inhours' => true,
                //                        'toadd' => $toadd,
                //                        'on_change' => 'showsolutionbutton();'
                //                    ]);
                //
                //                    echo "</span>";
                //                    echo "</div>";
                //                    echo "</div>";
                //                }
                $warnings = self::checkWarnings($params);
                if (is_array($warnings) && count($warnings) > 0) {
                    echo Html::scriptBlock(
                        "$(document).ready(function(){
                        $('.itilsolution').children().find(':submit').hide();
                     });"
                    );
                }
            }
        }
    }
}
