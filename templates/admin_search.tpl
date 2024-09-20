                                <!-- Main Common Content Block -->
                                <div class="main-common-block">


                                        <!-- Main Header -->
                                        <div class="main-common-header">
                                                <h2>{$title|default:" "}</h2>
                                        </div>
                                        <!-- /Main Header -->

                                        <!-- Notification -->
                                        {if !empty($smarty_notifications)}{include file=$notification_template}{/if}
                                        <!-- /Notification -->

{if !empty($results)}
                    <div class="flash flash-notice">
                        NOTE: Wash campaign phone number may not appear as these targets may have a different number format (e.g. 614xxx vs 04xxx).
                    </div>
					<table class="campaignTableCenter common-object-table" width="100%">
					        <thead><tr><th colspan="5"><span style="float: right;">All times are Queensland time.</span>Results for "{$destination}"</th></tr></thead>
					        <tbody>
{foreach from=$results key=timestamp item=timestampresults}
	{foreach from=$timestampresults item=result}
		{if $result.source == "campaign"}
							<tr style="background-color: #e1e1e1;">
								<td>
									<span class="famfam" style="background-position:  {if ($result.type == "phone")}-100px -440px;{elseif ($result.type == "sms")}-300px -340px;{elseif ($result.type == "wash")}-600px -140px;{else}-180px -180px;{/if}"  title="{$result.type}" alt="{$result.type}" />
								</td>
								<td colspan="4"><a href="admin_listcampaign.php?name={$result.name|urlencode}">{$result.name}</a> {if $result.targetstatus == "archived target" } (archived) {/if}</td>
							</tr>
		{elseif $result.source == "restsms"}
							<tr style="background-color: #e1e1e1;">
								<td><span class="famfam" style="background-position: -300px -340px;" title="REST SMS" alt="REST SMS" /></td>
								<td colspan="4">REST SMS: <a href="admin_listuser.php?name={$result.name|urlencode}">{$result.name}</a></td>
							</tr>
		{elseif $result.source == "restwash"}
							<tr style="background-color: #e1e1e1;">
								<td><span class="famfam" style="background-position: -600px -140px;" title="REST WASH" alt="REST WASH" /></td>
								<td colspan="4">REST WASH: <a href="admin_listuser.php?name={$result.name|urlencode}">{$result.name}</a></td>
							</tr>							
		{elseif $result.source == "smssent"}
							<tr style="background-color: #e1e1e1;">
								<td><span class="famfam" style="background-position: -300px -340px;" title="SMS SENT" alt="SMS SENT" /></td>
								<td colspan="4">SMS SENT: <a href="admin_listuser.php?name={$result.name|urlencode}">{$result.name}</a></td>
							</tr>
		{elseif $result.source == "smsreceived"}
							<tr style="background-color: #e1e1e1;">
								<td><span class="famfam" style="background-position: -300px -340px;" title="SMS RECEIVED" alt="SMS RECEIVED" /></td>
								<td colspan="4">SMS RECEIVED: <a href="admin_listsmsdid.php?name={$result.name|urlencode}">{$result.name}</a></td>
							</tr>
		{/if}
		{if !empty($result.events)}
			{foreach from=$result.events key=eventid item=eventresults}
						        <tr style="background-color: #eeeeee;">
						                <td style="width: 50px;">&gt;</td>
						                <td colspan="4">Event: {$eventid}</td>
						        </tr>
				{foreach from=$eventresults key=timestamp item=timestampresults}
					{foreach from=$timestampresults key=resultid item=itemresults}
						        <tr>
						                <td style="width: 50px;">&gt;</td>
						                <td style="width: 50px;">&gt;</td>
						                <td style="width: 200px;">{$timestamp|date_format:"%d/%m/%Y %T"}</td>
						                <td style="width: 200px;">{$itemresults.action|default:""}</td>
						                <td>{$itemresults.value}</td>
						        </tr>
					{/foreach}
				{/foreach}
			{/foreach}
		{else}
						        <tr style="background-color: #eeeeee;">
						                <td style="width: 50px;">&gt;</td>
						                <td colspan="4">Destination found but no events for this campaign.</td>
						        </tr>	
		{/if}
	{/foreach}
{/foreach}
						</tbody>
					</table>
{elseif isset($results)}
					<div class="flash flash-success">
						There are no results
					</div>
{/if}
					<form action="?" method="get" class="common-form">
					        <fieldset>
						        <legend>Search</legend>
						        <div class="inner">
						                <div class="field">
						                        <label>Destination:</label>
						                        <input name="destination" value="" type="text" class="textbox" />
						                        <p class="help">Full destination address</p>
						                </div>
						                <div class="form-controls">
						                        <button type="submit">Search</button>
						                </div>
						        </div>
					        </fieldset>
					</form>


				</div>
