<?php

namespace Services\Suppliers\Yabbr;

use Models\Rest\RequestEnvelop;
use Services\Exceptions\Suppliers\PhoneNumberValidationServiceException;
use Services\Suppliers\Interfaces\PhoneNumberValidationServiceInterface;
use Services\Suppliers\Yabbr\Client\RestClient;

class PhoneNumberValidationService implements PhoneNumberValidationServiceInterface
{
    /**
     * @var RestClient
     */
    private $client;

    public function __construct(RestClient $client)
    {
        $this->client = $client;
    }

    public function postNumber($phoneNumber)
    {
        $envelop = new RequestEnvelop();
        $envelop
            ->setAction(ApiActions::POST_NUMBER_VALIDATION)
            ->setBody([
                'number' => $phoneNumber
            ]);

        $response = $this->client->send($envelop);

        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody(), true);
            if ($body['status'] != 'OK') {
                throw new PhoneNumberValidationServiceException("Phone number 
                validation failed. Response: " . $response->getBody());
            }
            return $body['id'];
        }

        throw new PhoneNumberValidationServiceException("Phone number
        validation failed. Response: " . $response->getBody());
    }

    public function retrieveResult($validationId)
    {
        $envelop = new RequestEnvelop();
        $envelop
            ->setAction(ApiActions::RETRIEVE_NUMBER_VALIDATION)
            ->setBody([
                'id' => $validationId
            ]);

        $response = $this->client->send($envelop);

        $body = $response->getBody();

        $body = json_decode($body, true);
        if ($response->getStatusCode() !== 200 || $body['status'] != 'OK') {
            throw new PhoneNumberValidationServiceException('Phone number
            validation result retrieval failed. Response: ' . $body);
        }

        if (isset($body['validations'][0]["receipts"]['connected'])) {
            return self::STATUS_CONNECTED;
        }

        if (isset($body['validations'][0]["receipts"]['indeterminate'])) {
            return self::STATUS_INDETERMINANT;
        }

        if (isset($body['validations'][0]["receipts"]['disconnected'])) {
            return self::STATUS_DISCONNECTED;
        }

        return self::STATUS_PENDING;
    }
}
