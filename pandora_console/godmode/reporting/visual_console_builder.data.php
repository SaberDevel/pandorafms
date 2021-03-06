<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Login check
global $config;

check_login ();

if (empty($idVisualConsole)) {
	// ACL for the a new visual console
	// if (!isset($vconsole_read))
	// 	$vconsole_read = check_acl ($config['id_user'], 0, "VR");
	if (!isset($vconsole_write))
		$vconsole_write = check_acl ($config['id_user'], 0, "VW");
	if (!isset($vconsole_manage))
		$vconsole_manage = check_acl ($config['id_user'], 0, "VM");
}
else {
	// ACL for the existing visual console
	// if (!isset($vconsole_read))
	// 	$vconsole_read = check_acl ($config['id_user'], $idGroup, "VR");
	if (!isset($vconsole_write))
		$vconsole_write = check_acl ($config['id_user'], $idGroup, "VW");
	if (!isset($vconsole_manage))
		$vconsole_manage = check_acl ($config['id_user'], $idGroup, "VM");
}

if (!$vconsole_write && !$vconsole_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'] . '/include/functions_visual_map.php');
require_once ($config['homedir'] . '/include/functions_users.php');

$pure = get_parameter('pure', 0);

switch ($action) {
	case 'new':
		if (!defined('METACONSOLE')) {
			echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "' enctype='multipart/form-data'>";
			html_print_input_hidden('action', 'save');
		}
		else {
			echo '<form action="index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '" method="post"  enctype="multipart/form-data">';
			html_print_input_hidden('action2', 'save');
		}
		
		break;
	case 'update':
	case 'save':
		if (!defined('METACONSOLE')) {
			echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "' enctype='multipart/form-data'>";
			html_print_input_hidden('action', 'update');
		}
		else {
			//echo '<form action="index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure=' . $pure . '" method="post">';
			echo "<form action='index.php?sec=screen&sec2=screens/screens&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "&id_visualmap=" . $idVisualConsole . "&action=visualmap' method='post' enctype='multipart/form-data'>";
			html_print_input_hidden('action2', 'update');
		}
		break;
	case 'edit':
		if (!defined('METACONSOLE')) {
			echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "' enctype='multipart/form-data'>";
			html_print_input_hidden('action', 'update');
		}
		else {
			echo "<form action='index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&tab=" . $activeTab  . "&id_visual_console=" . $idVisualConsole . "&action=visualmap' method='post' enctype='multipart/form-data' >";
			html_print_input_hidden('action2', 'update');
		}
		break;
}

$table = new stdClass();
$table->width = '100%';
if (defined('METACONSOLE')) {
	$table->class = 'databox data';
	$table->head[0] = __("Create visual console");
	$table->head_colspan[0] = 5;
	$table->headstyle[0] = 'text-align: center';
	$table->align[0] = 'left';
	$table->align[1] = 'left';
}
$table->class = 'databox filters';
$table->data = array ();
$table->data[0][0] = __('Name:') .
	ui_print_help_tip(__("Use [ or ( as first character, for example '[*] Map name', to render this map name in main menu"), true);

$table->data[0][1] = html_print_input_text('name', $visualConsoleName,
	'', 80, 100, true);
$table->data[1][0] = __('Group:');
$groups = users_get_groups ($config['id_user'], 'RW');

$own_info = get_user_info($config['id_user']);
// Only display group "All" if user is administrator
// or has "RW" privileges
if ($own_info['is_admin'] || $vconsole_write || $vconsole_manage)
	$display_all_group = true;
else
	$display_all_group = false;

$table->data[1][1] = html_print_select_groups($config['id_user'], "RW",
	$display_all_group, 'id_group', $idGroup, '', '', '', true);
$backgrounds_list = list_files(
	$config['homedir'] . '/images/console/background/', "jpg", 1, 0);
$backgrounds_list = array_merge($backgrounds_list,
	list_files($config['homedir'] . '/images/console/background/', "png", 1, 0));
$table->data[2][0] = __('Background');
$table->data[2][1] = html_print_select($backgrounds_list, 'background',
	$background, '', '', 0, true);
$table->data[3][0] = __('Background image');
$table->data[3][1] = html_print_input_file('background_image',true);
if ($action == 'new') {
	$textButtonSubmit = __('Save');
	$classButtonSubmit = 'sub wand';
}
else {
	$textButtonSubmit = __('Update');
	$classButtonSubmit = 'sub upd';
}

html_print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button ($textButtonSubmit, 'update_layout', false,
	'class="' . $classButtonSubmit . '"');
echo '</div>';

echo "</form>";
?>
