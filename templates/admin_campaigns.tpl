                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

									{include file=$search_notification_pagination_template}

                                    <div>
                                        <a onclick="$('#search_tips').toggle(); return false;" style="font-size: 8pt; cursor:pointer;">want search tips?</a>
                                        <div id="search_tips" class="common-form" style="font-size: 8pt; display: none; background: #f7f6dc;">
                                            <fieldset style="padding: 5px;">
                                                <strong>You can activate the expression syntax using the 's:' prefix<br/>
                                                You can search campaign settings</strong>: {$searchable}<br/><br/>
                                                <code><i>Reachtel* s: owner == 5</i></code><br/>
                                                &nbsp;&nbsp;Return campaign(s) where name contains 'Reachtel' and owner user id = 5<br/><br/>
                                                <code><i>s: maxchannels >= 100 and stopateod == 1</i></code><br/>
                                                &nbsp;&nbsp;Return campaign(s) with maxchannels >= 100 with stopateod<br/><br/>
                                                <code><i>s: smsdid in 1..5 and region in ["AU", "NZ"]</i></code><br/>
                                                &nbsp;&nbsp;Return AU and NZ campaign(s) using SMSDID ids between 1 and 5<br/><br/>
                                                <code><i>s: tags matches "/public-holidays/" and not (tags matches "/NSW/") </i></code><br/>
                                                &nbsp;&nbsp;Search in campaign tags that contains 'public-holidays' but not 'NSW'<br/><br/>
                                                <code><i>s: sendrate * 60 / maxchannels > 5000</i></code><br/>
                                                &nbsp;&nbsp;Do the maths !<br/><br/>
                                            </fieldset>
                                        </div>
                                    </div>

									<table class="campaignTableCenter common-object-table" width="100%">
										<thead>
											<tr>
												<th style="width: 25px;">Type</th>
												<th style="width: 25px; text-align: center;">
												    <a href="?{if empty($activeonly)}activeonly=true{/if}">Status</a>
												</th>
                                                <th style="width: auto;">Name</th>
                                                <th style="width: 150px; text-align: center;">Created</th>
                                                <th style="width: 150px; text-align: center;">Group owner</th>
                                                {if !empty($customsettings)}
                                                {foreach from=$customsettings item=customsetting}
                                                    <th style="width: 75px; text-align: center; color: #666; background-color: #f6f6f6">
                                                        <small>{$customsetting}<small>
                                                    </th>
                                                {/foreach}
                                                {/if}
											</tr>
										</thead>
										<tbody>
{if !empty($campaigns)}
{foreach from=$campaigns key=k item=v}
											<tr>
												<td>
												    <span class="famfam" style="background-position: {if ($v.type == "phone")}-100px -440px;{elseif ($v.type == "sms")}-300px -340px;{elseif ($v.type == "wash")}-600px -140px;{else}-180px -180px;{/if}"></span>
												</td>
												<td style="text-align: center;">
												    <span class="famfam" style="background-position: {if ($v.status == "HIDDEN")}-180px -560px{elseif $v.status == "ACTIVE"}0px 0px{else}-100px -420px{/if};"></span>
												</td>
                                                <td>
                                                   <a href="admin_listcampaign.php?name={$v.name|urlencode}">
                                                       {$v.name|escape:html|highlight:$search nofilter}
                                                   </a>
                                                </td>
                                                <td style="text-align: center;">
                                                    {if isset($v.created)}
                                                        <small>{$v.created|date_format:"%a %e %b at %l:%M %p"}</small>
                                                    {/if}
                                                </td>
                                                <td style="text-align: center;">
                                                    {if isset($v.groupowner) && !empty($group_map[$v.groupowner])}
                                                        <a href="/admin_listgroup.php?id={$v.groupowner}" target="_blank">{$group_map[$v.groupowner]}</a>
                                                        <small>({$v.groupowner})</small>
                                                    {/if}
                                                </td>
                                                {if !empty($customsettings)}
                                                {foreach from=$customsettings item=customsetting}
                                                    <td>{if !empty($v[$customsetting])}{$v[$customsetting]}{/if}</th>
                                                {/foreach}
                                                {/if}
											</tr>
{/foreach}
{else}
											<tr>
												<td colspan="100%">No campaigns found</td>
											</tr>
{/if}
										</tbody>
									</table>

									<h3 class="secondary-header">New Campaign</h3>
									<form action="?" method="post" class="common-form" id="newcampaign" target="_self">
										<fieldset>
											<div class="inner">
												<div class="field">
													<label>Campaign Name</label>
													<input name="newcampaign" value="{$namesuggestion|default:""}" type="text" class="textbox" />
													<p class="help">5 to 40 characters, numbers, hyphens or spaces.</p>
												</div>
												<div class="field">
													<label>Type</label>
													<select name="type" class="selectbox"><option value="phone" title="Phone">Phone</option><option value="sms" title="SMS">SMS</option><option value="email" title="Email">Email</option><option value="wash" title="Wash">Wash</option></select>
												</div>
												<div class="field">
													<label>Duplicate</label>
													<select class="mediumdata selectbox" name="duplicate"><option label="-- NO DUPLICATION --" value="">-- NO DUPLICATION --</option>{html_options options=$dupeoptions selected=$dupeselected|default:""}</select>
												</div>
												<div class="form-controls">
													<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
													<button type="submit" name="submit">Create</button>
													<p class="help"><input type="checkbox" onclick="javascript: toggleOpenInNewWindow('newcampaign');" />&nbsp;Open in new window</p>
												</div>
											</div>
										</fieldset>
									</form>

	                        </div>
