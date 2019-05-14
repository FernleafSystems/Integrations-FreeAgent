<?php

namespace FernleafSystems\Integrations\Freeagent\Verify;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent;

/**
 * Class VerifyFreeagentConfig
 * @package FernleafSystems\Integrations\Freeagent\Verify
 */
class VerifyFreeagentConfig {

	use ConnectionConsumer,
		Freeagent\Consumers\FreeagentConfigVoConsumer;

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function runVerify() {
		$oCon = $this->getConnection();
		$oFAConf = $this->getFreeagentConfigVO();

		$oCoRetr = ( new Entities\Company\Retrieve() )->setConnection( $oCon );
		$oCompany = $oCoRetr->send();
		if ( empty( $oCompany ) ) {
			throw new \Exception( 'Company could not be retrieved: '.var_export( $oCoRetr->getLastErrorContent(), true ) );
		}
		else if ( $oFAConf->getBankAccountIdForCurrency( $oCompany->currency ) < 1 ) {
			throw new \Exception( 'Bank Account for native company currency is not valid.' );
		}

		$oContactVerifier = ( new Entities\Contacts\Retrieve() )
			->setConnection( $oCon )
			->setEntityId( $oFAConf->contact_id );
		if ( empty( $oFAConf->contact_id ) || !$oContactVerifier->exists() ) {
			throw new \Exception( sprintf( 'Contact ID for bills could not be verified: "%s"', $oFAConf->contact_id ) );
		}

		$oUser = ( new Entities\Users\RetrieveMe() )
			->setConnection( $oCon )
			->retrieve();
		if ( empty( $oUser ) || $oUser->permission_level < 6 ) {
			throw new \Exception( 'User permissions are not valid.' );
		}

		$bCatExists = ( new Entities\Categories\Retrieve() )
			->setConnection( $oCon )
			->setEntityId( $oFAConf->invoice_item_cat_id )
			->exists();
		if ( !$bCatExists ) {
			throw new \Exception( sprintf( 'Invoice item category does not exist: "%s"', $oFAConf->invoice_item_cat_id ) );
		}

		$bCatExists = ( new Entities\Categories\Retrieve() )
			->setConnection( $oCon )
			->setEntityId( $oFAConf->bill_cat_id )
			->exists();
		if ( !$bCatExists ) {
			throw new \Exception( sprintf( 'Bill category does not exist: "%s"', $oFAConf->bill_cat_id ) );
		}

		$oBankAccountRetriever = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $oCon );
		foreach ( $oFAConf->getAllBankAccounts() as $sCurrency => $nId ) {
			$oBankAccount = $oBankAccountRetriever
				->setEntityId( $nId )
				->retrieve();
			if ( empty( $oBankAccount ) || strcasecmp( $sCurrency, $oBankAccount->currency ) != 0 ) {
				throw new \Exception( sprintf( 'Back account is not valid for currency: "%s" "%s"', $nId, $sCurrency ) );
			}
		}

		if ( $oFAConf->bank_account_id_foreign > 0 ) {
			$bForeignBankExists = $oBankAccountRetriever
				->setEntityId( $oFAConf->bank_account_id_foreign )
				->exists();
			if ( !$bForeignBankExists ) {
				throw new \Exception( sprintf( 'Back account does not exist: "%s"', $oFAConf->bank_account_id_foreign ) );
			}
		}

		return true;
	}

	/**
	 * @param Freeagent\DataWrapper\FreeagentConfigVO $oFreeAgentConfig
	 * @return bool
	 */
	public function verify( Freeagent\DataWrapper\FreeagentConfigVO $oFreeAgentConfig ) {
		try {
			$bValid = $this->setFreeagentConfigVO( $oFreeAgentConfig )
						   ->runVerify();
		}
		catch ( \Exception $oE ) {
			$bValid = false;
		}
		return $bValid;
	}
}