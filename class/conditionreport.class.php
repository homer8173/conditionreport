<?php
/* Copyright (C) 2017  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023  Frédéric France          <frederic.france@netlogic.fr>
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
 * \file        class/conditionreport.class.php
 * \ingroup     conditionreport
 * \brief       This file is a CRUD class file for Conditionreport (Create/Read/Update/Delete)
 */
// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
dol_include_once('/conditionreport/class/conditionreportroom.class.php');

/**
 * Class for Conditionreport
 */
class Conditionreport extends CommonObject
{

    /**
     * @var string ID of module.
     */
    public $module = 'conditionreport';

    /**
     * @var string ID to identify managed object.
     */
    public $element = 'conditionreport';

    /**
     * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
     */
    public $table_element = 'conditionreport_conditionreport';

    /**
     * @var int  	Does this object support multicompany module ?
     * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
     */
    public $ismultientitymanaged = 0;

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string String with name of icon for conditionreport. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'conditionreport@conditionreport' if picto is file 'img/object_conditionreport.png'.
     */
    public $picto = 'fa-home';

    const STATUS_DRAFT         = 0;
    const STATUS_VALIDATED     = 1;
    const STATUS_SIGNED_LESSOR = 2;
    const STATUS_SIGNED_TENANT = 3;
    const STATUS_CANCELED      = 9;

    /**
     *  'type' field format:
     *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
     *  	'select' (list of values are in 'options'),
     *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
     *  	'chkbxlst:...',
     *  	'varchar(x)',
     *  	'text', 'text:none', 'html',
     *   	'double(24,8)', 'real', 'price', 'stock',
     *  	'date', 'datetime', 'timestamp', 'duration',
     *  	'boolean', 'checkbox', 'radio', 'array',
     *  	'mail', 'phone', 'url', 'password', 'ip'
     * 		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
     *  'label' the translation key.
     *  'picto' is code of a picto to show before value in forms
     *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
     *  'position' is the sort order of field.
     *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
     *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
     *  'noteditable' says if field is not editable (1 or 0)
     *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
     *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
     *  'index' if we want an index in database.
     *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
     *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
     *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
     *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
     *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
     *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
     *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
     *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
     *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
     *  'comment' is not used. You can store here any text of your choice. It is not used by application.
     * 	'validate' is 1 if need to validate with $this->validateField()
     *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
     *
     *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
     */
    // BEGIN MODULEBUILDER PROPERTIES

    /**
     * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
     */
    public $fields = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => "Id"),
        'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 4, 'noteditable' => '1', 'default' => '(PROV)', 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'validate' => '1', 'comment' => "Reference of object"),
        'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'help' => "Help text", 'validate' => '1',),
        'fk_property' => array('type' => 'integer:ImmoProperty:ultimateimmo/class/immoproperty.class.php:1:(status:=:1)', 'label' => 'RealEstate', 'picto' => 'company', 'enabled' => '1', 'position' => 51, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150', 'help' => "linkToProperty", 'validate' => '1',),
        'fk_lessor' => array('type' => 'integer:ImmoOwner:ultimateimmo/class/immoowner.class.php:1:(status:=:1)', 'label' => 'Lessor', 'picto' => 'company', 'enabled' => '1', 'position' => 51, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150', 'help' => "linkToLessor", 'validate' => '1',),
        'fk_tenant' => array('type' => 'integer:ImmoRenter:ultimateimmo/class/immorenter.class.php:1:(status:=:1)', 'label' => 'Tenant', 'picto' => 'company', 'enabled' => '1', 'position' => 52, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150', 'help' => "linkToPreviousCR", 'validate' => '1',),
        'fk_previous' => array('type' => 'integer:Conditionreport:conditionreport/class/conditionreport.class.php:1:(status:in:1,2,3)', 'label' => 'PreviousCR', 'picto' => 'company', 'enabled' => '1', 'position' => 52, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150', 'help' => "linkToTenant", 'validate' => '1',),
        'description' => array('type' => 'html', 'label' => 'Description', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => 3, 'validate' => '1',),
        'direction' => array('type' => 'select', 'label' => 'Direction', 'enabled' => '1', 'position' => 60, 'notnull' => 1, 'visible' => 1, 'arrayofkeyval' => ['0' => 'InputCR', '1' => 'OutputCR', 2 => 'tenantVisit']),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 61, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => '1',),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => '1', 'position' => 62, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => '1',),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 500, 'notnull' => 1, 'visible' => -2,),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => -2,),
        'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => -2, 'csslist' => 'tdoverflowmax150',),
        'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'picto' => 'user', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -2, 'csslist' => 'tdoverflowmax150',),
        'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => '1', 'position' => 600, 'notnull' => 0, 'visible' => 0,),
        'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 1000, 'notnull' => -1, 'visible' => -2,),
        'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => '1', 'position' => 1010, 'notnull' => -1, 'visible' => 0,),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 2000, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array('0' => 'Brouillon', '1' => 'Valid&eacute;', '2' => 'Sign&eacute; par le bailleur', '3' => 'Sign&eacute; par le locataire', '9' => 'Annul&eacute;'), 'validate' => '1',),
        'room_qty' => array('type' => 'integer', 'label' => 'RoomQty', 'enabled' => '1', 'position' => 53, 'notnull' => 1, 'visible' => 1, 'default' => '0', 'help' => "RoomQtyDetails",),
        'date_enter' => array('type' => 'datetime', 'label' => 'DateEnter', 'enabled' => '1', 'position' => 502, 'notnull' => 1, 'visible' => 1, 'help' => "DateEnterDetails",),
        'date_exit' => array('type' => 'datetime', 'label' => 'DateExit', 'enabled' => '1', 'position' => 503, 'notnull' => 0, 'visible' => 1, 'help' => "DateExitDetails",),
        'date_signature_lessor' => array('type' => 'datetime', 'label' => 'DateSignatureLessor', 'enabled' => '1', 'position' => 504, 'notnull' => 0, 'visible' => 1, 'help' => "DateSignatureLessorDetails",),
        'date_signature_tenant' => array('type' => 'datetime', 'label' => 'DateSignatureTenant', 'enabled' => '1', 'position' => 505, 'notnull' => 0, 'visible' => 1, 'help' => "DateSignatureTenantDetails",),
        'type_heater' => array('type' => 'sellist:c_type_heater:label:rowid::(active=1)', 'label' => 'type_heater', 'enabled' => '1', 'position' => 506, 'notnull' => 0, 'visible' => 3, 'help' => "type_heaterDetails",),
        'type_water_heater' => array('type' => 'sellist:c_type_heater:label:rowid::(active=1)', 'label' => 'type_water_heater', 'enabled' => '1', 'position' => 507, 'notnull' => 0, 'visible' => 3, 'help' => "type_water_heaterDetails",),
        'type_cooker' => array('type' => 'sellist:c_type_heater:label:rowid::(active=1)', 'label' => 'type_cooker', 'enabled' => '1', 'position' => 508, 'notnull' => 0, 'visible' => 3, 'help' => "type_cookerDetails",),
    );
    public $rowid;
    public $ref;
    public $label;
    public $amount;
    public $qty;
    public $fk_soc;
    public $fk_project;
    public $description;
    public $note_public;
    public $note_private;
    public $date_creation;
    public $tms;
    public $fk_user_creat;
    public $fk_user_modif;
    public $last_main_doc;
    public $import_key;
    public $model_pdf;
    public $status;

    // END MODULEBUILDER PROPERTIES
    // If this object has a subtable with lines

    /**
     * @var string    Name of subtable line
     */
    public $table_element_line = 'conditionreport_conditionreportroom';

    /**
     * @var string    Field with ID of parent key if this object has a parent
     */
    public $fk_element = 'fk_conditionreport';

    /**
     * @var string    Name of subtable class that manage subtable lines
     */
    public $class_element_line = 'Conditionreportline';

    /**
     * @var array	List of child tables. To test if we can delete object.
     */
//    protected $childtables = array('mychildtable' => array('name' => 'Conditionreport', 'fk_element' => 'fk_conditionreport'));

    /**
     * @var array    List of child tables. To know object to delete on cascade.
     *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
     *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
     */
//    protected $childtablesoncascade = array('conditionreport_conditionreportdet');

    /**
     * @var ConditionreportLine[]     Array of subtable lines
     */
    public $lines = array();

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        global $conf, $langs;

        $this->db = $db;

        if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
            $this->fields['rowid']['visible'] = 0;
        }
        if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
            $this->fields['entity']['enabled'] = 0;
        }

        // Example to show how to set values of fields definition dynamically
        /* if ($user->hasRight('conditionreport', 'conditionreport', 'read')) {
          $this->fields['myfield']['visible'] = 1;
          $this->fields['myfield']['noteditable'] = 0;
          } */

        // Unset fields that are disabled
        foreach ($this->fields as $key => $val) {
            if (isset($val['enabled']) && empty($val['enabled'])) {
                unset($this->fields[$key]);
            }
        }

        // Translate some data of arrayofkeyval
        if (is_object($langs)) {
            foreach ($this->fields as $key => $val) {
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    foreach ($val['arrayofkeyval'] as $key2 => $val2) {
                        $this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
                    }
                }
            }
        }
        if (!isModEnabled("ultimateimmo")) {
            $this->fields['fk_property']['type'] = 'integer:Product:product/class/product.class.php:1:(tosell:=:1)';
            $this->fields['fk_lessor']['type']   = 'integer:Societe:societe/class/societe.class.php:1:(status:=:1)';
            $this->fields['fk_tenant']['type']   = 'integer:Societe:societe/class/societe.class.php:1:(status:=:1)';
        }
    }

    /**
     * Create object into database
     *
     * @param  User $user      User that creates
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             Return integer <0 if KO, Id of created object if OK
     */
    public function create(User $user, $notrigger = false)
    {
        $resultcreate = $this->createCommon($user, $notrigger);
        if ($resultcreate > 0 && $this->room_qty > 0) {
            $json  = file_get_contents(dol_buildpath('/conditionreport/lodgment_model/model.json'));
            $model = json_decode($json, true);
            $rooms = (int) $this->room_qty;
            if ($rooms > 8)
                $rooms = 8;
            if (array_key_exists($rooms, $model)) {
                foreach ($model[$rooms] as $roomModel) {
                    $this->loadModel($user, $roomModel);
                }
            }
            if ($this->direction == '2') {
                $this->loadModel($user, '20securite.json');
            }
        }
        //$resultvalidate = $this->validate($user, $notrigger);

        return $resultcreate;
    }

    /**
     * Clone an object into another one
     *
     * @param  	User 	$user      	User that creates
     * @param  	int 	$fromid     Id of object to clone
     * @return 	mixed 				New object created, <0 if KO
     */
    public function createFromClone(User $user, $fromid)
    {
        global $langs, $extrafields;
        $error = 0;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $object = new self($this->db);

        $this->db->begin();

        // Load source object
        $result = $object->fetchCommon($fromid);
        if ($result > 0 && !empty($object->table_element_line)) {
            $object->fetchLines();
            foreach ($object->lines as &$line) {
                $line->ref    = "ROOM" . uniqid();
                $line->status = Conditionreportroom::STATUS_DRAFT;
            }
        }

        // get lines so they will be clone
        //foreach($this->lines as $line)
        //	$line->fetch_optionals();
        // Reset some properties
        unset($object->id);
        unset($object->fk_user_creat);
        unset($object->import_key);
        unset($object->date_signature_lessor);
        unset($object->date_signature_tenant);

        // Clear fields
        if (property_exists($object, 'ref')) {
            $object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_" . $object->ref : $this->fields['ref']['default'];
        }
        if (property_exists($object, 'label')) {
            $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
        }
        if (property_exists($object, 'status')) {
            $object->status = self::STATUS_DRAFT;
        }
        if (property_exists($object, 'date_creation')) {
            $object->date_creation = dol_now();
        }
        if (property_exists($object, 'date_modification')) {
            $object->date_modification = null;
        }
        // ...
        // Clear extrafields that are unique
        if (is_array($object->array_options) && count($object->array_options) > 0) {
            $extrafields->fetch_name_optionals_label($this->table_element);
            foreach ($object->array_options as $key => $option) {
                $shortkey = preg_replace('/options_/', '', $key);
                if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
                    //var_dump($key);
                    //var_dump($clonedObj->array_options[$key]); exit;
                    unset($object->array_options[$key]);
                }
            }
        }
        // set previous 
        $object->fk_previous                = $this->id;
        // Create clone
        $object->context['createfromclone'] = 'createfromclone';
        $result                             = $object->createCommon($user);
        if ($result < 0) {
            $error++;
            $this->setErrorsFromObject($object);
        }

        if (!$error) {
            // copy internal contacts
            if ($this->copy_linked_contact($object, 'internal') < 0) {
                $error++;
            }
        }

        if (!$error) {
            // copy external contacts if same company
            if (!empty($object->socid) && property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
                if ($this->copy_linked_contact($object, 'external') < 0) {
                    $error++;
                }
            }
        }

        unset($object->context['createfromclone']);

        // End
        if (!$error) {
            $this->db->commit();
            return $object;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param 	int    	$id   			Id object
     * @param 	string 	$ref  			Ref
     * @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
     * @param	int		$nolines		0=Default to load extrafields, 1=No extrafields
     * @return 	int     				Return integer <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
    {
        $result = $this->fetchCommon($id, $ref, '', $noextrafields);
        if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
            $this->fetchLines($noextrafields);
        }
        return $result;
    }

    /**
     * Load object lines in memory from the database
     *
     * @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
     * @return 	int         			Return integer <0 if KO, 0 if not found, >0 if OK
     */
    public function fetchLines($noextrafields = 0)
    {
        $this->lines = array();

        $result = $this->fetchLinesCommon('', $noextrafields);
        foreach ($this->lines as $key => $value) {
            $tmpCRR            = new Conditionreportroom($this->db);
            if ($tmpCRR->fetch($value->id) > 0)
                $this->lines[$key] = $tmpCRR;
        }
        return $result;
    }

    /**
     * Load list of objects in memory from the database. Using a fetchAll is a bad practice, instead try to forge you optimized and limited SQL request.
     *
     * @param  string      $sortorder    Sort Order
     * @param  string      $sortfield    Sort field
     * @param  int         $limit        limit
     * @param  int         $offset       Offset
     * @param  array       $filter       Filter array. Example array('mystringfield'=>'value', 'myintfield'=>4, 'customsql'=>...)
     * @param  string      $filtermode   Filter mode (AND or OR)
     * @return array|int                 int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = "SELECT ";
        $sql .= $this->getFieldList('t');
        $sql .= " FROM " . $this->db->prefix() . $this->table_element . " as t";
        if (isset($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
            $sql .= " LEFT JOIN " . $this->db->prefix() . $this->table_element . "_extrafields as te ON te.fk_object = t.rowid";
        }
        if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
            $sql .= " WHERE t.entity IN (" . getEntity($this->element) . ")";
        } else {
            $sql .= " WHERE 1 = 1";
        }
        // Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                $columnName = preg_replace('/^t\./', '', $key);
                if ($key === 'customsql') {
                    // Never use 'customsql' with a value from user input since it is injected as is. The value must be hard coded.
                    $sqlwhere[] = $value;
                    continue;
                } elseif (isset($this->fields[$columnName])) {
                    $type = $this->fields[$columnName]['type'];
                    if (preg_match('/^integer/', $type)) {
                        if (is_int($value)) {
                            // single value
                            $sqlwhere[] = $key . " = " . intval($value);
                        } elseif (is_array($value)) {
                            if (empty($value)) {
                                continue;
                            }
                            $sqlwhere[] = $key . ' IN (' . $this->db->sanitize(implode(',', array_map('intval', $value))) . ')';
                        }
                        continue;
                    } elseif (in_array($type, array('date', 'datetime', 'timestamp'))) {
                        $sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
                        continue;
                    }
                }

                // when the $key doesn't fall into the previously handled categories, we do as if the column were a varchar/text
                if (is_array($value) && count($value)) {
                    $value = implode(',', array_map(function ($v) {
                            return "'" . $this->db->sanitize($this->db->escape($v)) . "'";
                        }, $value));
                    $sqlwhere[] = $key . ' IN (' . $this->db->sanitize($value, true) . ')';
                } elseif (is_scalar($value)) {
                    if (strpos($value, '%') === false) {
                        $sqlwhere[] = $key . " = '" . $this->db->sanitize($this->db->escape($value)) . "'";
                    } else {
                        $sqlwhere[] = $key . " LIKE '%" . $this->db->escape($this->db->escapeforlike($value)) . "%'";
                    }
                }
            }
        }
        if (count($sqlwhere) > 0) {
            $sql .= " AND (" . implode(" " . $filtermode . " ", $sqlwhere) . ")";
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= $this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i   = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new self($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }

    /**
     * Update object into database
     *
     * @param  User $user      User that modifies
     * @param  bool $notrigger false=launch triggers after, true=disable triggers
     * @return int             Return integer <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = false)
    {
        return $this->updateCommon($user, $notrigger);
    }

    /**
     * Delete object in database
     *
     * @param User $user       User that deletes
     * @param bool $notrigger  false=launch triggers, true=disable triggers
     * @return int             Return integer <0 if KO, >0 if OK
     */
    public function delete(User $user, $notrigger = false)
    {
        foreach ($this->lines as $key => $line) {
            $line->delete($user, $notrigger);
        }

        return $this->deleteCommon($user, $notrigger);
        //return $this->deleteCommon($user, $notrigger, 1);
    }

    /**
     *  Delete a line of object in database
     *
     * 	@param  User	$user       User that delete
     *  @param	int		$idline		Id of line to delete
     *  @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
     *  @return int         		>0 if OK, <0 if KO
     */
    public function deleteLine(User $user, $idline, $notrigger = false)
    {
        if ($this->status < 0) {
            $this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
            return -2;
        }

        return $this->deleteLineCommon($user, $idline, $notrigger);
    }

    /**
     * 	Validate object
     *
     * 	@param		User	$user     		User making status change
     *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
     * 	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
     */
    public function validate($user, $notrigger = 0)
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $error = 0;

        // Protection
        if ($this->status == self::STATUS_VALIDATED) {
            dol_syslog(get_class($this) . "::validate action abandonned: already validated", LOG_WARNING);
            return 0;
        }

        /* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport', 'conditionreport', 'write'))
          || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport', 'conditionreport_advance', 'validate')))
          {
          $this->error='NotEnoughPermissions';
          dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
          return -1;
          } */

        $now = dol_now();

        $this->db->begin();

        // Define new ref
        if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
            $num = $this->getNextNumRef();
        } else {
            $num = $this->ref;
        }
        $this->newref = $num;

        if (!empty($num)) {
            // Validate
            $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
            $sql .= " SET ref = '" . $this->db->escape($num) . "',";
            $sql .= " status = " . self::STATUS_VALIDATED;
            if (!empty($this->fields['date_validation'])) {
                $sql .= ", date_validation = '" . $this->db->idate($now) . "'";
            }
            if (!empty($this->fields['fk_user_valid'])) {
                $sql .= ", fk_user_valid = " . ((int) $user->id);
            }
            $sql .= " WHERE rowid = " . ((int) $this->id);

            dol_syslog(get_class($this) . "::validate()", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (!$resql) {
                dol_print_error($this->db);
                $this->error = $this->db->lasterror();
                $error++;
            }

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('CONDITIONREPORT_VALIDATE', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }

        if (!$error) {
            $this->oldref = $this->ref;

            // Rename directory if dir was a temporary ref
            if (preg_match('/^[\(]?PROV/i', $this->ref)) {
                // Now we rename also files into index
                $sql   = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'conditionreport/" . $this->db->escape($this->newref) . "'";
                $sql   .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'conditionreport/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++;
                    $this->error = $this->db->lasterror();
                }
                $sql   = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filepath = 'conditionreport/" . $this->db->escape($this->newref) . "'";
                $sql   .= " WHERE filepath = 'conditionreport/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
                $resql = $this->db->query($sql);
                if (!$resql) {
                    $error++;
                    $this->error = $this->db->lasterror();
                }

                // We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
                $oldref    = dol_sanitizeFileName($this->ref);
                $newref    = dol_sanitizeFileName($num);
                $dirsource = $conf->conditionreport->dir_output . '/conditionreport/' . $oldref;
                $dirdest   = $conf->conditionreport->dir_output . '/conditionreport/' . $newref;
                if (!$error && file_exists($dirsource)) {
                    dol_syslog(get_class($this) . "::validate() rename dir " . $dirsource . " into " . $dirdest);

                    if (@rename($dirsource, $dirdest)) {
                        dol_syslog("Rename ok");
                        // Rename docs starting with $oldref with $newref
                        $listoffiles = dol_dir_list($conf->conditionreport->dir_output . '/conditionreport/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
                        foreach ($listoffiles as $fileentry) {
                            $dirsource = $fileentry['name'];
                            $dirdest   = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
                            $dirsource = $fileentry['path'] . '/' . $dirsource;
                            $dirdest   = $fileentry['path'] . '/' . $dirdest;
                            @rename($dirsource, $dirdest);
                        }
                    }
                }
            }
        }

        // Set new ref and current status
        if (!$error) {
            $this->ref    = $num;
            $this->status = self::STATUS_VALIDATED;
            foreach ($this->lines as $line) {
                $line->validate($user, $notrigger);
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * 	Sign object by lessor
     *
     * 	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
     */
    public function setSignedLessor()
    {
        global $conf, $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $error = 0;

        // Protection
        if ($this->status != self::STATUS_VALIDATED) {
            dol_syslog(get_class($this) . "::sign lessor action abandonned", LOG_WARNING);
            return 0;
        }

        $now = dol_now();

        $this->db->begin();

        // Validate
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " SET date_signature_lessor = '" . $this->db->idate($now) . "',";
        $sql .= " status = " . self::STATUS_SIGNED_LESSOR;
//            if (!empty($this->fields['date_validation'])) {
//                $sql .= ", date_validation = '" . $this->db->idate($now) . "'";
//            }
//            if (!empty($this->fields['fk_user_valid'])) {
//                $sql .= ", fk_user_valid = " . ((int) $user->id);
//            }
        $sql .= " WHERE rowid = " . ((int) $this->id);

        dol_syslog(get_class($this) . "::signed lessor()", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_print_error($this->db);
            $this->error = $this->db->lasterror();
            $error++;
        }

        // Set new ref and current status
        if (!$error) {
            $this->status = self::STATUS_SIGNED_LESSOR;
        }

        if (!$error) {
            $this->db->commit();
            $this->call_trigger('CONDITIONREPORT_SIGNED_LESSOR', $user);
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * 	Sign object by tenant
     *
     * 	@return  	int						Return integer <=0 if OK, 0=Nothing done, >0 if KO
     */
    public function setSignedTenant()
    {
        global $conf, $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $error = 0;

        // Protection
        if ($this->status != self::STATUS_SIGNED_LESSOR) {
            dol_syslog(get_class($this) . "::sign tenant action abandonned", LOG_WARNING);
            return 0;
        }

        $now = dol_now();

        $this->db->begin();

        // Validate
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " SET date_signature_tenant = '" . $this->db->idate($now) . "',";
        $sql .= " status = " . self::STATUS_SIGNED_TENANT;
//            if (!empty($this->fields['date_validation'])) {
//                $sql .= ", date_validation = '" . $this->db->idate($now) . "'";
//            }
//            if (!empty($this->fields['fk_user_valid'])) {
//                $sql .= ", fk_user_valid = " . ((int) $user->id);
//            }
        $sql .= " WHERE rowid = " . ((int) $this->id);

        dol_syslog(get_class($this) . "::signed tenant()", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql) {
            dol_print_error($this->db);
            $this->error = $this->db->lasterror();
            $error++;
        }

        // Set new ref and current status
        if (!$error) {
            $this->status = self::STATUS_SIGNED_TENANT;
        }

        if (!$error) {
            $this->db->commit();
            $this->call_trigger('CONDITIONREPORT_SIGNED_TENANT', $user);
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * 	Set draft status
     *
     * 	@param	User	$user			Object user that modify
     *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     * 	@return	int						Return integer <0 if KO, >0 if OK
     */
    public function setDraft($user, $notrigger = 0)
    {
        // Protection
        if ($this->status <= self::STATUS_DRAFT) {
            return 0;
        }

        /* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport','write'))
          || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport','conditionreport_advance','validate'))))
          {
          $this->error='Permission denied';
          return -1;
          } */

        return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'CONDITIONREPORT_CONDITIONREPORT_UNVALIDATE');
    }

    /**
     * 	Set cancel status
     *
     * 	@param	User	$user			Object user that modify
     *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     * 	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
     */
    public function cancel($user, $notrigger = 0)
    {
        // Protection
        if ($this->status != self::STATUS_VALIDATED) {
            return 0;
        }

        /* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport','write'))
          || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport','conditionreport_advance','validate'))))
          {
          $this->error='Permission denied';
          return -1;
          } */

        return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'CONDITIONREPORT_CONDITIONREPORT_CANCEL');
    }

    /**
     * 	Set back to validated status
     *
     * 	@param	User	$user			Object user that modify
     *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
     * 	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
     */
    public function reopen($user, $notrigger = 0)
    {
        // Protection
        if ($this->status == self::STATUS_VALIDATED) {
            return 0;
        }

        /* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport','write'))
          || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('conditionreport','conditionreport_advance','validate'))))
          {
          $this->error='Permission denied';
          return -1;
          } */

        return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'CONDITIONREPORT_CONDITIONREPORT_REOPEN');
    }

    /**
     * getTooltipContentArray
     *
     * @param 	array 	$params 	Params to construct tooltip data
     * @since 	v18
     * @return 	array
     */
    public function getTooltipContentArray($params)
    {
        global $langs;

        $datas = [];

        if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
            return ['optimize' => $langs->trans("ShowConditionreport")];
        }
        $datas['picto'] = img_picto('', $this->picto) . ' <u>' . $langs->trans("Conditionreport") . '</u>';
        if (isset($this->status)) {
            $datas['picto'] .= ' ' . $this->getLibStatut(5);
        }
        if (property_exists($this, 'ref')) {
            $datas['ref'] = '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        }
        if (property_exists($this, 'label')) {
            $datas['ref'] = '<br>' . $langs->trans('Label') . ':</b> ' . $this->label;
        }

        return $datas;
    }

    /**
     *  Return a link to the object card (with optionaly the picto)
     *
     *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
     *  @param  string  $option                     On what the link point to ('nolink', ...)
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  string  $morecss                    Add more css on link
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @return	string                              String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs, $hookmanager;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result          = '';
        $params          = [
            'id' => $this->id,
            'objecttype' => $this->element . ($this->module ? '@' . $this->module : ''),
            'option' => $option,
        ];
        $classfortooltip = 'classfortooltip';
        $dataparams      = '';
        if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
            $classfortooltip = 'classforajaxtooltip';
            $dataparams      = ' data-params="' . dol_escape_htmltag(json_encode($params)) . '"';
            $label           = '';
        } else {
            $label = implode($this->getTooltipContentArray($params));
        }

        $url = dol_buildpath('/conditionreport/conditionreport_card.php', 1) . '?id=' . $this->id;

        if ($option !== 'nolink') {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
                $add_save_lastsearch_values = 1;
            }
            if ($url && $add_save_lastsearch_values) {
                $url .= '&save_lastsearch_values=1';
            }
        }

        $linkclose = '';
        if (empty($notooltip)) {
            if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
                $label     = $langs->trans("ShowConditionreport");
                $linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
            }
            $linkclose .= ($label ? ' title="' . dol_escape_htmltag($label, 1) . '"' : ' title="tocomplete"');
            $linkclose .= $dataparams . ' class="' . $classfortooltip . ($morecss ? ' ' . $morecss : '') . '"';
        } else {
            $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
        }

        if ($option == 'nolink' || empty($url)) {
            $linkstart = '<span';
        } else {
            $linkstart = '<a href="' . $url . '"';
        }
        $linkstart .= $linkclose . '>';
        if ($option == 'nolink' || empty($url)) {
            $linkend = '</span>';
        } else {
            $linkend = '</a>';
        }

        $result .= $linkstart;

        if (empty($this->showphoto_on_popup)) {
            if ($withpicto) {
                $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
            }
        } else {
            if ($withpicto) {
                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

                list($class, $module) = explode('@', $this->picto);
                $upload_dir = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
                $filearray  = dol_dir_list($upload_dir, "files");
                $filename   = $filearray[0]['name'];
                if (!empty($filename)) {
                    $pospoint = strpos($filearray[0]['name'], '.');

                    $pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
                    if (!getDolGlobalString(strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS')) {
                        $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
                    } else {
                        $result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
                    }

                    $result .= '</div>';
                } else {
                    $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . '"'), 0, 0, $notooltip ? 0 : 1);
                }
            }
        }

        if ($withpicto != 2) {
            $result .= $this->ref;
        }

        $result .= $linkend;
        //if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action, $hookmanager;
        $hookmanager->initHooks(array($this->element . 'dao'));
        $parameters = array('id' => $this->id, 'getnomurl' => &$result);
        $reshook    = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) {
            $result = $hookmanager->resPrint;
        } else {
            $result .= $hookmanager->resPrint;
        }

        return $result;
    }

    /**
     * 	Return a thumb for kanban views
     *
     * 	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
     *  @param		array		$arraydata				Array of data
     *  @return		string								HTML Code for Kanban thumb.
     */
    public function getKanbanView($option = '', $arraydata = null)
    {
        global $conf, $langs;

        $selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

        $return = '<div class="box-flex-item box-flex-grow-zero">';
        $return .= '<div class="info-box info-box-sm">';
        $return .= '<span class="info-box-icon bg-infobox-action">';
        $return .= img_picto('', $this->picto);
        $return .= '</span>';
        $return .= '<div class="info-box-content">';
        $return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">' . (method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref) . '</span>';
        if ($selected >= 0) {
            $return .= '<input id="cb' . $this->id . '" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="' . $this->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
        }
        if (property_exists($this, 'label')) {
            $return .= ' <div class="inline-block opacitymedium valignmiddle tdoverflowmax100">' . $this->label . '</div>';
        }
        if (property_exists($this, 'thirdparty') && is_object($this->thirdparty)) {
            $return .= '<br><div class="info-box-ref tdoverflowmax150">' . $this->thirdparty->getNomUrl(1) . '</div>';
        }
        if (property_exists($this, 'amount')) {
            $return .= '<br>';
            $return .= '<span class="info-box-label amount">' . price($this->amount, 0, $langs, 1, -1, -1, $conf->currency) . '</span>';
        }
        if (method_exists($this, 'getLibStatut')) {
            $return .= '<br><div class="info-box-status">' . $this->getLibStatut(3) . '</div>';
        }
        $return .= '</div>';
        $return .= '</div>';
        $return .= '</div>';

        return $return;
    }

    /**
     *  Return the label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLabelStatus($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    /**
     *  Return the label of the status
     *
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return	string 			       Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

    /**
     *  Return the label of a given status
     *
     *  @param	int		$status        Id status
     *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
     *  @return string 			       Label of status
     */
    public function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        if (is_null($status)) {
            return '';
        }

        if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
            global $langs;
            //$langs->load("conditionreport@conditionreport");
            $this->labelStatus[self::STATUS_DRAFT]         = $langs->transnoentitiesnoconv('Draft');
            $this->labelStatus[self::STATUS_VALIDATED]     = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatus[self::STATUS_SIGNED_LESSOR] = $langs->transnoentitiesnoconv('SignedLessor');
            $this->labelStatus[self::STATUS_SIGNED_TENANT] = $langs->transnoentitiesnoconv('SignedTenant');
            $this->labelStatus[self::STATUS_CANCELED]      = $langs->transnoentitiesnoconv('Disabled');

            $this->labelStatusShort[self::STATUS_DRAFT]         = $langs->transnoentitiesnoconv('Draft');
            $this->labelStatusShort[self::STATUS_VALIDATED]     = $langs->transnoentitiesnoconv('Enabled');
            $this->labelStatusShort[self::STATUS_SIGNED_LESSOR] = $langs->transnoentitiesnoconv('SignedLessor');
            $this->labelStatusShort[self::STATUS_SIGNED_TENANT] = $langs->transnoentitiesnoconv('SignedTenant');
            $this->labelStatusShort[self::STATUS_CANCELED]      = $langs->transnoentitiesnoconv('Disabled');
        }

        $statusType = 'status' . $status;
        //if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
        if ($status == self::STATUS_CANCELED) {
            $statusType = 'status6';
        }

        return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
    }

    /**
     * 	Load the info information in the object
     *
     * 	@param  int		$id       Id of object
     * 	@return	void
     */
    public function info($id)
    {
        $sql = "SELECT rowid,";
        $sql .= " date_creation as datec, tms as datem";
        if (!empty($this->fields['date_validation'])) {
            $sql .= ", date_validation as datev";
        }
        if (!empty($this->fields['fk_user_creat'])) {
            $sql .= ", fk_user_creat";
        }
        if (!empty($this->fields['fk_user_modif'])) {
            $sql .= ", fk_user_modif";
        }
        if (!empty($this->fields['fk_user_valid'])) {
            $sql .= ", fk_user_valid";
        }
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
        $sql .= " WHERE t.rowid = " . ((int) $id);

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);

                $this->id = $obj->rowid;

                if (!empty($this->fields['fk_user_creat'])) {
                    $this->user_creation_id = $obj->fk_user_creat;
                }
                if (!empty($this->fields['fk_user_modif'])) {
                    $this->user_modification_id = $obj->fk_user_modif;
                }
                if (!empty($this->fields['fk_user_valid'])) {
                    $this->user_validation_id = $obj->fk_user_valid;
                }
                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
                if (!empty($obj->datev)) {
                    $this->date_validation = empty($obj->datev) ? '' : $this->db->jdate($obj->datev);
                }
            }

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     * Initialise object with example values
     * Id must be 0 if object instance is a specimen
     *
     * @return void
     */
    public function initAsSpecimen()
    {
        // Set here init that are not commonf fields
        // $this->property1 = ...
        // $this->property2 = ...

        $this->initAsSpecimenCommon();
    }

    /**
     * 	Create an array of lines
     *
     * 	@return array|int		array of lines if OK, <0 if KO
     */
    public function getLinesArray()
    {
        $this->lines = array();

        $objectline = new ConditionreportLine($this->db);
        $result     = $objectline->fetchAll('ASC', 'rang', 0, 0, array('customsql' => 'fk_conditionreport = ' . ((int) $this->id)));

        if (is_numeric($result)) {
            $this->setErrorsFromObject($objectline);
            return $result;
        } else {
            $this->lines = $result;
            foreach ($this->lines as $key => $value) {
                $tmpCRR            = new Conditionreportroom($this->db);
                if ($tmpCRR->fetch($value->id) > 0)
                    $this->lines[$key] = $tmpCRR;
            }
            return $this->lines;
        }
    }

    /**
     *  Returns the reference to the following non used object depending on the active numbering module.
     *
     *  @return string      		Object free reference
     */
    public function getNextNumRef()
    {
        global $langs, $conf;
        $langs->load("conditionreport@conditionreport");

        if (!getDolGlobalString('CONDITIONREPORT_CONDITIONREPORT_ADDON')) {
            $conf->global->CONDITIONREPORT_CONDITIONREPORT_ADDON = 'mod_conditionreport_standard';
        }

        if (getDolGlobalString('CONDITIONREPORT_CONDITIONREPORT_ADDON')) {
            $mybool = false;

            $file      = getDolGlobalString('CONDITIONREPORT_CONDITIONREPORT_ADDON') . ".php";
            $classname = getDolGlobalString('CONDITIONREPORT_CONDITIONREPORT_ADDON');

            // Include file with class
            $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
            foreach ($dirmodels as $reldir) {
                $dir = dol_buildpath($reldir . "core/modules/conditionreport/");

                // Load file with numbering class (if found)
                $mybool |= @include_once $dir . $file;
            }

            if ($mybool === false) {
                dol_print_error('', "Failed to include file " . $file);
                return '';
            }

            if (class_exists($classname)) {
                $obj    = new $classname();
                $numref = $obj->getNextValue($this);

                if ($numref != '' && $numref != '-1') {
                    return $numref;
                } else {
                    $this->error = $obj->error;
                    //dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
                    return "";
                }
            } else {
                print $langs->trans("Error") . " " . $langs->trans("ClassNotFound") . ' ' . $classname;
                return "";
            }
        } else {
            print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
            return "";
        }
    }

    /**
     *  Create a document onto disk according to template module.
     *
     *  @param	    string		$modele			Force template to use ('' to not force)
     *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
     *  @param      int			$hidedetails    Hide details of lines
     *  @param      int			$hidedesc       Hide description
     *  @param      int			$hideref        Hide ref
     *  @param      null|array  $moreparams     Array to provide more information
     *  @return     int         				0 if KO, 1 if OK
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
    {
        global $conf, $langs;

        $result               = 0;
        $includedocgeneration = 1;

        $langs->load("conditionreport@conditionreport");

        if (!dol_strlen($modele)) {
            $modele = 'standard_conditionreport';

            if (!empty($this->model_pdf)) {
                $modele = $this->model_pdf;
            } elseif (getDolGlobalString('CONDITIONREPORT_ADDON_PDF')) {
                $modele = getDolGlobalString('CONDITIONREPORT_ADDON_PDF');
            }
        }

        $modelpath = "core/modules/conditionreport/doc/";

        if ($includedocgeneration && !empty($modele)) {
            $result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
        }

        return $result;
    }

    /**
     * Action executed by scheduler
     * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
     * Use public function doScheduledJob($param1, $param2, ...) to get parameters
     *
     * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
     */
    public function doScheduledJob()
    {
        //global $conf, $langs;
        //$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlogfile.log';

        $error        = 0;
        $this->output = '';
        $this->error  = '';

        dol_syslog(__METHOD__ . " start", LOG_INFO);

        $now = dol_now();

        $this->db->begin();

        // ...

        $this->db->commit();

        dol_syslog(__METHOD__ . " end", LOG_INFO);

        return $error;
    }

    public function setErrorsFromObject($object)
    {
        if (!empty($object->error)) {
            $this->error = $object->error;
        }
        if (!empty($object->errors)) {
            $this->errors = array_merge($this->errors, $object->errors);
        }
    }
    // --------------------
    // TODO: All functions here must be redesigned and moved as they are not business functions but output functions
    // --------------------

    /* This is to show add lines */

    /**
     * 	Show add free and predefined products/services form
     *
     *  @param	int		        $dateSelector       1=Show also date range input fields
     *  @param	Societe			$seller				Object thirdparty who sell
     *  @param	Societe			$buyer				Object thirdparty who buy
     *  @param	string			$defaulttpldir		Directory where to find the template
     * 	@return	void
     */
    public function formAddObjectLine($dateSelector, $seller, $buyer, $defaulttpldir = '/core/tpl')
    {
        global $conf, $user, $langs, $object, $hookmanager, $extrafields, $form;

        // Line extrafield
        if (!is_object($extrafields)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
        }
        $extrafields->fetch_name_optionals_label($this->table_element_line);
        $tpl = dol_buildpath('/conditionreport/tplCR/objectline_create.tpl.php');
        if (empty($conf->file->strict_mode)) {
            $res = @include $tpl;
        } else {
            $res = include $tpl; // for debug
        }
    }

    /**
     * 	Load a Room from model
     *
     *  @param		User            $user               the user
     *  @param		string			$model				the model file name     *  @return     int             					>0 if OK, <0 if KO
     *
     */
    function loadModel($user, $model)
    {

        $filename = dol_buildpath('/conditionreport/room_models/fr/') . $model;
        if (file_exists($filename)) {
            try {
                $crr    = new Conditionreportroom($this->db);
                $model  = json_decode(file_get_contents($filename));
                $result = 0;
                if (isset($model->name)) {
                    $crr->ref                = "ROOM" . uniqid();
                    $crr->label              = $model->name;
                    $crr->fk_conditionreport = $this->id;
                    $result                  = $crr->create($user);
                    if ($result > 0 && isset($model->elements)) {
                        $crr->fetch($result);
                        foreach ($model->elements as $value) {
                            $crr->line                         = new ConditionreportroomLine($this->db);
                            $crr->line->fk_conditionreportroom = $crr->id;
                            $crr->line->label                  = $value;
                            $crr->line->qty                    = 1;
                            $crr->line->condition              = 3;
                            $result                            = $crr->line->insert($user);
                        }
                    }
                }
                return $result;
            } catch (Exception $exc) {
                return -1;
            }
        } else {
            return -2;
        }
    }

    /**
     * 	Add an order line into database (linked to product/service or not)
     *
     *  @param		string			$label				Label
     * 	@param      string			$desc            	Description of line
     *  @param		array			$array_options		extrafields array. Example array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
     *  @return     int             					>0 if OK, <0 if KO
     *
     * 	@see        add_product()
     *
     * 	Les parametres sont deja cense etre juste et avec valeurs finales a l'appel
     * 	de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
     * 	par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,produit)
     * 	et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
     */
    public function addline($label, $desc, $array_options = [])
    {
        global $mysoc, $conf, $langs, $user;

        $logtext = "::addline id=$this->id, label=$label, desc=$desc";
        dol_syslog(get_class($this) . $logtext, LOG_DEBUG);

        if ($this->statut == self::STATUS_DRAFT) {

            // Clean parameters

            $label = trim($label);
            $desc  = trim($desc);

            $this->db->begin();

            // Insert line
            $this->line = new ConditionreportLine($this->db);

            $this->line->ref                = "ROOM" . uniqid();
            $this->line->fk_conditionreport = $this->id;
            $this->line->label              = $label;
            $this->line->description        = $desc;

            if (is_array($array_options) && count($array_options) > 0) {
                $this->line->array_options = $array_options;
            }

            $result = $this->line->insert($user);

            if ($result > 0) {
                $this->db->commit();
                return $this->line->id;
            } else {
                $this->error = $this->line->error;
                dol_syslog(get_class($this) . "::addline error=" . $this->error, LOG_ERR);
                $this->db->rollback();
                return -1;
            }
        } else {
            dol_syslog(get_class($this) . "::addline status of condtionreportroom must be Draft to allow use of ->addline()", LOG_ERR);
            return -2;
        }
    }

    function sendSMS()
    {
        global $langs,$user;
        $account  = getDolGlobalString('CONDITIONREPORT_OVH_ACCOUNT');
        $login    = getDolGlobalString('CONDITIONREPORT_OVH_SMS_LOGIN');
        $password = getDolGlobalString('CONDITIONREPORT_OVH_SMS_PASSWORD');
        $from     = getDolGlobalString('CONDITIONREPORT_OVH_SMS_SENDER');
        $link     = $this->getOnlineSignatureUrl(0, 'conditionreport', $this->ref, 1, $this);
        $message  = $langs->transnoentitiesnoconv('SMSmessage', $link);

        if ($account && $this->fk_tenant) {
            $tenant = new Societe($this->db);
            if ($tenant->fetch($this->fk_tenant) && $tenant->phone) {
                $url = $this->generateSMSURL($account, $login, $password, $from, $tenant->phone, $message);
                $ret = file_get_contents($url);
                if (strpos($ret, 'OK') === 0) {
                    $this->call_trigger('CONDITIONREPORT_SENTBYSMS', $user);
                    setEventMessage($langs->trans('SMSsuccess',$tenant->phone));
                    return 1;
                } 
            }
        }
        setEventMessage($langs->trans('SMSerror',$tenant->phone), 'errors');
        return 0;
    }

    /**
     * Fonction pour générer une URL pour l'envoi de SMS OVH avec les paramètres spécifiés.
     *
     * @param string $account Le compte SMS.
     * @param string $login Le nom d'utilisateur.
     * @param string $password Le mot de passe.
     * @param string $from Le numéro ou le nom de l'expéditeur.
     * @param string $phone Le numéro de téléphone du destinataire.
     * @param string $message Le contenu du message SMS.
     * @param string $senderForResponse Le paramètre pour indiquer si l'expéditeur est destiné à recevoir une réponse (0 ou 1).
     * @return string L'URL complète pour l'envoi de SMS.
     */
    function generateSMSURL($account, $login, $password, $from, $phone, $message, $senderForResponse = 1)
    {
        // URL de base pour l'envoi de SMS
        $baseURL = 'https://www.ovh.com/cgi-bin/sms/http2sms.cgi';

        // Vérifier si le numéro commence par "+33"
        if (substr($phone, 0, 3) === '+33') {
            // Remplacer "+33" par "0033"
            $phone = '00' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) === '06' || substr($phone, 0, 2) === '07') {
            // Convertir "06" ou "07" en "0033" suivi des 8 chiffres restants
            $phone = '0033' . substr($phone, 1);
        }

        // Construire les paramètres de l'URL
        $params = array(
            'account' => $account,
            'login' => $login,
            'password' => $password,
            'from' => $from,
            'senderForResponse' => $senderForResponse,
            'to' => $phone,
            'message' => $message
        );

        // Générer l'URL complète en combinant la base URL et les paramètres
        $url = $baseURL . '?' . http_build_query($params);

        // Retourner l'URL générée
        return $url;
    }

    /**
     * Return string with full online Url to accept and sign a quote
     *
     * @param   string			$type		Type of URL ('proposal', ...)
     * @param	string			$ref		Ref of object
     * @param   Object  		$obj  		object (needed to make multicompany good links)
     * @return	string						Url string
     */
    function showOnlineSignatureUrl($type, $ref, $obj = null)
    {
        global $conf, $langs;

        // Load translation files required by the page
        $langs->loadLangs(array("payment", "paybox"));

        $servicename = 'Online';

        $out = img_picto('', 'globe') . ' <span class="opacitymedium">' . $langs->trans("ToOfferALinkForOnlineSignature", $servicename) . '</span><br>';
        $url = $this->getOnlineSignatureUrl(0, $type, $ref, 1, $obj);
        $out .= '<div class="urllink">';
        if ($url == $langs->trans("FeatureOnlineSignDisabled")) {
            $out .= $url;
        } else {
            $out .= '<input type="text" id="onlinesignatureurl" class="quatrevingtpercentminusx" value="' . $url . '">';
        }
        $out .= '<a class="" href="' . $url . '" target="_blank" rel="noopener noreferrer">' . img_picto('', 'globe', 'class="paddingleft"') . '</a>';
        $out .= '</div>';
        $out .= ajax_autoselect("onlinesignatureurl", 0);
        return $out;
    }

    /**
     * Return string with full Url
     *
     * @param   int				$mode				0=True url, 1=Url formated with colors
     * @param   string			$type				Type of URL ('proposal', ...)
     * @param	string			$ref				Ref of object
     * @param   string  		$localorexternal  	0=Url for browser, 1=Url for external access
     * @param   Object  		$obj  				object (needed to make multicompany good links)
     * @return	string								Url string
     */
    function getOnlineSignatureUrl($mode, $type, $ref = '', $localorexternal = 1, $obj = null, $signature = 'tenant')
    {
        global $conf, $dolibarr_main_url_root;

        if (empty($obj)) {
            // For compatibility with 15.0 -> 19.0
            global $object;
            if (empty($object)) {
                $obj = new stdClass();
            } else {
                dol_syslog(__FUNCTION__ . " using global object is deprecated, please give obj as argument", LOG_WARNING);
                $obj = $object;
            }
        }

        $ref = str_replace(' ', '', $ref);
        $out = '';

        // Define $urlwithroot
        $urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
        $urlwithroot       = $urlwithouturlroot . DOL_URL_ROOT; // This is to use external domain name found into config file
        //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

        $urltouse = DOL_MAIN_URL_ROOT;
        if ($localorexternal) {
            $urltouse = $urlwithroot;
        }

        $securekeyseed = getDolGlobalString(dol_strtoupper($type) . '_ONLINE_SIGNATURE_SECURITY_TOKEN');
        $out           = dol_buildpath('/conditionreport/onlinesign/newonlinesign.php', 3) . '?source=' . $type . '&ref=' . ($mode ? '<span style="color: #666666">' : '');

        if ($mode == 1) {
            $out .= $type . '_ref';
        }
        if ($mode == 0) {
            $out .= urlencode($ref);
        }
        $out .= ($mode ? '</span>' : '');
        if ($mode == 1) {
            $out .= "hash('" . $securekeyseed . "' + '" . $type . "' + $type + '_ref)";
        } else {
            $out .= '&securekey=' . dol_hash($securekeyseed . $type . $ref . (!isModEnabled('multicompany') ? '' : $object->entity), '0');
        }

        // For multicompany
        if (!empty($out) && isModEnabled('multicompany')) {
            $out .= "&entity=" . (empty($obj->entity) ? '' : (int) $obj->entity); // Check the entity because we may have the same reference in several entities
        }
        if (!empty($signature)) {
            $out .= "&signature=" . urlencode($signature); // Check the entity because we may have the same reference in several entities
        }
        if ($signature == 'tenant')
            $out = $this->shrinkUrl($out);
        return $out;
    }

    /**
     * Shorten URL via our service lru.sh
     *
     * @param   string				$url				0=True url, 1=Url formated with colors
     * @return	string								Url string
     */
    function shrinkUrl($url)
    {
        //
        // A very simple PHP example that sends a url to get short one
        //
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://lru.sh/");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('url' => $url, 'mode' => '')));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); //The number of seconds to wait while trying to connect.
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); //The maximum number of seconds to allow cURL functions to execute.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactive la vérification du certificat SSL
        // Receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output         = curl_exec($ch);
        curl_close($ch);
        $server_output_decoded = json_decode($server_output);
        // Further processing ...
        if (json_last_error() === JSON_ERROR_NONE) { //OK
            // get short url
            return $server_output_decoded->shorturl;
        } else { //KO
            return $url;
        }
    }

    /**
     * Load diff list with fk_previous
     *
     * @param   int				$param				0=True url, 1=Url formated with colors
     * @return	string								Url string
     */
    function getDiffList($param = false)
    {
        $diff     = [];
        $previous = new self($this->db);
        $res      = $previous->fetch($this->fk_previous);
        if (!$res)
            return $diff;
        foreach ($this->lines as $id1 => $crl) {
            // search room in previous
            foreach ($previous->lines as $ij1 => $pline) {
                if ($crl->label == $pline->label) {
                    foreach ($crl->lines as $id2 => $line) {
                        //search element in previous
                        foreach ($pline->lines as $ij2 => $plineLine) {
                            if ($line->label == $plineLine->label) {
                                //same element but qty doen't match
                                if ($line->qty < $plineLine->qty) {
                                    $ret               = new stdClass();
                                    $ret->reason       = 'MissingCRdiff';
                                    $ret->roomlabel    = $crl->label;
                                    $ret->roomid       = $crl->id;
                                    $ret->elementlabel = $line->label;
                                    $ret->qty          = $plineLine->qty - $line->qty;
                                    $ret->description  = $line->description;
                                    $ret->condition    = $line->condition;
                                    $diff[]            = $ret;
                                }
                                //same element but quality doesn't match
                                elseif ($line->condition != $plineLine->condition) {
                                    $ret               = new stdClass();
                                    $ret->reason       = 'DegradedCRdiff';
                                    $ret->roomlabel    = $crl->label;
                                    $ret->roomid       = $crl->id;
                                    $ret->elementlabel = $line->label;
                                    $ret->qty          = $line->qty;
                                    $ret->description  = $line->description;
                                    $ret->condition    = $line->condition;
                                    $diff[]            = $ret;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $diff;
    }

    /**
     * return label of the condition id
     *
     * @param   int				$condition				the condition id
     * @return	string								Url string
     */
    function getLabelCondition($condition)
    {
        global $langs;
        return $langs->trans(Conditionreportroom::getLibCondition($condition));
    }

    /**
     * Do something
     *
     * @param   int				$param				0=True url, 1=Url formated with colors
     * @return	string								Url string
     */
    function createPropal(array $rows)
    {
        return $this->createInvoice($rows, 'Propal');
    }

    /**
     * Do something
     *
     * @param   int				$rows				0=True url, 1=Url formated with colors
     * @return	string								Url string
     */
    function createInvoice(array $rows, $typeObject = 'Facture')
    {
        global $user, $conf;
        $newObject                    = new $typeObject($this->db);
        $newObject->linked_objects    = ['conditionreport' => $this->id];
        $newObject->socid             = $this->fk_tenant;
        $newObject->date              = time();
        $newObject->cond_reglement_id = 1; //ASAP
        $res                          = $newObject->create($user);
//        $newObject->fetch_thirdparty();
        $i                            = 0;
        foreach ($rows as $row) {
            //only selected rows
            if ($row['selected'] == 1) {
                $pu_ht = 0;
                $txtva = 0;
                if (GETPOST('product_id_' . $i)) {
                    $prod = new Product($this->db);
                    if ($prod->fetch(GETPOST('product_id_' . $i))) {
                        $pu_ht = $prod->price;
                        $txtva = $prod->tva_tx;
                    }
                }
                //fixed
                $txlocaltax1             = 0;
                $txlocaltax2             = 0;
                $remise_percent          = 0;
                $date_start              = '';
                $date_end                = '';
                $ventil                  = 0;
                $info_bits               = 0;
                $fk_remise_except        = '';
                $price_base_type         = 'HT';
                $pu_ttc                  = 0;
                $type                    = 0;
                $rang                    = -1;
                $special_code            = 0;
                $origin                  = '';
                $origin_id               = 0;
                $fk_parent_line          = 0;
                $fk_fournprice           = null;
                $pa_ht                   = 0;
                $label                   = $row['label'];
                $array_options           = 0;
                $situation_percent       = 100;
                $fk_prev_id              = 0;
                $fk_unit                 = null;
                $pu_ht_devise            = 0;
                $ref_ext                 = '';
                $noupdateafterinsertline = 0;
                // add invoice line 
                if ($typeObject == 'Facture') {
                    $newObject->addline(
                        $row['description'],
                        $pu_ht,
                        $row['qty'],
                        $txtva,
                        $txlocaltax1,
                        $txlocaltax2,
                        (int) GETPOST('product_id_' . $i),
                        $remise_percent,
                        $date_start,
                        $date_end,
                        $ventil,
                        $info_bits,
                        $fk_remise_except,
                        $price_base_type,
                        $pu_ttc,
                        $type,
                        $rang,
                        $special_code,
                        $origin,
                        $origin_id,
                        $fk_parent_line,
                        $fk_fournprice,
                        $pa_ht,
                        $label,
                        $array_options,
                        $situation_percent,
                        $fk_prev_id,
                        $fk_unit,
                        $pu_ht_devise,
                        $ref_ext,
                        $noupdateafterinsertline
                    );
                }
                // add propal line
                if ($typeObject == 'Propal') {
                    $res = $newObject->addline(
                        $row['description'],
                        $pu_ht,
                        $row['qty'],
                        $txtva,
                        $txlocaltax1,
                        $txlocaltax2,
                        (int) GETPOST('product_id_' . $i),
                        $remise_percent,
                        $price_base_type,
                        $pu_ttc,
                        $info_bits,
                        $type,
                        $rang,
                        $special_code,
                        $fk_parent_line,
                        $fk_fournprice,
                        $pa_ht,
                        $label,
                        $date_start,
                        $date_end,
                        $array_options,
                        $fk_unit,
                        $origin,
                        $origin_id,
                        $pu_ht_devise,
                        $fk_remise_except,
                        $noupdateafterinsertline
                    );
                }
            }
            $i++;
        }
        $res = $newObject->update($user);
        return $newObject->id;
    }
}

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';

/**
 * Class ConditionreportLine. You can also remove this and generate a CRUD class for lines objects.
 */
class ConditionreportLine extends Conditionreportroom
{

    public $table_element = 'conditionreport_conditionreportroom';
    // To complete with content of an object ConditionreportLine
    // We should have a field rowid, fk_conditionreport and position
    public $fields        = array(
        'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => "Id"),
        'fk_conditionreport' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 0, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => "fkId"),
        'ref' => array('type' => 'varchar(255)', 'label' => 'Ref', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'validate' => '1', 'comment' => "Reference of object"),
        'label' => array('type' => 'varchar(255)', 'label' => 'RoomName', 'enabled' => '1', 'position' => 30, 'notnull' => 1, 'visible' => 1, 'alwayseditable' => '1', 'searchall' => 1, 'css' => 'minwidth300', 'cssview' => 'wordbreak', 'help' => "RoomNameDetails", 'showoncombobox' => '2', 'validate' => '1',),
        'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => 3, 'validate' => '1',),
        'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => '1', 'position' => 61, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => '1',),
        'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => '1', 'position' => 62, 'notnull' => 0, 'visible' => 0, 'cssview' => 'wordbreak', 'validate' => '1',),
        'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 500, 'notnull' => 1, 'visible' => -2,),
        'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => -2,),
        'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'picto' => 'user', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => -2, 'csslist' => 'tdoverflowmax150',),
        'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'picto' => 'user', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -2, 'csslist' => 'tdoverflowmax150',),
        'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => '1', 'position' => 600, 'notnull' => 0, 'visible' => 0,),
        'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 1000, 'notnull' => -1, 'visible' => -2,),
        'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => '1', 'position' => 1010, 'notnull' => -1, 'visible' => 0, 'default' => 'standard'),
        'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => '1', 'position' => 2000, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array('0' => 'Brouillon', '1' => 'Valid&eacute;', '9' => 'Annul&eacute;'), 'validate' => '1',),
    );

    /**
     * @var int  Does object support extrafields ? 0=No, 1=Yes
     */
    public $isextrafieldmanaged = 1;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    function insert(User $user, $notrigger = false)
    {
        return parent::create($user, $notrigger);
    }
}
