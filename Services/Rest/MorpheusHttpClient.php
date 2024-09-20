<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rest;

use Guzzle\Http\Client;
use Services\Rest\Interfaces\MorpheusClientInterface;

/**
 * Class MorpheusHttpClient
 * @package Services\Rest
 */
class MorpheusHttpClient extends Client implements MorpheusClientInterface
{

}
