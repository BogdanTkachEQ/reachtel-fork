<table class="subinfo common-object-table" width="750px">
    <thead>
        <tr>
            <th style="width: 30px;">Type</th>
            <th style="width: 30px;">Id</th>
            <th style="width: 30px; text-align: center;">Status</th>
            <th>Name</th>
        </tr>
    </thead>
    <tbody>
    {if !empty($campaigns)}
    {foreach from=$campaigns key=id item=campaign}
    <tr>
        <td>
            <span class="famfam" style="background-position: {if ($campaign.type == $smarty.const.CAMPAIGN_TYPE_VOICE)}-100px -440px;{elseif ($campaign.type == $smarty.const.CAMPAIGN_TYPE_SMS)}-300px -340px;{elseif ($campaign.type == $smarty.const.CAMPAIGN_TYPE_WASH)}-600px -140px;{else}-180px -180px;{/if}"></span>
        </td>
        <td>{$id}</td>
        <td style="text-align: center;">
            <span class="famfam" style="background-position: {if $campaign.status == $smarty.const.CAMPAIGN_SETTING_STATUS_VALUE_ACTIVE}0px 0px{else}-100px -420px{/if};"></span></td>
        </td>
        <td>
            <a href="/admin_listcampaign.php?id={$id}" target="_blank">{$campaign.name}</a>
        </td>
    </tr>
    {/foreach}
    {else}
    <tr>
        <td colspan="4" style="text-align: center;">No campaigns</td>
    </tr>
    {/if}
    </tbody>
</table>
