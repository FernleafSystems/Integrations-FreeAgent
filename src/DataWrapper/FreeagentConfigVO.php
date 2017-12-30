<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class FreeagentConfigVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 */
class FreeagentConfigVO {

	use StdClassAdapter;

	/**
	 * Where the key is the ISO3 code for currency and the value is the bank account ID
	 * @return array
	 */
	public function getAllBankAccounts() {
		$aAccounts = array();

		foreach ( $this->getRawDataAsArray() as $sKey => $mValue ) {
			if ( preg_match( '#^bank_account_id_[a-z]{3}$#i', $sKey )  ) {
				$sCurrency = str_replace( 'bank_account_id_', '', $sKey );
				$aAccounts[ $sCurrency ] = $mValue;
			}
		}

		return $aAccounts;
	}

	/**
	 * @return int
	 */
	public function getBankAccountIdEur() {
		return $this->getBankAccountIdForCurrency( 'eur' );
	}

	/**
	 * @return int
	 */
	public function getBankAccountIdGbp() {
		return $this->getBankAccountIdForCurrency( 'gbp' );
	}

	/**
	 * @return int
	 */
	public function getBankAccountIdUsd() {
		return $this->getBankAccountIdForCurrency( 'usd' );
	}

	/**
	 * @param string $sCurrency
	 * @return int
	 */
	public function getBankAccountIdForCurrency( $sCurrency ) {
		return $this->getNumericParam( 'bank_account_id_'.strtolower( $sCurrency ) );
	}

	/**
	 * @return int
	 */
	public function getBankAccountIdForeignCurrencyTransfer() {
		return $this->getNumericParam( 'bank_account_id_foreign' );
	}

	/**
	 * @return int
	 */
	public function getInvoiceItemCategoryId() {
		return $this->getNumericParam( 'invoice_item_cat_id' );
	}

	/**
	 * @return int
	 */
	public function getBillCategoryId() {
		return $this->getNumericParam( 'bill_cat_id' );
	}

	/**
	 * @return int
	 */
	public function getContactId() {
		return $this->getNumericParam( 'contact_id' );
	}

	/**
	 * @return bool
	 */
	public function isAutoCreateBankTransactions() {
		return (bool)$this->getParam( 'auto_create_bank_txn' );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setBankAccountIdEur( $nVal ) {
		return $this->setParam( 'bank_account_id_eur', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setBankAccountIdGbp( $nVal ) {
		return $this->setParam( 'bank_account_id_gbp', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setBankAccountIdUsd( $nVal ) {
		return $this->setParam( 'bank_account_id_usd', $nVal );
	}

	/**
	 * @param string $sCurrency
	 * @param int $nVal
	 * @return $this
	 */
	public function setBankAccountIdForCurrency( $sCurrency, $nVal ) {
		return $this->setParam( 'bank_account_id_'.strtolower( $sCurrency ), $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setBankAccountIdForeignCurrencyTransfer( $nVal ) {
		return $this->setParam( 'bank_account_id_foreign', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setInvoiceItemCategoryId( $nVal ) {
		return $this->setParam( 'invoice_item_cat_id', $nVal );
	}

	/**
	 * @param bool $bAutoCreate
	 * @return $this
	 */
	public function setIsAutoCreateBankTransactions( $bAutoCreate = true ) {
		return $this->setParam( 'auto_create_bank_txn', $bAutoCreate );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setBillCategoryId( $nVal ) {
		return $this->setParam( 'bill_cat_id', $nVal );
	}

	/**
	 * @param int $nVal
	 * @return $this
	 */
	public function setContactId( $nVal ) {
		return $this->setParam( 'contact_id', $nVal );
	}
}