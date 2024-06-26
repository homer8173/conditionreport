<?php
/* Copyright (C) 2024 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) {
    define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIREDB')) {
    define('NOREQUIREDB', '1');
}
if (!defined('NOREQUIRESOC')) {
    define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
    define('NOREQUIRETRAN', '1');
}
if (!defined('NOCSRFCHECK')) {
    define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
    define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}


/**
 * \file    conditionreport/js/conditionreport.js.php
 * \ingroup conditionreport
 * \brief   JavaScript file for module Conditionreport.
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
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
    header('Cache-Control: max-age=3600, public, must-revalidate');
} else {
    header('Cache-Control: no-cache');
}
ob_start();

?><script><?php ob_end_clean(); ?>

    /* Javascript library of module Conditionreport */

    $(document).ready(function () {
        let editable = ".editable";

        $(editable).on('click', function () {
            $(this).off('click').children('div.view, div.edit').toggle();
        });

        $("#userfile").on("change", function () {
            $("form#formuserfile").submit();
        });


        $('.crrSaveButton').on('click', function (event) {
            event.preventDefault();
            let button = $(this);
            let targetID = $(this).data('targetid'); // the line ID
            let target = $(this).data('target');
            let val = $('[data-id="' + target + '_' + targetID + '"]').val(); // the value of the field
            console.log(targetID,target,val,button);
            // Requête AJAX POST
            $.post('<?php print dol_buildpath('/conditionreport/ajax/conditionreport.php', 1); ?>',
                    {action: 'updateLine', id: targetID, target: target, value: val},
                    function (response) {
                        // Traitement de la réponse
                        $('[data-idview="' + target + '_' + targetID + '"]').html(response);
                        button.closest(editable).on('click', function () {
                            $(this).off('click').children('div.view, div.edit').toggle();
                        }).children('div.view, div.edit').toggle();
                    }).fail(function (xhr, status, error) {
                // Gestion des erreurs
                console.error(status, error);
            });
        });
    });

