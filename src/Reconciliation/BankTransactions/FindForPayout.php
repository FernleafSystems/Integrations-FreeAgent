<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;
use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

class FindForPayout extends DynPropertiesClass {

	use ConnectionConsumer;
	use Consumers\BankAccountVoConsumer;
	use Consumers\PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function find() :?Entities\BankTransactions\BankTransactionVO {
		return ( new Entities\BankTransactions\Finder() )
			->filterByDateRange( $this->getPayoutVO()->date_arrival, 1 )
			->filterByBankAccount( $this->getBankAccountVo() )
			->filterByUnexplained()
			->setConnection( $this->getConnection() )
			->byAmount( $this->getPayoutVO()->getTotalNet() );
	}
}