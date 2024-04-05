<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2022	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * $seller, $buyer
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 * $canchangeproduct (0 by default, 1 to allow to change the product if it is a predefined product)
 */

// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
    print "Error, template page can't be called as URL";
    exit;
}


$usemargins = 0;
if (isModEnabled('margin') && !empty($object->element) && in_array($object->element, array('facture', 'facturerec', 'propal', 'commande'))) {
    $usemargins = 1;
}

global $forceall, $senderissupplier, $inputalsopricewithtax, $canchangeproduct;
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
if (empty($canchangeproduct)) {
    $canchangeproduct = 0;
}

// Define colspan for the button 'Add'
$colspan = 3; // Col total ht + col edit + col delete
if (!empty($inputalsopricewithtax)) {
    $colspan++; // We add 1 if col total ttc
}
if (in_array($object->element, array('propal', 'supplier_proposal', 'facture', 'facturerec', 'invoice', 'commande', 'order', 'order_supplier', 'invoice_supplier', 'invoice_supplier_rec'))) {
    $colspan++; // With this, there is a column move button
}
if (isModEnabled("multicurrency") && $object->multicurrency_code != $conf->currency) {
    $colspan += 2;
}
if (isModEnabled('asset') && $object->element == 'invoice_supplier') {
    $colspan++;
}



print "<!-- BEGIN PHP TEMPLATE objectline_edit.tpl.php -->\n";

$coldisplay = 0;

?>
<tr class="oddeven tredited">
    <?php if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) { ?>
        <td class="linecolnum center"><?php $coldisplay++; ?><?php echo ($i + 1); ?></td>
        <?php
    }

    $coldisplay++;

    ?>
    <td class="linecollabel minwidth250onall">
        <div id="line_<?php echo $line->id; ?>"></div>

        <input type="hidden" name="lineid" value="<?php echo $line->id; ?>">
        <?php
        $label = (GETPOST('label') ? GETPOST('label', 'alpha') : $line->label);
        echo '<input type="text" name="label" id="label" value="' . $label . '">';
        //Line extrafield
        if (!empty($extrafields)) {
            $temps = $line->showOptionals($extrafields, 'edit', array('class' => 'tredited'), '', '', 1, 'line');
            if (!empty($temps)) {
                print '<div style="padding-top: 10px" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
                print $temps;
                print '</div>';
            }
        }

        ?>
    </td>	<td class="linecolqty right">
        <?php
        $coldisplay++;
        print '<input size="3" type="text" class="flat right" name="qty" id="qty" value="' . (GETPOSTISSET('qty') ? GETPOST('qty') : $line->qty) . '" />';

        ?>
    </td><td class="linecolcondition right">
        <?php
        $coldisplay++;
        $conditions = [];
        foreach (Conditionreportroom::CONDITION as $key => $value) {
            $conditions[$key] = $langs->trans($value);
        }
        print $form->selectarray('condition', $conditions, (GETPOSTISSET('condition') ? GETPOST('condition') : $line->condition), 0, 0, 0, '', 0, 0, 0, '', 'minwidth75', 0);
        ?>
    </td>
    <td class="linecoldesc minwidth250onall">
        <?php
        // editor wysiwyg
        require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
        $nbrows = ROWS_2;
        if (!empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) {
            $nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
        }
        $enable      = (isset($conf->global->FCKEDITOR_ENABLE_DETAILS) ? $conf->global->FCKEDITOR_ENABLE_DETAILS : 0);
        $toolbarname = 'dolibarr_details';
        if (!empty($conf->global->FCKEDITOR_ENABLE_DETAILS_FULL)) {
            $toolbarname = 'dolibarr_notes';
        }
        $doleditor = new DolEditor('product_desc', GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : $line->description, '', (empty($conf->global->MAIN_DOLEDITOR_HEIGHT) ? 164 : $conf->global->MAIN_DOLEDITOR_HEIGHT), $toolbarname, '', false, true, $enable, $nbrows, '98%');
        $doleditor->Create();

        ?>
    </td>


    <!-- colspan for this td because it replace total_ht+3 td for buttons+... -->
    <td class="center valignmiddle" colspan="4">
        <input type="submit" class="reposition button buttongen marginbottomonly button-save" id="savelinebutton marginbottomonly" name="save" value="<?php echo $langs->trans("Save"); ?>"><br>
        <input type="submit" class="reposition button buttongen marginbottomonly button-cancel" id="cancellinebutton" name="cancel" value="<?php echo $langs->trans("Cancel"); ?>">
    </td>
</tr>
<script>

<?php
if (!empty($usemargins) && $user->rights->margins->creer) {

    ?>
        /* Some js test when we click on button "Add" */
        jQuery(document).ready(function () {
    <?php
    if (!empty($conf->global->DISPLAY_MARGIN_RATES)) {

        ?>
                $("input[name='np_marginRate']:first").blur(function (e) {
                    return checkFreeLine(e, "np_marginRate");
                });
        <?php
    }
    if (!empty($conf->global->DISPLAY_MARK_RATES)) {

        ?>
                $("input[name='np_markRate']:first").blur(function (e) {
                    return checkFreeLine(e, "np_markRate");
                });
        <?php
    }

    ?>
        });

        /* TODO This does not work for number with thousand separator that is , */
        function checkFreeLine(e, npRate)
        {
            var buying_price = $("input[name='buying_price']:first");
            var remise = $("input[name='remise_percent']:first");

            var rate = $("input[name='" + npRate + "']:first");
            if (rate.val() == '')
                return true;

            var ratejs = price2numjs(rate.val());
            if (!$.isNumeric(ratejs))
            {
                alert('<?php echo dol_escape_js($langs->transnoentities("rateMustBeNumeric")); ?>');
                e.stopPropagation();
                setTimeout(function () {
                    rate.focus()
                }, 50);
                return false;
            }
            if (npRate == "np_markRate" && rate.val() >= 100)
            {
                alert('<?php echo dol_escape_js($langs->transnoentities("markRateShouldBeLesserThan100")); ?>');
                e.stopPropagation();
                setTimeout(function () {
                    rate.focus()
                }, 50);
                return false;
            }

            var price = 0;
            remisejs = price2numjs(remise.val());

            if (remisejs != 100)	// If a discount not 100 or no discount
            {
                if (remisejs == '')
                    remisejs = 0;

                bpjs = price2numjs(buying_price.val());
                ratejs = price2numjs(rate.val());

                if (npRate == "np_marginRate")
                    price = ((bpjs * (1 + ratejs / 100)) / (1 - remisejs / 100));
                else if (npRate == "np_markRate")
                    price = ((bpjs / (1 - ratejs / 100)) / (1 - remisejs / 100));
            }
            $("input[name='price_ht']:first").val(price);	// TODO Must use a function like php price to have here a formated value

            return true;
        }
    <?php
}

?>

    jQuery(document).ready(function ()
    {
        jQuery("#price_ht").keyup(function (event) {
            // console.log(event.which);		// discard event tag and arrows
            if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#price_ht").val() != '') {
                jQuery("#price_ttc").val('');
                jQuery("#multicurrency_subprice").val('');
            }
        });
        jQuery("#price_ttc").keyup(function (event) {
            // console.log(event.which);		// discard event tag and arrows
            if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#price_ttc").val() != '') {
                jQuery("#price_ht").val('');
                jQuery("#multicurrency_subprice").val('');
            }
        });
        jQuery("#multicurrency_subprice").keyup(function (event) {
            // console.log(event.which);		// discard event tag and arrows
            if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#price_ttc").val() != '') {
                jQuery("#price_ht").val('');
                jQuery("#price_ttc").val('');
            }
        });

<?php if (in_array($object->table_element_line, array('propaldet', 'commandedet', 'facturedet'))) { ?>
            $("#date_start, #date_end").focusout(function () {
                if ($(this).val() == '' && !$(this).hasClass('inputmandatory')) {
                    $(this).addClass('inputmandatory');
                } else {
                    $(this).removeClass('inputmandatory');
                }
            });
    <?php
}

if (isModEnabled('margin')) {

    ?>
            /* Add rule to clear margin when we change some data, so when we change sell or buy price, margin will be recalculated after submitting form */
            jQuery("#tva_tx").click(function () {						/* somtimes field is a text, sometimes a combo */
                jQuery("input[name='np_marginRate']:first").val('');
                jQuery("input[name='np_markRate']:first").val('');
            });
            jQuery("#tva_tx").keyup(function () {						/* somtimes field is a text, sometimes a combo */
                jQuery("input[name='np_marginRate']:first").val('');
                jQuery("input[name='np_markRate']:first").val('');
            });
            jQuery("#price_ht").keyup(function () {
                jQuery("input[name='np_marginRate']:first").val('');
                jQuery("input[name='np_markRate']:first").val('');
            });
            jQuery("#qty").keyup(function () {
                jQuery("input[name='np_marginRate']:first").val('');
                jQuery("input[name='np_markRate']:first").val('');
            });
            jQuery("#remise_percent").keyup(function () {
                jQuery("input[name='np_marginRate']:first").val('');
                jQuery("input[name='np_markRate']:first").val('');
            });
            jQuery("#buying_price").keyup(function () {
                jQuery("input[name='np_marginRate']:first").val('');
                jQuery("input[name='np_markRate']:first").val('');
            });

            /* Init field buying_price and fournprice */
            var token = '<?php echo currentToken(); ?>';		// For AJAX Call we use old 'token' and not 'newtoken'
            $.post('<?php echo DOL_URL_ROOT; ?>/fourn/ajax/getSupplierPrices.php', {'idprod': <?php echo $line->fk_product ? $line->fk_product : 0; ?>, 'token': token}, function (data) {
                if (data && data.length > 0) {
                    var options = '';
                    var trouve = false;
                    $(data).each(function () {
                        options += '<option value="' + this.id + '" price="' + this.price + '"';
    <?php if ($line->fk_fournprice > 0) { ?>
                            if (this.id == <?php echo $line->fk_fournprice; ?>) {
                                options += ' selected';
                                $("#buying_price").val(this.price);
                                trouve = true;
                            }
    <?php } ?>
                        options += '>' + this.label + '</option>';
                    });
                    options += '<option value=null' + (trouve ? '' : ' selected') + '><?php echo $langs->trans("InputPrice"); ?></option>';
                    $("#fournprice").html(options);
                    if (trouve) {
                        $("#buying_price").hide();
                        $("#fournprice").show();
                    } else {
                        $("#buying_price").show();
                    }
                    $("#fournprice").change(function () {
                        var selval = $(this).find('option:selected').attr("price");
                        if (selval)
                            $("#buying_price").val(selval).hide();
                        else
                            $('#buying_price').show();
                    });
                } else {
                    $("#fournprice").hide();
                    $('#buying_price').show();
                }
            }, 'json');
    <?php
}

?>
    });

</script>
<!-- END PHP TEMPLATE objectline_edit.tpl.php -->
