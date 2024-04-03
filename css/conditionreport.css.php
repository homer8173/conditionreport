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
 */

/**
 * \file    conditionreport/css/conditionreport.css.php
 * \ingroup conditionreport
 * \brief   CSS file for module Conditionreport.
 */
//if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (!defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (!defined('NOREQUIRESOC')) {
    define('NOREQUIRESOC', '1');
}
//if (!defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
//if (!defined('NOCSRFCHECK'))   define('NOCSRFCHECK', 1);		// Should be disable only for special situation
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
    define('NOLOGIN', 1); // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
    define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');
// false or '' = keep cache instruction added by server
// 'public'  = remove cache instruction added by server
// and if no cache-control added later, a default cache delay (10800) will be added by PHP.
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/* if (empty($user->id) && !empty($_SESSION['dol_login'])) {
  $user->fetch('',$_SESSION['dol_login']);
  $user->getrights();
  } */


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) {
    header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
    header('Cache-Control: no-cache');
}
ob_start();

?><style><?php ob_end_clean(); ?>

    div.mainmenu.conditionreport::before {
        content: "\f249";
    }
    div.mainmenu.conditionreport {
        background-image: none;
    }

    ul.load_model  {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
    }

    .load_model li {
        margin-left: 1em;
    }


    .editable {
        cursor: url("data:image/svg+xml,%3C%3Fxml%20version%3D%221.0%22%20encoding%3D%22utf-8%22%3F%3E%0A%3Csvg%20width%3D%2232%22%20height%3D%2232%22%20viewBox%3D%220%200%201792%201792%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M888%201184l116-116-152-152-116%20116v56h96v96h56zm440-720q-16-16-33%201l-350%20350q-17%2017-1%2033t33-1l350-350q17-17%201-33zm80%20594v190q0%20119-84.5%20203.5t-203.5%2084.5h-832q-119%200-203.5-84.5t-84.5-203.5v-832q0-119%2084.5-203.5t203.5-84.5h832q63%200%20117%2025%2015%207%2018%2023%203%2017-9%2029l-49%2049q-14%2014-32%208-23-6-45-6h-832q-66%200-113%2047t-47%20113v832q0%2066%2047%20113t113%2047h832q66%200%20113-47t47-113v-126q0-13%209-22l64-64q15-15%2035-7t20%2029zm-96-738l288%20288-672%20672h-288v-288zm444%20132l-92%2092-288-288%2092-92q28-28%2068-28t68%2028l152%20152q28%2028%2028%2068t-28%2068z%22%2F%3E%3C%2Fsvg%3E") 8 8, pointer;
        text-decoration: underline var(--colortexttitlenotab) dashed;
        user-select: none;
    }
    .editable .edit{
        display: none;
    }
    .editable .view{
        display: block;
    }
    

    /* Cacher l\'input de type file */
    input.quickUpload {
        display: none;
    }
    
    /* Styles pour le bouton label */
    .custom-file-upload {
        display: inline-block;
        padding: 10px 20px;
        cursor: pointer;
        background-color: var(--butactionbg);
        color: #fff;
        border-radius: 5px;
    }

    /* Styles pour le bouton invisible */
    .custom-file-upload input.quickUpload {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }