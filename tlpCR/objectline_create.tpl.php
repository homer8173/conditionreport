<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2014		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019		Nicolas ZABOURI		<info@inovea-conseil.com>
 * Copyright (C) 2022		OpenDSI				<support@open-dsi.fr>
 * Copyright (C) 2022      	Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
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
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 or 2 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */
$colspan = 4;
// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
    print "Error: this template page cannot be called directly as an URL";
    exit;
}
$usemargins = 0;
if (isModEnabled('margin') && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) {
    $usemargins = 1;
}
if (!isset($dateSelector)) {
    global $dateSelector; // Take global var only if not already defined into function calling (for example formAddObjectLine)
}
global $forceall, $forcetoshowtitlelines, $senderissupplier, $inputalsopricewithtax;

if (!isset($dateSelector)) {
    $dateSelector = 1; // For backward compatibility
} elseif (empty($dateSelector)) {
    $dateSelector = 0;
}
if (empty($forceall)) {
    $forceall = 0;
}
if (empty($senderissupplier)) {
    $senderissupplier = 0;
}
if (empty($inputalsopricewithtax)) {
    $inputalsopricewithtax = 0;
}

print "<!-- BEGIN PHP TEMPLATE objectline_create.tpl.php -->\n";
$nolinesbefore = (count($this->lines) == 0 || $forcetoshowtitlelines);
if ($nolinesbefore) {

    ?>
    <tr class="liste_titre<?php echo (($nolinesbefore || $object->element == 'contrat') ? '' : ' liste_titre_add_') ?> nodrag nodrop">
        <?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
            <td class="linecolnum center"></td>
        <?php } ?>
        <td class="linecollabel minwidth400imp">
            <div id="add"></div><span class="hideonsmartphone"><?php echo $langs->trans('AddNewElement'); ?></span>
        </td>
        <td class="linecolqty right"><?php echo $langs->trans('Qty'); ?></td>
        <td class="linecolcondition right"><?php echo $langs->trans('Condition'); ?></td>
        <td class="linecoldescription center"><?php echo $langs->trans('Observations'); ?></td>
        <td class="linecoledit" colspan="<?php echo $colspan; ?>">&nbsp;</td>
    </tr>
    <?php
}

?>
<tr class="pair nodrag nodrop nohoverpair liste_titre_create">
    <?php
    $coldisplay = 0;
    // Adds a line numbering column
    if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
        $coldisplay++;
        echo '<td class="nobottom linecolnum center"></td>';
    }
    $coldisplay++;

    ?>
    <td class="nobottom linecolelement minwidth400imp">
        <input type="text" required="required" name="label" id="label" class="flat" style="width: 100%; " value="<?php echo (GETPOSTISSET("label") ? GETPOST("label", 'alpha', 2) : ''); ?>">
    </td>
    <td class="nobottom linecolqty right">
        <input type="text" name="qty" id="qty" class="flat width40 right" value="<?php echo (GETPOSTISSET("qty") ? GETPOST("qty", 'int', 2) : 1); ?>">
    </td>
    <td class="nobottom linecolqty right">
        <select id="condition" name="condition" class="flat minwidth75imp maxwidth150">
            <?php
            foreach (Conditionreportroom::CONDITION as $key => $value) {
                print '<option value="' . $key . '">';
                print $langs->trans($value);
                print '</option>';
            }

            ?>
        </select>
    </td>
    <td class="nobottom linecoldescription minwidth400imp">
        <?php
        // Editor wysiwyg
        require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        $nbrows  = ROWS_2;
        $enabled = (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
        if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
            $nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
        }
        $toolbarname = 'dolibarr_details';
        if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
            $toolbarname = 'dolibarr_notes';
        }
        $doleditor = new DolEditor('dp_desc', GETPOST('dp_desc', 'restricthtml'), '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 100 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enabled, $nbrows, '98%');
        $doleditor->Create();

        ?>
    </td>
    <td class="nobottom linecoledit center valignmiddle" colspan="<?php echo $colspan; ?>">
        <input type="submit" class="button reposition" value="<?php echo $langs->trans('Add'); ?>" name="addline" id="addline">
    </td>
</tr>
<!-- END PHP TEMPLATE objectline_create.tpl.php -->

