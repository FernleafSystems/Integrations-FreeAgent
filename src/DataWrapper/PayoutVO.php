<?php

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * @property string         $id
 * @property string         $currency
 * @property string         $gateway
 * @property ChargeVO[]     $charges
 * @property RefundVO[]     $refunds
 * @property GatewayFeeVO[] $gateway_fees
 * @property AdjustmentVO[] $adjustments
 * @property int            $date_arrival
 * @property int            $ext_bank_txn_id
 * @property int            $ext_bill_id
 */
class PayoutVO extends DynPropertiesClass {

	public function __get( string $key ) {
		$value = parent::__get( $key );

		switch ( $key ) {
			case 'charges':
			case 'refunds':
			case 'gateway_fees':
			case 'adjustments':
				if ( !\is_array( $value ) ) {
					$value = [];
				}
				break;
			default:
				break;
		}

		return $value;
	}

	/**
	 * @param ChargeVO|RefundVO $item
	 * @return $this
	 */
	public function addItem( $item ) :self {
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

	public function addCharge( ChargeVO $charge ) :self {
		$charges = $this->charges;
		if ( !isset( $charges[ $charge->id ] ) ) {
			$charges[ $charge->id ] = $charge;
			$this->charges = $charges;
		}
		return $this;
	}

	public function addAdjustment( AdjustmentVO $adjustment ) :self {
		$adjustments = $this->adjustments;
		if ( empty( $adjustments[ $adjustment->id ] ) ) {
			$adjustments[ $adjustment->id ] = $adjustment;
			$this->adjustments = $adjustments;
		}
		return $this;
	}

	public function addRefund( RefundVO $refund ) :self {
		$refunds = $this->refunds;
		if ( !isset( $refunds[ $refund->id ] ) ) {
			$refunds[ $refund->id ] = $refund;
			$this->refunds = $refunds;
		}
		return $this;
	}

	public function addGatewayFee( GatewayFeeVO $fee ) :self {
		$fees = $this->gateway_fees;
		if ( !isset( $fees[ $fee->id ] ) ) {
			$fees[ $fee->id ] = $fee;
			$this->gateway_fees = $fees;
		}
		return $this;
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

	public function getTotalGross() :string {
		return $this->bcAddMultiple( array_merge(
			array_map( fn( $VO ) => $VO->amount_gross, $this->charges ),
			array_map( fn( $VO ) => $VO->amount_gross, $this->refunds ),
			array_map( fn( $VO ) => $VO->amount_gross, $this->adjustments ),
		) );
	}

	public function getTotalFee() :string {
		return $this->bcAddMultiple( array_merge(
			array_map( fn( $VO ) => $VO->amount_fee, $this->charges ),
			array_map( fn( $VO ) => $VO->amount_fee, $this->refunds ),
			array_map( fn( $VO ) => $VO->amount_fee, $this->adjustments ),
			array_map( fn( $VO ) => $VO->amount, $this->gateway_fees ),
		) );
	}

	/**
	 * The supplementary gateway fees are "positive" values and are not taken from the charges' gross amounts.
	 * So they must be subtracted separately from the total net.
	 */
	public function getTotalNet() :string {
		return \bcsub(
			$this->bcAddMultiple( array_merge(
				\array_map( fn( $VO ) => $VO->amount_net, $this->charges ),
				\array_map( fn( $VO ) => $VO->amount_net, $this->refunds ),
				\array_map( fn( $VO ) => $VO->amount_net, $this->adjustments ),
			) ),
			$this->bcAddMultiple( array_map( fn( $VO ) => $VO->amount, $this->gateway_fees ) ),
			2
		);
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

	private function bcAddMultiple( $toAdd ) :string {
		$total = '0';
		foreach ( $toAdd as $add ) {
			$total = \bcadd( $total, $add, 2 );
		}
		return $total;
	}
}