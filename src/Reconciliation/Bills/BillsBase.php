<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Categories;
use FernleafSystems\Integrations\Freeagent\Consumers\{
	FreeagentConfigVoConsumer,
	PayoutVoConsumer
};

class BillsBase {

	use ConnectionConsumer;
	use FreeagentConfigVoConsumer;
	use PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	protected function getBillCategory() :Categories\CategoryVO {
		$cat = ( new Categories\Retrieve() )
			->setEntityId( $this->getFreeagentConfigVO()->bill_cat_id )
			->setConnection( $this->getConnection() )
			->retrieve();
		if ( empty( $cat ) ) {
			throw new \Exception( sprintf( 'Failed to retrieve FreeAgent Category for ID %s', $this->getFreeagentConfigVO()->bill_cat_id ) );
		}
		return $cat;
	}
}