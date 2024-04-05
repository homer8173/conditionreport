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
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $element     (used to test $user->rights->$element->creer)
 * $permtoedit  (used to replace test $user->rights->$element->creer)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $outputalsopricetotalwithtax
 * $usemargins (0 to disable all margins columns, 1 to show according to margin setup)
 * $object_rights->creer initialized from = $object->getRights()
 * $disableedit, $disablemove, $disableremove
 *
 * $text, $description, $line
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
    print "Error, template page can't be called as URL";
    exit;
}

global $mysoc;
global $forceall, $senderissupplier, $inputalsopricewithtax, $outputalsopricetotalwithtax;

$usemargins = 0;
if (isModEnabled('margin') && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) {
    $usemargins = 1;
}

if (empty($dateSelector)) {
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
if (empty($outputalsopricetotalwithtax)) {
    $outputalsopricetotalwithtax = 0;
}

// add html5 elements
$domData = ' data-element="' . $line->element . '"';
$domData .= ' data-id="' . $line->id . '"';
$domData .= ' data-qty="' . $line->qty . '"';
//$domData .= ' data-product_type="'.$line->product_type.'"';

$sign = 1;

$coldisplay = 0;

?>
<!-- BEGIN PHP TEMPLATE objectline_view.tpl.php -->
<tr  id="row-<?php print $line->id ?>" class="drag drop oddeven" <?php print $domData; ?> >
    <?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
        <td class="linecolnum center"><span class="opacitymedium"><?php $coldisplay++; ?><?php print ($i + 1); ?></span></td>
    <?php } ?>
    <td class="linecollabel minwidth300imp"><?php $coldisplay++; ?><div id="line_<?php print $line->id; ?>"></div>
        <?php
        $coldisplay++;
//        $text = img_object($langs->trans('RoomCR'), 'square');
        $text = ' <strong>' . $line->getNomUrl(1) . '</strong>';
        print $form->textwithtooltip($text, dol_htmlentitiesbr($line->description), 3, 0, '', $i);

        print '</td>';

        print '<td class="linecoldescription nowraponall left">';
        $coldisplay++;
        print dol_htmlentitiesbr($line->description);
        print '</td>';

        print '<td class="linecol nowraponall">';
        print '</td>';

        if ($this->statut == 0 && !empty($object_rights->write) && $action != 'selectlines') {
            $situationinvoicelinewithparent = 0;
            if (isset($line->fk_prev_id) && in_array($object->element, array('facture', 'facturedet'))) {
                if ($object->type == $object::TYPE_SITUATION) { // The constant TYPE_SITUATION exists only for object invoice
                    // Set constant to disallow editing during a situation cycle
                    $situationinvoicelinewithparent = 1;
                }
            }

            // Asset info
            if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
                print '<td class="linecolasset center">';
                $coldisplay++;
                if (!empty($product_static->accountancy_code_buy) ||
                    !empty($product_static->accountancy_code_buy_intra) ||
                    !empty($product_static->accountancy_code_buy_export)
                ) {
                    $accountancy_category_asset = $conf->global->ASSET_ACCOUNTANCY_CATEGORY;
                    $filters                    = array();
                    if (!empty($product_static->accountancy_code_buy))
                        $filters[]                  = "account_number = '" . $this->db->escape($product_static->accountancy_code_buy) . "'";
                    if (!empty($product_static->accountancy_code_buy_intra))
                        $filters[]                  = "account_number = '" . $this->db->escape($product_static->accountancy_code_buy_intra) . "'";
                    if (!empty($product_static->accountancy_code_buy_export))
                        $filters[]                  = "account_number = '" . $this->db->escape($product_static->accountancy_code_buy_export) . "'";
                    $sql                        = "SELECT COUNT(*) AS found";
                    $sql                        .= " FROM " . MAIN_DB_PREFIX . "accounting_account";
                    $sql                        .= " WHERE pcg_type = '" . $this->db->escape($conf->global->ASSET_ACCOUNTANCY_CATEGORY) . "'";
                    $sql                        .= " AND (" . implode(' OR ', $filters) . ")";
                    $resql_asset                = $this->db->query($sql);
                    if (!$resql_asset) {
                        print 'Error SQL: ' . $this->db->lasterror();
                    } elseif ($obj = $this->db->fetch_object($resql_asset)) {
                        if (!empty($obj->found)) {
                            print '<a class="reposition" href="' . DOL_URL_ROOT . '/asset/card.php?action=create&token=' . newToken() . '&supplier_invoice_id=' . $object->id . '">';
                            print img_edit_add() . '</a>';
                        }
                    }
                }
                print '</td>';
            }

            // Edit picto
            print '<td class="linecoledit center">';
            $coldisplay++;
            if ($object->status == Conditionreportroom::STATUS_DRAFT) {

                ?>
                <a class="editfielda" href="<?php print dol_buildpath('/conditionreport/conditionreportroom_card.php', 1) . '?id=' . $line->id ; ?>">
                    <?php
                    print img_edit() . '</a>';
                }
                print '</td>';

                // Delete picto
                print '<td class="linecoldelete center">';
                $coldisplay++;
                if ($object->status == Conditionreportroom::STATUS_DRAFT) {
                    print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=deleteline&token=' . newToken() . '&lineid=' . $line->id . '">';
                    print img_delete();
                    print '</a>';
                }
                print '</td>';

                // Move up-down picto
                if ($num > 1 && $conf->browser->layout != 'phone' && ($this->situation_counter == 1 || !$this->situation_cycle_ref) && empty($disablemove)) {
                    print '<td class="linecolmove tdlineupdown center">';
                    print '</td>';
                } else {
                    print '<td ' . (($conf->browser->layout != 'phone' && empty($disablemove)) ? ' class="linecolmove tdlineupdown center"' : ' class="linecolmove center"') . '></td>';
                    $coldisplay++;
                }
            } else {
                print '<td colspan="3"></td>';
                $coldisplay = $coldisplay + 3;
            }

            if ($action == 'selectlines') {

                ?>
                <td class="linecolcheck center"><input type="checkbox" class="linecheckbox" name="line_checkbox[<?php print $i + 1; ?>]" value="<?php print $line->id; ?>" ></td>
                <?php
            }

            print "</tr>\n";

            print "<!-- END PHP TEMPLATE objectline_view.tpl.php -->\n";
            