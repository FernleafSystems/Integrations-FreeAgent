<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Common\Constants;
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

		/**
		 * We'll try to divine what the status should be if it's not already set.
		 * Assumes UK-Based Freeagent Company
		 */
		if ( !isset( $charge->ec_status ) ) {

			if ( $charge->item_taxrate > 0 ) {
				if ( in_array( $contact->country, Constants::FREEAGENT_EU_COUNTRIES ) ) {
					$charge->ec_status = Constants::VAT_STATUS_EC_MOSS;
				}
				else {
					$charge->ec_status = Constants::VAT_STATUS_UK_NON_EC;
				}
			}
			elseif ( in_array( $contact->country, Constants::FREEAGENT_EU_COUNTRIES ) ) {
				$charge->ec_status = Constants::VAT_STATUS_REVERSE_CHARGE;
			}
			else {
				$charge->ec_status = Constants::VAT_STATUS_UK_NON_EC;
			}
		}

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

		$item = ( new Invoices\Items\InvoiceItemVO() )->setType( $charge->getItemPeriodType() );
		$item->description = $charge->item_name;
		$item->quantity = $charge->getItemQuantity();
		$item->price = $charge->getItemSubtotal();
		$item->sales_tax_rate = $charge->getItemTaxRate();
		$item->category = 'https://api.freeagent.com/v2/categories/'.$this->getFreeagentConfigVO()->invoice_item_cat_id;

		$invItems[] = $item;
		return $invItems;
	}
}