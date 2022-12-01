<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Categories\CategoryVO;
use FernleafSystems\Integrations\Freeagent\Consumers\{
	FreeagentConfigVoConsumer,
	PayoutVoConsumer
};

class CreateForPayout {

	use ConnectionConsumer;
	use FreeagentConfigVoConsumer;
	use PayoutVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function create() :Entities\Bills\BillVO {
		$faConfig = $this->getFreeagentConfigVO();
		$payout = $this->getPayoutVO();

		$billContact = ( new Entities\Contacts\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $faConfig->contact_id )
			->retrieve();
		if ( empty( $billContact ) ) {
			throw new \Exception( sprintf( 'Failed to load FreeAgent Contact bill for Payment processor with ID "%s" ', $faConfig->contact_id ) );
		}

		$billItem = new Entities\Bills\Items\BillItemVO();
		$billItem->description = $payout->id;
		$billItem->total_value = $payout->getTotalFee();
		$billItem->category = $this->getBillCategory()->url;
		$billItem->sales_tax_rate = 'Auto';
		$billItem->sales_tax_status = $billItem::TAX_STATUS_TAXABLE;

		$creator = ( new Entities\Bills\Create() )
			->setConnection( $this->getConnection() )
			->addBillItem( $billItem )
			->setContact( $billContact )
			->setReference( $payout->id )
			->setDatedOn( $payout->date_arrival )
			->setDueOn( $payout->date_arrival )
			->setComment( implode( "\n", [
				sprintf( 'Bill for Payout: %s', $payout->id ),
				sprintf( 'Payout Gross Amount: %s %s', $payout->currency, $payout->getTotalGross() ),
				sprintf( 'Payout Fees Total: %s %s', $payout->currency, $payout->getTotalFee() ),
				sprintf( 'Payout Net Amount: %s %s', $payout->currency, round( $payout->getTotalNet(), 2 ) )
			] ) )
			->setCurrency( $payout->currency );

		// TODO: This is a bit of a hack as no accounting for base account country.
		if ( $this->isEuCountry( $billContact->country ) ) {
			$creator->setEcStatus( Entities\Common\Constants::VAT_STATUS_EC_SERVICES );
		}

		$bill = $creator->create();

		if ( empty( $bill ) || empty( $bill->getId() ) ) {
			throw new \Exception( sprintf( 'Failed to create FreeAgent bill for Payout ID %s: %s ',
				$payout->id, $creator->getLastErrorContent() ) );
		}

		return $bill;
	}

	/**
	 * @throws \Exception
	 */
	private function getBillCategory() :CategoryVO {
		$cat = ( new Entities\Categories\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $this->getFreeagentConfigVO()->bill_cat_id )
			->retrieve();
		if ( empty( $cat ) ) {
			throw new \Exception( sprintf( 'Failed to retrieve FreeAgent Category for ID %s',
				$this->getFreeagentConfigVO()->bill_cat_id ) );
		}
		return $cat;
	}

	/**
	 * @param string $country
	 */
	private function isEuCountry( $country ) :bool {
		return in_array( strtolower( $country ), array_map( 'strtolower', $this->getEuCountries() ) );
	}

	private function getEuCountries() :array {
		return [
			'Austria',
			'Belgium',
			'Bulgaria',
			'Croatia',
			'Cyprus',
			'Czech Republic',
			'Czechia',
			'Denmark',
			'Estonia',
			'Finland',
			'France',
			'Germany',
			'Greece',
			'Hungary',
			'Ireland',
			'Italy',
			'Latvia',
			'Lithuania',
			'Luxembourg',
			'Malta',
			'Netherlands',
			'Poland',
			'Portugal',
			'Romania',
			'Slovakia',
			'Slovenia',
			'Spain',
			'Sweden'
		];
	}
}