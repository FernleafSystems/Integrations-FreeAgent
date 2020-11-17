<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class PayoutVO
 * @package FernleafSystems\Integrations\Freeagent\DataWrapper
 * @property string         $id
 * @property string         $currency
 * @property string         $gateway
 * @property ChargeVO[]     $charges
 * @property RefundVO[]     $refunds
 * @property AdjustmentVO[] $adjustments
 * @property int            $date_arrival
 * @property int            $ext_bank_txn_id
 * @property int            $ext_bill_id
 */
class PayoutVO {

	use StdClassAdapter;

	/**
	 * @param ChargeVO|RefundVO $item
	 * @return $this
	 */
	public function addItem( $item ) {
		if ( $item instanceof ChargeVO ) {
			$this->addCharge( $item );
		}
		elseif ( $item instanceof RefundVO ) {
			$this->addRefund( $item );
		}
		elseif ( $item instanceof AdjustmentVO ) {
			$this->addRefund( $item );
		}
		return $this;
	}

	/**
	 * @param ChargeVO $charge
	 * @return $this
	 */
	public function addCharge( $charge ) {
		if ( !$this->hasCharge( $charge ) ) {
			$aC = $this->getCharges();
			$aC[] = $charge;
			$this->setCharges( $aC );
		}
		return $this;
	}

	public function addAdjustment( AdjustmentVO $adjustment ) :self {
		$adjustments = $this->adjustments ?? [];
		if ( empty( $adjustments[ $adjustment->id ] ) ) {
			$adjustments[ $adjustment->id ] = $adjustment;
			$this->adjustments = $adjustments;
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
		return strtolower( $this->currency );
	}

	/**
	 * @return string
	 */
	public function getExternalBillId() {
		return $this->ext_bill_id;
	}

	/**
	 * @return float
	 */
	public function getTotalGross() {
		return bcadd(
			$this->getChargeTotalTally( 'amount_gross' ),
			bcadd(
				$this->getRefundTotalTally( 'amount_gross' ),
				$this->getAdjustmentsTotalTally( 'amount_gross' ),
				2
			),
			2
		);
	}

	/**
	 * @return float
	 */
	public function getTotalFee() {
		return bcadd(
			$this->getChargeTotalTally( 'amount_fee' ),
			bcadd(
				$this->getRefundTotalTally( 'amount_fee' ),
				$this->getAdjustmentsTotalTally( 'amount_fee' ),
				2
			),
			2
		);
	}

	/**
	 * @return int
	 */
	public function getTotalNet() {
		return bcadd(
			$this->getChargeTotalTally( 'amount_net' ),
			bcadd(
				$this->getRefundTotalTally( 'amount_net' ),
				$this->getAdjustmentsTotalTally( 'amount_net' ),
				2
			),
			2
		);
	}

	/**
	 * @param string $key
	 * @return float
	 */
	protected function getAdjustmentsTotalTally( string $key ) {
		$total = 0;
		foreach ( $this->adjustments ?? [] as $adj ) {
			$total = bcadd( $total, $adj->{$key}, 2 );
		}
		return $total;
	}

	/**
	 * @param string $key
	 * @return float
	 */
	protected function getChargeTotalTally( string $key ) {
		$total = 0;
		foreach ( $this->charges ?? [] as $ch ) {
			$total = bcadd( $total, $ch->{$key}, 2 );
		}
		return $total;
	}

	/**
	 * @param string $key
	 * @return float
	 */
	protected function getRefundTotalTally( string $key ) {
		$total = 0;
		foreach ( $this->refunds ?? [] as $ref ) {
			$total = bcadd( $total, $ref->{$key}, 2 );
		}
		return $total;
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
			if ( $oR->id == $oRefund->id ) {
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
		return $this->id;
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