<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\DataWrapper;

use FernleafSystems\ApiWrappers\Freeagent\Entities\Common\Constants;

/**
 * @property string $gateway
 * @property string $item_name
 * @property string $country
 * @property bool   $is_vatmoss
 * @property string $ec_status
 * @property int    $payment_terms - days
 * @property int    $item_quantity
 * @property float  $item_subtotal
 * @property float  $item_taxrate
 * @property string $item_type
 * @property mixed  $local_payment_id
 */
class ChargeVO extends BaseTxnVO {

	public function __get( string $key ) {
		$val = parent::__get( $key );
		switch ( $key ) {
			case 'payment_terms':
				if ( $val === null ) {
					$val = 10;
				}
				break;
			case 'is_vatmoss':
				if ( $val === null ) {
					$val = $this->ec_status === Constants::VAT_STATUS_EC_MOSS;
				}
				break;
			case 'item_quantity':
				if ( $val === null ) {
					$val = 1;
				}
				break;
			case 'ec_status':
				if ( $val === null ) {
					$val = Constants::VAT_STATUS_UK_NON_EC;
				}
				break;
			case 'item_taxrate':
				// out of 100%
				if ( $val > 0 && $val < 1 ) {
					$val *= 100;
				}
				$val = \abs( \round( $val, 2 ) );
				break;
			case 'item_type':
				if ( empty( $val ) ) {
					$val = 'Years';
				}
				$singular = \strtolower( \rtrim( $val, 's' ) );
				if ( \in_array( $singular, [ 'hour', 'day', 'week', 'month', 'year' ] ) ) {
					$val = \ucfirst( \strtolower( \rtrim( $val, 's' ).'s' ) );
				}
				break;
			default:
				break;
		}
		return $val;
	}
}