<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 SuperAdmin
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
 */

/**
 *  \file       conditionreport_contact.php
 *  \ingroup    conditionreport
 *  \brief      Tab for contacts linked to Conditionreport
 */
// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp  = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i    = strlen($tmp) - 1;
$j    = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
dol_include_once('/conditionreport/class/conditionreport.class.php');
dol_include_once('/conditionreport/lib/conditionreport_conditionreport.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("conditionreport@conditionreport", "companies", "other", "mails"));

$id     = (GETPOST('id') ? GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref    = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');
$socid  = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object              = new Conditionreport($db);
$extrafields         = new ExtraFields($db);
$diroutputmassaction = $conf->conditionreport->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array($object->element . 'diffcr', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 1;
if ($enablepermissioncheck) {
    $permissiontoread = $user->hasRight('conditionreport', 'conditionreport', 'read');
    $permissiontoadd  = $user->hasRight('conditionreport', 'conditionreport', 'write');
} else {
    $permissiontoread = 1;
    $permissiontoadd  = 1;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object->id, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("conditionreport")) {
    accessforbidden();
}
if (!$permissiontoread) {
    accessforbidden();
}

$rows = GETPOST('row', 'array');

/*
 * Add a new invoice
 */
$parameters = array('row' => $rows);
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    if ($action == 'createobject' && $permissiontoadd) {
        $mode   = GETPOST('mode', 'alpha');
        $object->fetch($id, $ref);
        if ($mode == 'CreateInvoice')
            $result = $object->createInvoice($rows);
        else
            $result = $object->createPropal($rows);
        if ($result > 0) {

            if ($mode == 'CreateInvoice')
                header("Location: " . dol_buildpath('/compta/facture/card.php', 2) . "?facid=" . $result);
            else
                header("Location: " . dol_buildpath('/comm/propal/card.php', 2) . "?id=" . $result);

            exit;
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}
/*
 * View
 */

$title    = $langs->trans("Conditionreport") . " - " . $langs->trans('ContactsAddresses');
//$title = $object->ref." - ".$langs->trans('ContactsAddresses');
$help_url = '';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-conditionreport page-card_contact');

$form          = new Form($db);
$formcompany   = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic    = new User($db);

/* * ************************************************************************** */
/*                                                                             */
/* View and edit mode                                                         */
/*                                                                             */
/* * ************************************************************************** */

if ($object->id) {
    /*
     * Show tabs
     */
    $head = conditionreportPrepareHead($object);

    print dol_get_fiche_head($head, 'diffcr', $langs->trans("Conditionreport"), -1, $object->picto);

    $linkback = '<a href="' . dol_buildpath('/conditionreport/conditionreport_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    /*
      // Ref customer
      $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
      $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
      // Thirdparty
      $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
      // Project
      if (isModEnabled('project')) {
      $langs->load("projects");
      $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
      if ($permissiontoadd)
      {
      if ($action != 'classify')
      //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
      $morehtmlref.=' : ';
      if ($action == 'classify') {
      //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
      $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
      $morehtmlref.='<input type="hidden" name="action" value="classin">';
      $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
      $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
      $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
      $morehtmlref.='</form>';
      } else {
      $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
      }
      } else {
      if (!empty($object->fk_project)) {
      $proj = new Project($db);
      $proj->fetch($object->fk_project);
      $morehtmlref .= ': '.$proj->getNomUrl();
      } else {
      $morehtmlref .= '';
      }
      }
      } */
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

    print dol_get_fiche_end();

    $list = $object->getDiffList();
//    var_dump($list);die();

    print '<br>';

    /**
     * Show list
     */
    $permission       = $user->hasRight("facture", "creer");
    $permissionPropal = $user->hasRight("propale", "creer");
    print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="createobject">';

    print '<div class="div-table-responsive-no-min">' . "\n";
    print '<table class="tagtable nobottomiftotal liste">';

    print '<tr class="liste_titre">';
    print '<th>' . $langs->trans("reasonCR") . '</th>';
    print '<th>' . $langs->trans("RoomName") . '</th>';
    print '<th>' . $langs->trans("elementlabel") . '</th>';
    print '<th>' . $langs->trans("Qty") . '</th>';
    print '<th>' . $langs->trans("Condition") . '</th>';
    print '<th>' . $langs->trans("Observations") . '</th>';
    if ($permission) {
        print '<th>' . $langs->trans("ProductInvoiced") . '</th>';
        print '<th>' . $langs->trans("InvoicedCR") . '</th>';
    }
    print "</tr>";
    foreach ($list as $ide => $entry) {
        print '<tr class="oddeven">';
        print '<td class="tdoverflowmax200">' . $langs->trans($entry->reason);
        print '<input type="hidden" name="row[' . $ide . '][label]" value="';
        print dol_escape_json($langs->trans($entry->reason));
        print '">';
        print '<input type="hidden" name="row[' . $ide . '][description]" value="';
        print "<p>" . dol_escape_json($langs->trans('RoomName') . ' : ' . $entry->roomlabel) . "<br />";
        print "" . dol_escape_json($langs->trans('elementlabel') . ' : ' . $entry->elementlabel) . ' (' . $object->getLabelCondition($entry->condition) . ')' . "</p>";
        print "<p>" . dol_escape_json($entry->description) . "</p>";
        print '">';
        print '</td>';
        print '<td class="tdoverflowmax200"><a href="' . dol_buildpath('/conditionreport/conditionreportroom_card.php', 2) . '?id=' . $entry->roomid . '">' . $entry->roomlabel . '</a></td>';
        print '<td class="nowrap"><span class="opacitymedium">' . $entry->elementlabel . '</span></td>';
        print '<td class="tdoverflowmax200"><input type="number" name="row[' . $ide . '][qty]" value="' . $entry->qty . '" class="flat width40 right" /></td>';
        print '<td class="tdoverflowmax200 center">' . $object->getLabelCondition($entry->condition) . '</td>';
        print '<td class="tdoverflowmax200 center">' . $entry->description . '</td>';

        if ($permission) {
            //($selected = 0, $htmlname = 'productid', $filtertype = '', $limit = 0, $price_level = 0, $status = 1, $finished = 2, $selected_input_value = '', $hidelabel = 0, $ajaxoptions = array(), $socid = 0, $showempty = '1', $forcecombo = 0, $morecss = '', $hidepriceinlabel = 0, $warehouseStatus = '', $selected_combinations = null, $nooutput = 0
//        $form->select_produits(GETPOST('idprod'), 'idprod', $filtertype, $conf->product->limit_size, $buyer->price_level, $statustoshow, 2, '', 1, array(), $buyer->id, '1', 0, 'maxwidth500', 0, $statuswarehouse, GETPOST('combinations', 'array'));
            print '<td class="">' . $form->select_produits('', 'product_id_' . $ide, '', $conf->product->limit_size, 0, 1, 2, '', 1, [], 0, '1', 0, 'maxwidth500', 0, '', [], 1) . '</td>';

            print '<td class="center">';
            print '<input type="checkbox" name="row[' . $ide . '][selected]" value="1" checked />';
//            print img_picto($langs->trans("Unlink"), "unlink");

            print "</td>";
        }
        print "</tr>";
    }
    if (empty($list)) {
        $colspan = 5 + ($permission ? 1 : 0);
        print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">';
        if (is_object($object) && !empty($object->thirdparty)) {
            print $form->textwithpicto($langs->trans("NoDiffCR"), $langs->trans("NoSpecificContactAddressBis"));
        } else {
            print $langs->trans("NoSpecificContactAddress");
        }
        print '</span>';
        print '</td></tr>';
    }
    print "</table>";
    print '</div>';

    print '<div class="tabsAction">' . "\n";
    $parameters = array();
    $reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if ($reshook < 0) {
        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    }

    if (empty($reshook)) {
        if ($permissionPropal) {
            print '<button class="butAction" name="mode" type="submit" value="CreatePropale" >' . $langs->trans('CreatePropale') . '</button>';
            }
        if ($permission) {
            print '<button class="butAction" name="mode" type="submit" value="CreateInvoice"> ' . $langs->trans('CreateInvoice') . '</button>';
        }
    }
    print '</div>' . "\n";

    print "</form>";
}

// End of page
llxFooter();
$db->close();
