<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Invoices;
use FernleafSystems\Integrations\Freeagent\Consumers;

class CreateFromCharge {

	use ConnectionConsumer;
	use Consumers\BridgeConsumer;
	use Consumers\ChargeVoConsumer;
	use Consumers\FreeagentConfigVoConsumer;

	/**
	 * @throws \Exception
	 */
	public function create() :?Invoices\InvoiceVO {
		$charge = $this->getChargeVO();
		$contact = $this->getBridge()->createFreeagentContact( $this->getChargeVO() );

		$creator = ( new Invoices\Create() )
			->setConnection( $this->getConnection() )
			->setContact( $contact )
			->setDatedOn( $charge->date )
			->setPaymentTerms( $charge->getPaymentTerms() )
			->setExchangeRate( 1.0 )// TODO: Verify this perhaps with Txn
			->setCurrency( $charge->currency )
			->setComments(
				serialize(
					[
						'local_payment_id'  => $charge->getLocalPaymentId(),
						'gateway'           => $charge->gateway,
						'gateway_charge_id' => $charge->id
					]
				)
			)
			->addInvoiceItemVOs( $this->buildLineItemsFromCartItem() );

		if ( $charge->is_vatmoss ) {
			$creator->setEcStatusVatMoss()
					->setEcPlaceOfSupply( $charge->country ?? $contact->country );
		}
		else {
			$creator->setEcStatus( $charge->ec_status );
		}

		$exportedInvoice = $creator->create();

		if ( $exportedInvoice instanceof Invoices\InvoiceVO ) {
			sleep( 5 );
			$exportedInvoice = $this->markInvoiceAsSent( $exportedInvoice );
		}
		else {
//			var_dump( $creator->getRawDataAsArray() );
			throw new \Exception( sprintf( 'Could not create invoice for Charge %s: %s',
				$charge->id, $creator->getLastError()->getMessage() ) );
		}

		$this->getBridge()->storeFreeagentInvoiceIdForCharge( $charge, $exportedInvoice );

		return $exportedInvoice;
	}

	protected function markInvoiceAsSent( Invoices\InvoiceVO $invoice ) :Invoices\InvoiceVO {
		( new Invoices\MarkAs() )
			->setConnection( $this->getConnection() )
			->setEntityId( $invoice->getId() )
			->sent();
		sleep( 2 );
		return ( new Invoices\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $invoice->getId() )
			->retrieve();
	}

	/**
	 * @return Invoices\Items\InvoiceItemVO[]
	 */
	protected function buildLineItemsFromCartItem() :array {
		$invItems = [];
		$charge = $this->getChargeVO();

		$item = ( new Invoices\Items\InvoiceItemVO() )
			->setType( $charge->getItemPeriodType() );
		$item->description = $charge->item_name;
		$item->quantity = $charge->getItemQuantity();
		$item->price = $charge->getItemSubtotal();
		$item->sales_tax_rate = $charge->getItemTaxRate();
		$item->category = 'https://api.freeagent.com/v2/categories/'.$this->getFreeagentConfigVO()->invoice_item_cat_id;

		$invItems[] = $item;
		return $invItems;
	}
}