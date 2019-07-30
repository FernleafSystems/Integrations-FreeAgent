<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;

/**
 * Class CreateForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\Bills
 */
class CreateForPayout {

	use ConnectionConsumer,
		Consumers\FreeagentConfigVoConsumer,
		Consumers\PayoutVoConsumer;

	/**
	 * @return Entities\Bills\BillVO|null
	 * @throws \Exception
	 */
	public function create() {
		$oFaConfig = $this->getFreeagentConfigVO();
		$oPayout = $this->getPayoutVO();

		$nTotalFees = $oPayout->getTotalFee();

		$oBillContact = ( new Entities\Contacts\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oFaConfig->contact_id )
			->retrieve();
		if ( empty( $oBillContact ) ) {
			throw new \Exception( sprintf( 'Failed to load FreeAgent Contact bill for Payment processor with ID "%s" ', $oFaConfig->contact_id ) );
		}

		$aComments = [
			sprintf( 'Bill for Payout: %s', $oPayout->id ),
			sprintf( 'Payout Gross Amount: %s %s', $oPayout->getCurrency(), $oPayout->getTotalGross() ),
			sprintf( 'Payout Fees Total: %s %s', $oPayout->getCurrency(), $nTotalFees ),
			sprintf( 'Payout Net Amount: %s %s', $oPayout->getCurrency(), round( $oPayout->getTotalNet(), 2 ) )
		];

		$oBillCreator = ( new Entities\Bills\Create() )
			->setConnection( $this->getConnection() )
			->setContact( $oBillContact )
			->setReference( $oPayout->id )
			->setDatedOn( $oPayout->date_arrival )
			->setDueOn( $oPayout->date_arrival )
			->setCategoryId( $this->getFreeagentConfigVO()->bill_cat_id )
			->setComment( implode( "\n", $aComments ) )
			->setTotalValue( $nTotalFees )
			->setSalesTaxRate( 0 );

		// TODO: This is a bit of a hack as no accounting for base account country.
		if ( $this->isEuCountry( $oBillContact->getCountry() ) ) {
			$oBillCreator->setEcStatus( 'EC Services' );
		}

		$oBill = $oBillCreator->create();

		if ( empty( $oBill ) || empty( $oBill->getId() ) ) {
			throw new \Exception( sprintf( 'Failed to create FreeAgent bill for Payout ID %s: %s ',
				$oPayout->id, $oBillCreator->getLastErrorContent() ) );
		}

		return $oBill;
	}

	/**
	 * @param string $sCountry
	 * @return bool
	 */
	private function isEuCountry( $sCountry ) {
		return in_array( strtolower( $sCountry ), array_map( 'strtolower', $this->getEuCountries() ) );
	}

	/**
	 * @return string[]
	 */
	private function getEuCountries() {
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