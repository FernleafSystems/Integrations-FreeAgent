<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge;

use FernleafSystems\Integrations\Freeagent;

abstract class StandardBridge implements Freeagent\Reconciliation\Bridge\BridgeInterface {

	use Freeagent\Consumers\FreeagentConfigVoConsumer;
}