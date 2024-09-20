<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Plotter;

use Services\Plotter\Interfaces\PlotterDataExtractionStrategyInterface;
use Services\Utils\StringFunctions;
use Services\Exceptions\InvalidEmailException;

/**
 * Class PlotterDataExtractionContext
 */
class PlotterDataExtractionContext
{

    /** @var PlotterDataExtractionStrategyInterface */
    private $dataExtractionStrategy;

    /** @var integer */
    private $campaignId;

    /** @var string */
    private $notificationEmails;

    /** @var integer */
    private $userId;

    /**
     * PlotterDataExtractionContext Constructor
     * @param PlotterDataExtractionStrategyInterface $strategy
     * @param integer                                $userId
     * @param integer                                $campaignId
     * @param string                                 $notificationEmails
     */
    public function __construct(
        PlotterDataExtractionStrategyInterface $strategy,
        $userId,
        $campaignId,
        $notificationEmails
    ) {
        $this->dataExtractionStrategy = $strategy;
        $this->campaignId = $campaignId;
        $this->notificationEmails = $notificationEmails;
        $this->userId = $userId;
    }

    /**
     * @param array   $extractionParams
     * @param array   $onSuccessNotificationEmailParams
     * @param boolean $notifyOnProcessFailure
     * @return boolean
     * @throws \Exception
     */
    public function extractAndUpdateCampaign(
        array $extractionParams,
        array $onSuccessNotificationEmailParams,
        $notifyOnProcessFailure = true
    ) {
        try {
            self::runValidations($this->campaignId, $this->userId, $this->notificationEmails);
        } catch (InvalidEmailException $e) {
            api_error_raise(
                'Invalid notification emails passed for plotter data extraction for campaign id: ' . $this->campaignId
            );
            throw $e;
        } catch (\Exception $e) {
            $this->notify(
                '[ReachTEL] Plotter data extract failure',
                'Failed to extract data from plotter for the campaign ' .
                $this->campaignId .
                '. Data we received to carry out extraction seems to be invalid. Please contact support.'
            );
            api_error_raise($e->getMessage());
            throw $e;
        }

        $this->notify(
            '[ReachTEL] Plotter data extraction notification',
            'We have started the job to extract data for the campaign ' .
            $this->campaignId .
            '. You will get another notification when the job is completed.'
        );

        try {
            $extract = $this->dataExtractionStrategy->extractData($extractionParams);
            $data = $extract->getExtractedData();
            $warningString = '';
            if ($extract->getWarnings()) {
                $warningString = "\n\nWarnings received: " . implode(', ', $extract->getWarnings());
            }

            if (!$data) {
                $this->notify(
                    '[ReachTEL] Plotter data extract notification',
                    'No data was retrieved for upload to campaign id:' .
                    $this->campaignId .
                    $warningString
                );
                return true;
            }
        } catch (\InvalidArgumentException $e) {
            api_error_raise($e->getMessage());
            $this->notify(
                '[ReachTEL] Plotter data extract failure',
                'There was an issue with the data we received for performing export to campaign id:' .
                $this->campaignId .
                '. Please contact support.'
            );
            throw $e;
        }

        if ($this->uploadDataToCampaign($data)) {
            return $this->notify(
                $onSuccessNotificationEmailParams['subject'],
                $onSuccessNotificationEmailParams['body'] . $warningString
            );
        }

        if ($notifyOnProcessFailure) {
            $this->notify(
                '[ReachTEL] Plotter data extract failure',
                'An error occurred when uploading plotter data to campaign id: ' .
                $this->campaignId .
                '. Please contact support.' .
                $warningString
            );
        }

        return api_error_raise('Error while uploading plotter extracts to campaign id:' . $this->campaignId);
    }

    /**
     * @param integer $campaignId
     * @param integer $userId
     * @param string  $emails
     * @return boolean
     */
    public static function runValidations($campaignId, $userId, $emails = null)
    {
        if (!is_null($emails)) {
            $emails = explode(',', $emails);

            foreach ($emails as $email) {
                if (!StringFunctions::validateEmail($email)) {
                    throw new InvalidEmailException('Invalid notification email passed');
                }
            }
        }

        if (api_campaigns_setting_getsingle($campaignId, CAMPAIGN_SETTING_OWNER) !== (string) $userId) {
            throw new \RuntimeException(
                'User id:' . $userId . ' does not have permission to update campaign id:' . $campaignId
            );
        }

        return true;
    }

    /**
     * @param array $data
     * @return boolean
     */
    private function uploadDataToCampaign(array $data)
    {
        $filename = 'Plotter-extract-' . $this->campaignId . '-' . api_misc_uniqueid();
        $tmpFile = tempnam('/tmp', $filename);
        if (!api_csv_file($tmpFile, $data)) {
            return api_error_raise('Failed to write data to csv before performing target upload.');
        }

        if (!api_targets_fileupload($this->campaignId, $tmpFile, $filename . '.csv', true)) {
            return false;
        }

        // Disable download for the campaign for plotter upload
        api_campaigns_disable_download($this->campaignId);

        return api_targets_dedupe($this->campaignId);
    }

    /**
     * @param string $subject
     * @param string $text
     * @return boolean
     */
    private function notify($subject, $text)
    {
        $email["to"] = $this->notificationEmails;
        $email["cc"] = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["from"] = "ReachTEL Support <support@ReachTEL.com.au>";
        $email["subject"] = $subject;
        $email["content"] = $text;

        if (!api_email_template($email)) {
            throw new \RuntimeException('Failed to send notification email');
        }

        return true;
    }
}
