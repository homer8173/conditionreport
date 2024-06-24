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
 * \file    core/triggers/interface_99_modConditionreport_ConditionreportTriggers.class.php
 * \ingroup conditionreport
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modConditionreport_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */
require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for Conditionreport module
 */
class InterfaceConditionreportTriggers extends DolibarrTriggers
{

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name        = preg_replace('/^Interface/i', '', get_class($this));
        $this->family      = "demo";
        $this->description = "Conditionreport triggers.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version     = 'development';
        $this->picto       = 'conditionreport@conditionreport';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string 		$action 	Event action code
     * @param CommonObject 	$object 	Object
     * @param User 			$user 		Object user
     * @param Translate 	$langs 		Object langs
     * @param Conf 			$conf 		Object conf
     * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (!isModEnabled('conditionreport')) {
            return 0; // If module is not enabled, we do nothing
        }

        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
        // For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
        $callback   = array($this, $methodName);
        if (is_callable($callback)) {
            dol_syslog(
                "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id
            );

            return call_user_func($callback, $action, $object, $user, $langs, $conf);
        }

        // Or you can execute some code here
        switch ($action) {
            // Users
            //case 'USER_CREATE':
            //case 'USER_MODIFY':
            //case 'USER_NEW_PASSWORD':
            //case 'USER_ENABLEDISABLE':
            //case 'USER_DELETE':
            // Actions
            //case 'ACTION_MODIFY':
            //case 'ACTION_CREATE':
            //case 'ACTION_DELETE':
            // Groups
            //case 'USERGROUP_CREATE':
            //case 'USERGROUP_MODIFY':
            //case 'USERGROUP_DELETE':
            // Companies
            //case 'COMPANY_CREATE':
            //case 'COMPANY_MODIFY':
            //case 'COMPANY_DELETE':
            // Contacts
            //case 'CONTACT_CREATE':
            //case 'CONTACT_MODIFY':
            //case 'CONTACT_DELETE':
            //case 'CONTACT_ENABLEDISABLE':
            // Products
            //case 'PRODUCT_CREATE':
            //case 'PRODUCT_MODIFY':
            //case 'PRODUCT_DELETE':
            //case 'PRODUCT_PRICE_MODIFY':
            //case 'PRODUCT_SET_MULTILANGS':
            //case 'PRODUCT_DEL_MULTILANGS':
            //Stock mouvement
            //case 'STOCK_MOVEMENT':
            //MYECMDIR
            //case 'MYECMDIR_CREATE':
            //case 'MYECMDIR_MODIFY':
            //case 'MYECMDIR_DELETE':
            // Sales orders
            //case 'ORDER_CREATE':
            //case 'ORDER_MODIFY':
            //case 'ORDER_VALIDATE':
            //case 'ORDER_DELETE':
            //case 'ORDER_CANCEL':
            //case 'ORDER_SENTBYMAIL':
            //case 'ORDER_CLASSIFY_BILLED':
            //case 'ORDER_SETDRAFT':
            //case 'LINEORDER_INSERT':
            //case 'LINEORDER_UPDATE':
            //case 'LINEORDER_DELETE':
            // Supplier orders
            //case 'ORDER_SUPPLIER_CREATE':
            //case 'ORDER_SUPPLIER_MODIFY':
            //case 'ORDER_SUPPLIER_VALIDATE':
            //case 'ORDER_SUPPLIER_DELETE':
            //case 'ORDER_SUPPLIER_APPROVE':
            //case 'ORDER_SUPPLIER_REFUSE':
            //case 'ORDER_SUPPLIER_CANCEL':
            //case 'ORDER_SUPPLIER_SENTBYMAIL':
            //case 'ORDER_SUPPLIER_RECEIVE':
            //case 'LINEORDER_SUPPLIER_DISPATCH':
            //case 'LINEORDER_SUPPLIER_CREATE':
            //case 'LINEORDER_SUPPLIER_UPDATE':
            //case 'LINEORDER_SUPPLIER_DELETE':
            // Proposals
            //case 'PROPAL_CREATE':
            //case 'PROPAL_MODIFY':
            //case 'PROPAL_VALIDATE':
            //case 'PROPAL_SENTBYMAIL':
            //case 'PROPAL_CLOSE_SIGNED':
            //case 'PROPAL_CLOSE_REFUSED':
            //case 'PROPAL_DELETE':
            //case 'LINEPROPAL_INSERT':
            //case 'LINEPROPAL_UPDATE':
            //case 'LINEPROPAL_DELETE':
            // SupplierProposal
            //case 'SUPPLIER_PROPOSAL_CREATE':
            //case 'SUPPLIER_PROPOSAL_MODIFY':
            //case 'SUPPLIER_PROPOSAL_VALIDATE':
            //case 'SUPPLIER_PROPOSAL_SENTBYMAIL':
            //case 'SUPPLIER_PROPOSAL_CLOSE_SIGNED':
            //case 'SUPPLIER_PROPOSAL_CLOSE_REFUSED':
            //case 'SUPPLIER_PROPOSAL_DELETE':
            //case 'LINESUPPLIER_PROPOSAL_INSERT':
            //case 'LINESUPPLIER_PROPOSAL_UPDATE':
            //case 'LINESUPPLIER_PROPOSAL_DELETE':
            // Contracts
            //case 'CONTRACT_CREATE':
            //case 'CONTRACT_MODIFY':
            //case 'CONTRACT_ACTIVATE':
            //case 'CONTRACT_CANCEL':
            //case 'CONTRACT_CLOSE':
            //case 'CONTRACT_DELETE':
            //case 'LINECONTRACT_INSERT':
            //case 'LINECONTRACT_UPDATE':
            //case 'LINECONTRACT_DELETE':
            // Bills
            //case 'BILL_CREATE':
            //case 'BILL_MODIFY':
            //case 'BILL_VALIDATE':
            //case 'BILL_UNVALIDATE':
            //case 'BILL_SENTBYMAIL':
            //case 'BILL_CANCEL':
            //case 'BILL_DELETE':
            //case 'BILL_PAYED':
            //case 'LINEBILL_INSERT':
            //case 'LINEBILL_UPDATE':
            //case 'LINEBILL_DELETE':
            //Supplier Bill
            //case 'BILL_SUPPLIER_CREATE':
            //case 'BILL_SUPPLIER_UPDATE':
            //case 'BILL_SUPPLIER_DELETE':
            //case 'BILL_SUPPLIER_PAYED':
            //case 'BILL_SUPPLIER_UNPAYED':
            //case 'BILL_SUPPLIER_VALIDATE':
            //case 'BILL_SUPPLIER_UNVALIDATE':
            //case 'LINEBILL_SUPPLIER_CREATE':
            //case 'LINEBILL_SUPPLIER_UPDATE':
            //case 'LINEBILL_SUPPLIER_DELETE':
            // Payments
            //case 'PAYMENT_CUSTOMER_CREATE':
            //case 'PAYMENT_SUPPLIER_CREATE':
            //case 'PAYMENT_ADD_TO_BANK':
            //case 'PAYMENT_DELETE':
            // Online
            //case 'PAYMENT_PAYBOX_OK':
            //case 'PAYMENT_PAYPAL_OK':
            //case 'PAYMENT_STRIPE_OK':
            // Donation
            //case 'DON_CREATE':
            //case 'DON_UPDATE':
            //case 'DON_DELETE':
            // Interventions
            //case 'FICHINTER_CREATE':
            //case 'FICHINTER_MODIFY':
            //case 'FICHINTER_VALIDATE':
            //case 'FICHINTER_DELETE':
            //case 'LINEFICHINTER_CREATE':
            //case 'LINEFICHINTER_UPDATE':
            //case 'LINEFICHINTER_DELETE':
            // Members
            //case 'MEMBER_CREATE':
            //case 'MEMBER_VALIDATE':
            //case 'MEMBER_SUBSCRIPTION':
            //case 'MEMBER_MODIFY':
            //case 'MEMBER_NEW_PASSWORD':
            //case 'MEMBER_RESILIATE':
            //case 'MEMBER_DELETE':
            // Categories
            //case 'CATEGORY_CREATE':
            //case 'CATEGORY_MODIFY':
            //case 'CATEGORY_DELETE':
            //case 'CATEGORY_SET_MULTILANGS':
            // Projects
            //case 'PROJECT_CREATE':
            //case 'PROJECT_MODIFY':
            //case 'PROJECT_DELETE':
            // Project tasks
            //case 'TASK_CREATE':
            //case 'TASK_MODIFY':
            //case 'TASK_DELETE':
            // Task time spent
            //case 'TASK_TIMESPENT_CREATE':
            //case 'TASK_TIMESPENT_MODIFY':
            //case 'TASK_TIMESPENT_DELETE':
            //case 'PROJECT_ADD_CONTACT':
            //case 'PROJECT_DELETE_CONTACT':
            //case 'PROJECT_DELETE_RESOURCE':
            // Shipping
            //case 'SHIPPING_CREATE':
            //case 'SHIPPING_MODIFY':
            //case 'SHIPPING_VALIDATE':
            //case 'SHIPPING_SENTBYMAIL':
            //case 'SHIPPING_BILLED':
            //case 'SHIPPING_CLOSED':
            //case 'SHIPPING_REOPEN':
            //case 'SHIPPING_DELETE':
            // and more...
            case 'CONDITIONREPORT_SENTBYMAIL':
            case 'CONDITIONREPORT_SENTBYSMS':
            case 'CONDITIONREPORT_VALIDATE':
            case 'CONDITIONREPORT_SIGNED_LESSOR':
            case 'CONDITIONREPORT_SIGNED_TENANT':

                // Insertion action
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                $now                      = dol_now();
                $elementtype              = 'conditionreport';
                $elementmodule            = 'conditionreport';
                $actioncomm               = new ActionComm($this->db);
                $actioncomm->type_code    = 'AC_OTH_AUTO'; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                $actioncomm->code         = 'AC_' . $action;
                $actioncomm->label        = $langs->trans('Auto' . $action);
                $actioncomm->note_private = $object->actionmsg;
                $actioncomm->datep        = $now;
                $actioncomm->datef        = $now;
                $actioncomm->durationp    = 0;
                $actioncomm->percentage   = -1; // Not applicable
                $actioncomm->socid        = $object->fk_tenant;
                $actioncomm->authorid     = $user->id; // User saving action
                $actioncomm->userownerid  = $user->id; // Owner of action
                // Fields defined when action is an email (content should be into object->actionmsg to be added into event note, subject should be into object->actionms2 to be added into event label)
                if (!property_exists($object, 'email_fields_no_propagate_in_actioncomm') || empty($object->email_fields_no_propagate_in_actioncomm)) {
                    $actioncomm->email_msgid   = empty($object->email_msgid) ? null : $object->email_msgid;
                    $actioncomm->email_from    = empty($object->email_from) ? null : $object->email_from;
                    $actioncomm->email_sender  = empty($object->email_sender) ? null : $object->email_sender;
                    $actioncomm->email_to      = empty($object->email_to) ? null : $object->email_to;
                    $actioncomm->email_tocc    = empty($object->email_tocc) ? null : $object->email_tocc;
                    $actioncomm->email_tobcc   = empty($object->email_tobcc) ? null : $object->email_tobcc;
                    $actioncomm->email_subject = empty($object->email_subject) ? null : $object->email_subject;
                    $actioncomm->errors_to     = empty($object->errors_to) ? null : $object->errors_to;
                }

                // Object linked (if link is for thirdparty, contact or project, it is a recording error. We should not have links in link table
                // for such objects because there is already a dedicated field into table llx_actioncomm or llx_actioncomm_resources.
//                if (!in_array($elementtype, array('societe', 'contact', 'project'))) {
                $actioncomm->fk_element  = $object->id;
                $actioncomm->elementtype = $elementtype . ($elementmodule ? '@' . $elementmodule : '');
//                }

                if (property_exists($object, 'attachedfiles') && is_array($object->attachedfiles) && count($object->attachedfiles) > 0) {
                    $actioncomm->attachedfiles = $object->attachedfiles;
                }
                if (property_exists($object, 'sendtouserid') && is_array($object->sendtouserid) && count($object->sendtouserid) > 0) {
                    $actioncomm->userassigned = $object->sendtouserid;
                }
                if (property_exists($object, 'sendtoid') && is_array($object->sendtoid) && count($object->sendtoid) > 0) {
                    foreach ($object->sendtoid as $val) {
                        $actioncomm->socpeopleassigned[$val] = $val;
                    }
                }

                $ret = $actioncomm->create($user); // User creating action
//                var_dump($ret,$actioncomm->error);die();
                if ($ret > 0 && !empty($conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO)) {
                    if (property_exists($object, 'attachedfiles') && is_array($object->attachedfiles) && array_key_exists('paths', $object->attachedfiles) && count($object->attachedfiles['paths']) > 0) {
                        foreach ($object->attachedfiles['paths'] as $key => $filespath) {
                            $srcfile  = $filespath;
                            $destdir  = $conf->agenda->dir_output . '/' . $ret;
                            $destfile = $destdir . '/' . $object->attachedfiles['names'][$key];
                            if (dol_mkdir($destdir) >= 0) {
                                require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                                dol_copy($srcfile, $destfile);
                            }
                        }
                    }
                }
                break;

            default:
                dol_syslog("Trigger '" . $this->name . "' for action '" . $action . "' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }

        return 0;
    }
}
