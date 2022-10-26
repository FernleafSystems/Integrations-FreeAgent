<?php

namespace FernleafSystems\Integrations\Freeagent\Verify;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent;

class VerifyFreeagentConfig {

	use ConnectionConsumer;
	use Freeagent\Consumers\FreeagentConfigVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function runVerify() :bool {
		$conn = $this->getConnection();
		$faConf = $this->getFreeagentConfigVO();

		$company = ( new Entities\Company\Retrieve() )
			->setConnection( $conn )
			->retrieve();
		if ( empty( $company ) ) {
			throw new \Exception( 'Company could not be retrieved.' );
		}
		elseif ( $faConf->getBankAccountIdForCurrency( $company->currency ) < 1 ) {
			throw new \Exception( 'Bank Account for native company currency is not valid.' );
		}

		$contactVerifier = ( new Entities\Contacts\Retrieve() )
			->setConnection( $conn )
			->setEntityId( $faConf->contact_id );
		if ( empty( $faConf->contact_id ) || !$contactVerifier->exists() ) {
			throw new \Exception( sprintf( 'Contact ID for bills could not be verified: "%s"', $faConf->contact_id ) );
		}

		$userMe = ( new Entities\Users\RetrieveMe() )
			->setConnection( $conn )
			->retrieve();
		if ( empty( $userMe ) || $userMe->permission_level < 6 ) {
			throw new \Exception( 'User permissions are not valid.' );
		}

		$categoryExists = ( new Entities\Categories\Retrieve() )
			->setConnection( $conn )
			->setEntityId( $faConf->invoice_item_cat_id )
			->exists();
		if ( !$categoryExists ) {
			throw new \Exception( sprintf( 'Invoice item category does not exist: "%s"', $faConf->invoice_item_cat_id ) );
		}

		$categoryExists = ( new Entities\Categories\Retrieve() )
			->setConnection( $conn )
			->setEntityId( $faConf->bill_cat_id )
			->exists();
		if ( !$categoryExists ) {
			throw new \Exception( sprintf( 'Bill category does not exist: "%s"', $faConf->bill_cat_id ) );
		}

		$bankAccountRetriever = ( new Entities\BankAccounts\Retrieve() )
			->setConnection( $conn );
		foreach ( $faConf->getAllBankAccounts() as $currency => $ID ) {
			$BA = $bankAccountRetriever
				->setEntityId( $ID )
				->retrieve();
			if ( empty( $BA ) || strcasecmp( $currency, $BA->currency ) != 0 ) {
				throw new \Exception( sprintf( 'Back account is not valid for currency: "%s" "%s"', $ID, $currency ) );
			}
		}

		if ( $faConf->bank_account_id_foreign > 0 ) {
			$foreignBankExists = $bankAccountRetriever
				->setEntityId( $faConf->bank_account_id_foreign )
				->exists();
			if ( !$foreignBankExists ) {
				throw new \Exception( sprintf( 'Back account does not exist: "%s"', $faConf->bank_account_id_foreign ) );
			}
		}

		return true;
	}

	public function verify() :bool {
		try {
			$valid = $this->runVerify();
		}
		catch ( \Exception $e ) {
			$valid = false;
		}
		return $valid;
	}
}