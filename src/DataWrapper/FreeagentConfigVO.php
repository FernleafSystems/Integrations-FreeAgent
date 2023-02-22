<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * @property int  $bank_account_id_foreign
 * @property int  $bank_account_id_gbp
 * @property int  $bank_account_id_eur
 * @property int  $bank_account_id_usd
 * @property int  $invoice_item_cat_id
 * @property int  $bill_cat_id
 * @property int  $contact_id            - for payment process bills, such as Stripe / PayPal
 * @property bool $auto_create_bank_txn
 * @property bool $auto_locate_bank_txn
 * @property bool $foreign_currency_bills
 * @property bool $use_recurring_invoices
 * @property int  $invoice_payment_terms
 */
class FreeagentConfigVO extends DynPropertiesClass {

	/**
	 * @return mixed
	 */
	public function __get( string $key ) {

		$value = parent::__get( $key );

		switch ( $key ) {
			case 'invoice_item_cat_id':
				$value = str_pad( $value, '3', '0', STR_PAD_LEFT );
				break;

			case 'foreign_currency_bills':
				$value = is_null( $value ) || $value;
				break;

			case 'invoice_payment_terms':
				$value = is_null( $value ) ? 14 : (int)$value;
				break;
			default:
				break;
		}

		return $value;
	}

	/**
	 * Where the key is the ISO3 code for currency and the value is the bank account ID
	 */
	public function getAllBankAccounts() :array {
		$accounts = [];

		foreach ( $this->getRawData() as $key => $value ) {
			if ( preg_match( '#^bank_account_id_[a-z]{3}$#i', $key ) ) {
				$currency = str_replace( 'bank_account_id_', '', $key );
				$accounts[ $currency ] = $value;
			}
		}

		return $accounts;
	}

	public function getBankAccountIdForCurrency( string $currency ) :int {
		return (int)$this->{'bank_account_id_'.strtolower( $currency )};
	}
}