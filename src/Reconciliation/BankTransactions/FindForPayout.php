<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;
use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class FindForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\BankTransactions
 */
class FindForPayout {

	use Consumers\BankAccountVoConsumer,
		Consumers\PayoutVoConsumer,
		ConnectionConsumer,
		StdClassAdapter;

	/**
	 * @return Entities\BankTransactions\BankTransactionVO|null
	 * @throws \Exception
	 */
	public function find() {
		return ( new Entities\BankTransactions\Finder() )
			->setConnection( $this->getConnection() )
			->filterByDateRange( $this->getPayoutVO()->date_arrival, 1 )
			->filterByBankAccount( $this->getBankAccountVo() )
			->filterByUnexplained()
			->byAmount( $this->getPayoutVO()->getTotalNet() );
	}
}