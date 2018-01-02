<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills\BillVO;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills\Create;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts\ContactVO;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts\Retrieve;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;

/**
 * Class CreateForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\Bills
 */
class CreateForPayout {

	use ConnectionConsumer,
		FreeagentConfigVoConsumer,
		PayoutVoConsumer;

	/**
	 * @return BillVO|null
	 * @throws \Exception
	 */
	public function create() {
		$oFaConfig = $this->getFreeagentConfigVO();
		$oPayout = $this->getPayoutVO();

		$nTotalFees = $oPayout->getTotalFee();

		/** @var ContactVO $oBillContact */
		$oBillContact = ( new Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oFaConfig->getContactId() )
			->retrieve();
		if ( empty( $oBillContact ) ) {
			throw new \Exception( sprintf( 'Failed to load FreeAgent Contact bill for Stripe with ID "%s" ', $oFaConfig->getContactId() ) );
		}

		$aComments = array(
			sprintf( 'Bill for Payout: %s', $oPayout->getId() ),
			sprintf( 'Payout Gross Amount: %s %s', $oPayout->getCurrency(), $oPayout->getTotalGross() ),
			sprintf( 'Payout Fees Total: %s %s', $oPayout->getCurrency(), $nTotalFees ),
			sprintf( 'Payout Net Amount: %s %s', $oPayout->getCurrency(), round( $oPayout->getTotalNet(), 2 ) )
		);

		$oBill = ( new Create() )
			->setConnection( $this->getConnection() )
			->setContact( $oBillContact )
			->setReference( $oPayout->getId() )
			->setDatedOn( $oPayout->getDateArrival() )
			->setDueOn( $oPayout->getDateArrival() )
			->setCategoryId( $this->getFreeagentConfigVO()->getBillCategoryId() )
			->setComment( implode( "\n", $aComments ) )
			->setTotalValue( $nTotalFees )
			->setSalesTaxRate( 0 )
			->setEcStatus( 'EC Services' )
			->create();

		if ( empty( $oBill ) || empty( $oBill->getId() ) ) {
			throw new \Exception( sprintf( 'Failed to create FreeAgent bill for Stripe Payout ID %s / ', $oPayout->getId() ) );
		}

		return $oBill;
	}
}