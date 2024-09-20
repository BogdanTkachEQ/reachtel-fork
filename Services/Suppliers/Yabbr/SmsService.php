<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Suppliers\Yabbr;

use Models\Rest\RequestEnvelop;
use Models\Sms;
use Services\Exceptions\Suppliers\SmsServiceException;
use Services\Suppliers\Interfaces\SmsRetrievableInterface;
use Services\Suppliers\Interfaces\SmsSendableInterface;
use Services\Suppliers\SmsServiceFactory;
use Services\Suppliers\Yabbr\Client\RestClient;

/**
 * Class SmsService
 */
class SmsService implements SmsSendableInterface, SmsRetrievableInterface
{
    /**
     * @var RestClient
     */
    private $client;

    /**
     * @var boolean
     */
    private $isSimulated;

    /**
     * SmsService constructor.
     * @param RestClient $client
     * @param boolean    $isSimulated
     */
    public function __construct(RestClient $client, $isSimulated = false)
    {
        $this->client = $client;
        $this->isSimulated = $isSimulated;
    }

    /**
     * @param Sms     $sms
     * @param integer $eventId
     * @return string | integer
     * @throws SmsServiceException
     */
    public function sendSms(Sms $sms, $eventId)
    {
        $envelop = new RequestEnvelop();
        $envelop
            ->setAction(ApiActions::SEND_MESSAGES_ACTION)
            ->setBody([
                'to' => $sms->getTo(),
                'from' => $sms->getFrom(),
                'content' => $sms->getContent(),
                'type' => 'sms',
                'simulated' => $this->isSimulated
            ]);

        $response = $this->client->send($envelop);

        if ($response->getStatusCode() === 200) {
             $body = json_decode($response->getBody(), true);
             return $body['messages'][0]['id'];
        }

        throw new SmsServiceException('Sms send failed. Response: ' . $response->getBody());
    }

    /**
     * @param string | integer $messageId
     * @return Sms
     * @throws SmsServiceException
     */
    public function retrieveSms($messageId)
    {
        $envelop = new RequestEnvelop();
        $envelop
            ->setAction(ApiActions::RETRIEVE_MESSAGE_ACTION)
            ->setBody([
                'id' => $messageId
            ]);

        $response = $this->client->send($envelop);

        $body = $response->getBody();
        if ($response->getStatusCode() !== 200) {
            throw new SmsServiceException('Sms retrieve failure. Response: ' . $body);
        }

        $body = json_decode($body, true);
        $sms = new Sms();

        $message = $body['messages'][0];

        $sms
            ->setFrom($message['from'])
            ->setTo($message['to'])
            ->setContent($message['content'])
            ->setId($messageId)
            ->setStatus(SmsFunctions::getStatusFromReceipts($message['receipts']))
            ->setStatusUpdateTime(SmsFunctions::getStatusUpdateDateTimeFromReceipts($message['receipts']));

        $sms->getDeliveryReceipt()->setSupplierId(SmsServiceFactory::SMS_SUPPLIER_YABBR_ID);

        return $sms;
    }
}
