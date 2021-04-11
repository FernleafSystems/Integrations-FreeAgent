<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * Class FreeagentConfigVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
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
	 * @param string $key
	 * @return mixed
	 */
	public function __get( string $key ) {

		$value = parent::__get( $key );

		switch ( $key ) {
			case 'invoice_item_cat_id':
				$value = str_pad( $value, '3', '0', STR_PAD_LEFT );
				break;

			case 'foreign_currency_bills':
				$value = is_null( $value ) ? true : (bool)$value;
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
	 * @return array
	 */
	public function getAllBankAccounts() {
		$aAccounts = [];

		foreach ( $this->getRawDataAsArray() as $sKey => $mValue ) {
			if ( preg_match( '#^bank_account_id_[a-z]{3}$#i', $sKey ) ) {
				$sCurrency = str_replace( 'bank_account_id_', '', $sKey );
				$aAccounts[ $sCurrency ] = $mValue;
			}
		}

		return $aAccounts;
	}

	/**
	 * @param string $sCurrency
	 * @return int
	 */
	public function getBankAccountIdForCurrency( $sCurrency ) {
		$sParam = 'bank_account_id_'.strtolower( $sCurrency );
		return $this->{$sParam};
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getBankAccountIdEur() {
		return $this->bank_account_id_eur;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getBankAccountIdGbp() {
		return $this->bank_account_id_gbp;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getBankAccountIdUsd() {
		return $this->bank_account_id_usd;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getBankAccountIdForeignCurrencyTransfer() {
		return $this->bank_account_id_foreign;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getInvoiceItemCategoryId() {
		return $this->invoice_item_cat_id;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getBillCategoryId() {
		return $this->bill_cat_id;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getContactId() {
		return $this->contact_id;
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	public function isAutoCreateBankTransactions() {
		return $this->auto_create_bank_txn;
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	public function isAutoLocateBankTransactions() {
		return $this->auto_locate_bank_txn;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdEur( $value ) :self {
		$this->bank_account_id_eur = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdGbp( $value ) :self {
		$this->bank_account_id_gbp = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdUsd( $value ) :self {
		$this->bank_account_id_usd = $value;
		return $this;
	}

	/**
	 * @param string $currency
	 * @param int    $value
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdForCurrency( string $currency, $value ) :self {
		$this->{'bank_account_id_'.strtolower( $currency )} = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdForeignCurrencyTransfer( $value ) :self {
		$this->bank_account_id_foreign = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setBillCategoryId( $value ) :self {
		$this->bill_cat_id = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setBillContactId( $value ) :self {
		$this->contact_id = $value;
		return $this;
	}

	/**
	 * @param int $value
	 * @return $this
	 * @deprecated
	 */
	public function setInvoiceItemCategoryId( $value ) :self {
		$this->invoice_item_cat_id = $value;
		return $this;
	}

	/**
	 * @param bool $auto
	 * @return $this
	 * @deprecated
	 */
	public function setIsAutoCreateBankTransactions( $auto = true ) :self {
		$this->auto_create_bank_txn = $auto;
		return $this;
	}

	/**
	 * @param bool $auto
	 * @return $this
	 * @deprecated
	 */
	public function setIsAutoLocateBankTxns( $auto = true ) :self {
		$this->auto_locate_bank_txn = $auto;
		return $this;
	}
}