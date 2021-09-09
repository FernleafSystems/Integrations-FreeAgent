<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Service\PayPal;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * @property string  $id
 * @property string  $status                - e.g. COMPLETED
 * @property array[] $amount_with_breakdown - gross_amount, fee_amount, net_amount (each with ["currency_code","value"]
 * @property array   $payer_name
 * @property array   $payer_email
 * @property string  $time                  - e.g. 2021-09-08T12:47:08.000Z
 *
 * ####### VIRTUAL ######
 * @property string  $gross_value
 * @property string  $fee_value
 * @property string  $net_value
 * @property string  $currency
 * @property int     $ts
 */
class TransactionVO extends DynPropertiesClass {

	public function __get( string $key ) {
		$value = parent::__get( $key );

		switch ( $key ) {
			case 'gross_value':
				$value = $this->amount_with_breakdown[ 'gross_amount' ][ 'value' ];
				break;
			case 'fee_value':
				$value = $this->amount_with_breakdown[ 'fee_amount' ][ 'value' ];
				break;
			case 'net_value':
				$value = $this->amount_with_breakdown[ 'net_amount' ][ 'value' ];
				break;
			case 'currency':
				$value = $this->amount_with_breakdown[ 'gross_amount' ][ 'currency_code' ];
				break;
			case 'ts':
				$value = strtotime( $this->time );
				break;
			default:
				break;
		}

		return $value;
	}
}