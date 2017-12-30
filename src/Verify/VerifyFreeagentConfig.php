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

		$nStripeContactID = $oFreeAgentConfig->getContactId();
		$bValid = $bValid && ( $nStripeContactID > 0 ) &&
				  ( new Entities\Contacts\Retrieve() )
					  ->setConnection( $oCon )
					  ->setEntityId( $oFreeAgentConfig->getContactId() )
					  ->exists();

		if ( $bValid ) {
			$nApiUserPermissionLevel = ( new Entities\Users\RetrieveMe() )
				->setConnection( $oCon )
				->retrieve()
				->getPermissionLevel();
			$bValid = ( $nApiUserPermissionLevel >= 6 ); // at least Banking level
		}

		$bValid = $bValid && ( new Entities\Categories\Retrieve() )
				->setConnection( $oCon )
				->setEntityId( $oFreeAgentConfig->getInvoiceItemCategoryId() )
				->exists();

		$bValid = $bValid && ( new Entities\Categories\Retrieve() )
				->setConnection( $oCon )
				->setEntityId( $oFreeAgentConfig->getBillCategoryId() )
				->exists();

		$oBankAccountRetriever = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $oCon );
		if ( $bValid ) {
			foreach ( $oFreeAgentConfig->getAllBankAccounts() as $sCurrency => $nId ) {
				$oBankAccount = $oBankAccountRetriever
					->setEntityId( $nId )
					->retrieve();
				$bValid = $bValid && !is_null( $oBankAccount ) &&
						  strcasecmp( $sCurrency, $oBankAccount->getCurrency() ) == 0;
			}
		}

		$nForeignCurrencyId = $oFreeAgentConfig->getBankAccountIdForeignCurrencyTransfer();
		if ( $bValid && $nForeignCurrencyId > 0 ) {
			$bValid = $oBankAccountRetriever
				->setEntityId( $nForeignCurrencyId )
				->exists();
		}

		if ( $bValid ) {
			$sBaseAccountCurrency = ( new Entities\Company\Retrieve() )
				->setConnection( $oCon )
				->retrieve()
				->getCurrency();
			$bValid = $oFreeAgentConfig->getBankAccountIdForCurrency( $sBaseAccountCurrency ) > 0;
		}

		return $bValid;
	}
}