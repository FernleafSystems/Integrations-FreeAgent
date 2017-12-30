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
	public function createBill() {
		$oBill = ( new FindForPayout() )
			->setConnection( $this->getConnection() )
			->setPayoutVO( $this->getPayoutVO() )
			->find();
		if ( empty( $oBill ) ) {
			$oBill = $this->create();
		}
		return $oBill;
	}

	/**
	 * Also store Payout meta data: ext_bill_id to reference the FreeAgent Bill ID (saves us searching
	 * for it later).
	 * @return BillVO|null
	 * @throws \Exception
	 */
	protected function create() {
		$oFaConfig = $this->getFreeagentConfigVO();
		$oPayout = $this->getPayoutVO();

		$nTotalFees = $oPayout->getTotalFees();

		/** @var ContactVO $oStripeContact */
		$oStripeContact = ( new Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oFaConfig->getContactId() )
			->retrieve();
		if ( empty( $oStripeContact ) ) {
			throw new \Exception( sprintf( 'Failed to load FreeAgent Contact bill for Stripe with ID "%s" ', $oFaConfig->getContactId() ) );
		}

		$aComments = array(
			sprintf( 'Bill for Stripe Payout: https://dashboard.stripe.com/payouts/%s', $oPayout->getId() ),
			sprintf( 'Gross Amount: %s %s', $oPayout->getCurrency(), $oPayout->getAmount_Net() ),
			sprintf( 'Fees Total: %s %s', $oPayout->getCurrency(), $nTotalFees ),
			sprintf( 'Net Amount: %s %s', $oPayout->getCurrency(), round( $oPayout->getAmount_Net(), 2 ) )
		);

		$oBill = ( new Create() )
			->setConnection( $this->getConnection() )
			->setContact( $oStripeContact )
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

		$oPayout->metadata[ 'ext_bill_id' ] = $oBill->getId();
		$oPayout->save();

		return $oBill;
	}
}