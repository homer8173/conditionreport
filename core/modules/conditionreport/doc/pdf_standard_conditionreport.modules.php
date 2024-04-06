<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand        <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       core/modules/conditionreport/doc/pdf_standard.modules.php
 *  \ingroup    conditionreport
 *  \brief      File of class to generate document from standard template
 */
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php';

dol_include_once('/conditionreport/core/modules/conditionreport/modules_conditionreport.php');
dol_include_once('/custom/ultimateimmo/class/immorenter.class.php');
dol_include_once('/custom/ultimateimmo/class/immoowner.class.php');
dol_include_once('/custom/ultimateimmo/class/immoproperty.class.php');
dol_include_once('/custom/ultimateimmo/class/immocompteur.class.php');
dol_include_once('/custom/ultimateimmo/class/immocompteur_type.class.php');

/**
 * 	Class to manage PDF template standard_conditionreport
 */
class pdf_standard_conditionreport extends ModelePDFConditionreport
{

    /**
     * @var DoliDb Database handler
     */
    public $db;

    /**
     * @var int The environment ID when using a multicompany module
     */
    public $entity;

    /**
     * @var string model name
     */
    public $name;

    /**
     * @var string model description (short text)
     */
    public $description;

    /**
     * @var int     Save the name of generated file as the main doc when generating a doc with this template
     */
    public $update_main_doc_field;

    /**
     * @var string document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 7.0 = array(7, 0)
     */
    public $phpmin = array(7, 0);

    /**
     * Dolibarr version of the loaded document
     * @var string
     */
    public $version = 'dolibarr';

    /**
     * Issuer
     * @var Societe Object that emits
     */
    public $emetteur;

    /**
     * @var array of document table columns
     */
    public $cols;

    /**
     * @var lodgement
     */
    public $property;

    /**
     * 	Constructor
     *
      \n  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        global $conf, $langs, $mysoc;

        // Translations
        $langs->loadLangs(array("main", "bills"));

        $this->db                    = $db;
        $this->name                  = "standard";
        $this->description           = $langs->trans('DocumentModelStandardPDF');
        $this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template
        // Dimension page
        $this->type                  = 'pdf';
        $formatarray                 = pdf_getFormat();
        $this->page_largeur          = $formatarray['width'];
        $this->page_hauteur          = $formatarray['height'];
        $this->format                = array($this->page_largeur, $this->page_hauteur);
        $this->marge_gauche          = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
        $this->marge_droite          = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
        $this->marge_haute           = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
        $this->marge_basse           = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

        // Get source company
        $this->emetteur = $mysoc;
        if (empty($this->emetteur->country_code)) {
            $this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
        }

        // Define position of columns
        $this->posxdesc = $this->marge_gauche + 1; // used for notes ans other stuff


        $this->tabTitleHeight = 5; // default height
        //  Use new system for position of columns, view  $this->defineColumnField()

        $this->tva                   = array();
        $this->tva_array             = array();
        $this->localtax1             = array();
        $this->localtax2             = array();
        $this->atleastoneratenotnull = 0;
        $this->atleastonediscount    = 0;
    }
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
      \n  Function to build pdf onto disk
     *
      \n  @param		Conditionreport	$object				Object to generate
      \n  @param		Translate	$outputlangs		Lang output object
      \n  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
      \n  @param		int			$hidedetails		Do not show line details
      \n  @param		int			$hidedesc			Do not show desc
      \n  @param		int			$hideref			Do not show ref
      \n  @return     int         	    			1=OK, 0=KO
     */
    public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        // phpcs:enable
        global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

        dol_syslog("write_file outputlangs->defaultlang=" . (is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

        if (!is_object($outputlangs)) {
            $outputlangs = $langs;
        }
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        if (getDolGlobalInt('MAIN_USE_FPDF')) {
            $outputlangs->charset_output = 'ISO-8859-1';
        }

        // Load translation files required by the page
        $outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

        // Show Draft Watermark
        if (getDolGlobalString('CONDITIONREPORT_DRAFT_WATERMARK') && $object->statut == $object::STATUS_DRAFT) {
            $this->watermark = getDolGlobalString('CONDITIONREPORT_DRAFT_WATERMARK');
        }

        global $outputlangsbis;
        $outputlangsbis = null;
        if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
            $outputlangsbis = new Translate('', $conf);
            $outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
            $outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
        }

        $nblines = (is_array($object->lines) ? count($object->lines) : 0);

        $hidetop = 0;
        if (getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE')) {
            $hidetop = getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE');
        }

        // Loop on each lines to detect if there is at least one image to show
        $realpatharray         = array();
        $this->atleastonephoto = false;
        /*
          if (getDolGlobalInt('MAIN_GENERATE_CONDITIONREPORT_WITH_PICTURE'))) {
          $objphoto = new Product($this->db);

          for ($i = 0; $i < $nblines; $i++) {
          if (empty($object->lines[$i]->fk_product)) {
          continue;
          }

          $objphoto->fetch($object->lines[$i]->fk_product);
          //var_dump($objphoto->ref);exit;
          if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
          $pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
          $pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
          } else {
          $pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product'); // default
          $pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/"; // alternative
          }

          $arephoto = false;
          foreach ($pdir as $midir) {
          if (!$arephoto) {
          if ($conf->entity != $objphoto->entity) {
          $dir = $conf->product->multidir_output[$objphoto->entity].'/'.$midir; //Check repertories of current entities
          } else {
          $dir = $conf->product->dir_output.'/'.$midir; //Check repertory of the current product
          }

          foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
          if (!getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES')) {		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
          if ($obj['photo_vignette'])	{
          $filename = $obj['photo_vignette'];
          } else {
          $filename = $obj['photo'];
          }
          } else {
          $filename = $obj['photo'];
          }

          $realpath = $dir.$filename;
          $arephoto = true;
          $this->atleastonephoto = true;
          }
          }
          }

          if ($realpath && $arephoto) {
          $realpatharray[$i] = $realpath;
          }
          }
          }
         */

        //if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

        if (getMultidirOutput($object)) {
            $object->fetch_thirdparty();

            // Definition of $dir and $file
            if ($object->specimen) {
                $dir  = getMultidirOutput($object);
                $file = $dir . "/SPECIMEN.pdf";
            } else {
                $objectref = dol_sanitizeFileName($object->ref);
                $dir       = getMultidirOutput($object) . "/" . $objectref;
                $file      = $dir . "/" . $objectref . ".pdf";
            }
            if (!file_exists($dir)) {
                if (dol_mkdir($dir) < 0) {
                    $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                    return 0;
                }
            }

            if (file_exists($dir)) {
                // Add pdfgeneration hook
                if (!is_object($hookmanager)) {
                    include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
                    $hookmanager = new HookManager($this->db);
                }
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook    = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
                // Set nblines with the new lines content after hook
                $nblines    = (is_array($object->lines) ? count($object->lines) : 0);

                // Create pdf instance
                $pdf               = pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
                $pdf->SetAutoPageBreak(1, 0);

                $heightforinfotot  = 50; // Height reserved to output the info and total part and payment part
                $heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
                $heightforfooter   = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

                if (class_exists('TCPDF')) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

                // Set path to the background PDF File
                if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
                    $logodir = $conf->mycompany->dir_output;
                    if (!empty($conf->mycompany->multidir_output[$object->entity])) {
                        $logodir = $conf->mycompany->multidir_output[$object->entity];
                    }
                    $pagecount = $pdf->setSourceFile($logodir . '/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
                    $tplidx    = $pdf->importPage(1);
                }

                $pdf->Open();
                $pagenb = 0;
                $pdf->SetDrawColor(128, 128, 128);

                $pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
                $pdf->SetSubject($outputlangs->transnoentities("PdfTitle"));
                $pdf->SetCreator("Dolibarr " . DOL_VERSION);
                $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
                $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref) . " " . $outputlangs->transnoentities("PdfTitle") . " " . $outputlangs->convToOutputCharset($object->thirdparty->name));
                if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
                    $pdf->SetCompression(false);
                }

                // Set certificate
                $cert = empty($user->conf->CERTIFICATE_CRT) ? '' : $user->conf->CERTIFICATE_CRT;
                // If user has no certificate, we try to take the company one
                if (!$cert) {
                    $cert = getDolGlobalString('CERTIFICATE_CRT');
                }
                // If a certificate is found
                if ($cert) {
                    $info = array(
                        'Name' => $this->emetteur->name,
                        'Location' => getCountry($this->emetteur->country_code, 0),
                        'Reason' => 'CONDITIONREPORT',
                        'ContactInfo' => $this->emetteur->email
                    );
                    $pdf->setSignature($cert, $cert, $this->emetteur->name, '', 2, $info);
                }

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
                // New page
                $pdf->AddPage();
                if (!empty($tplidx)) {
                    $pdf->useTemplate($tplidx);
                }
                $pagenb++;

                $top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, ''); // Set interline to 3
                $pdf->SetTextColor(0, 0, 0);

                $tab_top         = 90 + $top_shift;
                $tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);

                $tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

                $tab_height_newpage = 150;
                if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                    $tab_height_newpage -= $top_shift;
                }

                $nexY = $tab_top - 1;

                // Display notes
                $notetoshow = empty($object->note_public) ? '' : $object->note_public;
                // Extrafields in note
                $extranote  = $this->getExtrafieldsInHtml($object, $outputlangs);
                if (!empty($extranote)) {
                    $notetoshow = dol_concatdesc($notetoshow, $extranote);
                }

                $pagenb = $pdf->getPage();
                if ($notetoshow) {
                    $tab_top -= 2;

                    $tab_width         = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
                    $pageposbeforenote = $pagenb;

                    $substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
                    complete_substitutions_array($substitutionarray, $outputlangs, $object);
                    $notetoshow        = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
                    $notetoshow        = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

                    $pdf->startTransaction();

                    $pdf->SetFont('', '', $default_font_size - 1);
                    $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                    // Description
                    $pageposafternote = $pdf->getPage();
                    $posyafter        = $pdf->GetY();

                    if ($pageposafternote > $pageposbeforenote) {
                        $pdf->rollbackTransaction(true);

                        // prepare pages to receive notes
                        while ($pagenb < $pageposafternote) {
                            $pdf->AddPage();
                            $pagenb++;
                            if (!empty($tplidx)) {
                                $pdf->useTemplate($tplidx);
                            }
                            if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                $this->_pagehead($pdf, $object, 0, $outputlangs);
                            }
                            // $this->_pagefoot($pdf,$object,$outputlangs,1);
                            $pdf->setTopMargin($tab_top_newpage);
                            // The only function to edit the bottom margin of current page to set it.
                            $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
                        }

                        // back to start
                        $pdf->setPage($pageposbeforenote);
                        $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
                        $pdf->SetFont('', '', $default_font_size - 1);
                        $pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
                        $pageposafternote = $pdf->getPage();

                        $posyafter = $pdf->GetY();

                        if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) { // There is no space left for total+free text
                            $pdf->AddPage('', '', true);
                            $pagenb++;
                            $pageposafternote++;
                            $pdf->setPage($pageposafternote);
                            $pdf->setTopMargin($tab_top_newpage);
                            // The only function to edit the bottom margin of current page to set it.
                            $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
                            //$posyafter = $tab_top_newpage;
                        }


                        // apply note frame to previous pages
                        $i = $pageposbeforenote;
                        while ($i < $pageposafternote) {
                            $pdf->setPage($i);

                            $pdf->SetDrawColor(128, 128, 128);
                            // Draw note frame
                            if ($i > $pageposbeforenote) {
                                $height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
                                $pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
                            } else {
                                $height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
                                $pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);
                            }

                            // Add footer
                            $pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
                            $this->_pagefoot($pdf, $object, $outputlangs, 1);

                            $i++;
                        }

                        // apply note frame to last page
                        $pdf->setPage($pageposafternote);
                        if (!empty($tplidx)) {
                            $pdf->useTemplate($tplidx);
                        }
                        if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                            $this->_pagehead($pdf, $object, 0, $outputlangs);
                        }
                        $height_note = $posyafter - $tab_top_newpage;
                        $pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
                    } else {
                        // No pagebreak
                        $pdf->commitTransaction();
                        $posyafter   = $pdf->GetY();
                        $height_note = $posyafter - $tab_top;
                        $pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);

                        if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
                            // not enough space, need to add page
                            $pdf->AddPage('', '', true);
                            $pagenb++;
                            $pageposafternote++;
                            $pdf->setPage($pageposafternote);
                            if (!empty($tplidx)) {
                                $pdf->useTemplate($tplidx);
                            }
                            if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                $this->_pagehead($pdf, $object, 0, $outputlangs);
                            }

                            $posyafter = $tab_top_newpage;
                        }
                    }

                    $tab_height = $tab_height - $height_note;
                    $tab_top    = $posyafter + 6;
                } else {
                    $height_note = 0;
                }

                // Use new auto column system
                $this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

                // Table simulation to know the height of the title line
                $pdf->startTransaction();
                $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
                $pdf->rollbackTransaction(true);

                $nexY = $tab_top + $this->tabTitleHeight;

                // Loop on each lines
                $this->_pagefoot($pdf, $object, $outputlangs);
                $pageposbeforeprintlines = $pdf->getPage();
                $pagenb                  = $pageposbeforeprintlines;
                for ($i = 0; $i < $nblines; $i++) {
                    $crr        = $object->lines[$i];
                    $crr->generateDocument('', $outputlangs);
                    $pdfExterne = DOL_DATA_ROOT . '/' . $crr->last_main_doc;
                    if (file_exists($pdfExterne) && mime_content_type($pdfExterne) == 'application/pdf') {
                        // Ouvrir le PDF source
                        $pageCount = $pdf->setSourceFile($pdfExterne);
                        // Boucle pour importer chaque page du PDF source
                        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                            // Ajouter une nouvelle page au PDF TCPDF
                            $pdf->AddPage();
                            $pagenb++;
                            // Importer la page du PDF source
                            $templateIdCR = $pdf->importPage($pageNumber, '/MediaBox');

                            // Utiliser la page importée dans le PDF TCPDF
                            $pdf->useTemplate($templateIdCR);
                            if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                                $this->_pagehead($pdf, $object, 0, $outputlangs);
                            }
                            $this->_pagefoot($pdf, $object, $outputlangs);
                        }
                    }
                }
                $pdf->AddPage();
                $pagenb++;
                if (!empty($tplidx)) {
                    $pdf->useTemplate($tplidx);
                }
                if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
                    $this->_pagehead($pdf, $object, 0, $outputlangs);
                }

                // Show square
//				if ($pagenb == $pageposbeforeprintlines) {
//					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, $hidetop, 0, $object->multicurrency_code, $outputlangsbis);
//				} else {
//					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code, $outputlangsbis);
//				}
//				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
                // Display infos area
                //$posy = $this->drawInfoTable($pdf, $object, $bottomlasttab, $outputlangs);
                // Display total zone
                //$posy = $this->drawTotalTable($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);
                // Display payment area
                /*
                  if ($deja_regle)
                  {
                  $posy = $this->drawPaymentsTable($pdf, $object, $posy, $outputlangs);
                  }
                 */

                $pdf->SetFont('', 'B', $default_font_size + 5);
                $fullWidth = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

                // Titre centré
                $pdf->SetTextColor(0, 0, 60);
                $pdf->Cell(0, 10, $outputlangs->transnoentities("titreSignatureCR"), 0, 1, 'C');
                $tab_top = $pdf->getY() + 5;
                $pdf->SetTextColor(0, 0, 0);

                //legals
                $pdf->SetTextColor(125, 125, 125);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->SetXY($this->marge_gauche, $top_shift);
                $pdf->MultiCell($fullWidth, 5, $outputlangs->transnoentities("texteLegalCR2"), 0, $ltrdirection);

                // done at 
                if (is_object($this->property))
                    $town = $this->property->town;
                $pdf->SetTextColor(0, 0, 60);
                $pdf->SetFont('', '', $default_font_size + 2);
                $pdf->SetXY($this->marge_gauche, $pdf->getY() + 10);
                $pdf->MultiCell($fullWidth, 5, $outputlangs->transnoentities("DoneAtCR", $town, dol_print_date(time(), 'daytext')), 0, $ltrdirection);

                $posY = $pdf->getY() + 15;

                // sign owner
                $pdf->SetTextColor(0, 0, 60);
                $pdf->SetFont('', '', $default_font_size + 3);
                $pdf->SetXY($this->marge_gauche, $posY);
                $pdf->MultiCell($fullWidth / 2, 5, $outputlangs->transnoentities("SigneOwner"), 0, $ltrdirection);

                $pdf->SetTextColor(125, 125, 125);
                $pdf->SetFont('', '', $default_font_size);
                $pdf->SetXY($this->marge_gauche, $posY + 7);
                $pdf->MultiCell($fullWidth / 2, 5, $outputlangs->transnoentities("SigneOwnerDetails"), 0, $ltrdirection);

                // sign tenant                
                $pdf->SetTextColor(0, 0, 60);
                $pdf->SetFont('', '', $default_font_size + 3);
                $pdf->SetXY($this->marge_gauche + $fullWidth / 2 +1, $posY);
                $pdf->MultiCell($fullWidth / 2, 5, $outputlangs->transnoentities("SigneTenant"), 0, $ltrdirection);

                $pdf->SetTextColor(125, 125, 125);
                $pdf->SetFont('', '', $default_font_size);
                $pdf->SetXY($this->marge_gauche + $fullWidth / 2 +1, $posY + 7);
                $pdf->MultiCell($fullWidth / 2, 5, $outputlangs->transnoentities("SigneTenantDetails"), 0, $ltrdirection);

                // Pagefoot
                $this->_pagefoot($pdf, $object, $outputlangs);
                if (method_exists($pdf, 'AliasNbPages')) {
                    $pdf->AliasNbPages();
                }

                $pdf->Close();

                $pdf->Output($file, 'I');

                // Add pdfgeneration hook
                $hookmanager->initHooks(array('pdfgeneration'));
                $parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
                global $action;
                $reshook    = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
                if ($reshook < 0) {
                    $this->error  = $hookmanager->error;
                    $this->errors = $hookmanager->errors;
                }

                //dolChmod($file);

                $this->result = array('fullpath' => $file);

                return 1; // No error
            } else {
                $this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
                return 0;
            }
        } else {
            $this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
            return 0;
        }
    }
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
      \n  Return list of active generation modules
     *
      \n  @param	DoliDB	$db     			Database handler
      \n  @param  integer	$maxfilenamelength  Max length of value to show
      \n  @return	array						List of templates
     */
    public static function liste_modeles($db, $maxfilenamelength = 0)
    {
        // phpcs:enable
        return parent::liste_modeles($db, $maxfilenamelength); // TODO: Change the autogenerated stub
    }
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore

    /**
      \n   Show table for lines
     *
      \n   @param		tcpdf		$pdf     		Object PDF
      \n   @param		string		$tab_top		Top position of table
      \n   @param		string		$tab_height		Height of table (rectangle)
      \n   @param		int			$nexY			Y (not used)
      \n   @param		Translate	$outputlangs	Langs object
      \n   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
      \n   @param		int			$hidebottom		Hide bottom bar of array
      \n   @param		string		$currency		Currency code
      \n   @param		Translate	$outputlangsbis	Langs object bis
      \n   @return	void
     */
    protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
    {
        global $conf;

        // Force to disable hidetop and hidebottom
        $hidebottom = 0;
        if ($hidetop) {
            $hidetop = -1;
        }

        $currency          = !empty($currency) ? $currency : $conf->currency;
        $default_font_size = pdf_getPDFFontSize($outputlangs);

        // Amount in (at tab_top - 1)
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('', '', $default_font_size - 2);

        if (empty($hidetop)) {
            $titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency" . $currency));
            if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
                $titre .= ' - ' . $outputlangsbis->transnoentities("AmountInCurrency", $outputlangsbis->transnoentitiesnoconv("Currency" . $currency));
            }

            $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
            $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

            //$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
            if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
                $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->tabTitleHeight, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
            }
        }

        $pdf->SetDrawColor(128, 128, 128);
        $pdf->SetFont('', '', $default_font_size - 1);

        // Output Rect
        $this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter


        $this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);

        if (empty($hidetop)) {
            $pdf->line($this->marge_gauche, $tab_top + $this->tabTitleHeight, $this->page_largeur - $this->marge_droite, $tab_top + $this->tabTitleHeight); // line takes a position y in 2nd parameter and 4th parameter
        }
    }
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore

    /**
      \n  Show top header of page.
     *
      \n  @param	TCPDF		$pdf     		Object PDF
      \n  @param  Conditionreport	$object     	Object to show
      \n  @param  int	    	$showaddress    0=no, 1=yes
      \n  @param  Translate	$outputlangs	Object lang for output
      \n  @param  Translate	$outputlangsbis	Object lang for output bis
      \n  @return	float|int
     */
    protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
    {
        // phpcs:enable
        global $conf, $langs;

        $ltrdirection = 'L';
        if ($outputlangs->trans("DIRECTION") == 'rtl') {
            $ltrdirection = 'R';
        }

        // Load traductions files required by page
        $outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

        $default_font_size = pdf_getPDFFontSize($outputlangs);

        pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

        // Show Draft Watermark
        if (getDolGlobalString('CONDITIONREPORT_DRAFT_WATERMARK') && $object->statut == $object::STATUS_DRAFT) {
            pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', dol_escape_htmltag(getDolGlobalString('CONDITIONREPORT_DRAFT_WATERMARK')));
        }

        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size + 3);

        $w = 110;

        $posy = $this->marge_haute;
        $posx = $this->page_largeur - $this->marge_droite - $w;

        $pdf->SetXY($this->marge_gauche, $posy);

        // Logo
        if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO') && false) {
            if ($this->emetteur->logo) {
                $logodir = $conf->mycompany->dir_output;
                if (!empty(getMultidirOutput($object, 'mycompany'))) {
                    $logodir = getMultidirOutput($object, 'mycompany');
                }
                if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
                    $logo = $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
                } else {
                    $logo = $logodir . '/logos/' . $this->emetteur->logo;
                }
                if (is_readable($logo)) {
                    $height = pdf_getHeightForLogo($logo);
                    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
                } else {
                    $pdf->SetTextColor(200, 0, 0);
                    $pdf->SetFont('', 'B', $default_font_size - 2);
                    $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
                    $pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
                }
            } else {
                $text = $this->emetteur->name;
                $pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
            }
        }

        if ($showaddress) {
            $pdf->SetFont('', 'B', $default_font_size + 10);
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $title = $outputlangs->transnoentities("DirectionCR$object->direction");
            if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
                $title .= ' - ';
                $title .= $outputlangsbis->transnoentities("PdfTitle");
            }
            $pdf->SetXY($this->marge_gauche, $posy);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 3, $title, '', 'C');
            $pdf->SetFont('', 'B', $default_font_size);
            $posy += 10;
            $pdf->SetXY($this->marge_gauche, $posy);
            $pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 3, $outputlangs->transnoentities("normeCR"), '', 'C');

            $pdf->SetFont('', 'B', $default_font_size);
            $posy += 10;
        } else {
            $pdf->SetFont('', 'B', $default_font_size);
            $posy -= 5;
        }
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);
        $textref = $outputlangs->transnoentities("Ref") . " : " . $outputlangs->convToOutputCharset($object->ref);
        if ($object->statut == $object::STATUS_DRAFT) {
            $pdf->SetTextColor(128, 0, 0);
            $textref .= ' - ' . $outputlangs->transnoentities("NotValidated");
        }
        $pdf->MultiCell($w, 4, $textref, '', 'R');

        $posy += 1;
        $pdf->SetFont('', '', $default_font_size - 2);

        $posy += 5;
        $pdf->SetXY($posx, $posy);
        $pdf->SetTextColor(0, 0, 60);

        $title = $outputlangs->transnoentities("DateEnter");
        $pdf->MultiCell($w, 3, $title . " : " . dol_print_date($object->date_enter, "day", false, $outputlangs, true), '', 'R');

        $posy  += 4;
        $pdf->SetXY($posx, $posy);
        $title = $outputlangs->transnoentities("DateExit");
        $pdf->MultiCell($w, 3, $title . " : " . dol_print_date($object->date_exit, "day", false, $outputlangs, true), '', 'R');

        if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_CODE') && !empty($object->thirdparty->code_client)) {
            $posy += 3;
            $pdf->SetXY($posx, $posy);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode") . " : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
        }

        // Get contact
        if (getDolGlobalInt('DOC_SHOW_FIRST_SALES_REP')) {
            $arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
            if (count($arrayidcontact) > 0) {
                $usertmp = new User($this->db);
                $usertmp->fetch($arrayidcontact[0]);
                $posy    += 4;
                $pdf->SetXY($posx, $posy);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($w, 3, $langs->transnoentities("SalesRepresentative") . " : " . $usertmp->getFullName($langs), '', 'R');
            }
        }

        $posy += 1;

        $top_shift = 0;
        // Show list of linked objects
        $current_y = $pdf->getY();
        $posy      = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
        if ($current_y < $pdf->getY()) {
            $top_shift = $pdf->getY() - $current_y;
        }
        if ($showaddress) {
            // show legals
            $pdf->SetTextColor(125, 125, 125);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($this->marge_gauche, $posy + 10);
            $pdf->MultiCell($this->largeur - $this->marge_gauche - $this->marge_droite, 5, $outputlangs->transnoentities("texteLegalCR"), 0, $ltrdirection);
            $top_shift += 35;
        }

        if ($showaddress) {
            // Sender properties

            $lessor = new ImmoOwner($this->db);
            $r      = $lessor->fetch($object->fk_lessor);

            if ($r > 0 && is_object($lessor)) {
                $carac_emetteur_name = html_entity_decode($lessor->getCivilityLabel($lessor->civility_id) . " " . $lessor->lastname . " " . $lessor->firstname);
                $carac_emetteur      = html_entity_decode($lessor->address . "\n" . $lessor->zip . " " . $lessor->town . "\n" . $lessor->getCountry($lessor->country_id));
            }
            // Show sender
            $posy = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
            $posy += $top_shift;
            $posx = $this->marge_gauche;
            if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) {
                $posx = $this->page_largeur - $this->marge_droite - 80;
            }

            $hautcadre   = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
            $widthrecbox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;

            // Show sender frame
            if (!getDolGlobalString('MAIN_PDF_NO_SENDER_FRAME')) {
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->SetXY($posx + 2, $posy - 5);
                $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("TheLessor") . ":", 0, $ltrdirection);
                $pdf->SetXY($posx, $posy);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
                $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
                $pdf->SetTextColor(0, 0, 60);
            }

            // Show sender name
            if (!getDolGlobalString('MAIN_PDF_HIDE_SENDER_NAME')) {
                $pdf->SetXY($posx + 2, $posy + 3);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($carac_emetteur_name), 0, $ltrdirection);
                $posy = $pdf->getY();
            }

            // Show sender information
            $pdf->SetXY($posx + 2, $posy);
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, $ltrdirection);

            $tenant = new ImmoRenter($this->db);
            $r      = $tenant->fetch($object->fk_tenant);

            if ($r > 0 && is_object($tenant)) {
                $carac_client_name = html_entity_decode($tenant->getCivilityLabel($tenant->civility_id) . " " . $tenant->lastname . " " . $tenant->firstname);
                $carac_client      = html_entity_decode($outputlangs->trans("BornAt") . " " . $tenant->town . ", " . $tenant->getCountry($tenant->country_id)) . "\n";
            }

//            $mode         = 'target';
//            $carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object);
            // Show recipient
            $widthrecbox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 100;
            if ($this->page_largeur < 210) {
                $widthrecbox = 84; // To work with US executive format
            }
            $posy = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
            $posy += $top_shift;
            $posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
            if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) {
                $posx = $this->marge_gauche;
            }

            // Show recipient frame
            if (!getDolGlobalString('MAIN_PDF_NO_RECIPENT_FRAME')) {
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('', '', $default_font_size - 2);
                $pdf->SetXY($posx + 2, $posy - 5);
                $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("TheTenant") . ":", 0, $ltrdirection);
                $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
            }

            // Show recipient name
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, $ltrdirection);

            $posy = $pdf->getY();

            // Show recipient information
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy);
            $pdf->MultiCell($widthrecbox, 4, $carac_client, 0, $ltrdirection);
        }
        // logement
        if ($showaddress) {
            //load lodgement
            $prop           = new ImmoProperty($this->db);
            $prop->fetch($object->fk_property);
            $this->property = $prop;

            // show cadre
            $posy        += $hautcadre + 10;
            $posx        = $this->marge_gauche;
            $widthrecbox = $this->page_largeur - $this->marge_droite - $this->marge_gauche;
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + 2, $posy - 5);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("TheLodgement") . ":", 0, $ltrdirection);
            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre * 1.55);

            // show lodgement
            $lodgement_carac = $prop->label . " " . $prop->type . " ID" . $prop->ref;
            $lodgement_carac .= ", " . $prop->address . " " . $prop->zip . " " . $prop->town . ", " . $prop->getCountry($prop->country_id);
            $pdf->SetXY($posx + 2, $posy + 3);
            $pdf->SetFont('', 'B', $default_font_size);
            $pdf->SetTextColor(0, 0, 60);
            $pdf->MultiCell($widthrecbox, 2, $lodgement_carac, 0, $ltrdirection);

            $posy                    = $pdf->getY();
            $lodgement_carac_details = '  ';
            $lodgement_carac_details .= $outputlangs->transnoentities("Juridique") . ": " . html_entity_decode($prop->fields['juridique_id']['arrayofkeyval'][$prop->juridique_id]) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("DateBuilt") . ": " . html_entity_decode($prop->fields['datebuilt']['arrayofkeyval'][$prop->datebuilt]) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("Area") . ": " . html_entity_decode($prop->area) . " m²  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("NumberOfRoom") . ": " . html_entity_decode($prop->numroom) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("NumFlat") . ": " . html_entity_decode($prop->numflat) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("NumDoor") . ": " . html_entity_decode($prop->numdoor) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("NumFloor") . ": " . html_entity_decode($prop->numfloor) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("Staircase") . ": " . html_entity_decode($prop->staircase) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("Building") . ": " . html_entity_decode($prop->building) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("UISectioncadastrale") . ": " . html_entity_decode($prop->section_cadastrale) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("UIParcellecadastrale") . ": " . html_entity_decode($prop->parcelle_cadastrale) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("UINumPrmEdf") . ": " . html_entity_decode($prop->num_prm_edf) . "  \n  ";
            $lodgement_carac_details .= $outputlangs->transnoentities("UINumLigneNet") . ": " . html_entity_decode($prop->num_internet_line);

            // Show lodgement details
            $pdf->SetFont('', '', $default_font_size - 1);
            $pdf->SetXY($posx + 2, $posy);
            $pdf->MultiCell($widthrecbox, 4, $lodgement_carac_details, 0, $ltrdirection);
        }
        // compteurs
        if ($showaddress) {
            //load compteurs
            $compteurs = new ImmoCompteur($this->db);
            $res       = $compteurs->fetchAll('', '', 0, 0, ['fk_immoproperty' => $prop->id]);

            // show cadre
            $posy        += $hautcadre * 1.55 + 10;
            $posx        = $this->marge_gauche;
            $widthrecbox = $this->page_largeur - $this->marge_droite - $this->marge_gauche;
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('', '', $default_font_size - 2);
            $pdf->SetXY($posx + 2, $posy - 5);
            $pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("TheCompteurs") . ":", 0, $ltrdirection);
            $pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

            if (is_array($res) && count($res) > 0)
                $widthrecbox = $widthrecbox / count($res);$posx        += 1;
            foreach ($res as $compteur) {
                // show lodgement
                $ict            = new ImmoCompteur_Type($this->db);
                $ict->fetch($compteur->compteur_type_id);
                $compteur_carac = $outputlangs->trans('ImmoCompteurType') . ": " . $ict->getNomUrl(0, 'nolink');
                $pdf->SetXY($posx, $posy + 3);
                $pdf->SetFont('', 'B', $default_font_size);
                $pdf->SetTextColor(0, 0, 60);
                $pdf->MultiCell($widthrecbox, 2, $compteur_carac, 0, $ltrdirection);

                $compteur_carac_details = html_entity_decode($outputlangs->trans('ImmoCompteurDateStatement')) . ": " . dol_print_date($compteur->date_relever, "day", false, $outputlangs, true) . "\n";
                $compteur_carac_details .= html_entity_decode($outputlangs->trans('ImmoCompteurStatement')) . ": " . $compteur->qty;
                // Show lodgement details
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->SetXY($posx, $posy + 10);
                $pdf->MultiCell($widthrecbox, 4, $compteur_carac_details, 0, $ltrdirection);
                $posx                   += $widthrecbox;
            }
            $posy = $pdf->getY();
        }
        $pdf->SetTextColor(0, 0, 0);
        return $top_shift;
    }
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore

    /**
      \n   	Show footer of page. Need this->emetteur object
     *
      \n   	@param	TCPDF		$pdf     			PDF
     * 		@param	Object		$object				Object to show
      \n      @param	Translate	$outputlangs		Object lang for output
      \n      @param	int			$hidefreetext		1=Hide free text
      \n      @return	int								Return height of bottom margin including footer text
     */
    protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
    {
        global $conf;
        $showdetails = !getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 0 : getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');
        return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
    }

    /**
      \n  Define Array Column Field
     *
      \n  @param	object			$object    		common object
      \n  @param	Translate		$outputlangs    langs
      \n  @param	int			   $hidedetails		Do not show line details
      \n  @param	int			   $hidedesc		Do not show desc
      \n  @param	int			   $hideref			Do not show ref
      \n  @return	void
     */
    public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $conf, $hookmanager;

        // Default field style for content
        $this->defaultContentsFieldsStyle = array(
            'align' => 'R', // R,C,L
            'padding' => array(1, 0.5, 1, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
        );

        // Default field style for content
        $this->defaultTitlesFieldsStyle = array(
            'align' => 'C', // R,C,L
            'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
        );

        /*
         * For exemple
          $this->cols['theColKey'] = array(
          'rank' => $rank, // int : use for ordering columns
          'width' => 20, // the column width in mm
          'title' => array(
          'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
          'label' => ' ', // the final label : used fore final generated text
          'align' => 'L', // text alignement :  R,C,L
          'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
          ),
          'content' => array(
          'align' => 'L', // text alignement :  R,C,L
          'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
          ),
          );
         */

        $rank               = 0; // do not use negative rank
        $this->cols['desc'] = array(
            'rank' => $rank,
            'width' => false, // only for desc
            'status' => true,
            'title' => array(
                'textkey' => 'Designation', // use lang key is usefull in somme case with module
                'align' => 'L',
                // 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
                // 'label' => ' ', // the final label
                'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
            ),
            'content' => array(
                'align' => 'L',
                'padding' => array(1, 0.5, 1, 1.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
            ),
        );

        // PHOTO
        $rank                = $rank + 10;
        $this->cols['photo'] = array(
            'rank' => $rank,
            'width' => (!getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH') ? 20 : getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH')), // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'Photo',
                'label' => ' '
            ),
            'content' => array(
                'padding' => array(0, 0, 0, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
            ),
            'border-left' => false, // remove left line separator
        );

        if (getDolGlobalInt('MAIN_GENERATE_INVOICES_WITH_PICTURE') && !empty($this->atleastonephoto)) {
            $this->cols['photo']['status'] = true;
        }


        $rank              = $rank + 10;
        $this->cols['vat'] = array(
            'rank' => $rank,
            'status' => false,
            'width' => 16, // in mm
            'title' => array(
                'textkey' => 'VAT'
            ),
            'border-left' => true, // add left line separator
        );

        if (!getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
            $this->cols['vat']['status'] = true;
        }

        $rank                   = $rank + 10;
        $this->cols['subprice'] = array(
            'rank' => $rank,
            'width' => 19, // in mm
            'status' => true,
            'title' => array(
                'textkey' => 'PriceUHT'
            ),
            'border-left' => true, // add left line separator
        );

        $rank              = $rank + 10;
        $this->cols['qty'] = array(
            'rank' => $rank,
            'width' => 16, // in mm
            'status' => true,
            'title' => array(
                'textkey' => 'Qty'
            ),
            'border-left' => true, // add left line separator
        );

        $rank               = $rank + 10;
        $this->cols['unit'] = array(
            'rank' => $rank,
            'width' => 11, // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'Unit'
            ),
            'border-left' => true, // add left line separator
        );
        if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
            $this->cols['unit']['status'] = true;
        }

        $rank                   = $rank + 10;
        $this->cols['discount'] = array(
            'rank' => $rank,
            'width' => 13, // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'ReductionShort'
            ),
            'border-left' => true, // add left line separator
        );
        if ($this->atleastonediscount) {
            $this->cols['discount']['status'] = true;
        }

        $rank                       = $rank + 1000; // add a big offset to be sure is the last col because default extrafield rank is 100
        $this->cols['totalexcltax'] = array(
            'rank' => $rank,
            'width' => 26, // in mm
            'status' => true,
            'title' => array(
                'textkey' => 'TotalHTShort'
            ),
            'border-left' => true, // add left line separator
        );

        // Add extrafields cols
        if (!empty($object->lines)) {
            $line = reset($object->lines);
            $this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
        }

        $parameters = array(
            'object' => $object,
            'outputlangs' => $outputlangs,
            'hidedetails' => $hidedetails,
            'hidedesc' => $hidedesc,
            'hideref' => $hideref
        );

        $reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this); // Note that $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        } elseif (empty($reshook)) {
            $this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
        } else {
            $this->cols = $hookmanager->resArray;
        }
    }
}
