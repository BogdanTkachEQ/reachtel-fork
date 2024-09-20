<?php

/**
 * Function to allow campaign tag hooks to be tested
 *
 * @param $campaignId
 * @return bool
 */
function api_campaigns_hooks_testhook($campaignId){
	if($campaignId) {
		return true;
	}
	return false;
}
