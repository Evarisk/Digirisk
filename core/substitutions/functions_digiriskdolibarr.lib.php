<?php
/** 		Function called to complete substitution array (before generating on ODT, or a personalized email)
 * 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * 		is inside directory htdocs/core/substitutions
 * 
 *		@param	array		$substitutionarray	Array with substitution key=>val
 *		@param	Translate	$langs			Output langs
 *		@param	Object		$object			Object to use to get values
 * 		@return	void					The entry parameter $substitutionarray is modified
 */
function digiriskdolibarr_completesubstitutionarray(&$substitutionarray,$langs,$object)
{
    global $conf,$db;

    //Copier la condition ci-dessous pour chaque extrafield à rajouter

    if ($object->fk_soc_samu > 0)
    {
		$samu = new Societe($db);
		$result = $samu->fetch($object->fk_soc_samu);
		if ($result < 0) dol_print_error('', $samu->error);
		elseif ($result > 0) {
            $substitutionarray['samu']=$samu->phone;
        }
    }
    
}