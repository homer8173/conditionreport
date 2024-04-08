<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *       \file       htdocs/comm/propal/document.php
 *       \ingroup    propal
 *       \brief      Management page of documents attached to a business proposal
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}

dol_include_once('/conditionreport/class/conditionreport.class.php');
dol_include_once('/conditionreport/lib/conditionreport_conditionreport.lib.php');
dol_include_once('/ultimateimmo/class/immoproperty.class.php');
dol_include_once('/ultimateimmo/class/immorent.class.php');
dol_include_once('/ultimateimmo/class/immorenter.class.php');
dol_include_once('/ultimateimmo/lib/immoproperty.lib.php');
dol_include_once('/ultimateimmo/lib/immorent.lib.php');
dol_include_once('/ultimateimmo/lib/immorenter.lib.php');

// Load translation files required by the page
$langs->loadLangs(array('conditionreport@conditionreport', 'other', 'companies'));

$action  = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$source  = GETPOST('source', 'alpha');
$id      = GETPOST('id', 'int');
$ref     = GETPOST('ref', 'alpha');

// Get parameters
$limit     = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page      = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
    $page = 0;
}     // If $page is not defined, or '' or -1
$offset   = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!empty($conf->global->MAIN_DOC_SORT_FIELD)) {
    $sortfield = $conf->global->MAIN_DOC_SORT_FIELD;
}
if (!empty($conf->global->MAIN_DOC_SORT_ORDER)) {
    $sortorder = $conf->global->MAIN_DOC_SORT_ORDER;
}

if (!$sortorder) {
    $sortorder = "DESC";
}
if (!$sortfield) {
    $sortfield = "rowid";
}

$search = [];
switch ($source) {
    case 'immorenter':
        $object                = new ImmoRenter($db);
        $search['fk_tenant']   = $id;
        break;
    case 'immoproperty':
        $object                = new ImmoProperty($db);
        $search['fk_property'] = $id;
        break;
    case 'immorent':
        $object                = new ImmoRent($db);
        break;

    default:
        break;
}
$object->fetch($id, $ref);
if ($source == 'immorent') {
    // search all params
    if ($object->fk_property)
        $search['fk_property'] = $object->fk_property;
    if ($object->fk_owner)
        $search['fk_lessor']   = $object->fk_owner;
    if ($object->fk_renter)
        $search['fk_tenant']   = $object->fk_renter;
}

$usercancreate    = $permissiontoadd  = $user->hasRight("conditionreport", "conditionreport", "create");
$permissiontoread = $user->hasRight("conditionreport", "conditionreport", "read");
if (!$permissiontoread)
    accessforbidden();

// Security check
$socid = '';
if (!empty($user->socid)) {
    $socid = $user->socid;
}

/*
 * Actions
 */



/*
 * View
 */
$title    = $object->ref . " - " . $langs->trans('Documents');
$help_url = 'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos';
llxHeader('', $title, $help_url);

$form = new Form($db);

if ($object->id > 0) {
    $upload_dir = $conf->propal->multidir_output[$object->entity] . '/' . dol_sanitizeFileName($object->ref);

    switch ($source) {
        case 'immorenter':
            $head = immorenterPrepareHead($object);
            break;
        case 'immoproperty':
            $head = immopropertyPrepareHead($object);
            break;
        case 'immorent':
            $head = immorentPrepareHead($object);
            break;

        default:
            $head = [];
            break;
    }

    print dol_get_fiche_head($head, 'tabcr', $langs->trans('titleTabCR'), -1, 'company');

    // Build file list
    $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
    $totalsize = 0;
    foreach ($filearray as $key => $file) {
        $totalsize += $file['size'];
    }


    //  card
    //$linkback = '<a href="' . DOL_URL_ROOT . '/comm/propal/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    // Ref customer
    $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref', $object->ref, $object, 0, 'string', '', 0, 1);
    $morehtmlref .= $form->editfieldval("RefCustomer", 'ref', $object->ref, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref .= '<br>';

    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border tableforfield centpercent">';

    print "</table>\n";

    print '</div>';

    print dol_get_fiche_end();

    $crs  = new Conditionreport($db);
    $list = $crs->fetchAll($sortorder, $sortfield, 20, 0, $search);

    // Re-sort list
//    $list        = dol_sort_array($list, $sortfield, $sortorder, 1, 0, 1);
    $param       = "source=$source&id=$id";
    $arrayfields = array(
        'rowid' => array('label' => $langs->trans("Id"), 'checked' => 1),
        'ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
        'label' => array('label' => $langs->trans("Label"), 'checked' => 1),
        'status' => array('label' => $langs->trans("Status"), 'checked' => 1)
    );
    print '<div class="underbanner clearboth"></div>' . "\n";
    print '<div class="div-table-responsive-no-min">' . "\n";
    print '<div class="tagtable tableforcontact centpercent noborder nobordertop allwidth">' . "\n";

    print '<table class="tagtable nobottomiftotal liste">';
    print'<tr class="liste_titre">';
    print_liste_field_titre($arrayfields['ref']['label'], $_SERVER["PHP_SELF"], "ref", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre($arrayfields['label']['label'], $_SERVER["PHP_SELF"], "label", "", $param, "", $sortfield, $sortorder);
    print_liste_field_titre($arrayfields['status']['label'], $_SERVER["PHP_SELF"], "status", "", $param, "", $sortfield, $sortorder, 'center ');
    print_liste_field_titre('', $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder, 'center maxwidthsearch ');
    print '</tr>';

    foreach ($list as $key => $cr) {
        print'<tr class="oddeven">';

        print '<td class="tdoverflowmax200">';
        print $cr->getNomUrl(1);
        print '</td>';

        print '<td class="tdoverflowmax200">';
        print $cr->label;
        print '</td>';

        print '<td class="nowrap center">';
        print $cr->getLibStatut(2);
        print '</td>';
        print '<td class="nowrap">';
        print '</td>';

        print '</tr>';
    }

    print '</table>';

    print '</div></div>';

//    $modulepart      = 'propal';
//    $permissiontoadd = $user->rights->propal->creer;
//    $permtoedit      = $user->rights->propal->creer;
//    $param           = '&id=' . $object->id;
//    include DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
} else {
    print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
