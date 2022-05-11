<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynProperties;

/**
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

	use DynProperties;

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
			$this->addAdjustment( $item );
		}
		return $this;
	}

	/**
	 * @param ChargeVO $charge
	 * @return $this
	 */
	public function addCharge( $charge ) {
		if ( !$this->hasCharge( $charge ) ) {
			$c = $this->getCharges();
			$c[] = $charge;
			$this->setCharges( $c );
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
			$r = $this->getRefunds();
			$r[] = $oRefund;
			$this->setRefunds( $r );
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
	 * @param RefundVO $refund
	 * @return bool
	 */
	public function hasRefund( $refund ) :bool {
		$exists = false;
		foreach ( $this->getRefunds() as $r ) {
			if ( $r->id == $refund->id ) {
				$exists = true;
				break;
			}
		}
		return $exists;
	}

	/**
	 * @param ChargeVO[] $charges
	 * @return $this
	 */
	public function setCharges( array $charges ) :self {
		$this->charges = $charges;
		return $this;
	}

	/**
	 * @param RefundVO[] $r
	 * @return $this
	 */
	public function setRefunds( array $r ) :self {
		$this->refunds = $r;
		return $this;
	}

	/**
	 * @param string $value
	 * @return $this
	 * @deprecated
	 */
	public function setCurrency( $value ) {
		$this->currency = $value;
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
	 * @param int $id
	 * @return $this
	 * @deprecated
	 */
	public function setExternalBankTxnId( $id ) {
		$this->ext_bank_txn_id = $id;
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
		return $this->gateway;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getDateArrival() {
		return $this->date_arrival;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getExternalBankTxnId() {
		return $this->ext_bank_txn_id;
	}
}