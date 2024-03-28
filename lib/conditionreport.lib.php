<?php
/* Copyright (C) 2024 SuperAdmin
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    conditionreport/lib/conditionreport.lib.php
 * \ingroup conditionreport
 * \brief   Library files with common functions for Conditionreport
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function conditionreportAdminPrepareHead()
{
	global $langs, $conf;

	 global $db;
	 $extrafields = new ExtraFields($db);
	 $extrafields->fetch_name_optionals_label('conditionreport');

	$langs->load("conditionreport@conditionreport");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/conditionreport/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	
	$head[$h][0] = dol_buildpath("/conditionreport/admin/conditionreport_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFieldsConditionreport");
	$nbExtrafields = is_countable($extrafields->attributes['conditionreport']['label']) ? count($extrafields->attributes['conditionreport']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'conditionreport_extrafields';
	$h++;
    
    
	 $extrafields2 = new ExtraFields($db);
	 $extrafields2->fetch_name_optionals_label('conditionreportroom');
	$head[$h][0] = dol_buildpath("/conditionreport/admin/conditionreportroom_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFieldsConditionreportroom");
	$nbExtrafields2 = is_countable($extrafields2->attributes['conditionreportroom']['label']) ? count($extrafields2->attributes['conditionreportroom']['label']) : 0;
	if ($nbExtrafields2 > 0) {
		$head[$h][1] .= ' <span class="badge">' . $nbExtrafields2 . '</span>';
	}
	$head[$h][2] = 'conditionreportroom_extrafields';
	$h++;
	

	$head[$h][0] = dol_buildpath("/conditionreport/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@conditionreport:/conditionreport/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@conditionreport:/conditionreport/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'conditionreport@conditionreport');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'conditionreport@conditionreport', 'remove');

	return $head;
}
