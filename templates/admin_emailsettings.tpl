{assign var='is_child_view'  value=(isset($child_view) && $child_view)}

<!-- Main Common Content Block -->
<div class="main-common-block">

    {include file=$search_notification_pagination_template}


    <h3>DKIM</h3>

    {if $system_dkim_key|default:''}
    <div class="flash flash-message">

        This group currently has a system wide DKIM key assigned to it <b>({$system_dkim_key})</b>.
        <br />
        <br />
        The key is assigned to the following selector for this group: <b>{$system_dkim_group_selector}</b>

        </p>
        <br /> This key will be applied to all email campaigns for this group unless a campaign DKIM key is specified.
        <form action="?" method="post" class="common-form">
            <input type="hidden" name="action" value="unlinkdkimsystemkey"/>
            <input type="hidden" name="id" value="{$id}"/>
            <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}"/>
            <div class="form-controls">
                <button onclick="return confirm('Are you sure?');">Unassign Key</button>
            </div>
        </form>
    </div>
    {/if}
    <table class="campaignTableCenter common-object-table" width="600px">
        <thead>
        <tr>
            <th>{if !$is_system_view|default:false }DKIM Selector{else}Key Name{/if}</th>
            <th>Public Key</th>
            <th style="width: 50px; text-align: center;">Download Public Key</th>
            {if $is_system_view|default:false }<th style="width: 50px; text-align: center;">Default System Key</th>{/if}
            <th style="width: 50px; text-align: center;">Delete</th>
        </tr>
        </thead>
        <tbody>
        {if $system_dkim_key|default:''}
        <tr>
            <td>

            </td>
            <td>

            </td>
            <td>

            </td>
        </tr>
        {/if}
        {if !empty($dkim_keys)}
            {foreach from=$dkim_keys key=k item=v}
                <tr>
                    <td style="width: 300px;">
                        {$v.selector|escape:html nofilter}
                    </td>
                    <td>{if !empty($v.value)}{$v.value}{else}<span class="soft-notice">none</span>{/if}</td>
                    <td>&nbsp;
                        <a href="?{if !$is_system_view|default:''}id={$id|default:''|urlencode}&amp;{/if}action=downloaddkimpublickey&key={$v.selector}" target="_blank">
                            <span class="famfam" style="background-position: -100px -380px;" title="View Key" alt="View Key"></span>
                        </a>
                    </td>
                    {if $is_system_view|default:''}
                        <td>
                            <form action="?" method="post" class="common-form">
                                <input type="hidden" name="action" value="{if $default_dkim_selector|default:'' != $v.selector}setdefaultdkimkey{else}unsetdefaultdkimkey{/if}"/>
                                <input type="hidden" name="selector" value="{$v.selector}"/>
                                <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}"/>
                                <div class="form-controls">
                                    {if $default_dkim_selector|default:'' != $v.selector}
                                    <input type="checkbox"  onclick="if(confirm(
                                    'Are you sure you wish to set this as the default system DKIM key?\n' +
                                     'All emails sent by Morpheus from @reachtel.com.au will be signed with this key as soon as this is set - a DNS record for it must be created first!'
                                    )) this.form.submit();this.checked=!this.checked"></input>
                                    {else}
                                        <input type="checkbox"  checked  onclick="if(confirm(
                                        'Are you sure you wish to deselect this as the default system key?\n' +
                                        'All emails sent by Morpheus from @reachtel.com.au will no longer be signed by DKIM!'
                                    )) this.form.submit();this.checked=!this.checked"></input>
                                    {/if}
                                </div>
                            </form>

                        </td>
                    {/if}

                    <td style="width: 50px; text-align: center;">
                        <form action="?" method="post" class="common-form">
                            <input type="hidden" name="action" value="deletedkimkey"/>
                            {if !$is_system_view|default:''}
                                <input type="hidden" name="id" value="{$id}"/>
                            {/if}
                            <input type="hidden" name="selector" value="{$v.selector}"/>
                            <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}"/>
                            <div class="form-controls">
                                <button onclick="return confirm('Are you sure?');">Delete</button>
                            </div>
                        </form>
                    </td>


                </tr>
            {/foreach}
        {else}
            <tr>
                <td colspan="3">No DKIM Keys</td>
            </tr>
        {/if}
        </tbody>
    </table>

    <form action="?" method="post" class="common-form">
        <input type="hidden" name="action" value="adddkimkey"/>
        <input type="hidden" name="id" value="{$id|default:null}"/>
        <fieldset>
            <legend>New DKIM Key</legend>
            <div class="inner">
                <div class="field">
                    {if $is_system_view|default:false }
                        <label>Key Name</label>
                    {else}
                        <label>Selector Name</label>
                    {/if}
                    <input name="selector" value="" type="text" class="textbox"/>
                    <p class="help"></p>
                </div>

                <h4>Key Source</h4>
                <label for="keysource-generate">Generate keys</label>
                <input type="radio" name="keysource" id="keysource-generate" value="generate">
                <label for="keysource-existing">Keys supplied</label>
                <input type="radio" name="keysource" id="keysource-existing" value="existing">
                {if !$is_system_view|default:false }
                <label for="keysource-existing">System Key</label>
                <input type="radio" name="keysource" id="keysource-system" value="system">

                <span style="display:none" id="keysource-system-box">
                    <div class="field">
					<label>Select the system wide DKIM key that will be applied to this group</label>
					<select name="dkimsystemkey">
                        {html_options output=$system_dkim_keys values=$system_dkim_keys }
                    </select>
					<p class="help"></p>
				</div>
                </span>
                {/if}

                <span style="display:none" id="keysource-existing-box">
                <div class="field">
					<label>Private Key</label>
					<textarea rows="10" cols="60" name="private_key" value="" type="text" class="textbox"/>
					<p class="help"></p>
				</div>
				<div class="field">
					<label>Public Key</label>
					<textarea rows="10" cols="60" name="public_key" value="" type="text" class="textbox"/>
					<p class="help"></p>
				</div>
                </span>

                <div class="form-controls">
                    <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}"/>
                    <button type="submit" name="submit">Create</button>
                </div>
            </div>
        </fieldset>
    </form>
</div>

<script>
    $('input[name="keysource"]').change(function () {
        this.value === "existing" ? $('#keysource-existing-box').show() :  $('#keysource-existing-box').hide();
        this.value === "system" ? $('#keysource-system-box').show() :  $('#keysource-system-box').hide();
    });
</script>