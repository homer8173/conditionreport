<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *    \file       conditionreportroom_card.php
 *    \ingroup    conditionreport
 *    \brief      Page to create/edit/view conditionreportroom
 */
// General defined Options
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');					// Force use of CSRF protection with tokens even for GET
//if (! defined('MAIN_AUTHENTICATION_MODE')) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined('MAIN_LANG_DEFAULT'))        define('MAIN_LANG_DEFAULT', 'auto');					// Force LANG (language) to a particular value
//if (! defined('MAIN_SECURITY_FORCECSP'))   define('MAIN_SECURITY_FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');					// Disable browser notification
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOLOGIN'))                  define('NOLOGIN', '1');						// Do not use login - if this page is public (can be called outside logged session). This includes the NOIPCHECK too.
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       	  		// Do not load ajax.lib.php library
//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');					// Do not create database handler $db
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// Do not load html.form.class.php
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// Do not load and show top and left menu
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOSESSION'))                define('NOSESSION', '1');						// On CLI mode, no need to use web sessions
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
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

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
dol_include_once('/conditionreport/class/conditionreportroom.class.php');
dol_include_once('/conditionreport/lib/conditionreport_conditionreportroom.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("conditionreport@conditionreport", "other"));

// Get parameters
$id     = GETPOST('id', 'int');
$ref    = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');

$action              = GETPOST('action', 'aZ09');
$confirm             = GETPOST('confirm', 'alpha');
$cancel              = GETPOST('cancel', 'aZ09');
$contextpage         = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
$backtopage          = GETPOST('backtopage', 'alpha');     // if not set, a default page will be used
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha'); // if not set, $backtopage will be used
$backtopagejsfields  = GETPOST('backtopagejsfields', 'alpha');
$dol_openinpopup     = GETPOST('dol_openinpopup', 'aZ09');

if (!empty($backtopagejsfields)) {
    $tmpbacktopagejsfields = explode(':', $backtopagejsfields);
    $dol_openinpopup       = $tmpbacktopagejsfields[0];
}

// Initialize technical objects
$object              = new Conditionreportroom($db);
$extrafields         = new ExtraFields($db);
$diroutputmassaction = $conf->conditionreport->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array($object->element . 'card', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search     = array();
foreach ($object->fields as $key => $val) {
    if (GETPOST('search_' . $key, 'alpha')) {
        $search[$key] = GETPOST('search_' . $key, 'alpha');
    }
}

if (empty($action) && empty($id) && empty($ref)) {
    $action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.
// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 1;
if ($enablepermissioncheck) {
    $permissiontoread   = $user->hasRight('conditionreport', 'conditionreportroom', 'read');
    $permissiontoadd    = $user->hasRight('conditionreport', 'conditionreportroom', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
    $permissiontodelete = $user->hasRight('conditionreport', 'conditionreportroom', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
    $permissionnote     = $user->hasRight('conditionreport', 'conditionreportroom', 'write'); // Used by the include of actions_setnotes.inc.php
    $permissiondellink  = $user->hasRight('conditionreport', 'conditionreportroom', 'write'); // Used by the include of actions_dellink.inc.php
} else {
    $permissiontoread   = 1;
    $permissiontoadd    = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
    $permissiontodelete = 1;
    $permissionnote     = 1;
    $permissiondellink  = 1;
}

$upload_dir = $conf->conditionreport->multidir_output[isset($object->entity) ? $object->entity : 1] . '/conditionreportroom';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
if (!isModEnabled("conditionreport")) {
    accessforbidden();
}
if (!$permissiontoread) {
    accessforbidden();
}


/*
 * Actions
 */

$parameters = array();
$reshook    = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
    $error = 0;

    $backurlforlist = dol_buildpath('/conditionreport/conditionreportroom_list.php', 1);

    if (empty($backtopage) || ($cancel && empty($id))) {
        if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
            if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                $backtopage = $backurlforlist;
            } else {
                $backtopage = dol_buildpath('/conditionreport/conditionreportroom_card.php', 1) . '?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
            }
        }
    }

    $triggermodname = 'CONDITIONREPORT_CONDITIONREPORTROOM_MODIFY'; // Name of trigger action code to execute when we modify record
    // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
    include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

    // Actions when linking object each other
    include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

    // Actions when printing a doc from card
    include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

    // Action to move up and down lines of object
    //include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';
    // Action to build doc
    include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

    if ($action == 'set_thirdparty' && $permissiontoadd) {
        $object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
    }
    if ($action == 'classin' && $permissiontoadd) {
        $object->setProject(GETPOST('projectid', 'int'));
    }

    // Actions to send emails
    $triggersendname = 'CONDITIONREPORT_CONDITIONREPORTROOM_SENTBYMAIL';
    $autocopy        = 'MAIN_MAIL_AUTOCOPY_CONDITIONREPORTROOM_TO';
    $trackid         = 'conditionreportroom' . $object->id;
    include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';

    if ($action == 'addline' && $permissiontoadd) {  // Add a new line
        $langs->load('errors');
        $error = 0;

        $label        = (GETPOSTISSET('label') ? GETPOST('label', 'alpha') : '');
        $product_desc = (GETPOSTISSET('dp_desc') ? GETPOST('dp_desc', 'restricthtml') : '');

        $condition = price2num(GETPOST('condition', 'int'), 0, 2);
        $qty       = price2num(GETPOST('qty', 'alpha'), 0, 2);

        // Extrafields
        $extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
        $array_options   = $extrafields->getOptionalsFromPost($object->table_element_line);
        // Unset extrafield
        if (is_array($extralabelsline)) {
            // Get extra fields
            foreach ($extralabelsline as $key => $value) {
                unset($_POST["options_" . $key]);
            }
        }

        if ($qty < 0) {
            setEventMessages($langs->trans('FieldCannotBeNegative', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
            $error++;
        }
        if (!$error && ($qty >= 0)) {


            $product_desc = dol_htmlcleanlastbr($product_desc);

            if (!$error) {
                // Insert line
                $result = $object->addline($label, $qty, $condition, $product_desc, $array_options);

                if ($result > 0) {
                    $ret = $object->fetch($object->id); // Reload to get new records

                    if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
                        $object->generateDocument($object->model_pdf, $langs, $hidedetails, $hidedesc, $hideref);
                    }
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
                    exit();
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }
        }
    } elseif ($action == 'updateline' && $permissiontoadd && GETPOST('save')) {


        $label        = (GETPOSTISSET('label') ? GETPOST('label', 'alpha') : '');
        $product_desc = (GETPOSTISSET('product_desc') ? GETPOST('product_desc', 'restricthtml') : '');

        $condition = price2num(GETPOST('condition', 'int'), 0, 2);
        $qty       = price2num(GETPOST('qty', 'alpha'), 0, 2);

        // Extrafields
        $extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
        $array_options   = $extrafields->getOptionalsFromPost($object->table_element_line);
        // Unset extrafield
        if (is_array($extralabelsline)) {
            // Get extra fields
            foreach ($extralabelsline as $key => $value) {
                unset($_POST["options_" . $key]);
            }
        }

        if ($qty < 0) {
            setEventMessages($langs->trans('FieldCannotBeNegative', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
            $error++;
        }
        if (!$error) {
            $result = $object->updateline(GETPOST('lineid', 'int'), $label, $qty, $condition, $product_desc, $array_options);
            if ($result >= 0) {
                if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
                    $ret = $object->fetch($object->id); // Reload to get new records
                    $object->generateDocument($object->model_pdf, $langs, $hidedetails, $hidedesc, $hideref);
                }
                header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
                exit();
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }
    } elseif ($action == 'updateline' && $permissiontoadd && GETPOST('cancel', 'alpha')) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
        exit();
    } elseif ($action == 'deleteline' && $permissiontoadd) {
        $object->deleteLine($user, GETPOST('lineid', 'int'));
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
        exit();
    } elseif ($action == 'load_model' && GETPOSTISSET('model_name') && $object->status == Conditionreportroom::STATUS_DRAFT) {
        // load model from json
        $object->loadModel($user, GETPOST('model_name', 'alpha'));
    }
}



/*
 * View
 */

$form        = new Form($db);
$formfile    = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Conditionreportroom") . " - " . $langs->trans('Card');
//$title = $object->ref." - ".$langs->trans('Card');
if ($action == 'create') {
    $title = $langs->trans("NewObject", $langs->transnoentitiesnoconv("Conditionreportroom"));
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, [dol_buildpath('/conditionreport/js/conditionreport.js.php', 2)], [dol_buildpath('/conditionreport/css/conditionreport.css.php', 2)], '', 'mod-conditionreport page-card');

// Example : Adding jquery code
// print '<script type="text/javascript">
// jQuery(document).ready(function() {
// 	function init_myfunc()
// 	{
// 		jQuery("#myid").removeAttr(\'disabled\');
// 		jQuery("#myid").attr(\'disabled\',\'disabled\');
// 	}
// 	init_myfunc();
// 	jQuery("#mybutton").click(function() {
// 		init_myfunc();
// 	});
// });
// </script>';
// Part to create
if ($action == 'create') {
    if (empty($permissiontoadd)) {
        accessforbidden('NotEnoughPermissions', 0, 1);
    }

    print load_fiche_titre($title, '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="add">';
//    if ($backtopage) {
//        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
//    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }
    if ($backtopagejsfields) {
        print '<input type="hidden" name="backtopagejsfields" value="' . $backtopagejsfields . '">';
    }
    if ($dol_openinpopup) {
        print '<input type="hidden" name="dol_openinpopup" value="' . $dol_openinpopup . '">';
    }

    print dol_get_fiche_head(array(), '');

    // Set some default values
    //if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

    print '<table class="border centpercent tableforfieldcreate">' . "\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

    print '</table>' . "\n";

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel("Create");

    print '</form>';

    //dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
    print load_fiche_titre($langs->trans("Conditionreportroom"), '', 'object_' . $object->picto);

    print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
    print '<input type="hidden" name="token" value="' . newToken() . '">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="' . $object->id . '">';
    if ($backtopage) {
        print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
    }
    if ($backtopageforcancel) {
        print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
    }

    print dol_get_fiche_head();

    print '<table class="border centpercent tableforfieldedit">' . "\n";

    // Common attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

    print '</table>';

    print dol_get_fiche_end();

    print $form->buttonsSaveCancel();

    print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
    $head = conditionreportroomPrepareHead($object);

    print dol_get_fiche_head($head, 'card', $langs->trans("Conditionreportroom"), -1, $object->picto, 0, '', '', 0, '', 1);

    $formconfirm = '';

    // Confirmation to delete (using preloaded confirm popup)
    if ($action == 'delete' || ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile))) {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteConditionreportroom'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 'action-delete');
    }
    // Confirmation to delete line
    if ($action == 'deleteline') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
    }

    // Clone confirmation
    if ($action == 'clone') {
        // Create an array for form
        $formquestion = array();
        $formconfirm  = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
    }

    // Confirmation of action xxxx (You can use it for xxx = 'close', xxx = 'reopen', ...)
    if ($action == 'xxx') {
        $text = $langs->trans('ConfirmActionConditionreportroom', $object->ref);
        /* if (isModEnabled('notification'))
          {
          require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
          $notify = new Notify($db);
          $text .= '<br>';
          $text .= $notify->confirmMessage('CONDITIONREPORTROOM_CLOSE', $object->socid, $object);
          } */

        $formquestion = array();

        /*
          $forcecombo=0;
          if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
          $formquestion = array(
          // 'text' => $langs->trans("ConfirmClone"),
          // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
          // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
          // array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
          );
         */
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
    }

    // Call Hook formConfirm
    $parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
    $reshook    = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) {
        $formconfirm .= $hookmanager->resPrint;
    } elseif ($reshook > 0) {
        $formconfirm = $hookmanager->resPrint;
    }

    // Print form confirm
    print $formconfirm;

    // Object card
    // ------------------------------------------------------------
    $linkback='';
    if (isset($object->fk_conditionreport)) {
        $linkback .= '<a href="' . dol_buildpath('/conditionreport/conditionreport_card.php', 2) . '?id=' . $object->fk_conditionreport . '">' . $langs->trans('backToCR') . '</a><br />';
    }
    $linkback .= '<a href="' . dol_buildpath('/conditionreport/conditionreportroom_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

    $morehtmlref = '<div class="refidno">';
    /*
      // Ref customer
      $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $permissiontoadd, 'string', '', 0, 1);
      $morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $permissiontoadd, 'string'.(getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') ? ':'.getDolGlobalInt('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
      // Thirdparty
      $morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'customer');
      if (!getDolGlobalInt('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
      $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
      }
      // Project
      if (isModEnabled('project')) {
      $langs->load("projects");
      $morehtmlref .= '<br>';
      if ($permissiontoadd) {
      $morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
      if ($action != 'classify') {
      $morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
      }
      $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
      } else {
      if (!empty($object->fk_project)) {
      $proj = new Project($db);
      $proj->fetch($object->fk_project);
      $morehtmlref .= $proj->getNomUrl(1);
      if ($proj->title) {
      $morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
      }
      }
      }
      }
     */
    if ($object->status == Conditionreportroom::STATUS_DRAFT) {
        $morehtmlref .= '<form name="formuserfile" id="formuserfile" action="' . dol_buildpath('/conditionreport/conditionreportroom_document.php', 2) . '?id=' . $object->id . '&amp;uploadform=1" enctype="multipart/form-data" method="POST">
        <input type="hidden" name="token" value="' . newToken() . '">            
        <input type="hidden" name="backtopage" value="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">
        <input type="hidden" name="sendit" value="' . $langs->trans("Upload") . '">
        ';

        $maxfilesizearray = getMaxFileSizeArray();
        $maxmin           = $maxfilesizearray['maxmin'];
        if ($maxmin > 0) {
            $morehtmlref .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . ($maxmin * 1024) . '">'; // MAX_FILE_SIZE must precede the field type=file
        }
        $morehtmlref .= '<label class="custom-file-upload">
    <input type="file" class="quickUpload" name="userfile[]" id="userfile" multiple="" accept="image/*" capture="environment">
    <i class="fa fa-camera-retro" aria-hidden="true" style="font-size:60px;"></i>
</label>
    </form>';
    }
    if (isset($object->fk_conditionreport)) {

        $morehtmlref .= '<span class="backToCR"><a href="' . dol_buildpath('/conditionreport/conditionreport_card.php', 2) . '?id=' . $object->fk_conditionreport . '">' . $langs->trans('backToCR') . '</a></span>';
    }
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent tableforfield">' . "\n";

    // Common attributes
    //$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
    //unset($object->fields['fk_project']);				// Hide field already shown in banner
    //unset($object->fields['fk_soc']);					// Hide field already shown in banner
    include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

    // Other attributes. Fields from hook formObjectOptions and Extrafields.
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    if ($object->status == $object::STATUS_DRAFT) {
        print '<td colspan="2">';
        print $langs->trans("LoadRoomModele") . " :<br />";
        $files = glob(dol_buildpath('/conditionreport/room_models/fr/') . "*.json");
        if (count($files)) {
            print '<ul class="load_model">';
            foreach ($files as $filename) {
                try {
                    $model = json_decode(file_get_contents($filename));
                    print '<li><a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=load_model&model_name=' . basename($filename) . '" title="' . implode("\n", $model->elements) . '">' . $model->name . '</a></li>';
                } catch (Exception $exc) {
                    
                }
            }
            print '</ul>';
        }
        print '</td>';
    }
    print '</table>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    print dol_get_fiche_end();

    /*
     * Lines
     */

    if (!empty($object->table_element_line)) {
        // Show object lines
        $result = $object->getLinesArray();
        print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#line_' . GETPOST('lineid', 'int')) . '" method="POST">
		<input type="hidden" name="token" value="' . newToken() . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="page_y" value="">
		<input type="hidden" name="id" value="' . $object->id . '">
		';

        if (!empty($conf->use_javascript_ajax) && $object->status == 0) {
            include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
        }

        print '<div class="div-table-responsive-no-min">';
        if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
            print '<table id="tablelines" class="noborder noshadow" width="100%">';
        }

        if (!empty($object->lines)) {
            $dir = '/conditionreport/tplCRR';
            // is module in custom ?
            if (!is_dir(DOL_DOCUMENT_ROOT . $dir)) {
                $dir = '/custom' . $dir;
            }
            $object->printObjectLines($action, $mysoc, null, GETPOST('lineid', 'int'), 1, $dir);
        }

        // Form to add new line
        if ($object->status == 0 && $permissiontoadd && $action != 'selectlines') {
            if ($action != 'editline') {
                // Add products/services form

                $parameters = array();
                $reshook    = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                if ($reshook < 0) {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                }
                if (empty($reshook)) {
                    $object->formAddObjectLine(1, $mysoc, $soc);
                }
            }
        }

        if (!empty($object->lines) || ($object->status == $object::STATUS_DRAFT && $permissiontoadd && $action != 'selectlines' && $action != 'editline')) {
            print '</table>';
        }
        print '</div>';

        print "</form>\n";
    }


    // Buttons for actions

    if ($action != 'presend' && $action != 'editline') {
        print '<div class="tabsAction">' . "\n";
        $parameters = array();
        $reshook    = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Send
            if (empty($user->socid)) {
                print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&token=' . newToken() . '&mode=init#formmailbeforetitle');
            }

            // Back to draft
            if ($object->status == $object::STATUS_VALIDATED) {
                print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=confirm_setdraft&confirm=yes&token=' . newToken(), '', $permissiontoadd);
            }

            // Modify
            print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);

            // Validate
            if ($object->status == $object::STATUS_DRAFT) {
                if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
                    print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=confirm_validate&confirm=yes&token=' . newToken(), '', $permissiontoadd);
                } else {
                    $langs->load("errors");
                    print dolGetButtonAction($langs->trans("ErrorAddAtLeastOneLineFirst"), $langs->trans("Validate"), 'default', '#', '', 0);
                }
            }

            // Clone
            if ($permissiontoadd) {
                print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . (!empty($object->socid) ? '&socid=' . $object->socid : '') . '&action=clone&token=' . newToken(), '', $permissiontoadd);
            }

            /*
              // Disable / Enable
              if ($permissiontoadd) {
              if ($object->status == $object::STATUS_ENABLED) {
              print dolGetButtonAction('', $langs->trans('Disable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=disable&token='.newToken(), '', $permissiontoadd);
              } else {
              print dolGetButtonAction('', $langs->trans('Enable'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=enable&token='.newToken(), '', $permissiontoadd);
              }
              }
              if ($permissiontoadd) {
              if ($object->status == $object::STATUS_VALIDATED) {
              print dolGetButtonAction('', $langs->trans('Cancel'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=close&token='.newToken(), '', $permissiontoadd);
              } else {
              print dolGetButtonAction('', $langs->trans('Re-Open'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=reopen&token='.newToken(), '', $permissiontoadd);
              }
              }
             */

            // Delete (with preloaded confirm popup)
            $deleteUrl = $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token=' . newToken();
            $buttonId  = 'action-delete-no-ajax';
            if ($conf->use_javascript_ajax && empty($conf->dol_use_jmobile)) { // We can use preloaded confirm if not jmobile
                $deleteUrl = '';
                $buttonId  = 'action-delete';
            }
            $params = array();
            print dolGetButtonAction('', $langs->trans("Delete"), 'delete', $deleteUrl, $buttonId, $permissiontodelete, $params);
        }
        print '</div>' . "\n";
    }


    // Select mail models is same action as presend
    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    if ($action != 'presend') {
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a name="builddoc"></a>'; // ancre

        $includedocgeneration = 1;

        // Documents
        if ($includedocgeneration) {
            $objref       = dol_sanitizeFileName($object->ref);
            $relativepath = $objref . '/' . $objref . '.pdf';
            $filedir      = $conf->conditionreport->dir_output . '/' . $object->element . '/' . $objref;
            $urlsource    = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
            $genallowed   = $permissiontoread; // If you can read, you can build the PDF to read content
            $delallowed   = $permissiontoadd; // If you can create/edit, you can remove a file on card
            print $formfile->showdocuments('conditionreport:Conditionreportroom', $object->element . '/' . $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
        }

        // Show links to link elements
        $linktoelem     = $form->showLinkToObjectBlock($object, null, array('conditionreportroom'));
        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

        print '</div><div class="fichehalfright">';

        $MAXEVENT = 10;

        $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/conditionreport/conditionreportroom_agenda.php', 1) . '?id=' . $object->id);

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
        $formactions    = new FormActions($db);
        $somethingshown = $formactions->showactions($object, $object->element . '@' . $object->module, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlcenter);

        print '</div></div>';
    }

    //Select mail models is same action as presend
    if (GETPOST('modelselected')) {
        $action = 'presend';
    }

    // Presend form
    $modelmail    = 'conditionreportroom';
    $defaulttopic = 'InformationMessage';
    $diroutput    = $conf->conditionreport->dir_output;
    $trackid      = 'conditionreportroom' . $object->id;

    include DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
