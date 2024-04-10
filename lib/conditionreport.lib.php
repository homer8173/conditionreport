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




/**
 * Show footer of company in HTML pages
 *
 * @param   Societe		$fromcompany	Third party
 * @param   Translate	$langs			Output language
 * @param	int			$addformmessage	Add the payment form message
 * @param	string		$suffix			Suffix to use on constants
 * @param	Object		$object			Object related to payment
 * @return	void
 */
function htmlPrintOnlineFooter($fromcompany, $langs, $addformmessage = 0, $suffix = '', $object = null)
{
	global $conf;

	$reg = array();

	// Juridical status
	$line1 = "";
	if ($fromcompany->forme_juridique_code) {
		$line1 .= ($line1 ? " - " : "").getFormeJuridiqueLabel($fromcompany->forme_juridique_code);
	}
	// Capital
	if ($fromcompany->capital) {
		$line1 .= ($line1 ? " - " : "").$langs->transnoentities("CapitalOf", $fromcompany->capital)." ".$langs->transnoentities("Currency".$conf->currency);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
		$field = $langs->transcountrynoentities("ProfId1", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line1 .= ($line1 ? " - " : "").$field.": ".$fromcompany->idprof1;
	}
	// Prof Id 2
	if ($fromcompany->idprof2) {
		$field = $langs->transcountrynoentities("ProfId2", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line1 .= ($line1 ? " - " : "").$field.": ".$fromcompany->idprof2;
	}

	// Second line of company infos
	$line2 = "";
	// Prof Id 3
	if ($fromcompany->idprof3) {
		$field = $langs->transcountrynoentities("ProfId3", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line2 .= ($line2 ? " - " : "").$field.": ".$fromcompany->idprof3;
	}
	// Prof Id 4
	if ($fromcompany->idprof4) {
		$field = $langs->transcountrynoentities("ProfId4", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg)) {
			$field = $reg[1];
		}
		$line2 .= ($line2 ? " - " : "").$field.": ".$fromcompany->idprof4;
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '') {
		$line2 .= ($line2 ? " - " : "").$langs->transnoentities("VATIntraShort").": ".$fromcompany->tva_intra;
	}

	print '<!-- htmlPrintOnlineFooter -->'."\n";

	// css centpercent has been removed from class="..." because not compatible with paddingleft/right and there is an horizontal scroll appearring on payment page for example.
	print '<footer class="center paddingleft paddingright opacitymedium">'."\n";
	print '<br>';
	if ($addformmessage) {
		print '<!-- object = '.(empty($object) ? 'undefined' : $object->element).' -->';
		print '<br>';

		$parammessageform = 'ONLINE_PAYMENT_MESSAGE_FORM_'.$suffix;
		if (getDolGlobalString($parammessageform) !== '') {
			print $langs->transnoentities(getDolGlobalString($parammessageform));
		} elseif (getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORM')  !== '') {
			print $langs->transnoentities(getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORM'));
		}

		// Add other message if VAT exists
		if (!empty($object->total_vat) || !empty($object->total_tva)) {
			$parammessageform = 'ONLINE_PAYMENT_MESSAGE_FORMIFVAT_'.$suffix;
			if (getDolGlobalString($parammessageform)  !== '') {
				print $langs->transnoentities(getDolGlobalString($parammessageform));
			} elseif (getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORMIFVAT') !== '') {
				print $langs->transnoentities(getDolGlobalString('ONLINE_PAYMENT_MESSAGE_FORMIFVAT'));
			}
		}
	}

	print '<span style="font-size: 10px;"><br><hr>'."\n";
	print $fromcompany->name.'<br>';
	print $line1;
	if (strlen($line1.$line2) > 50) {
		print '<br>';
	} else {
		print ' - ';
	}
	print $line2;
	print '</span>';
	print '</footer>'."\n";
}