<?php
/**
 * @author      rohith.mohan@equifax.com
 * @copyright   ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\helpers;

/**
 * Trait SmsModuleHelper
 */
trait SmsModuleHelper
{
	use SmsDidModuleHelper;

	/**
	 * @param string    $to
	 * @param string    $message
	 * @param integer   $event_id
	 * @param \DateTime $date
	 * @param string    $from
	 * @return boolean
	 */
	protected function create_campaign_sms($to, $message, $event_id = null, \DateTime $date = null, $from = null) {
		if (is_null($event_id)) {
			$event_id = api_misc_uniqueid();
		}

		if (!is_null($date)) {
			$date = new \DateTime();
		}

		$sms_account = $this->create_new_smsdid($from);

		$sql = 'INSERT INTO `sms_sent` (
					`eventid`,
					`supplier`,
					`supplieruid`,
					`timestamp`,
					`sms_account`,
					`to`,
					`contents`
				) VALUES (?, ?, ?, ?, ?, ?, ?)';

		api_db_query_write(
			$sql,
			[
				$event_id,
				2,
				rand(1, 127),
				$date->format('Y-m-d H:i:s'),
				$sms_account,
				$to,
				$message
			]
		);

		return api_db_lastid();
	}
}
