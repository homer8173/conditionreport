<?php
/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

/**
 * Do something
 *
 * @param   int				$param				0=True url, 1=Url formated with colors
 * @return	string								Url string
 */
function conditionreport_completesubstitutionarray(&$substitutionarray, $outputlangs, $object, $parameters)
{
    if (is_object($object)&& is_callable([$object ,'getOnlineSignatureUrl']) && get_class($object) =='Conditionreport')
        $substitutionarray['__ONLINE_SIGN_URL__'] = $object->getOnlineSignatureUrl(0, 'conditionreport', $object->ref, 1, $object);
}
