<script>
    $(function () {
        $("#cascading").change(function () {
            this.checked ? $('#cascading-campaign-settings').show() : $('#cascading-campaign-settings').hide();
        });

        $("#base-cascading-template").change(function () {
            this.checked ? $('#cascade-first-iteration-container').show() : $('#cascade-first-iteration-container').hide();
        });

        $("#create-first-cascade-iteration").change(function () {
            this.checked ? $('#cascade-first-iteration-name-container').show() : $('#cascade-first-iteration-name-container').hide();
        });

        if($("#base-cascading-template").prop("checked") == true){
            $('#cascade-first-iteration-container').show();
        }
    });

</script>
<h4 class="secondary-header">Campaign Hooks</h4>

<p><input id="cascading" type="checkbox" name="setting[cascadingcampaign]" value="1"
          {if isset($setting.cascadingcampaign|default:null)}checked{/if}/> Cascading Campaign</p>

<table id="cascading-campaign-settings" style="display:{if !isset($setting.cascadingcampaign|default:null)}none{/if}"
       class="campaignTable common-object-table">
<tr>

<td>
    <p class="help">
        Cascading campaigns automatically create a new subsequent campaign after finishing.<br />
        The hook clones the campaign template defined in "Cascading Next Template" and copies the abandoned targets to it.
        <br />
        Beware that cascading campaigns operate on the concept of templates. Templates themselves do not run, they are used only
        as the settings container for each iteration of the campaign.
    </p>

    {if $setting.cascadingcampaigntemplateid|default: null != null }

        <h4>Campaign Iteration</h4>
        <p>
        This campaign is an iteration in a cascading campaign, details follow:
        <table class="common-object-table">
            <tr>
                <td>Iteration Number</td>
                <td>{$setting.cascadingcampaigniteration}</td>
            </tr>
            <tr>
                <td>Parent Campaign</td>
                <td>
                    {if $setting.cascadingcampaigniteration|default: null > 1}
                        {api_campaigns_setting_getsingle($setting.cascadingcampaignpreviousiterationid|default: null, CAMPAIGN_SETTING_NAME)}
                        ({$setting.cascadingcampaignpreviousiterationid|default: "N/A"})
                    {else}
                        N/A
                    {/if}
                </td>
            </tr>
            <tr>
                <td>Template</td>
                <td>
                    {api_campaigns_setting_getsingle($setting.cascadingcampaigntemplateid|default: null, CAMPAIGN_SETTING_NAME)}
                    ({$setting.cascadingcampaigntemplateid|default: "N/A"})
                </td>
            </tr>
        </table>
        </p>
    {/if}

</td>
</tr>
    <tr>
        <td colspan="2">
            <h4>Cascading Next Template</h4>
            <p class="help">
                The name of the campaign to use as the template for generating the *next* campaign after this one has completed.
            </p>
            <input type="text" size="55" name="setting[cascadingcampaignnexttemplate]" value="{$setting.cascadingcampaignnexttemplate|default:''}"/>
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <h4>Cascading Base Template</h4>
            <p class="help">
                Is this the template for the first iteration? <input type="checkbox" id="base-cascading-template" name="setting[cascadingcampaignbasetemplate]"  {if $setting.cascadingcampaignbasetemplate|default:"" != ""} checked {/if}"/>
            </p>

            <span id="cascade-first-iteration-container" style="display: none">
            {if $setting.cascadingcampaigntemplateid|default: null == null }
            <h4>Create the first iteration?</h4>
            <p class="help">
                Create the first iteration (step-1) of this campaign now?
                <input id="create-first-cascade-iteration" type="checkbox" name="createfirstcascadeiteration" value="1"/>
                <br />
                <span id="cascade-first-iteration-name-container" style="display: none">
                First campaign name: <input size="55" type="text" name="firstcascadeiterationname" />
                </span>
            </p>
            {/if}
            </span>
        </td>
    </tr>
    {if $setting.cascadingcampaignpreviousiterationid|default: null != null && $setting.cascadingcampaigniteration|default: null > 1 }
    <tr>
        <td colspan="2">
            <h4>Previous Campaign ID</h4>
            <p class="help">
                As this campaign is an iteration in a cascade it has a parent. This field contains the parent campaign id.<br />
                This link between cascading campaigns provides the system the ability to walk up or down the cascading campaign tree.
                Change this with care.
            </p>
            <input type="text" min="0" size="15" name="setting[cascadingcampaignpreviousiterationid]" value="{$setting.cascadingcampaignpreviousiterationid|default:null}"/>
        </td>
    </tr>
    {/if}
    <tr>
        <td colspan="2">
            <h4>Cascading Send Rate Modifier</h4>
            <p class="help">
                A modifier to change the rate at which contacts can be made (target count / send modifier rate = send rate limit)
            </p>
            <input type="text" min="0" size="2" name="setting[cascadingcampaignsendratemodifier]" value="{$setting.cascadingcampaignsendratemodifier|default:0}"/>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <h4>Call Delay (Hours)</h4>
            <p class="help">
                A time delay to place upon casacading calls on the second and third iterations.
            </p>
            <input type="text" min="0" size="2" name="setting[cascadingcampaigndelay]" value="{$setting.cascadingcampaigndelay|default:0}"/>

        </td>
    </tr>
    <input type="hidden" name="action" value="hooks" />

</table>