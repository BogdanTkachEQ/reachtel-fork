
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

					<div id="smsdid">

						<script type="text/javascript">

							$(function() {ldelim}
								$( "#tabs" ).tabs({ldelim}
									active   : Cookies.get('activetab-sms-{$setting.name}'),
									activate : function( event, ui ){ldelim}
										Cookies.set( 'activetab-sms-{$setting.name}', ui.newTab.index(),{ldelim}
											expires : 7
										{rdelim});
									{rdelim},
									show: {ldelim}
										effect: 'fade',
										duration: 200
									{rdelim},
									ajaxOptions: {ldelim}
										error: function( xhr, status, index, anchor ) {ldelim}
											$( anchor.hash ).html("Woops...that didn't work." );
										{rdelim}
									{rdelim}
								{rdelim});
							{rdelim});
						</script>

{if $linked}
						<div class="breadcrumbs">
							<ol>
								<li>
									Linked Voice DID: <a href="admin_listvoicedid.php?name={$setting.name|urlencode}">{$setting.name}</a>
								</li>
							</ol>
							<div class="clear-both"></div>
						</div>
{/if}

						<div id="tabs" style="width: 100%; border: none;">
							<ul style="width: 100%;">
								<li><a href="#tabs-settings">Settings</a></li>
								<li><a href="#tabs-last10messages">History</a></li>
                                <li><a href="?name={$setting.name|urlencode}&template=campaigns">Campaigns</a></li>
								<li><a href="?name={$setting.name|urlencode}&template=tags">Tags</a></li>
							</ul>

							<div id="tabs-settings">
								{if $is_script_attached}
									<p style="color:red; font-weight:bold;">There is a script attached to this DID</p>
								{/if}
								<form action="?name={$setting.name|urlencode}" method="post" class="common-form">
									<fieldset>
										<input type="hidden" name="id" value="{$id}" />
										<legend>General information - {$setting.name} (ID: {$id})</legend>
										<div class="inner">
											<div class="column">
												<div class="field">
													<label>Use:</label>
													<input name="setting[use]" value="{$setting.use|default:""}" type="text" class="textbox" />
													<p class="help">Purpose of this DID</p>
												</div>
												<div class="field">
													<label>Link to campaign set opt-out and response handling:</label>
													<input name="setting[linktocampaign]" type="checkbox" {if isset($setting.linktocampaign) && $setting.linktocampaign == "on"}CHECKED{/if} />
													<p class="help">Use campaign settings for DNC list, email replies and delivery receipts.</p>
												</div>
												<div class="field">
													<label>Inbound SMS postback URL</label>
													<input class="textbox" type="text" name="setting[restpostback.smsreceive]" value="{$setting["restpostback.smsreceive"]|default:""}" />
													<p class="help">URL to send HTTP POST inbound SMS to</p>
												</div>
                                                <div class="field">
                                                    <label>Use on shore providers only</label>
                                                    <select class="mediumdata selectbox" name="setting[{$smarty.const.SMS_DID_SETTING_USE_ON_SHORE_PROVIDER}]">
                                                        {html_options values=$onshoreonlyoptions output=$onshoreonlyoptions selected=$setting[$smarty.const.SMS_DID_SETTING_USE_ON_SHORE_PROVIDER]}
                                                    </select>
                                                </div>
												<div class="field">
													<label>Enable Call me:</label>
													<input name="setting[enablecallme]" type="checkbox" {if isset($setting.enablecallme) && $setting.enablecallme == "on"}CHECKED{/if} />
													<p class="help">Enables call me feature for the did. Please ensure all tags are set.</p>
												</div>
												<div class="field">
													<label>Opt out to dnc:</label>
													<select class="mediumdata" name="setting[optouttodnc]" style="width: 100%;">{html_options options=$optouttodnc selected=$optouttodnc_selected}</select>
													<p class="help">Add dnc list id to opt out to. When the sms contains opt out message the number will be added to the dnc list mentioned.</p>
												</div>
												<div class="form-controls">
													<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
													<button type="submit" name="submit">Save</button>
												</div>
											</div>
											<div class="column">
												<div class="field">
													<label>SMS to Email status:</label>
													<select class="mediumdata selectbox" name="setting[sms2email]">{html_options options=$enableddisabled selected=$setting.sms2email|default:'disabled'}</select>
													<p class="help">Enable or disable SMS-to-Email functionality</p>
												</div>
												<div class="field">
													<label>SMS to Email destination:</label>
													<input name="setting[sms2emaildestination]" value="{$setting.sms2emaildestination|default:""}" type="text" class="textbox" />
													<p class="help">Catch-all email address to forward inbound SMS-to-Email requests to</p>
												</div>
												<div class="field">
													<label>SMS to Email route to user:</label>
													<select class="mediumdata selectbox" name="setting[sms2emailroutetouser]">{html_options options=$enableddisabled selected=$setting.sms2emailroutetouser|default:'disabled'}</select>
													<p class="help">Route SMS-to-Email messages back to the user that sent them?</p>
												</div>
												<div class="field">
													<label>SMS to Email Exclusion Filters:</label>
													<input name="setting[sms2emailexclusionfilters]" value="{$setting.sms2emailexclusionfilters|default:""}" type="text" class="textbox" />
													<p class="help">Sms text with any of these pipe separated key words will be excluded from getting sent(eg: test|exclusion|filters)</p>
												</div>
												<div class="field">
													<label>Group owner:</label>
													<select class="mediumdata selectbox" name="setting[groupowner]">{html_options options=$user_groups values=$user_groups selected=$setting.groupowner|default:$smarty.const.ADMIN_GROUP_OWNER_ID}</select>
													<p class="help">User group to which the sms did belongs</p>
												</div>
											</div>
										</div>
									</fieldset>
								</form>

							</div>

							<div id="tabs-last10messages">

								<form class="common-form">
									<fieldset>
										<legend>Last 10 inbound messages</legend>
										<div class="inner">
											<table class="subinfo common-object-table" width="750px">
												<thead>
													<tr>
														<th class="subinfoHeader">Timestamp</th>
														<th class="subinfoHeader">From</th>
														<th class="subinfoHeader">Content</th>
													</tr>
												</thead>
												<tbody>
													{if !empty($messagehistory)}
													{foreach from=$messagehistory key=k item=message}
													<tr>
														<td style="width: 150px;">{$message.timestamp|date_format:"%d/%m/%Y %T"}</td>
														<td style="width: 100px;">{$message.number}</td>
														<td style="width: 500px;">{$message.contents|default:''}</td>
													</tr>
													{/foreach}
													{else}
													<tr>
														<td colspan="3">No messages</td>
													</tr>
													{/if}
												</tbody>
											</table>
										</div>
									</fieldset>
								</form>

								<form action="?" method="post" class="common-form">
									<fieldset>
										<legend>Export messages</legend>
										<input type="hidden" name="id" value="{$id}" />
										<div class="inner">
											<div class="column">
												<div class="field">
													<label>Start date:</label>
													<input name="starttime" value="{$smarty.now|date_format:"%F"}" type="text" class="textbox" maxlength="10" />
													<p class="help">In the format of "{$smarty.now|date_format:"%F"}"</p>
												</div>
												<div class="field">
													<label>End date:</label>
													<input name="endtime" value="{$smarty.now|date_format:"%F"}" type="text" class="textbox" maxlength="10" />
													<p class="help">In the format of "{$smarty.now|date_format:"%F"}"</p>
												</div>
												<div class="form-controls">
													<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
													<button type="submit" name="action" value="exporthistory">Generate</button>
												</div>
											</div>
											<div class="column">
												<div class="field">
													<label>Message direction:</label>
													<select class="mediumdata selectbox" name="direction"><option value="inbound">Inbound</option><option value="outbound">Outbound</option></select>
												</div>
											</div>
											<div class="clear-both"></div>
										</div>
									</fieldset>
								</form>
							</div>
						</div>
					</div>
				</div>
