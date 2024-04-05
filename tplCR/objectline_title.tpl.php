<?php
/* Copyright (C) 2010-2013	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2017		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 *
 * $type, $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page can't be called as URL";
	exit;
}

print "<!-- BEGIN PHP TEMPLATE objectline_title.tpl.php -->\n";

// Title line
print "<thead>\n";

print '<tr class="liste_titre nodrag nodrop">';

// Adds a line numbering column
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
	print '<th class="linecolnum center">&nbsp;</th>';
}
?>

        <th class="linecollabel minwidth400imp">
            <div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('RoomCR'); ?></span>
        </th>
        <th class="linecoldescription center"><?php echo $langs->trans('Description'); ?></th>
        <th class="linecoledit" colspan="<?php echo $colspan; ?>">&nbsp;</th>
<?php


print '<th class="linecoledit"></th>'; // No width to allow autodim

print '<th class="linecoldelete" style="width: 10px"></th>';

print '<th class="linecolmove" style="width: 10px"></th>';

if ($action == 'selectlines') {
	print '<th class="linecolcheckall center">';
	print '<input type="checkbox" class="linecheckboxtoggle" />';
	print '<script>$(document).ready(function() {$(".linecheckboxtoggle").click(function() {var checkBoxes = $(".linecheckbox");checkBoxes.prop("checked", this.checked);})});</script>';
	print '</th>';
}

print "</tr>\n";
print "</thead>\n";

print "<!-- END PHP TEMPLATE objectline_title.tpl.php -->\n";
