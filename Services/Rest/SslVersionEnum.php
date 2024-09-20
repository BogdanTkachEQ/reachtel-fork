<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace Services\Rest;

use MabeEnum\Enum;

/**
 * Class SslVersionEnum
 */
class SslVersionEnum extends Enum
{
    const TLS1_2 = CURL_SSLVERSION_TLSv1_2;
}
