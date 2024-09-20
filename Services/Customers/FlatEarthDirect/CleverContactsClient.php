<?php
/**
 * Clever Contacts API Client
 *
 * @author christopher.colborne@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Customers\FlatEarthDirect;

use DateTime;
use DateTimeZone;
use Services\Customers\FlatEarthDirect\Utils\Constants;

class CleverContactsClient
{
    /** @var string */
    const TAG_NAME_EMAIL = 'api-error-email-destination';

    /** @var string */
    private $endpoint;

    /** @var string */
    private $apiUser;

    /** @var string */
    private $apiKey;

    /** @var string */
    private $errorMessage = '';

    /** @var string */
    private $response = '';

    /** @var int */
    private $responseCode;

    public function __construct($endpoint, $apiUser, $apiKey)
    {
        $this->endpoint = $endpoint;
        $this->apiUser = $apiUser;
        $this->apiKey = $apiKey;
    }

    /**
     * Submit message details to Clever Contacts API
     *
     * @param array $message
     * @param bool  $emailOnError
     * @return bool
     */
    public function submit(array $message, $emailOnError = true)
    {
        $id = $message['supplieruid'];
        $from = $message['e164'];
        $smsDidName = $message['sms_did_name'];
        $contents = $message['contents'];
        $target = isset($message['target']) ? $message['target'] : [];
        $campaignId = isset($target['campaignid']) ? $target['campaignid'] : '';
        $postFields = [
            'APIUser' => $this->apiUser,
            'APIKey' => $this->apiKey,

            // this is similar to the restpostback.smsreceive payload, but with Clever Contacts field names
            "custom_reference" => $id,
            "phone_number" => $from,
            "api_source" => 'ReachTEL: ' . $smsDidName,
            "message_text" => $contents,
            "campaign_id" => $campaignId,
        ];

        $headers = [
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($postFields),
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if (defined('PROXY_EXTERNAL')) {
            curl_setopt($curl, CURLOPT_PROXY, PROXY_EXTERNAL);
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $this->setResponseCode($responseCode);

        curl_close($curl);

        if ($err) {
            $this->setErrorMessage($err);
            //@FIXME REACHTEL-193 disabled emails, we got too many 5xx errors
            api_misc_audit('CLEVER_CONTACTS', "CURL ERROR: {$err}");
            if ($emailOnError) {
                $this->sendErrorEmail($message);
            }
            return false; // set true if you want to disable emails in inbound scripts
        }

        // Error out for a non 2** status code
        if (substr((string)$responseCode, 0, 1) !== '2') {
            $this->setErrorMessage('HTTP ' . $responseCode . ': ' . $response);
            //@FIXME REACHTEL-193 disabled emails, we got too many 5xx errors
            api_misc_audit('CLEVER_CONTACTS', 'HTTP ' . $responseCode . ': ' . $response);
            if ($emailOnError) {
                $this->sendErrorEmail($message);
            }
            return false; // set true if you want to disable emails in inbound scripts
        }

        $this->setResponse($response);

        return true;
    }

    /**
     * @param array $message
     * @return void
     */
    private function sendErrorEmail(array $message)
    {
        $tags = api_groups_tags_get(Constants::USER_GROUP_ID);
        $to = isset($tags[self::TAG_NAME_EMAIL]) ? $tags[self::TAG_NAME_EMAIL] : 'AUReachTELITSupport@equifax.com';

        $err = $this->getErrorMessage();
        $created = (new DateTime())->createFromFormat('U', $message['received']);
        $created->setTimeZone(new DateTimeZone('Australia/Brisbane'));
        $created = $created->format(DateTime::RFC2822);
        $from = $message['e164'];
        $smsDidName = $message['sms_did_name'];
        $smsDidId = $message['sms_account'];

        $email = [
            'to' => $to,
            'from' => 'support@reachtel.com.au',
            'subject' => '[ReachTEL] Clever Contacts API Error',
            'content' => <<<EOF
Hi,

There was an error submitting a received SMS to the Clever Contacts API.

CURL Error: `$err`

SMS DID: $smsDidName ($smsDidId)
Received at: $created
From: $from
EOF
        ];

        api_email_template($email);
    }

    /**
     * Set error message
     *
     * @param string $errorMessage
     * @return CleverContactsClient
     */
    private function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * Return last error message
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Set response
     *
     * @param string $response
     * @return CleverContactsClient
     */
    private function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Return last response
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set response code
     *
     * @param int $responseCode
     * @return CleverContactsClient
     */
    private function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;

        return $this;
    }

    /**
     * Return last response code
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }
}
