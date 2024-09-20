<table class="campaignTableCenter common-object-table" width="800px">
	<thead>
		<tr>
			<th style="width: 75px;">Clicks</th>
			<th>URL</th>
		</tr>
	</thead>
	<tbody>
{foreach from=$responses key=question item=answers name=ques}
	{if $answers.question == "CLICK"}
		{foreach from=$answers.answers key=answer item=count name=ans}
			{if !empty($answer)}
		<tr>
			<td>{$count}</td>
			<td><a href="{$answer}" target="_blank">{$answer}</a></td>
		</tr>
			{/if}
		{/foreach}
	{/if}
{/foreach}
	</tbody>
</table>
