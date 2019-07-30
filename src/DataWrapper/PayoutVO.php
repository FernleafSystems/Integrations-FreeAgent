<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class PayoutVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property ChargeVO[] $charges
 * @property RefundVO[] $refunds
 * @property string     $currency
 * @property int        $date_arrival
 * @property int        $ext_bank_txn_id
 * @property int        $ext_bill_id
 * @property string     $gateway
 * @property string     $id
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
		return is_array( $this->charges ) ? $this->charges : [];
	}

	/**
	 * @return RefundVO[]
	 */
	public function getRefunds() {
		return is_array( $this->refunds ) ? $this->refunds : [];
	}

	/**
	 * @return string
	 */
	public function getCurrency() {
		return strtolower( $this->getStringParam( 'currency' ) );
	}

	/**
	 * @return string
	 */
	public function getExternalBillId() {
		return $this->getParam( 'ext_bill_id' );
	}

	/**
	 * @return float
	 */
	public function getTotalGross() {
		return bcadd( $this->getChargeTotalTally( 'amount_gross' ), $this->getRefundTotalTally( 'amount_gross' ), 2 );
	}

	/**
	 * @return float
	 */
	public function getTotalFee() {
		return bcadd( $this->getChargeTotalTally( 'amount_fee' ), $this->getRefundTotalTally( 'amount_fee' ), 2 );
	}

	/**
	 * @return int
	 */
	public function getTotalNet() {
		return bcadd( $this->getChargeTotalTally( 'amount_net' ), $this->getRefundTotalTally( 'amount_net' ), 2 );
	}

	/**
	 * @param string $sKey
	 * @return float
	 */
	protected function getChargeTotalTally( $sKey ) {
		$nTotal = 0;
		foreach ( $this->getCharges() as $oCh ) {
			$nTotal = bcadd( $nTotal, $oCh->{$sKey}, 2 );
		}
		return $nTotal;
	}

	/**
	 * @param string $sKey
	 * @return float
	 */
	protected function getRefundTotalTally( $sKey ) {
		$nTotal = 0;
		foreach ( $this->getRefunds() as $oR ) {
			$nTotal = bcadd( $nTotal, $oR->{$sKey}, 2 );
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
			if ( $oCharge->id == $oChargeVO->id ) {
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
	 * @param ChargeVO[] $aC
	 * @return $this
	 */
	public function setCharges( $aC ) {
		$this->charges = $aC;
		return $this;
	}

	/**
	 * @param RefundVO[] $aR
	 * @return $this
	 */
	public function setRefunds( $aR ) {
		$this->refunds = $aR;
		return $this;
	}

	/**
	 * @param string $mVal
	 * @return $this
	 * @deprecated
	 */
	public function setCurrency( $mVal ) {
		$this->currency = $mVal;
		return $this;
	}

	/**
	 * @param int $mVal
	 * @return $this
	 * @deprecated
	 */
	public function setDateArrival( $mVal ) {
		$this->date_arrival = $mVal;
		return $this;
	}

	/**
	 * @param int $mVal
	 * @return $this
	 * @deprecated
	 */
	public function setExternalBankTxnId( $mVal ) {
		$this->ext_bank_txn_id = $mVal;
		return $this;
	}

	/**
	 * @param int $mVal
	 * @return $this
	 * @deprecated
	 */
	public function setExternalBillId( $mVal ) {
		$this->ext_bill_id = $mVal;
		return $this;
	}

	/**
	 * @param string $mVal
	 * @return $this
	 */
	public function setGateway( $mVal ) {
		$this->gateway = $mVal;
		return $this;
	}

	/**
	 * @param string $mVal
	 * @return $this
	 */
	public function setId( $mVal ) {
		$this->id = $mVal;
		return $this;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getId() {
		return $this->getStringParam( 'id' );
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getGateway() {
		return $this->getStringParam( 'gateway' );
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getDateArrival() {
		return $this->getParam( 'date_arrival' );
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getExternalBankTxnId() {
		return $this->getParam( 'ext_bank_txn_id' );
	}
}