<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;

/**
 * TODO: INVALID
 * Class FindForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\Bills
 */
class FindForPayout {

	use ConnectionConsumer,
		PayoutVoConsumer;

	/**
	 * @return bool
	 */
	public function hasBill() {
		return !empty( $this->find() );
	}

	/**
	 * @return Entities\Bills\BillVO|null
	 */
	public function find() {
		$oBill = null;
		$oPayout = $this->getPayoutVO();

		if ( !empty( $oPayout->getExternalBillId() ) ) {
			$oBill = ( new Entities\Bills\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $oPayout->getExternalBillId() )
				->retrieve();
		}

		if ( empty( $oBill ) ) {
			try {
				$oBill = $this->findBillForStripePayout();
				$oPayout->metadata[ 'ext_bill_id' ] = $oBill->getId();
				$oPayout->save();
			}
			catch ( \Exception $oE ) {
				trigger_error( $oE->getMessage() );
			}
		}

		return $oBill;
	}

	/**
	 * @return Entities\Bills\BillVO|null
	 * @throws \Exception
	 */
	protected function findBillForStripePayout() {
		$oPayout = $this->getPayoutVO();

		/** @var Entities\Bills\BillVO $oBill */
		$oBill = ( new Entities\Bills\Finder() )
			->setConnection( $this->getConnection() )
			->filterByDateRange( $oPayout->getDateArrival(), 5 )
			->findByReference( $oPayout->getId() );

		if ( empty( $oBill ) ) {
			throw new \Exception( sprintf( 'Failed to find bill in FreeAgent for Payout transfer ID %s', $oPayout->getId() ) );
		}
		return $oBill;
	}
}