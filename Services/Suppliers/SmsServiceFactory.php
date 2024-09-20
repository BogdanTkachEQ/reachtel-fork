<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers;

use Services\Rest\MorpheusHttpClient;
use Services\Suppliers\Interfaces\SmsServiceInterface;
use Services\Suppliers\Sinch\ApiActions as SinchApiActions;
use Services\Suppliers\Sinch\Client\RestClient as SinchRestClient;
use Services\Suppliers\Sinch\SmsService as SinchSmsService;
use Services\Suppliers\Yabbr\ApiActions as YabbrApiActions;
use Services\Suppliers\Yabbr\Client\RestClient as YabbrRestClient;
use Services\Suppliers\Yabbr\SmsService as YabbrSmsService;

/**
 * Class SmsServiceFactory
 */
class SmsServiceFactory
{
    const SMS_SUPPLIER_SINCH_ID = 21;
    const SMS_SUPPLIER_YABBR_ID = 22;

    /**
     * @var array
     */
    private static $supplierIdServiceMap = [];

    /**
     * @param integer $supplierId
     * @return SmsServiceInterface
     * @throws \Exception
     */
    public static function getSmsService($supplierId)
    {
        if (!isset(static::$supplierIdServiceMap[$supplierId])) {
            static::$supplierIdServiceMap[$supplierId] = static::createSmsService($supplierId);
        }

        return static::$supplierIdServiceMap[$supplierId];
    }

    /**
     * @param integer $supplierId
     * @return SmsServiceInterface
     * @throws \Exception
     */
    private static function createSmsService($supplierId)
    {
        switch ($supplierId) {
            case self::SMS_SUPPLIER_SINCH_ID:
                $client = new SinchRestClient(
                    new MorpheusHttpClient(),
                    new SinchApiActions(SINCH_SERVICE_PLAN_ID),
                    SINCH_HOST_NAME,
                    SINCH_API_TOKEN
                );
                $service = new SinchSmsService($client);
                break;

            case self::SMS_SUPPLIER_YABBR_ID:
                $client = new YabbrRestClient(
                    new MorpheusHttpClient(),
                    new YabbrApiActions(),
                    YABBR_API_HOST_NAME,
                    YABBR_API_KEY
                );

                $service = new YabbrSmsService($client, (defined(YABBR_API_SIMULATED) && YABBR_API_SIMULATED));
                break;

            default:
                throw new \Exception('Invalid supplier id received');
        }

        return $service;
    }
}
