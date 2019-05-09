<?php

namespace FernleafSystems\Integrations\Freeagent\Verify;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\DataWrapper\FreeagentConfigVO;

/**
 * Class VerifyFreeagentConfig
 * @package FernleafSystems\Integrations\Freeagent\Verify
 */
class VerifyFreeagentConfig {

	use ConnectionConsumer;

	/**
	 * @param FreeagentConfigVO $oFreeAgentConfig
	 * @return bool
	 */
	public function verify( $oFreeAgentConfig ) {
		$oCon = $this->getConnection();

		$oCompany = ( new Entities\Company\Retrieve() )
			->setConnection( $oCon )
			->retrieve();

		$bValid = !empty( $oCompany );

		$bValid = $bValid && ( $oFreeAgentConfig->contact_id > 0 ) &&
				  ( new Entities\Contacts\Retrieve() )
					  ->setConnection( $oCon )
					  ->setEntityId( $oFreeAgentConfig->contact_id )
					  ->exists();

		if ( $bValid ) {
			$nApiUserPermissionLevel = ( new Entities\Users\RetrieveMe() )
				->setConnection( $oCon )
				->retrieve()
				->permission_level;
			$bValid = ( $nApiUserPermissionLevel >= 6 ); // at least Banking level
		}

		$bValid = $bValid && ( new Entities\Categories\Retrieve() )
				->setConnection( $oCon )
				->setEntityId( $oFreeAgentConfig->invoice_item_cat_id )
				->exists();

		$bValid = $bValid && ( new Entities\Categories\Retrieve() )
				->setConnection( $oCon )
				->setEntityId( $oFreeAgentConfig->bill_cat_id )
				->exists();

		$oBankAccountRetriever = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $oCon );
		if ( $bValid ) {
			foreach ( $oFreeAgentConfig->getAllBankAccounts() as $sCurrency => $nId ) {
				$oBankAccount = $oBankAccountRetriever
					->setEntityId( $nId )
					->retrieve();
				$bValid = $bValid && !is_null( $oBankAccount ) &&
						  strcasecmp( $sCurrency, $oBankAccount->currency ) == 0;
			}
		}

		if ( $bValid && $oFreeAgentConfig->bank_account_id_foreign > 0 ) {
			$bValid = $oBankAccountRetriever
				->setEntityId( $oFreeAgentConfig->bank_account_id_foreign )
				->exists();
		}

		if ( $bValid ) {
			$sBaseAccountCurrency = ( new Entities\Company\Retrieve() )
				->setConnection( $oCon )
				->retrieve()
				->currency;
			$bValid = $oFreeAgentConfig->getBankAccountIdForCurrency( $sBaseAccountCurrency ) > 0;
		}

		return $bValid;
	}
}