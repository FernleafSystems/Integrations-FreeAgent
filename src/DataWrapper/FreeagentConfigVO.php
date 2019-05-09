<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

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
 */
class FreeagentConfigVO {

	use StdClassAdapter;

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
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdEur( $nVal ) {
		return $this->setParam( 'bank_account_id_eur', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdGbp( $nVal ) {
		return $this->setParam( 'bank_account_id_gbp', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdUsd( $nVal ) {
		return $this->setParam( 'bank_account_id_usd', $nVal );
	}

	/**
	 * @param string $sCurrency
	 * @param int    $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdForCurrency( $sCurrency, $nVal ) {
		return $this->setParam( 'bank_account_id_'.strtolower( $sCurrency ), $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setBankAccountIdForeignCurrencyTransfer( $nVal ) {
		return $this->setParam( 'bank_account_id_foreign', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setBillCategoryId( $nVal ) {
		return $this->setParam( 'bill_cat_id', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setBillContactId( $nVal ) {
		return $this->setParam( 'contact_id', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 * @deprecated
	 */
	public function setInvoiceItemCategoryId( $nVal ) {
		return $this->setParam( 'invoice_item_cat_id', $nVal );
	}

	/**
	 * @param bool $bAutoCreate
	 * @return $this
	 * @deprecated
	 */
	public function setIsAutoCreateBankTransactions( $bAutoCreate = true ) {
		return $this->setParam( 'auto_create_bank_txn', $bAutoCreate );
	}

	/**
	 * @param bool $bAutoLocate
	 * @return $this
	 * @deprecated
	 */
	public function setIsAutoLocateBankTxns( $bAutoLocate = true ) {
		return $this->setParam( 'auto_locate_bank_txn', $bAutoLocate );
	}
}