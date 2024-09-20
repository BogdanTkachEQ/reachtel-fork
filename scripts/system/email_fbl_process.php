<?php

require_once("Morpheus/api.php");

$mbox = imap_open(EMAIL_IMAP_CONNECTION, EMAIL_FBL_USERNAME, EMAIL_FBL_PASSWORD) or die("Connection to server failed");

$MB = imap_check($mbox);

if($MB->Nmsgs == 0) exit;

$messages = imap_fetch_overview($mbox, "1:" . $MB->Nmsgs, 0);

foreach($messages as $message){

	if(preg_match("/X-Tracking-Id: ([0-9a-z]+)/i", imap_body($mbox, $message->msgno), $matches)){

		$targetid = trim(api_misc_decrypt(base64_decode($matches[1])));
		if(!preg_match("/^[0-9]+$/", $targetid)) $targetid = trim(@api_misc_decrypt_safe($matches[1]));

		if(preg_match("/^[0-9]+$/", $targetid)){
			$target = api_targets_getinfo($targetid);
			api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "TRACK", "TRACK");
			api_data_responses_add($target["campaignid"], 0, $targetid, $target["targetkey"], "UNSUBSCRIBE", "UNSUBSCRIBE");
			api_restrictions_donotcontact_addbytargetid($targetid);

			imap_mail_move($mbox, $message->msgno, "INBOX.processed");
		}
	}
}

imap_expunge($mbox);
imap_close($mbox);

?>
