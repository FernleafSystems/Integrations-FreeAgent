<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers\BridgeConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\ChargeVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;

class CreateFromCharge {

	use BridgeConsumer,
		ChargeVoConsumer,
		ConnectionConsumer,
		FreeagentConfigVoConsumer;

	/**
	 * @return Entities\Invoices\InvoiceVO|null
	 */
	public function create() {
		$oCharge = $this->getChargeVO();
		$oContact = $this->getContact();

		$oInvoiceCreator = ( new Entities\Invoices\Create() )
			->setConnection( $this->getConnection() )
			->setContact( $oContact )
			->setDatedOn( $oCharge->getDate() )
			->setPaymentTerms( $oCharge->getPaymentTerms() )
			->setExchangeRate( 1.0 )// TODO: Verify this perhaps with Txn
			->setCurrency( $oCharge->getCurrency() )
			->setComments(
				serialize(
					array(
						'local_payment_id'  => $oCharge->getLocalPaymentId(),
						'gateway'           => $oCharge->getGateway(),
						'gateway_charge_id' => $oCharge->getId()
					)
				)
			)
			->addInvoiceItemVOs( $this->buildLineItemsFromCartItem() );

		if ( $oCharge->isEuVatMoss() ) {
			$oInvoiceCreator->setEcPlaceOfSupply( $oContact->getCountry() )
							->setEcStatusVatMoss();
		}
		else {
			$oInvoiceCreator->setEcStatusNonEc();
		}

		$oExportedInvoice = $oInvoiceCreator->create();

		if ( !is_null( $oExportedInvoice ) ) {
			sleep( 2 );
			$oExportedInvoice = $this->markInvoiceAsSent( $oExportedInvoice );
		}
		return $oExportedInvoice;
	}

	/**
	 * @param Entities\Invoices\InvoiceVO $oInvoice
	 * @return Entities\Invoices\InvoiceVO
	 */
	protected function markInvoiceAsSent( $oInvoice ) {
		( new Entities\Invoices\MarkAs() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oInvoice->getId() )
			->sent();
		return ( new Entities\Invoices\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oInvoice->getId() )
			->retrieve();
	}

	/**
	 * @return Entities\Invoices\Items\InvoiceItemVO[]
	 */
	protected function buildLineItemsFromCartItem() {
		$aInvoiceItems = array();
		$oCharge = $this->getChargeVO();

		$aInvoiceItems[] = ( new Entities\Invoices\Items\InvoiceItemVO() )
			->setDescription( $oCharge->getItemName() )
			->setQuantity( $oCharge->getItemQuantity() )
			->setPrice( $oCharge->getItemSubtotal() )
			->setSalesTaxRate( $oCharge->getItemTaxRate() )
			->setType( $oCharge->getItemPeriodType() )
			->setCategoryId( $this->getFreeagentConfigVO()->getInvoiceItemCategoryId() );

		return $aInvoiceItems;
	}

	/**
	 * @return Entities\Contacts\ContactVO
	 */
	public function getContact() {
		return ( new Entities\Contacts\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $this->getBridge()->getFreeagentContactId( $this->getChargeVO() ) )
			->retrieve();
	}

	/**
	 * TODO Take this out and put it in the building of a charge.
	 * @return array
	 */
	protected function getTaxCountriesRates() {
		$aCountriesToRates = array();
		foreach ( edd_get_tax_rates() as $aCountryRate ) {
			if ( !empty( $aCountryRate[ 'country' ] ) ) {
				$aCountriesToRates[ $aCountryRate[ 'country' ] ] = $aCountryRate[ 'rate' ];
			}
		}
		return $aCountriesToRates;
	}

	/**
	 * @return bool
	 */
	protected function isPaymentEuVatMossRegion() {
		$sPaymentCountry = $this->getChargeVO()->getCountry(); //$this->getPayment()->address[ 'country' ];
		return ( $sPaymentCountry != 'GB' &&
				 array_key_exists( $sPaymentCountry, $this->getTaxCountriesRates() ) );
	}
}