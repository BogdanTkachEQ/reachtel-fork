<script type="text/javascript" src="/js/datepair-20130405.js"></script>
<script type="text/javascript" src="/js/daysOfWeek.js"></script>
<script type="text/javascript">

	$('#starttime').timepicker({ldelim} 'timeFormat': 'H:i' {rdelim});
	$('#endtime').timepicker({ldelim} 'timeFormat': 'H:i' {rdelim});

	{if !empty($default_time_specific)}
		setDefaultSpecificTime(
			"{$default_time_specific.starttime|date_format:'%H:%M'}",
			"{$default_time_specific.starttime|date_format:'%Y-%m-%d'}",
			"{$default_time_specific.endtime|date_format:'%H:%M'}",
			"{$default_time_specific.endtime|date_format:'%Y-%m-%d'}"
		);
	{/if}

	{if !empty($default_time_recurring)}
		setDefaultRecurringTime("{$default_time_recurring.starttime}", "{$default_time_recurring.endtime}");
	{/if}

    function postChangeUpdate() {
        // if there's an error showing, reload the page, otherwise just reload the tab
        if ($('.flash-error').length) {
            location.reload();
        } else {
            $('#tabs').tabs('load', $('#tabs').tabs('option', 'active'));
        }
    }

</script>

{if isset($rules_message) AND $rules_message}
    <div class="flash flash-message" role="alert">{nl2br($rules_message) nofilter}</div>
{/if}

<table class="subinfo common-object-table" width="100%">
	<tr style="vertical-align: top; text-align: center;"><td>
		<table class="subinfo common-object-table" width="370px">
			<thead>
				<tr>
					<th class="subinfoHeader" colspan="4"><div style="float: right;"><i>{$setting.timezone|default:$smarty.const.DEFAULT_TIMEZONE}</i></div>Specific time periods</th>
				</tr>
			</thead>
			<tr class="datepair">
				<td style="width: 160px;"><input type="text" class="time start" style="text-align: center; width: 45px;" id="specificstarttime" /><input type="text" class="date start" style="width: 80px;" id="specificstartdate" /></td>
				<td style="width: 10px;"><span class="famfam" style="background-position: -240px -20px;" alt="to" title="to"></span></td>
				<td style="width: 160px;"><input type="text" class="time end" style="text-align: center; width: 45px;" id="specificendtime" onkeydown="if (event.keyCode == 13) document.getElementById('specificadd').click();" /><input type="text" class="date end" style="width: 80px;" id="specificenddate" /></td>
				<td style="width: 30px;"><img id="specificadd" onclick="javascript: jQuery.post('?', {ldelim}'campaignid' : {$campaignid}, 'action' : 'addspecific', 'starttime' : $('#specificstarttime').val(), 'startdate' : $('#specificstartdate').val(), 'endtime' : $('#specificendtime').val(), 'enddate' : $('#specificenddate').val(), 'csrftoken' : '{$smarty.session.csrftoken|default:''}'{rdelim}, function(response) {ldelim} if (response.status == 'OK') {ldelim} $('#tabs').tabs('load', $('#tabs').tabs('option', 'active')); {rdelim} else {ldelim} alert('ERROR: ' + response.error); {rdelim} {rdelim}, 'json'); return false;"  src="{$TE_IMAGE_LOCATION}icons/add.png" height="16px" width="16px" alt="Add Period" title="Add Period" style="border: none;"/></td>
			</tr>
			{if !empty($time_specific)}
			<tbody>
				{foreach from=$time_specific key=k item=v}
				<tr style="background-color: {if $v.status === 1}#90D0FF{elseif $v.status === -1}#FFC0C0{else}#B0F07F{/if};">
					<td style="width: 160px;">{$v.starttime|date_format:"%H:%M %d/%m/%Y"}</td>
					<td style="width: 20px;"><span class="famfam" style="background-position: -240px -20px;" alt="to" title="to"></span></td>
					<td style="width: 160px;">{$v.endtime|date_format:"%H:%M %d/%m/%Y"}</td>
					<td style="width: 30px;"><span onclick="javascript: jQuery.post('?', {ldelim}'campaignid' : {$campaignid}, 'action' : 'deletespecific', 'periodid' : {$k}, 'csrftoken' : '{$smarty.session.csrftoken|default:''}'{rdelim}, postChangeUpdate); return false;"><span class="famfam" style="background-position: -280px -160px;" alt="Delete Period" title="Delete Period"></span></span></td>
				</tr>
				{/foreach}
				{else}
				<tr>
					<td colspan="4">No specific periods</td>
				</tr>
				{/if}
			</tbody>
		</table>
	</td>
	<td>
		<table class="subinfo common-object-table" width="370px">
			<thead>
				<tr>
					<th class="subinfoHeader" colspan="5"><div style="float: right;"><i>{$setting.timezone|default:$smarty.const.DEFAULT_TIMEZONE}</i></div>Recurring time periods</th>
				</tr>
			</thead>
			<tr>
				<td style="width: 170px;"><input class="days-of-week" id="daysofweek" type="text" value="31" /></td>
				<td style="width: 100px;"><span class="ui-timepicker-container"><input value="" onkeydown="if (event.keyCode == 13) document.getElementById('recurringadd').click();" autocomplete="off" id="starttime" class="recurring-time time ui-timepicker-input" style="text-align: center; width: 75px;" type="text" /></span></td>
				<td style="width: 20px;"><span class="famfam" style="background-position: -240px -20px;" alt="to" title="to"></span></td>
				<td style="width: 100px;"><span class="ui-timepicker-container"><input value="" onkeydown="if (event.keyCode == 13) document.getElementById('recurringadd').click();" autocomplete="off" id="endtime" class="recurring-time time ui-timepicker-input" style="text-align: center; width: 75px;" type="text"></span></td>
				<td style="width: 30px;"><img id="recurringadd" onclick="javascript: jQuery.post('?', {ldelim}'campaignid' : {$campaignid}, 'action' : 'addrecurring', 'starttime' : $('#starttime').val(), 'endtime' : $('#endtime').val(), 'daysofweek': $('#daysofweek').val(), 'csrftoken': '{$smarty.session.csrftoken|default:''}' {rdelim}, function(response) {ldelim} if (response.status == 'OK') {ldelim} $('#tabs').tabs('load', $('#tabs').tabs('option', 'active')); {rdelim} else {ldelim} alert('ERROR: ' + response.error); {rdelim} {rdelim}, 'json'); return false;"  src="{$TE_IMAGE_LOCATION}icons/add.png" height="16px" width="16px" alt="Add Period" title="Add Period" style="border: none;"/></td>
			</tr>
			{if !empty($time_recurring)}
			{foreach from=$time_recurring key=k item=v}
			<tr style="background-color: {if $v.status === 0}#B0F07F{else}#90D0FF{/if};">
				<td style="width: 170px;"><input class="days-of-week" type="text" value="{$v.daysofweek|default:'31'}" data-disabled="true"/></td>
				<td style="width: 100px;">{$v.starttime|substr:0:-3}</td>
				<td style="width: 20px;"><span class="famfam" style="background-position: -240px -20px;" alt="to" title="to"></span></td>
				<td style="width: 100px;">{$v.endtime|substr:0:-3}</td>
				<td style="width: 30px;"><span onclick="javascript: jQuery.post('?', {ldelim}'campaignid' : {$campaignid}, 'action' : 'deleterecurring', 'periodid' : {$k}, 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, postChangeUpdate); return false;"><span class="famfam" style="background-position: -280px -160px;" alt="Delete Period" title="Delete Period"></span></span></td>
			</tr>
			{/foreach}
			{else}
			<tr>
				<td colspan="5">No recurring periods</td>
			</tr>
			{/if}
		</table>
	</td></tr>
</table>
