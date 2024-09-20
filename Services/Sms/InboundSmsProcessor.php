<?php
/**
 * @author rohith.mohan@equifax.com
 * @copyright ReachTel (ABN 40 133 677 933)
 */

namespace Services\Sms;

use Models\Sms;
use Services\PCI\PCIValidator;

/**
 * Class InboundSmsProcessor
 * @package Services\Sms
 */
class InboundSmsProcessor
{
    /**
     * @param Sms $sms
     * @return boolean
     */
    public function saveSms(Sms $sms)
    {
        // This code is copied from smpp-rx-default.php
        // TODO: Update smpp-rx-default.php to call this function
        // TODO: Clean up code below and add unit test

        if (preg_match("/^614[0-9]{8}$/", $sms->getFrom())) {
            $from = "0" . substr($sms->getFrom(), 2);
        } elseif (preg_match("/^64/", $sms->getFrom())) {
            $from = "0" . substr($sms->getFrom(), 2);
        } else {
            $from = $sms->getFrom(); // We have an alphanumeric sender
        }

        if (preg_match("/^614[0-9]{8}$/", $sms->getTo())) {
            $to = "0" . substr($sms->getTo(), 2);
        }

        $sms_account = api_sms_dids_checkexists($sms->getTo());

        if (!is_numeric($sms_account)) {
            $sms_account = api_sms_dids_checkexists($to);
        }

        if (!is_numeric($sms_account)) {
            api_misc_audit(
                "SMSDID_ERROR",
                "Unidentified message recieved. Source: " . $sms->getFrom() . "; Destination=" . $sms->getTo()
            );
            return false;
        }

        $message = $sms->getContent();
        try {
            $pci_validator = new PCIValidator();
            if ($match = $pci_validator->matchAllPANData($sms->getContent())) {
                foreach ($match as $pci) {
                    $message = str_replace(
                        $pci,
                        $pci_validator->maskPANData($pci),
                        $message
                    );
                }
                api_error_audit("PCI_SMS_RECEIVE", "Message contains PCI data from SMSDID: {$sms_account}");
            }
        } catch (\Exception $e) {
            // PCIValidator should not throw exceptions, this being defensive
            api_error_audit("PCI_SMS_RECEIVE", "CRITICAL ERROR: " . $e->getMessage());
        }
        return api_sms_receive(time(), null, $sms_account, $from, $message, $sms->getFrom());
    }
}
