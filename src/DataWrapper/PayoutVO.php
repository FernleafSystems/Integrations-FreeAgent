<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class PayoutVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 */
class PayoutVO {

	use StdClassAdapter;

	/**
	 * @param ChargeVO|RefundVO $oItem
	 * @return $this
	 */
	public function addItem( $oItem ) {
		if ( $oItem instanceof ChargeVO ) {
			$this->addCharge( $oItem );
		}
		else if ( $oItem instanceof RefundVO ) {
			$this->addRefund( $oItem );
		}
		return $this;
	}

	/**
	 * @param ChargeVO $oCharge
	 * @return $this
	 */
	public function addCharge( $oCharge ) {
		if ( !$this->hasCharge( $oCharge ) ) {
			$aC = $this->getCharges();
			$aC[] = $oCharge;
			$this->setCharges( $aC );
		}
		return $this;
	}

	/**
	 * @param RefundVO $oRefund
	 * @return $this
	 */
	public function addRefund( $oRefund ) {
		if ( !$this->hasRefund( $oRefund ) ) {
			$aR = $this->getRefunds();
			$aR[] = $oRefund;
			$this->setRefunds( $aR );
		}
		return $this;
	}

	/**
	 * @return ChargeVO[]
	 */
	public function getCharges() {
		$a = $this->getArrayParam( 'charges' );
		return is_array( $a ) ? $a : array();
	}

	/**
	 * @return RefundVO[]
	 */
	public function getRefunds() {
		$a = $this->getArrayParam( 'refunds' );
		return is_array( $a ) ? $a : array();
	}

	/**
	 * @return string
	 */
	public function getCurrency() {
		return strtolower( $this->getStringParam( 'currency' ) );
	}

	/**
	 * @return int
	 */
	public function getDateArrival() {
		return $this->getParam( 'date_arrival' );
	}

	/**
	 * @return string
	 */
	public function getId() {
		return $this->getStringParam( 'id' );
	}

	/**
	 * @return string
	 */
	public function getExternalBankTxnId() {
		return $this->getParam( 'ext_bank_txn_id' );
	}

	/**
	 * @return string
	 */
	public function getExternalBillId() {
		return $this->getParam( 'ext_bill_id' );
	}

	/**
	 * @return string
	 */
	public function getGateway() {
		return $this->getStringParam( 'gateway' );
	}

	/**
	 * @return float
	 */
	public function getTotalGross() {
		return $this->getChargeTotalTally( 'amount_gross' );
	}

	/**
	 * @return float
	 */
	public function getTotalFee() {
		return $this->getChargeTotalTally( 'amount_fee' );
	}

	/**
	 * @return int
	 */
	public function getTotalNet() {
		return $this->getChargeTotalTally( 'amount_net' );
	}

	/**
	 * @param string $sKey
	 * @return float
	 */
	protected function getChargeTotalTally( $sKey ) {
		$nTotal = 0;
		foreach ( $this->getCharges() as $oCh ) {
			$nTotal += $oCh->getParam( $sKey );
		}
		return $nTotal;
	}

	/**
	 * @param ChargeVO $oChargeVO
	 * @return bool
	 */
	public function hasCharge( $oChargeVO ) {
		$bExists = false;
		foreach ( $this->getCharges() as $oCharge ) {
			if ( $oCharge->getId() == $oChargeVO->getId() ) {
				$bExists = true;
				break;
			}
		}
		return $bExists;
	}

	/**
	 * @param RefundVO $oRefund
	 * @return bool
	 */
	public function hasRefund( $oRefund ) {
		$bExists = false;
		foreach ( $this->getRefunds() as $oR ) {
			if ( $oR->getId() == $oRefund->getId() ) {
				$bExists = true;
				break;
			}
		}
		return $bExists;
	}

	/**
	 * @param ChargeVO[] $mVal
	 * @return $this
	 */
	public function setCharges( $mVal ) {
		return $this->setParam( 'charges', $mVal );
	}

	/**
	 * @param RefundVO[] $aR
	 * @return $this
	 */
	public function setRefunds( $aR ) {
		return $this->setParam( 'refunds', $aR );
	}

	/**
	 * @param string $mVal
	 * @return $this
	 */
	public function setCurrency( $mVal ) {
		return $this->setParam( 'currency', $mVal );
	}

	/**
	 * @param int $mVal
	 * @return $this
	 */
	public function setDateArrival( $mVal ) {
		return $this->setParam( 'date_arrival', $mVal );
	}

	/**
	 * @param int $mVal
	 * @return $this
	 */
	public function setExternalBankTxnId( $mVal ) {
		return $this->setParam( 'ext_bank_txn_id', $mVal );
	}

	/**
	 * @param int $mVal
	 * @return $this
	 */
	public function setExternalBillId( $mVal ) {
		return $this->setParam( 'ext_bill_id', $mVal );
	}

	/**
	 * @param string $sVal
	 * @return $this
	 */
	public function setGateway( $sVal ) {
		return $this->setParam( 'gateway', $sVal );
	}

	/**
	 * @param string $mVal
	 * @return $this
	 */
	public function setId( $mVal ) {
		return $this->setParam( 'id', $mVal );
	}
}