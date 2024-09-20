
<script type="text/javascript">
	$(document).ready(function() {ldelim}
{if $type == 'sms'}
		smsLength();
{/if}
		mergeData('{$name}');
	{rdelim});
</script>

<form action="?name={$name}" method="post" name="settingsForm"{if isset($confirmation_data)} onsubmit="return activateCampaignConfirmation(this, {$confirmation_data|@json_encode});"{/if}>
	<table class="campaignTable common-object-table" style="width: 100%;">
{if $type == 'campaign'}
		<tr>
			<td>Status</td><td>{if $setting.donotcontactdestination == 6}Choose DNC list first{elseif $setting.groupowner == 2}Choose Group Owner first{else}<select class="mediumdata campaign-status" name="setting[status]" style="width: 100%;">{html_options options=$status selected=$status_selected}</select>{/if}</td>
			<td rowspan="2">DNC wash against</td><td rowspan="2"><select class="mediumdata" name="setting[donotcontact][]" style="width: 100%;" multiple>{html_options options=$donotcontact_lists selected=$donotcontact_lists_selected}</select></td>
		</tr>
		<tr>
			<td>Save DNC to</td><td><select class="mediumdata" name="setting[donotcontactdestination]" style="width: 100%;">{html_options options=$donotcontact_lists selected=$setting.donotcontactdestination}</select></td>
		</tr>
		<tr>
			<td>Classification</td>
			<td colspan="3">
				<select id="campaign-classification" class="mediumdata" name="setting[classification]">{html_options options=$classification_options selected=$classification_selected}
				</select>
			</td>
		</tr>
		<tr>
			<td>Notes</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[notes]" value="{$setting.notes|default:""}" /></td>
		</tr>
		<tr>
			<td colspan="4">Created {if !empty($setting.created)}<span title="{$setting.created|date_format:"%H:%M:%S %d/%m/%Y"|default:'unknown'}">{$setting.created|api_misc_timeformat}</span>{/if} by <i><a href="/admin_listuser.php?id={$setting.owner}">{$ownername}</a></i>.</td>
		</tr>
{elseif $type == 'advanced'}
		<tr>
			<td>Stop question</td><td><input class="mediumdata" type="text" name="setting[stopflag]" value="{$setting.stopflag|default:""}"/></td>
			<td>Region</td><td><select class="mediumdata" name="setting[region]" style="width: 100%;">{html_options options=$regions selected=$setting.region}</select></td>
		</tr>
		<tr>
			<td>Stop question #</td><td><input class="mediumdata" type="number" name="setting[stopflagcount]" value="{$setting.stopflagcount|default:""}"/></td>
			<td>Timezone</td><td><select class="mediumdata" name="setting[timezone]" style="width: 100%;">{html_options output=$timezones values=$timezones selected=$setting.timezone|default:$smarty.const.DEFAULT_TIMEZONE}</select></td>
		</tr>
		<tr>
			<td>Start when Done</td><td><input class="mediumdata" type="text" name="setting[startwhendone]" value="{$setting.startwhendone|default:""}"/></td>
			<td>Randomise?</td><td><input type="checkbox" name="setting[random]" {if !empty($setting.random) AND ($setting.random == "on")}CHECKED{/if}/></td>
		</tr>
		<tr>
			<td>Default targetkey name</td><td><input class="mediumdata" type="text" name="setting[defaulttargetkey]" value="{$setting.defaulttargetkey|default:""}"/></td>
			<td>Stop at EOD?</td><td><input type="checkbox" name="setting[stopateod]" {if !empty($setting.stopateod) AND ($setting.stopateod == "on")}CHECKED{/if}/></td>
		</tr>
		<tr>
			<td>Default destination1 name</td><td><input class="mediumdata" type="text" name="setting[defaultdestination1]" value="{$setting.defaultdestination1|default:""}"/></td>
			<td>Extra variable 1</td><td><input class="mediumdata" type="text" name="setting[extravariable1]" value="{$setting.extravariable1|default:""}"/></td>
		</tr>
		<tr>
			<td>Default destination2 name</td><td><input class="mediumdata" type="text" name="setting[defaultdestination2]" value="{$setting.defaultdestination2|default:""}"/></td>
			<td>Extra variable 2</td><td><input class="mediumdata" type="text" name="setting[extravariable2]" value="{$setting.extravariable2|default:""}"/></td>
		</tr>
		<tr>
			<td>Default destination3 name</td><td><input class="mediumdata" type="text" name="setting[defaultdestination3]" value="{$setting.defaultdestination3|default:""}"/></td>
			<td>Extra variable 3</td><td><input class="mediumdata" type="text" name="setting[extravariable3]" value="{$setting.extravariable3|default:""}"/></td>
		</tr>
		<tr>
			<td>Default destination4 name</td><td><input class="mediumdata" type="text" name="setting[defaultdestination4]" value="{$setting.defaultdestination4|default:""}"/></td>
			<td>Extra variable 4</td><td><input class="mediumdata" type="text" name="setting[extravariable4]" value="{$setting.extravariable4|default:""}"/></td>
		</tr>
		<tr>
			<td>Default destination5 name</td><td><input class="mediumdata" type="text" name="setting[defaultdestination5]" value="{$setting.defaultdestination5|default:""}"/></td>
			<td>Extra variable 5</td><td><input class="mediumdata" type="text" name="setting[extravariable5]" value="{$setting.extravariable5|default:""}"/></td>
		</tr>
		<tr>
			<td>Skip initial data rows</td><td><input class="mediumdata" type="number" name="setting[skipinitialdatarows]" value="{$setting.skipinitialdatarows|default:""}"/></td>
			<td>Mandatory fields</td><td><input class="mediumdata" type="text" name="setting[mandatoryfields]" value="{$setting.mandatoryfields|default:""}"/></td>
		</tr>
		<tr>
			<td>CALLME destination</td><td><input class="mediumdata" type="text" name="setting[callmedestination]" value="{$setting.callmedestination|default:""}"/></td>
			<td>Add header row</td><td><input class="mediumdata" type="text" name="setting[headerrow]" value="{$setting.headerrow|default:""}"/></td>
		</tr>
{elseif ($type == 'billing') AND api_security_isadmin($smarty.session.userid)}
		<tr>
			<td>Group owner</td><td><select class="mediumdata" name="setting[groupowner]" style="width: 100%;">{html_options options=$usergroups selected=$setting.groupowner}</select></td>
			<td></td><td></td>
		</tr>
		<tr>
			<td>API name</td><td>{$enccampaignid}</td>
		</tr>
{elseif $type == 'survey'}
		<tr>
			<td>Weight data against</td><td><select class="mediumdata" name="setting[surveyweight]">{html_options options=$surveyweight selected=$setting.surveyweight}</select></td>
			<td>Download report</td><td><a href="?name={$setting.name|urlencode}&amp;action=surveyresults"><span class="famfam" style="background-position: -680px -380px;" title="Export Survey Results" alt="Export Survey Results"></span></a></td>
		</tr>
{elseif $type == 'reporting'}
		<tr>
			<td>Email report</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[emailreport]" value="{$setting.emailreport|default:""}"/></td>
		</tr>
		<tr>
			<td>Report format override</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[reportformatoverride]" value="{$setting.reportformatoverride|default:""}"/></td>
		</tr>
		<tr>
			<td>Report format complete override</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[reportformatcompleteoverride]" value="{$setting.reportformatcompleteoverride|default:""}"/></td>
		</tr>
		<tr>
			<td>PGP encryption key(s)</td><td><input style="text-align: center;" class="mediumdata" type="text" name="setting[pgpemail]" value="{$setting.pgpemail|default:""}"/></td>
			<td>Delayed report 1 / 2</td><td><input style="width: 50px; text-align: center;" class="mediumdata" type="text" name="setting[delayedreport1]" value="{$setting.delayedreport1|default:""}"/> / <input style="width: 50px; text-align: center;" class="mediumdata" type="text" name="setting[delayedreport2]" value="{$setting.delayedreport2|default:""}"/> minutes</td>
		</tr>
		<tr>
			<td>No initial report?</td><td><input type="checkbox" name="setting[noreport]" {if $setting.noreport == "on"}checked="checked"{/if} /></td>
			<td>SFTP report?</td><td><input type="checkbox" name="setting[sftpreport]" {if !empty($setting.sftpreport) AND ($setting.sftpreport == "on")}checked="checked"{/if} /></td>
		</tr>
{elseif $type == 'voice'}
		<tr>
			<td>Dial plan {if isset($setting.dialplan)}&nbsp;&nbsp;<a href="admin_listdialplan.php?name={$dialplan[$setting.dialplan]|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}" target="_blank"><span class="famfam" style="background-position: -100px -380px;" title="View Dialplan" alt="View Dialplan"></span></a>{/if}</td><td><select class="mediumdata" name="setting[dialplan]" style="width: 100%;">{html_options options=$dialplan values=$dialplan selected=(isset($setting.dialplan))?$setting.dialplan:''}</select></td>
			<td>Reattempt delay</td><td><input class="smalldata" style="width: 50px; text-align: center;" type="text" name="setting[redialtimeout]" value="{$setting.redialtimeout|default:"60"}"/> minutes</td>
		</tr>
		<tr>
			<td>Voice DID</td><td><select class="mediumdata" name="setting[voicedid]" style="width: 100%;">{html_options options=$voicedids selected=$setting.voicedid}</select></td>
			<td>Ring time</td><td><input style="width: 50px; text-align: center;" class="smalldata" type="text" name="setting[ringtime]" value="{$setting.ringtime|default:"30"}"/> seconds</td>
		</tr>
		<tr>
			<td>Callback number</td><td><input class="mediumdata" type="text" name="setting[callbacknumber]" value="{$setting.callbacknumber|default:""}"/></td>
			<td>Max ringouts</td><td><input style="width: 50px; text-align: center;" class="smalldata" type="text" name="setting[ringoutlimit]" value="{$setting.ringoutlimit|default:"1"}"/></td>
		</tr>
		<tr>
			<td>Service provider</td><td><select class="mediumdata" name="setting[voicesupplier]" style="width: 100%;">{html_options options=$voicesupplier values=$voicesupplier selected=$setting.voicesupplier}</select></td>
			<td>Max retries</td><td><input style="width: 50px; text-align: center;" class="smalldata" type="text" name="setting[retrylimit]" value="{$setting.retrylimit}"/></td>
		</tr>
		<tr>
			<td>Max channels</td><td><input style="width: 50px; text-align: center;" class="smalldata" type="text" name="setting[maxchannels]" value="{$setting.maxchannels}"/></td>
			<td>Withhold CID</td>
			<td>
				<input type="checkbox" name="setting[withholdcid]" {if $setting.classification|default:'' != "exempt"} disabled title="Enabled for ACMA exempt classifications only" {/if} {if isset($setting.withholdcid) && $setting.withholdcid == "on"}checked="checked"{/if} />
			</td>
		</tr>
		<tr>
			<td>Send rate</td><td><input class="smalldata" style="width: 50px; text-align: center;" type="text" name="setting[sendrate]" value="{$setting.sendrate}"> messages/hour</td>
			<td colspan="2">&nbsp;</td>
		</tr>
{elseif $type == 'wash'}
		<tr>
			<td style="width: 200px;">Return mobile carrier</td><td><input type="hidden" name="page" value="wash" /><input type="checkbox" name="setting[returncarrier]" {if !empty($setting.returncarrier) AND ($setting.returncarrier == "on")}checked="checked"{/if} /></td>
			<td style="width: 200px;">Return HLR code</td><td><input type="checkbox" name="setting[returnhlrcode]" {if !empty($setting.returnhlrcode) AND ($setting.returnhlrcode == "on")}checked="checked"{/if} /></td>
		</tr>
        <tr>
            <td>Max channels</td><td><input style="width: 50px; text-align: center;" class="smalldata" type="text" name="setting[maxchannels]" value="{$setting.maxchannels}"/></td>
            <td>Wash rate</td><td><input class="smalldata" style="width: 50px; text-align: center;" type="text" name="setting[sendrate]" value="{$setting.sendrate}"> wash/hour</td>
        </tr>
{elseif $type == 'sms'}
		<tr>
			<td>Email SMS replies</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[smsreplyemail]" value="{$setting.smsreplyemail|default:''}"/></td>
		</tr>
		<tr>
			<td>Email SMS receipts</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[smsreceiptemail]" value="{$setting.smsreceiptemail|default:''}"/></td>
		</tr>
		<tr>
			<td>SMS DID</td><td><select class="mediumdata" name="setting[smsdid]" style="width: 100%;">{html_options options=$smsdids selected=$smsdid_selected}</select></td>
			<td>Send rate</td><td><input class="smalldata" style="width: 50px; text-align: center;" type="text" name="setting[sendrate]" value="{$setting.sendrate}"> messages/hour</td>
		</tr>
		<tr>
			<td>Shorten URLs?</td><td><input type="checkbox" name="setting[shortenurls]" {if !empty($setting.shortenurls) AND ($setting.shortenurls == "on")}checked="checked"{/if} /></td>
			<td colspan="2">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="4">
				<span style="text-align: left;">SMS Body:</span><span id="smsCount" class="help" style="float: right;"></span><textarea onkeyup="javascript: smsLength();" id="smscontent" name="setting[content]" rows="3" style="width: 100%;">{$setting.content|default:''}</textarea><br />
				<span id="mergedata"></span>
			</td>
		</tr>
{elseif $type == 'email'}
		<tr>
			<td>From</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[from]" value="{$setting.from}"/></td>
		</tr>
		<tr>
			<td>Reply-To</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[replyto]" value="{$setting.replyto}"/></td>
		</tr>
		<tr>
			<td>Subject</td><td colspan="3"><input style="width: 100%;" type="text" name="setting[subject]" value="{$setting.subject}"/></td>
		</tr>
		<tr>
			<td>Email Template&nbsp;&nbsp;<a href="{api_hosts_gettrack()}/view.php?dv={$enccampaignid}" target="_blank"><span class="famfam" style="background-position: -100px -380px;" title="View Template" alt="View Template"></span></a></td><td colspan="3"><select class="mediumdata" name="setting[template]" style="width: 100%;">{html_options options=$template selected=$template_selected}</select></td>
		</tr>
		<tr>
			<td>Remove List-Unsub:</td><td><input type="checkbox" name="setting[removelistunsub]" {if $setting.removelistunsub == "on"}checked="checked"{/if} /></td>
			<td>Send rate</td><td><input class="smalldata" style="width: 50px; text-align: center;" type="text" name="setting[sendrate]" value="{$setting.sendrate}"> messages/hour</td>
		</tr>
		<tr>
			<td>
				DKIM Key (Selector)
				{if $dkim_selected|default:null}
				&nbsp;<a href="admin_listgroup.php?id={$setting.groupowner|urlencode}&amp;action=downloaddkimpublickey&key={$dkim_selected}" target="_blank">
					<span class="famfam" style="background-position: -100px -380px;" title="View Key" alt="View Key"></span>
				</a>
				{/if}
			</td>
			<td colspan="3">
				{if $dkim_has_system_selector|default:false }
					This group has a system DKIM key allocated, unless overridden here all emails will be signed with the DKIM selector: <b>{$dkim_system_selector|default:''}</b>
				{/if}
				<select class="mediumdata" name="setting[dkim]" style="width: 100%;"><option />{html_options values=$dkim_selectors output=$dkim_selectors selected=$dkim_selected}</select>
			</td>
		</tr>
{elseif $type == 'hooks'}
	<tr>
		<td>
			{include file="$templates_path/admin_campaignhooks.tpl"}
		</td>
	</tr>
{/if}
{if $show_boost_spooler && $type != 'hooks'}
		<tr>
		<td>Boost spooler:</td><td><input type="checkbox" name="setting[{$smarty.const.CAMPAIGN_SETTING_BOOST_SPOOLER}]" {if array_key_exists($smarty.const.CAMPAIGN_SETTING_BOOST_SPOOLER, $setting) && $setting[$smarty.const.CAMPAIGN_SETTING_BOOST_SPOOLER] == "on"}checked="checked"{/if} /></td>
		</tr>
{/if}
		<tr>
			<td colspan="4"><input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" /><input type="hidden" name="campaignid" value="{$campaignid}" /><input type="submit" name="submit" value="Save"/></td>
		</tr>
	</table>
</form>
