<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Common\Constants;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts;

class CreateForPayout extends BillsBase {

	/**
	 * @throws \Exception
	 */
	public function create() :Bills\BillVO {
		$cfg = $this->getFreeagentConfigVO();
		$payout = $this->getPayoutVO();

		$billContact = ( new Contacts\Retrieve() )
			->setEntityId( $cfg->contact_id )
			->setConnection( $this->getConnection() )
			->retrieve();
		if ( empty( $billContact ) ) {
			throw new \Exception( sprintf( 'Failed to load FreeAgent Contact bill for Payment processor with ID "%s" ', $cfg->contact_id ) );
		}

		$billItem = new Bills\Items\BillItemVO();
		$billItem->description = $payout->id;
		$billItem->total_value = $payout->getTotalFee();
		$billItem->category = $this->getBillCategory()->url;
		$billItem->sales_tax_rate = 'Auto';
		$billItem->sales_tax_status = $billItem::TAX_STATUS_TAXABLE;

		$creator = ( new Bills\Create() )
			->addBillItem( $billItem )
			->setContact( $billContact )
			->setConnection( $this->getConnection() )
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
		if ( $this->isEuCountry( (string)$billContact->country ) ) {
			$creator->setEcStatus( Constants::VAT_STATUS_EC_SERVICES );
		}

		$bill = $creator->create();

		if ( empty( $bill ) || empty( $bill->getId() ) ) {
			throw new \Exception( sprintf( 'Failed to create FreeAgent bill for Payout ID %s: %s ',
				$payout->id, $creator->getLastErrorContent() ) );
		}

		return $bill;
	}

	private function isEuCountry( string $country ) :bool {
		return \in_array( \strtolower( $country ), \array_map( 'strtolower', $this->getEuCountries() ) );
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