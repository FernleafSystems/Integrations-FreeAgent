<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Freeagent\Consumers;

class CreateFromCharge {

	use Consumers\BridgeConsumer,
		Consumers\ChargeVoConsumer,
		ConnectionConsumer,
		Consumers\FreeagentConfigVoConsumer;

	/**
	 * @return Entities\Invoices\InvoiceVO|null
	 * @throws \Exception
	 */
	public function create() {
		$oCharge = $this->getChargeVO();
		$oContact = $this->getContact();

		$oCreator = ( new Entities\Invoices\Create() )
			->setConnection( $this->getConnection() )
			->setContact( $oContact )
			->setDatedOn( $oCharge->getDate() )
			->setPaymentTerms( $oCharge->getPaymentTerms() )
			->setExchangeRate( 1.0 )// TODO: Verify this perhaps with Txn
			->setCurrency( $oCharge->getCurrency() )
			->setComments(
				serialize(
					[
						'local_payment_id'  => $oCharge->getLocalPaymentId(),
						'gateway'           => $oCharge->gateway,
						'gateway_charge_id' => $oCharge->id
					]
				)
			)
			->addInvoiceItemVOs( $this->buildLineItemsFromCartItem() );

		if ( $oCharge->isEuVatMoss() ) {
			$oCreator->setEcPlaceOfSupply( $oContact->country )
					 ->setEcStatusVatMoss();
		}
		else {
			$oCreator->setEcStatusNonEc();
		}

		$oExportedInvoice = $oCreator->create();

		if ( !is_null( $oExportedInvoice ) ) {
			sleep( 2 );
			$oExportedInvoice = $this->markInvoiceAsSent( $oExportedInvoice );
		}
		else {
//			var_dump( $oCreator->getRawDataAsArray() );
			throw new \Exception( sprintf( 'Could not create invoice for Charge %s: %s',
				$oCharge->id, $oCreator->getLastError()->getMessage() ) );
		}

		$this->getBridge()
			 ->storeFreeagentInvoiceIdForCharge( $oCharge, $oExportedInvoice );

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
		$aInvoiceItems = [];
		$oCharge = $this->getChargeVO();

		$oItem = ( new Entities\Invoices\Items\InvoiceItemVO() )
			->setType( $oCharge->getItemPeriodType() );
		$oItem->description = $oCharge->item_name;
		$oItem->quantity = $oCharge->getItemQuantity();
		$oItem->price = $oCharge->getItemSubtotal();
		$oItem->sales_tax_rate = $oCharge->getItemTaxRate();
		$oItem->category = 'https://api.freeagent.com/v2/categories/'.$this->getFreeagentConfigVO()->invoice_item_cat_id;

		$aInvoiceItems[] = $oItem;
		return $aInvoiceItems;
	}

	/**
	 * @return Entities\Contacts\ContactVO
	 */
	public function getContact() {
		return $this->getBridge()->createFreeagentContact( $this->getChargeVO() );
	}
}