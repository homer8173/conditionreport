<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2012      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *  \file			htdocs/core/modules/conditionreport/modules_conditionreport.php
 *  \ingroup		conditionreport
 *  \brief			File that contains parent class for conditionreports document models and parent class for conditionreports numbering models
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commondocgenerator.class.php';
if (file_exists(DOL_DOCUMENT_ROOT . '/core/class/commonnumrefgenerator.class.php'))
    require_once DOL_DOCUMENT_ROOT . '/core/class/commonnumrefgenerator.class.php';
else // not available on Dol 17
    dol_include_once('/conditionreport/core/modules/conditionreport/commonnumrefgenerator.class.php');

/**
 * 	Parent class for documents models
 */
abstract class ModelePDFConditionreportroom extends CommonDocGenerator
{
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Return list of active generation modules
     *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
     */
    public static function liste_modeles($db, $maxfilenamelength = 0)
    {
        // phpcs:enable
        $type = 'conditionreport';
        $list = array();

        include_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
        $list = getListOfModels($db, $type, $maxfilenamelength);

        return $list;
    }
}

/**
 *  Parent class to manage numbering of Conditionreportroom
 */
abstract class ModeleNumRefConditionreportroom extends CommonNumRefGenerator
{
    // No overload code
    
    function getMultidirOutput()
    {
        return dol_buildpath(DOL_DOCUMENT_ROOT.'/conditionreport/room/');
    }
}
