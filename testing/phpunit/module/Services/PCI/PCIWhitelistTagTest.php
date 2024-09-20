<?php
/**
 * PCIValidatorUnitTest
 *
 * @author		kevin.ohayon@reachtel.com.au
 * @copyright	ReachTel (ABN 40 133 677 933)
 */

namespace testing\module\Services\PCI;

use Services\PCI\PCIRecorder;
use testing\module\AbstractPhpunitModuleTest;
use testing\module\helpers\CampaignModuleHelper;

/**
 * PCIWhitelistTagTest
 */
class PCIWhitelistTagTest extends AbstractPhpunitModuleTest
{
	use CampaignModuleHelper;

	/**
	 * @return array
	 */
	public function campaignDataTagFilter() {
		// amex card
		$card = '378282246310005';
		// tag name
		$tag = 'pci-cards-whitelist';

		return [
			'No PCI and no campaign tags' => [
				false, 'targetkey-1'
			],
			'No PCI and empty campaign tag' => [
				false, 'targetkey-1', [$tag => '']
			],
			'No PCI and campaign tag is set' => [
				false, 'targetkey-1', [$tag => 'amex']
			],
			'PCI and campaign tag is set as string' => [
				false, $card, [$tag => 'amex']
			],
			'PCI and campaign tag is set as string comma delimited' => [
				false, $card, [$tag => 'amex,unionpay']
			],
			'PCI and campaign tag is set as array' => [
				false, $card, [$tag => ['amex', 'unionpay']]
			],
			'PCI and no campaign tags' => [
				true, $card
			],
			'PCI and empty campaign tag' => [
				true, $card, [$tag => '']
			],
			'PCI and campaign tag is set as string but does not match' => [
				true, $card, [$tag => 'visa']
			],
			'PCI and campaign tag is set as string comma delimited but does not match' => [
				true, $card, [$tag => 'visa,unionpay']
			],
			'PCI and campaign tag is set as array but does not match' => [
				true, $card, [$tag => ['visa', 'unionpay']]
			],
		];
	}

	/**
	 * @param boolean $expectedMatch
	 * @param string  $targetkey
	 * @param array   $tags
	 * @dataProvider campaignDataTagFilter
	 * @return void
	 */
	public function testCampaignDataTagFilter($expectedMatch, $targetkey, array $tags = []) {
		// create new campaign
		$campaign_id = $this->get_expected_next_campaign_id();
		$this->assertSameEquals(
			$campaign_id,
			$this->create_new_campaign(null, 'phone')
		);

		if ($tags) {
			api_campaigns_tags_set($campaign_id, $tags);
		}

		// recording PCI while adding a new target
		$recorder = PCIRecorder::getInstance();
		$recorder->start();
		api_targets_add_single($campaign_id, '0420111222', $targetkey);

		// check if PCI has been found
		$this->assertSameEquals(
			$expectedMatch,
			(bool) $recorder->getRecords()
		);
		$recorder->stop();
	}
}
