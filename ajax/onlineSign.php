<?php
/*
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
 *    \file       /htdocs/core/ajax/onlineSign.php
 *    \brief      File to make Ajax action on Knowledge Management
 */
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}
// Needed to create other objects with workflow
/* if (!defined('NOREQUIRESOC')) {
  define('NOREQUIRESOC', '1');
  } */
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
    define("NOLOGIN", '1');
}
if (!defined('NOIPCHECK')) {
    define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
    define('NOBROWSERNOTIF', '1');
}
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
    define("DOLENTITY", $entity);
}


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

dol_include_once('/conditionreport/core/modules/conditionreport/modules_conditionreport.php');

$action = GETPOST('action', 'aZ09');

$signature        = GETPOST('signaturebase64');
$signatureMode    = GETPOST('signature');
$ref              = GETPOST('ref', 'aZ09');
$mode             = GETPOST('mode', 'aZ09');    // 'proposal', ...
$SECUREKEY        = GETPOST("securekey"); // Secure key
$online_sign_name = GETPOST("onlinesignname") ? GETPOST("onlinesignname") : '';

$error    = 0;
$response = "";

$type = $mode;
$langs->loadLangs(['conditionreport@conditionreport']);

// Security check
$securekeyseed = '';
if ($type == 'proposal') {
    $securekeyseed = getDolGlobalString('PROPOSAL_ONLINE_SIGNATURE_SECURITY_TOKEN');
} elseif ($type == 'contract') {
    $securekeyseed = getDolGlobalString('CONTRACT_ONLINE_SIGNATURE_SECURITY_TOKEN');
} elseif ($type == 'fichinter') {
    $securekeyseed = getDolGlobalString('FICHINTER_ONLINE_SIGNATURE_SECURITY_TOKEN');
} else {
    $securekeyseed = getDolGlobalString(strtoupper($type) . '_ONLINE_SIGNATURE_SECURITY_TOKEN');
}
if (empty($SECUREKEY) || !dol_verifyHash($securekeyseed . $type . $ref . (!isModEnabled('multicompany') ? '' : $entity), $SECUREKEY, '0')) {
    httponly_accessforbidden('Bad value for securitykey. Value provided ' . dol_escape_htmltag($SECUREKEY) . ' does not match expected value for ref=' . dol_escape_htmltag($ref), 403);
}

/**
 * sort File By Time
 *
 * @param  string				$a				file a
 * @param  string				$b				file b
 * @return	bool								
     */
function sortFileByTime($a,$b)
{
    return filemtime($b) <=> filemtime($a);
}

/*
 * Actions
 */

// None


/*
 * View
 */

top_httphead();


if ($action == "importSignature") {
    $issignatureok = (!empty($signature) && $signature[0] == "image/png;base64");
    if ($issignatureok) {
        $signature = $signature[1];
        $data      = base64_decode($signature);

        if ($mode == 'conditionreport') {
            dol_include_once('/conditionreport/class/conditionreport.class.php');
            require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';
            $object     = new Conditionreport($db);
            $object->fetch(0, $ref);
            $upload_dir = !empty($conf->conditionreport->multidir_output[$object->entity]) ? $conf->conditionreport->multidir_output[$object->entity] : $conf->conditionreport->dir_output;
            $upload_dir .= '/conditionreport/' . dol_sanitizeFileName($object->ref) . '/';

            $date     = dol_print_date(dol_now(), "%Y%m%d%H%M%S");
            $filename = "signatures/" . $date . "_signature.png";
            if (!is_dir($upload_dir . "signatures/")) {
                if (!dol_mkdir($upload_dir . "signatures/")) {
                    $response = "Error mkdir. Failed to create dir " . $upload_dir . "signatures/";
                    $error++;
                }
            }
            if (!$error) {
                $return = file_put_contents($upload_dir . $filename, $data);
                if ($return == false) {
                    $error++;
                    $response = 'Error file_put_content: failed to create signature file.';
                }
            }

            if (!$error) {
                // Defined modele of doc
                $last_main_doc_file = $object->last_main_doc;
                $directdownloadlink = $object->getLastMainDocLink('conditionreport');    // url to download the $object->last_main_doc
                if (preg_match('/\.pdf/i', $last_main_doc_file)) {
                    // TODO Use the $last_main_doc_file to defined the $newpdffilename and $sourcefile
                    $newpdffilename = $upload_dir . $ref . "_signed-" . $date . ".pdf";
                    $sourcefile     = $upload_dir . $ref . ".pdf";
                    //find signed by lessor files
                    if ($signatureMode == 'tenant') {
                        $files = glob($upload_dir . $ref . "_signed-*.pdf");
                        if (count($files)) {
                            usort($files, 'sortFileByTime');
                            $sourcefile=$files[0];
                        }
                    }

                    // where positions of signature are stored
                    $dirJson = $upload_dir . $ref . "/signature/positioncr.json";
                    if (file_exists($dirJson)) {
                        //load position
                        $positions = json_decode(file_get_contents($dirJson));
                        $p         = $signatureMode . '_sign';
                        $position  = $positions->$p;
                        if (!$error && dol_is_file($sourcefile)) {
                            // We build the new PDF
                            $pdf = pdf_getInstance();
                            if (class_exists('TCPDF')) {
                                $pdf->setPrintHeader(false);
                                $pdf->setPrintFooter(false);
                            }
                            $pdf->SetFont(pdf_getPDFFont($langs));

                            if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
                                $pdf->SetCompression(false);
                            }

                            //$pdf->Open();
                            $pagecount = $pdf->setSourceFile($sourcefile);        // original PDF

                            $param                     = array();
                            $param['online_sign_name'] = $online_sign_name;
                            $param['pathtoimage']      = $upload_dir . $filename;

                            $s = array();    // Array with size of each page. Exemple array(w' => 210, 'h' => 297);
                            for ($pageNb = 1;
                                $pageNb < ($pagecount + 1);
                                $pageNb++) {
                                try {
                                    $tppl = $pdf->importPage($pageNb);
                                    $s    = $pdf->getTemplatesize($tppl);
                                    $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                                    $pdf->useTemplate($tppl);
                                    if ($pageNb == $position->page) {
                                        // A signature image file is 720 x 180 (ratio 1/4) but we use only the size into PDF
                                        // TODO Get position of box from PDF template

                                        $param['xforimgstart'] = $position->x;
                                        $param['yforimgstart'] = $position->y;
                                        $param['wforimg']      = $position->w;
                                        $param['mention']      = $langs->transnoentities('signExact') . ' - ';
                                        if ($signatureMode == 'tenant')
                                            $param['mention']      .= $langs->transnoentities('signExactTenant') . ' - ';


                                        dolPrintSignatureImage($pdf, $langs, $param);
                                    }
                                } catch (Exception $e) {
                                    dol_syslog("Error when manipulating some PDF by onlineSign: " . $e->getMessage(), LOG_ERR);
                                    $response = $e->getMessage();
                                    $error++;
                                }
                            }
                            if ($signatureMode == 'lessor')
                                $res = $object->setSignedLessor();
                            elseif ($signatureMode == 'tenant')
                                $res = $object->setSignedTenant();

                            //$pdf->Close();
                            $pdf->Output($newpdffilename, "F");

                            // Index the new file and update the last_main_doc property of object.
                            $object->indexFile($newpdffilename, 1);
                        } else {
                            $error++;
                            $response = 'Can t find file to sign' . $sourcefile;
                        }
                    } else {
                        $response = 'can t find position file';
                        $error++;
                    }
                    if (!$error) {
                        $response = "success";
                    }
                } elseif (preg_match('/\.odt/i', $last_main_doc_file)) {
                    // Adding signature on .ODT not yet supported
                    // TODO
                } else {
                    // Document format not supported to insert online signature.
                    // We should just create an image file with the signature.
                }
            }
        }
    } else {
        $error++;
        $response = 'error signature_not_found';
    }
}

if ($error) {
    http_response_code(501);
}

echo $response;

/**
 * Output the signature file
 *
 * @param 	TCPDF 		$pdf		PDF handler
 * @param	Translate	$langs		Language
 * @param	array		$params		Array of params
 * @return	void
 */
function dolPrintSignatureImage(TCPDF $pdf, $langs, $params)
{
    $default_font_size = pdf_getPDFFontSize($langs); // Must be after pdf_getInstance
    $default_font      = pdf_getPDFFont($langs); // Must be
    $xforimgstart      = $params['xforimgstart'];
    $yforimgstart      = $params['yforimgstart'];
    $wforimg           = $params['wforimg'];
    $mention           = $params['mention'];

    $pdf->SetXY($xforimgstart, $yforimgstart);
    $pdf->SetFont($default_font, '', $default_font_size - 1);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->MultiCell($wforimg, 4, $mention . $langs->trans("Signature") . ': ' . dol_print_date(dol_now(), "day", false, $langs, true) . ' - ' . $params['online_sign_name'], 0, 'L');
    //$pdf->SetXY($xforimgstart, $yforimgstart + round($wforimg / 4));
    //$pdf->MultiCell($wforimg, 4, $langs->trans("Lastname") . ': ' . $online_sign_name, 0, 'L');

    $pdf->Image($params['pathtoimage'], $xforimgstart, $yforimgstart + 10, $wforimg, round($wforimg / 4));

    return;
}
