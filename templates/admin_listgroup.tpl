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

									<!-- Pagination -->
									{if !empty($paginate_template)}{include file=$paginate_template}{else}<div>&nbsp;</div>{/if}
									<!-- /Pagination -->

									<div id="group">

										<script type="text/javascript">

												$(function() {ldelim}
														$( "#tabs" ).tabs({ldelim}
															active   : Cookies.get('activetab-group-{$setting.name}'),
															activate : function( event, ui ){ldelim}
																Cookies.set( 'activetab-group-{$setting.name}', ui.newTab.index(),{ldelim}
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

										<div id="tabs" style="width: 100%; border: none;">
											<ul style="width: 100%;">
													<li><a href="#tabs-settings">Settings</a></li>
													<li><a href="?name={$setting.name|urlencode}&amp;template=invoiceitems">Ad hoc invoice items</a></li>
													<li><a href="?name={$setting.name|urlencode}&amp;template=tags">Tags</a></li>
													<li><a href="?name={$setting.name|urlencode}&amp;template=smsdids">SMS DIDs</a></li>
													<li><a href="?name={$setting.name|urlencode}&amp;template=voicedids">Voice DIDs</a></li>
													<li><a href="?name={$setting.name|urlencode}&amp;template=emailsettings">Email</a></li>
													{if $id != ADMIN_GROUP_OWNER_ID}
													<li><a href="#tabs-disable" style="color: #FF3333;">Contract termination checklist</a></li>
													{/if}
											</ul>
											<div id="tabs-settings">
												<form action="?name={$setting.name}" method="post" class="common-form">
													<fieldset>
														<legend>{$setting.name} ({$id})</legend>
														<div class="inner">
															<div class="column">
																<div class="field">
																	<label>Group name:</label>
																	<input class="textbox" type="text" name="setting[name]" value="{$setting.name|default:""}" />
																	<p class="help">Group name</p>
																</div>
																<div class="field">
																	<label>Entity name:</label>
																	<input class="textbox" type="text" name="setting[customername]" value="{$setting.customername|default:""}" />
																	<p class="help">Customers name</p>
																</div>
																<div class="field">
																	<label>Selcomm Account No:</label>
																	<input class="textbox" type="text" name="setting[selcommaccountno]" value="{$setting.selcommaccountno|default:""}" />
																	<p class="help">Account number for linking with Selcomm for invoicing</p>
																</div>
                                                                <div class="field">
                                                                    <label>First Interval:</label>
                                                                    <input name="setting[firstinterval]" value="{$setting.firstinterval|default:30}" type="text" class="textbox" maxlength="7" />
                                                                    <p class="help">First billing interval in seconds</p>
                                                                </div>
																<div class="form-controls">
																	<input type="hidden" name="name" value="{$setting.name}" />
																	<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
																	<button type="submit" class="designated">Save</button>
																</div>
															</div>
															<div class="column">
																<div class="field">
																	<label>SFTP email notification TO:</label>
																	<input class="textbox" type="text" name="setting[sftpemailnotificationto]" value="{$setting.sftpemailnotificationto|default:""}" />
																	<p class="help">Email TO address for SFTP</p>
																</div>
                                                                <div class="field">
                                                                    <!-- Align with first interval field -->
                                                                    <br/><br/><br/>
                                                                </div>
                                                                <div class="field">
                                                                    <label>Next Interval:</label>
                                                                    <input name="setting[nextinterval]" value="{$setting.nextinterval|default:6}" type="text" class="textbox" maxlength="7" />
                                                                    <p class="help">Next billing interval in seconds</p>
                                                                </div>
															</div>
															<div class="clear-both"></div>
														</div>
													</fieldset>
												</form>
											</div>
											{if $id != ADMIN_GROUP_OWNER_ID}
											<div id="tabs-disable">
												<div>
													<p><strong>The following checklist should be completed when a client terminates all contracts with ReachTEL.</strong></p>
												</div>
												<h3 class="secondary-header" style="color: #FF3333;">1 - Disable all users</h3>
												<div>
													<p>
														Disable login access of all users part of this group.
														This task set user status to '<i>Disabled (closed)</i>'<br/>
														Right now, the {$name} group contains {$usersStatusMap.total} users:<br/>
														- {$usersStatusMap.active} active user(s)<br/>
														- {$usersStatusMap.disabled} disabled user(s)<br/>
													</p>
													<form action="?name={$setting.name|urlencode}" method="post" class="common-form">
														<div class="form-controls">
															<button type="submit" class="designated" onclick="javascript: if(confirm('Please confirm you want to disable all users access for this group. THIS ACTION CANNOT BE UNDONE!')) { jQuery.post('?', {ldelim}'action' : 'contract_termination_task', 'task' : 'disable_all_users_from_group', 'id' : '{$id}', 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function(r) {ldelim} alert(r.message); {rdelim}, 'json'); return false; } return false;">
																Disable access
															</button>
														</div>
													</form>
												</div>
												<br/>
												<h3 class="secondary-header" style="color: #FF3333;">2 - Remove all REST API tokens</h3>
												<div>
													<p>Delete REST API tokens of all users part of this group.</p>
													<form action="?name={$setting.name|urlencode}" method="post" class="common-form">
														<div class="form-controls">
															<button type="submit" class="designated" onclick="javascript: if(confirm('Please confirm you want to delete REST API tokens for this group. THIS ACTION CANNOT BE UNDONE!')) { jQuery.post('?', {ldelim}'action' : 'contract_termination_task', 'task' : 'delete_all_rest_tokens_from_group', 'id' : '{$id}', 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function(r) {ldelim} alert(r.message); {rdelim}, 'json'); return false; } return false;">
																Delete tokens
															</button>
														</div>
													</form>
												</div>
												<br/>
												<h3 class="secondary-header" style="color: #FF3333;">3 - Remove related cron jobs</h3>
												<div>
													<p>
														Unfortunately this action cannot be automated.<br/>
														A Morpheus admin is required to ensure that all related cron jobs are removed from
														<a href="/admin_cron.php" target="_blank">the admin cron tasks page.</a>
													</p>
												</div>
												<br/>
												<h3 class="secondary-header" style="color: #FF3333;">4 - Remove SFTP directories &amp; files</h3>
												<div>
													<p>
														Unfortunately this action cannot be automated.<br/>
														A ReachTEL developer is required to ensure that all SFTP files are removed
														from SFTP server(s).
													</p>
												</div>
												<br/>
												<h3 class="secondary-header" style="color: #FF3333;">5 - Remove User Group data</h3>
												<div>
													{if $delete_user_group_notBefore}
													<p>The records on this user group were scheduled to be deleted at {$delete_user_group_notBefore}. </p>													
													{else}
													<p>By clicking this button you will create a scheduled job to delete all of this customers information in {$QUEUE_NOT_BEFORE_DELETE_GROUP_RECORDS} stored within the following tables</p>
													<p>do_not_contact_data, sms_out, sms_out_status, sms_received, sms_sent, sms_status, wash_out, response_data, response_data_archive, call_results, call_results_archive, merge_data, merge_data_archive, targets, targets_archive, targets_out.</p>
													<p>This should ONLY be done when a customer has provided written notice to terminate their contract with ReachTEL</p>
													<form action="?name={$setting.name|urlencode}" method="post" class="common-form">
														<div class="form-controls">
															<button type="submit" class="designated" onclick="javascript: if(confirm('Please confirm you want to delete all records for this group. THIS ACTION CANNOT BE UNDONE!')) { jQuery.post('?', {ldelim}'action' : 'contract_termination_task', 'task' : 'delete_all_records_from_group', 'id' : '{$id}', 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function(r) {ldelim} alert(r.message); {rdelim}, 'json'); window.location.reload(false); return false; } return false;">
																Create a Scheduled Job
															</button>
														</div>
													</form>
													{/if}
												</div>
												<br/>
												<div class="clear-both"></div>
											</div>
											{/if}
										</div>
									</div>
								</div>
