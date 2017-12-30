<?php

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bills;

use FernleafSystems\Integrations\Freeagent\Consumers\PayoutVoConsumer;

/**
 * Class SumTotalFeesForPayout
 * @package FernleafSystems\Integrations\Freeagent\Reconciliation\Bills
 */
abstract class SumTotalFeesForPayout {

	use PayoutVoConsumer;

	abstract public function count();
}