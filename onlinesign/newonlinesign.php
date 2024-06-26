<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2023		anthony Berton			<anthony.berton@bb2a.fr>
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
 *     	\file       htdocs/public/onlinesign/newonlinesign.php
 * 		\ingroup    core
 * 		\brief      File to offer a way to make an online signature for a particular Dolibarr entity
 * 					Example of URL: https://localhost/public/onlinesign/newonlinesign.php?ref=PR...
 */
if (!defined('NOLOGIN')) {
    define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
    define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
    define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
    define("DOLENTITY", $entity);
}

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

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
dol_include_once('/conditionreport/lib/conditionreport.lib.php');
dol_include_once('/ultimateimmo/class/immoowner.class.php');
dol_include_once('/ultimateimmo/class/immorenter.class.php');

// Load translation files
$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "errors", "members", "paybox", "propal", "commercial", "conditionreport@conditionreport"));

// Security check
// No check on module enabled. Done later according to $validpaymentmethod
// Get parameters
$action  = GETPOST('action', 'aZ09');
$cancel  = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$refusepropal = GETPOST('refusepropal', 'alpha');
$message      = GETPOST('message', 'aZ09');

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

$suffix    = GETPOST("suffix", 'aZ09');
$source    = GETPOST("source", 'alpha');
$signature = GETPOST("signature", 'alpha');
$ref       = $REF       = GETPOST("ref", 'alpha');
$urlok     = '';
$urlko     = '';

if (empty($source)) {
    $source = 'proposal';
}
if (!empty($refusepropal)) {
    $action = "refusepropal";
}

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.
// Complete urls for post treatment
$SECUREKEY   = GETPOST("securekey"); // Secure key

if (!empty($source)) {
    $urlok .= 'source=' . urlencode($source) . '&';
    $urlko .= 'source=' . urlencode($source) . '&';
}
if (!empty($REF)) {
    $urlok .= 'ref=' . urlencode($REF) . '&';
    $urlko .= 'ref=' . urlencode($REF) . '&';
}
if (!empty($SECUREKEY)) {
    $urlok .= 'securekey=' . urlencode($SECUREKEY) . '&';
    $urlko .= 'securekey=' . urlencode($SECUREKEY) . '&';
}
if (!empty($entity)) {
    $urlok .= 'entity=' . urlencode($entity) . '&';
    $urlko .= 'entity=' . urlencode($entity) . '&';
}
$urlok = preg_replace('/&$/', '', $urlok); // Remove last &
$urlko = preg_replace('/&$/', '', $urlko); // Remove last &

$creditor = $mysoc->name;

$type = $source;

if (!$action) {
    if ($source && !$ref) {
        httponly_accessforbidden($langs->trans('ErrorBadParameters') . " - ref missing", 400, 1);
    }
}

// Check securitykey
$securekeyseed = '';
if ($source == 'conditionreport') {
    $securekeyseed = getDolGlobalString('CONDITIONREPORT_ONLINE_SIGNATURE_SECURITY_TOKEN');
}
if (!dol_verifyHash($securekeyseed . $type . $ref . (isModEnabled('multicompany') ? $entity : ''), $SECUREKEY, '0')) {
    httponly_accessforbidden('Bad value for securitykey. Value provided ' . dol_escape_htmltag($SECUREKEY) . ' does not match expected value for ref=' . dol_escape_htmltag($ref), 403, 1);
}

if ($source == 'conditionreport') {
    dol_include_once('/conditionreport/class/conditionreport.class.php');
    $object = new Conditionreport($db);
    $result = $object->fetch(0, $ref, '', $entity);
} else {
    httponly_accessforbidden($langs->trans('ErrorBadParameters') . " - Bad value for source. Value not supported.", 400, 1);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('onlinesign'));

$error = 0;

/*
 * Actions
 */

if ($action == 'confirm_refusepropal' && $confirm == 'yes') {
    $db->begin();

    $sql = "UPDATE " . MAIN_DB_PREFIX . "propal";
    $sql .= " SET fk_statut = " . ((int) $object::STATUS_NOTSIGNED) . ", note_private = '" . $db->escape($object->note_private) . "', date_signature='" . $db->idate(dol_now()) . "'";
    $sql .= " WHERE rowid = " . ((int) $object->id);

    dol_syslog(__FILE__, LOG_DEBUG);
    $resql = $db->query($sql);
    if (!$resql) {
        $error++;
    }

    if (!$error) {
        $db->commit();

        $message = 'refused';
        setEventMessages("PropalRefused", null, 'warnings');
        if (method_exists($object, 'call_trigger')) {
            // Online customer is not a user, so we use the use that validates the documents
            $user            = new User($db);
            $user->fetch($object->user_validation_id);
            $object->context = array('closedfromonlinesignature' => 'closedfromonlinesignature');
            $result          = $object->call_trigger('PROPAL_CLOSE_REFUSED', $user);
            if ($result < 0) {
                $error++;
            }
        }
    } else {
        $db->rollback();
    }

    $object->fetch(0, $ref);
}


/*
 * View
 */

$form = new Form($db);
$head = '';
if (getDolGlobalString('MAIN_SIGN_CSS_URL')) {
    $head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MAIN_SIGN_CSS_URL') . '?lang=' . $langs->defaultlang . '">' . "\n";
}

$conf->dol_hide_topmenu  = 1;
$conf->dol_hide_leftmenu = 1;

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '') . '<div>';
llxHeader($head, $langs->trans("OnlineSignature"), '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1);

if ($action == 'refusepropal') {
    print $form->formconfirm($_SERVER["PHP_SELF"] . '?ref=' . urlencode($ref) . '&securekey=' . urlencode($SECUREKEY) . (isModEnabled('multicompany') ? '&entity=' . $entity : ''), $langs->trans('RefusePropal'), $langs->trans('ConfirmRefusePropal', $object->ref), 'confirm_refusepropal', '', '', 1);
}

// Check link validity for param 'source' to avoid use of the examples as value
if (!empty($source) && in_array($ref, array('member_ref', 'contractline_ref', 'invoice_ref', 'order_ref', 'proposal_ref', ''))) {
    $langs->load("errors");
    dol_print_error_email('BADREFINONLINESIGNFORM', $langs->trans("ErrorBadLinkSourceSetButBadValueForRef", $source, $ref));
    // End of page
    llxFooter();
    $db->close();
    exit;
}

print '<span id="dolpaymentspan"></span>' . "\n";
print '<div class="center">' . "\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . newToken() . '">' . "\n";
print '<input type="hidden" name="action" value="dosign">' . "\n";
print '<input type="hidden" name="tag" value="' . GETPOST("tag", 'alpha') . '">' . "\n";
print '<input type="hidden" name="signature" value="' . GETPOST("signature", 'alpha') . '">' . "\n";
print '<input type="hidden" name="suffix" value="' . GETPOST("suffix", 'alpha') . '">' . "\n";
print '<input type="hidden" name="securekey" value="' . $SECUREKEY . '">' . "\n";
print '<input type="hidden" name="entity" value="' . $entity . '" />';
print '<input type="hidden" name="page_y" value="" />';
print '<input type="hidden" name="source" value="' . $source . '" />';
print '<input type="hidden" name="ref" value="' . $ref . '" />';
print "\n";
print '<!-- Form to sign -->' . "\n";

print '<table id="dolpublictable" summary="Payment form" class="center">' . "\n";

// Show logo (search order: logo defined by ONLINE_SIGN_LOGO_suffix, then ONLINE_SIGN_LOGO_, then small company logo, large company logo, theme logo, common logo)
// Define logo and logosmall
$logosmall = $mysoc->logo_small;
$logo      = $mysoc->logo;
$paramlogo = 'ONLINE_SIGN_LOGO_' . $suffix;
if (!empty($conf->global->$paramlogo)) {
    $logosmall = $conf->global->$paramlogo;
} elseif (getDolGlobalString('ONLINE_SIGN_LOGO')) {
    $logosmall = $conf->global->ONLINE_SIGN_LOGO;
}
//print '<!-- Show logo (logosmall='.$logosmall.' logo='.$logo.') -->'."\n";
// Define urllogo
$urllogo     = '';
$urllogofull = '';
if (!empty($logosmall) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $logosmall)) {
    $urllogo     = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/thumbs/' . $logosmall);
    $urllogofull = $dolibarr_main_url_root . '/viewimage.php?modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode('logos/thumbs/' . $logosmall);
} elseif (!empty($logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $logo)) {
    $urllogo     = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity=' . $conf->entity . '&amp;file=' . urlencode('logos/' . $logo);
    $urllogofull = $dolibarr_main_url_root . '/viewimage.php?modulepart=mycompany&entity=' . $conf->entity . '&file=' . urlencode('logos/' . $logo);
}
// Output html code for logo
if ($urllogo) {
    print '<div class="backgreypublicpayment">';
    print '<div class="logopublicpayment">';
    print '<img id="dolpaymentlogo" src="' . $urllogo . '"';
    print '>';
    print '</div>';
    if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
        print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">' . $langs->trans("PoweredBy") . '<br><img class="poweredbyimg" src="' . DOL_URL_ROOT . '/theme/dolibarr_logo.svg" width="80px"></a></div>';
    }
    print '</div>';
}
if ($source == 'proposal' && getDolGlobalString('PROPOSAL_IMAGE_PUBLIC_SIGN')) {
    print '<div class="backimagepublicproposalsign">';
    print '<img id="idPROPOSAL_IMAGE_PUBLIC_INTERFACE" src="' . getDolGlobalString('PROPOSAL_IMAGE_PUBLIC_SIGN') . '">';
    print '</div>';
}

// Output introduction text
$text = '';
if (getDolGlobalString('ONLINE_SIGN_NEWFORM_TEXT')) {
    $reg = array();
    if (preg_match('/^\((.*)\)$/', $conf->global->ONLINE_SIGN_NEWFORM_TEXT, $reg)) {
        $text .= $langs->trans($reg[1]) . "<br>\n";
    } else {
        $text .= getDolGlobalString('ONLINE_SIGN_NEWFORM_TEXT') . "<br>\n";
    }
    $text = '<tr><td align="center"><br>' . $text . '<br></td></tr>' . "\n";
}
if (empty($text)) {
    if ($source == 'proposal') {
        $text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageProposal", $mysoc->name) . '</strong></td></tr>' . "\n";
        $text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromProposal", $creditor) . '<br><br></td></tr>' . "\n";
    } elseif ($source == 'contract') {
        $text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageContract", $mysoc->name) . '</strong></td></tr>' . "\n";
        $text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromContract", $creditor) . '<br><br></td></tr>' . "\n";
    } elseif ($source == 'fichinter') {
        $text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePageFichinter", $mysoc->name) . '</strong></td></tr>' . "\n";
        $text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFromFichinter", $creditor) . '<br><br></td></tr>' . "\n";
    } else {
        $text .= '<tr><td class="textpublicpayment"><br><strong>' . $langs->trans("WelcomeOnOnlineSignaturePage" . dol_ucfirst($source), $mysoc->name) . '</strong></td></tr>' . "\n";
        $text .= '<tr><td class="textpublicpayment opacitymedium">' . $langs->trans("ThisScreenAllowsYouToSignDocFrom" . dol_ucfirst($source), $creditor) . '<br><br></td></tr>' . "\n";
    }
}
print $text;

// Output payment summary form
print '<tr><td align="center">';
print '<table with="100%" id="tablepublicpayment">';
if ($source == 'proposal') {
    print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignProposal") . ' :</td></tr>' . "\n";
} elseif ($source == 'contract') {
    print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignContract") . ' :</td></tr>' . "\n";
} elseif ($source == 'fichinter') {
    print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSignFichinter") . ' :</td></tr>' . "\n";
} else {
    print '<tr><td align="left" colspan="2" class="opacitymedium">' . $langs->trans("ThisIsInformationOnDocumentToSign" . dol_ucfirst($source)) . ' :</td></tr>' . "\n";
}
$found = false;
$error = 0;

// Signature on commercial proposal
if ($source == 'proposal' && false) {
    $found = true;
    $langs->load("proposal");

    $result = $object->fetch_thirdparty($object->socid);

    // Creditor
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Creditor");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $creditor . '</b>';
    print '<input type="hidden" name="creditor" value="' . $creditor . '">';
    print '</td></tr>' . "\n";

    // Debitor
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $object->thirdparty->name . '</b>';
    print '</td></tr>' . "\n";

    // Amount

    $amount = '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Amount");
    $amount .= '</td><td class="CTableRow2">';
    $amount .= '<b>' . price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency) . '</b>';
    if ($object->multicurrency_code != $conf->currency) {
        $amount .= ' (' . price($object->multicurrency_total_ttc, 0, $langs, 1, -1, -1, $object->multicurrency_code) . ')';
    }
    $amount .= '</td></tr>' . "\n";

    // Call Hook amountPropalSign
    $parameters = array('source' => $source);
    $reshook    = $hookmanager->executeHooks('amountPropalSign', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook)) {
        $amount .= $hookmanager->resPrint;
    } elseif ($reshook > 0) {
        $amount = $hookmanager->resPrint;
    }

    print $amount;

    // Object
    $text = '<b>' . $langs->trans("SignatureProposalRef", $object->ref) . '</b>';
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
    print '</td><td class="CTableRow2">' . $text;

    $last_main_doc_file = $object->last_main_doc;

    if ($object->status == $object::STATUS_VALIDATED) {
        if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
            // It seems document has never been generated, or was generated and then deleted.
            // So we try to regenerate it with its default template.
            $defaulttemplate = '';  // We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
            $object->generateDocument($defaulttemplate, $langs);
        }

        $directdownloadlink = $object->getLastMainDocLink('proposal');
        if ($directdownloadlink) {
            print '<br><a href="' . $directdownloadlink . '">';
            print img_mime($object->last_main_doc, '');
            print $langs->trans("DownloadDocument") . '</a>';
        }
    } else {
        if ($object->status == $object::STATUS_NOTSIGNED) {
            $directdownloadlink = $object->getLastMainDocLink('proposal');
            if ($directdownloadlink) {
                print '<br><a href="' . $directdownloadlink . '">';
                print img_mime($last_main_doc_file, '');
                print $langs->trans("DownloadDocument") . '</a>';
            }
        } elseif ($object->status == $object::STATUS_SIGNED || $object->status == $object::STATUS_BILLED) {
            if (preg_match('/_signed-(\d+)/', $last_main_doc_file)) { // If the last main doc has been signed
                $last_main_doc_file_not_signed = preg_replace('/_signed-(\d+)/', '', $last_main_doc_file);

                $datefilesigned    = dol_filemtime($last_main_doc_file);
                $datefilenotsigned = dol_filemtime($last_main_doc_file_not_signed);

                if (empty($datefilenotsigned) || $datefilesigned > $datefilenotsigned) {
                    $directdownloadlink = $object->getLastMainDocLink('proposal');
                    if ($directdownloadlink) {
                        print '<br><a href="' . $directdownloadlink . '">';
                        print img_mime($object->last_main_doc, '');
                        print $langs->trans("DownloadDocument") . '</a>';
                    }
                }
            }
        }
    }

    print '<input type="hidden" name="source" value="' . GETPOST("source", 'alpha') . '">';
    print '<input type="hidden" name="ref" value="' . $object->ref . '">';
    print '</td></tr>' . "\n";
} elseif ($source == 'contract' && false) { // Signature on contract
    $found = true;
    $langs->load("contract");

    $result = $object->fetch_thirdparty($object->socid);

    // Proposer
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Proposer");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $creditor . '</b>';
    print '<input type="hidden" name="creditor" value="' . $creditor . '">';
    print '</td></tr>' . "\n";

    // Target
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $object->thirdparty->name . '</b>';
    print '</td></tr>' . "\n";

    // Object
    $text = '<b>' . $langs->trans("SignatureContractRef", $object->ref) . '</b>';
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
    print '</td><td class="CTableRow2">' . $text;

    $last_main_doc_file = $object->last_main_doc;

    if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
        // It seems document has never been generated, or was generated and then deleted.
        // So we try to regenerate it with its default template.
        $defaulttemplate = '';  // We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
        $object->generateDocument($defaulttemplate, $langs);
    }

    $directdownloadlink = $object->getLastMainDocLink('contract');
    if ($directdownloadlink) {
        print '<br><a href="' . $directdownloadlink . '">';
        print img_mime($object->last_main_doc, '');
        if ($message == "signed") {
            print $langs->trans("DownloadSignedDocument") . '</a>';
        } else {
            print $langs->trans("DownloadDocument") . '</a>';
        }
    }


    print '<input type="hidden" name="source" value="' . GETPOST("source", 'alpha') . '">';
    print '<input type="hidden" name="ref" value="' . $object->ref . '">';
    print '</td></tr>' . "\n";
} elseif ($source == 'fichinter' && false) {
    // Signature on fichinter
    $found = true;
    $langs->load("fichinter");

    $result = $object->fetch_thirdparty($object->socid);

    // Proposer
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Proposer");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $creditor . '</b>';
    print '<input type="hidden" name="creditor" value="' . $creditor . '">';
    print '</td></tr>' . "\n";

    // Target
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $object->thirdparty->name . '</b>';
    print '</td></tr>' . "\n";

    // Object
    $text = '<b>' . $langs->trans("SignatureFichinterRef", $object->ref) . '</b>';
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
    print '</td><td class="CTableRow2">' . $text;

    $last_main_doc_file = $object->last_main_doc;

    if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
        // It seems document has never been generated, or was generated and then deleted.
        // So we try to regenerate it with its default template.
        $defaulttemplate = '';  // We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
        $object->generateDocument($defaulttemplate, $langs);
    }

    $directdownloadlink = $object->getLastMainDocLink('fichinter');
    if ($directdownloadlink) {
        print '<br><a href="' . $directdownloadlink . '">';
        print img_mime($object->last_main_doc, '');
        if ($message == "signed") {
            print $langs->trans("DownloadSignedDocument") . '</a>';
        } else {
            print $langs->trans("DownloadDocument") . '</a>';
        }
    }
    print '<input type="hidden" name="source" value="' . GETPOST("source", 'alpha') . '">';
    print '<input type="hidden" name="ref" value="' . $object->ref . '">';
    print '</td></tr>' . "\n";
} elseif ($source == 'societe_rib' && false) {
    $found = true;
    $langs->loadLangs(array("companies", "commercial", "withdrawals"));

    $result = $object->fetch_thirdparty();

    // Proposer
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("TheLessor");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $creditor . '</b>';
    print '<input type="hidden" name="creditor" value="' . $creditor . '">';
    print '</td></tr>' . "\n";

    // Target
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("TheTenant");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    print '<b>' . $object->thirdparty->name . '</b>';
    print '</td></tr>' . "\n";

    // Object
    $text = '<b>' . $langs->trans("Signature" . dol_ucfirst($source) . "Ref", $object->ref) . '</b>';
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
    print '</td><td class="CTableRow2">' . $text;

    $last_main_doc_file = $object->last_main_doc;
    $diroutput          = $conf->societe->multidir_output[$object->thirdparty->entity] . '/'
        . dol_sanitizeFileName($object->thirdparty->id) . '/';
    if ((empty($last_main_doc_file) ||
        !dol_is_file($diroutput
            . $langs->transnoentitiesnoconv("SepaMandateShort") . ' ' . $object->id . "-" . dol_sanitizeFileName($object->rum) . ".pdf")) && $message != "signed") {
        // It seems document has never been generated, or was generated and then deleted.
        // So we try to regenerate it with its default template.
        //$defaulttemplate = 'sepamandate';
        $defaulttemplate = getDolGlobalString("BANKADDON_PDF");

        $object->setDocModel($user, $defaulttemplate);
        $moreparams            = array(
            'use_companybankid' => $object->id,
            'force_dir_output' => $diroutput
        );
        $result                = $object->thirdparty->generateDocument($defaulttemplate, $langs, 0, 0, 0, $moreparams);
        $object->last_main_doc = $object->thirdparty->last_main_doc;
    }
    $directdownloadlink = $object->getLastMainDocLink('company');
    if ($directdownloadlink) {
        print '<br><a href="' . $directdownloadlink . '">';
        print img_mime($object->last_main_doc, '');
        if ($message == "signed") {
            print $langs->trans("DownloadSignedDocument") . '</a>';
        } else {
            print $langs->trans("DownloadDocument") . '</a>';
        }
    }
} else {
    $found = true;
    $langs->load('companies');

    if (!empty($object->socid) || !empty($object->fk_soc)) {
        $result = $object->fetch_thirdparty();
    }

    // lessor
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("TheLessor");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    $lessor = new Societe($db);
    $lessor->fetch($object->fk_lessor);
    print '<b>' . ($lessor->array_options['options_civility'] ? getCivilityLabel($lessor->array_options['options_civility']) . " " : '') . ' ' . $lessor->name . ' ' . $lessor->array_options['options_firstname'] . ' ' . $lessor->lastname . '</b>';
    print '<input type="hidden" name="creditor" value="' . $creditor . '">';
    print '</td></tr>' . "\n";

    // tenant
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("TheTenant");
    print '</td><td class="CTableRow2">';
    print img_picto('', 'company', 'class="pictofixedwidth"');
    $renter = new Societe($db);
    $renter->fetch($object->fk_tenant);
    print '<b>' . ($renter->array_options['options_civility'] ? getCivilityLabel($renter->array_options['options_civility']) . " " : '') . ' ' . $renter->name . ' ' . $renter->array_options['options_firstname'] . ' ' . $renter->lastname . '</b>';
    print '</td></tr>' . "\n";
    // Object
    $text   = '<b>' . $langs->trans("Signature" . dol_ucfirst($source) . "Ref", $object->ref) . '</b>';
    print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
    print '</td><td class="CTableRow2">' . $text;

    $last_main_doc_file = $object->last_main_doc;

    if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT . '/' . $object->last_main_doc)) {
        // It seems document has never been generated, or was generated and then deleted.
        // So we try to regenerate it with its default template.
        $defaulttemplate = '';  // We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
        die('la');
        $object->generateDocument($defaulttemplate, $langs);
    }

    $directdownloadlink = $object->getLastMainDocLink($source, '1');
    if ($directdownloadlink) {
        print '<br><a href="' . $directdownloadlink . '">';
        print img_mime($object->last_main_doc, '');
        if ($message == "signed") {
            print $langs->trans("DownloadSignedDocument") . '</a>';
        } else {
            print $langs->trans("DownloadDocument") . '</a>';
        }
    }
}

// Call Hook addFormSign
$parameters = array('source' => $source);
$reshook    = $hookmanager->executeHooks('addFormSign', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

if (!$found && !$mesg) {
    $mesg = $langs->transnoentitiesnoconv("ErrorBadParameters");
}

if ($mesg) {
    print '<tr><td class="center" colspan="2"><br><div class="warning">' . dol_escape_htmltag($mesg) . '</div></td></tr>' . "\n";
}

print '</table>' . "\n";
print "\n";

if ($action != 'dosign') {
    if ($found && !$error) {
        // We are in a management option and no error
    } else {
        dol_print_error_email('ERRORNEWONLINESIGN');
    }
} else {
    // Print
}

print '</td></tr>' . "\n";
print '<tr><td class="center">';

if ($action == "dosign" && empty($cancel)) {
    print '<div class="tablepublicpayment">';
    print '<input type="text" class="paddingleftonly marginleftonly paddingrightonly marginrightonly marginbottomonly" id="name"  placeholder="' . $langs->trans("LastnameFirstname") . '" autofocus>';
    print '<label><input type="checkbox" required name="certifexact" id="certifexact" value="1" />' . $langs->trans("CertifExact") . '</label>';
    print '<div id="signature" style="border:solid;"></div>';
    print '</div>';
    print '<input type="button" class="small noborderbottom cursorpointer buttonreset" id="clearsignature" value="' . $langs->trans("ClearSignature") . '">';

    // Do not use class="reposition" here: It breaks the submit and there is a message on top to say it's ok, so going back top is better.
    print '<div>';
    print '<input type="button" class="button marginleftonly marginrightonly" id="signbutton" value="' . $langs->trans("Sign") . '">';
    print '<input type="submit" class="button butActionDelete marginleftonly marginrightonly" name="cancel" value="' . $langs->trans("Cancel") . '">';
    print '</div>';

    // Add js code managed into the div #signature
    print '<script language="JavaScript" type="text/javascript" src="' . DOL_URL_ROOT . '/includes/jquery/plugins/jSignature/jSignature.js"></script>
	<script type="text/javascript">
	$(document).ready(function() {
	  $("#signature").jSignature({ color:"#000", lineWidth:0, ' . (empty($conf->dol_optimize_smallscreen) ? '' : 'width: 280, ') . 'height: 180});
      let check= function(){
		$("#clearsignature").css("display","");
        if($("#certifexact").is(":checked") && $("#signature").jSignature("getData", "native").length != 0){
            $("#signbutton").attr("disabled",false);
            console.log($("#signature").jSignature("getData", "native").length)
            if(!$._data($("#signbutton")[0], "events")){
                $("#signbutton").on("click",function(){
                    console.log("We click on button sign");
                    document.body.style.cursor = \'wait\';
                    /* $("#signbutton").val(\'' . dol_escape_js($langs->transnoentities('PleaseBePatient')) . '\'); */
                    var signature = $("#signature").jSignature("getData", "image");
                    var name = document.getElementById("name").value;
                    $.ajax({
                        type: "POST",
                        url: "' . dol_buildpath('/conditionreport/ajax/onlineSign.php', 2) . '",
                        dataType: "text",
                        data: {
                            "action" : "importSignature",
                            "token" : \'' . newToken() . '\',
                            "signaturebase64" : signature,
                            "onlinesignname" : name,
                            "ref" : \'' . dol_escape_js($REF) . '\',
                            "securekey" : \'' . dol_escape_js($SECUREKEY) . '\',
                            "mode" : \'' . dol_escape_htmltag($source) . '\',
                            "signature" : \'' . dol_escape_htmltag($signature) . '\',
                            "entity" : \'' . dol_escape_htmltag($entity) . '\',
                        },
                        success: function(response) {
                            if(response == "success"){
                                console.log("Success on saving signature");
                                window.location.replace("' . $_SERVER["PHP_SELF"] . '?ref=' . urlencode($ref) . '&source=' . urlencode($source) . '&message=signed&securekey=' . urlencode($SECUREKEY) . (isModEnabled('multicompany') ? '&entity=' . $entity : '') . '&signature=' . $signature . '");
                            }else{
                                console.error(response);
                            }
                        },
                    });
                });
            }
        } else {
            $("#signbutton").attr("disabled",true);
        }
	  }

	  $("#certifexact").on("change",check);
	  $("#signature").on("change",check);

	  $("#clearsignature").on("click",function(){
		$("#signature").jSignature("clear");
		$("#signbutton").attr("disabled",true);
		// document.getElementById("onlinesignname").value = "";
	  });

	  $("#signbutton").attr("disabled",true);
	});
	</script>';
} else {
    if ($source == 'proposal') {
        if ($object->status == $object::STATUS_SIGNED) {
            print '<br>';
            if ($message == 'signed') {
                print img_picto('', 'check', '', false, 0, 0, '', 'size2x') . '<br>';
                print '<span class="ok">' . $langs->trans("PropalSigned") . '</span>';
            } else {
                print img_picto('', 'check', '', false, 0, 0, '', 'size2x') . '<br>';
                print '<span class="ok">' . $langs->trans("PropalAlreadySigned") . '</span>';
            }
        } elseif ($object->status == $object::STATUS_NOTSIGNED) {
            print '<br>';
            if ($message == 'refused') {
                print img_picto('', 'cross', '', false, 0, 0, '', 'size2x') . '<br>';
                print '<span class="ok">' . $langs->trans("PropalRefused") . '</span>';
            } else {
                print img_picto('', 'cross', '', false, 0, 0, '', 'size2x') . '<br>';
                print '<span class="warning">' . $langs->trans("PropalAlreadyRefused") . '</span>';
            }
        } else {
            print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("SignPropal") . '">';
            print '<input name="refusepropal" type="submit" class="butActionDelete small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("RefusePropal") . '">';
        }
    } elseif ($source == 'contract') {
        if ($message == 'signed') {
            print '<span class="ok">' . $langs->trans("ContractSigned") . '</span>';
        } else {
            print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("SignContract") . '">';
        }
    } elseif ($source == 'fichinter') {
        if ($message == 'signed') {
            print '<span class="ok">' . $langs->trans("FichinterSigned") . '</span>';
        } else {
            print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("SignFichinter") . '">';
        }
    } else {
        if ($message == 'signed') {
            print '<span class="ok">' . $langs->trans(dol_ucfirst($source) . "Signed") . '</span>';

            if ($object->status == Conditionreport::STATUS_SIGNED_LESSOR && $signature == 'lessor') {
                //print ;
                print '<br><br><a href="' . dol_buildpath('/conditionreport/conditionreport_card.php?id=', 2) . $object->id . '" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition">' . $langs->trans("BackToCR") . '</a>';
            }
        } else {
            print '<input type="submit" class="butAction small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="' . $langs->trans("Sign" . dol_ucfirst($source)) . '">';
        }
    }
}
print '</td></tr>' . "\n";
print '</table>' . "\n";
print '</form>' . "\n";
print '</div>' . "\n";
print '<br>';

htmlPrintOnlineFooter($mysoc, $langs);

llxFooter('', 'public');

$db->close();
