<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Sinch;

use Models\Rest\RequestEnvelop;
use Models\Sms;
use Services\Exceptions\Suppliers\SmsServiceException;
use Services\Suppliers\Interfaces\SmsSendableInterface;
use Services\Suppliers\Sinch\Client\RestClient;

/**
 * Class SmsService
 * @package Services\Suppliers\Sinch
 */
class SmsService implements SmsSendableInterface
{
    const SMS_FROM_KEY = 'from';
    const SMS_TO_KEY = 'to';
    const SMS_BODY_KEY = 'body';

    /**
     * @var RestClient
     */
    private $client;

    public function __construct(RestClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param Sms     $sms
     * @param integer $eventId
     * @return string | integer
     * @throws SmsServiceException
     */
    public function sendSms(Sms $sms, $eventId)
    {
        $body = [
            self::SMS_FROM_KEY => $sms->getFrom(),
            self::SMS_TO_KEY => [$sms->getTo()],
            self::SMS_BODY_KEY => $sms->getContent(),
            'delivery_report' => 'full'
        ];

        if (defined('SINCH_DELIVERY_REPORT_OVERRIDE_URL') && SINCH_DELIVERY_REPORT_OVERRIDE_URL) {
            $body['callback_url'] = SINCH_DELIVERY_REPORT_OVERRIDE_URL;
        }

        $envelop = $this->buildRequestEnvelop(ApiActions::BATCH_MESSAGE_SEND_ACTION, $body);

        $response = $this->client->send($envelop);

        if ($response->getStatusCode() === 201) {
            //TODO: Add code to send stuffs to librato
            $body = json_decode($response->getBody(), true);
            return $body['id'];
        }

        throw new SmsServiceException('Sms send failed. Response: ' . $response->getBody());
    }

    /**
     * @param string $action
     * @param mixed  $body
     * @param array  $parameters
     * @return RequestEnvelop
     */
    private function buildRequestEnvelop($action, $body = null, $parameters = [])
    {
        $envelop = new RequestEnvelop();
        return $envelop
            ->setAction($action)
            ->setBody($body)
            ->setParameters($parameters);
    }
}
