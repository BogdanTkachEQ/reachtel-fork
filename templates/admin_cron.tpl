									<!-- Main Common Content Block -->
									<div class="main-common-block">

										{include file=$search_notification_pagination_template}

										<table class="campaignTableCenter common-object-table" width="960px">
											<thead>
												<tr>
													<th>Task</th>
													<th style="width: 50px; text-align: center;">Status</th>
													<th style="width: 215px; text-align: center;">Last Run</th>
													<th style="width: 250px; text-align: center;">Timing (mn h dom mth dow)</th>
													<th style="width: 50px; text-align: center;">Delete</th>
												</tr>
											</thead>
											<tbody>
{if !empty($crons)}
{foreach from=$crons key=k item=v}
												<tr>
													<td><a href="admin_listcron.php?name={$v.name|urlencode}">{$v.name|escape:html|highlight:$search nofilter}</a></td>
													<td style="width: 50px; text-align: center;"><span class="famfam" style="background-position: {if $v.status == "ACTIVE"}0px 0px{else}-100px -420px{/if};"></span></td>
													<td style="text-align: center">{$v.lastrun|date_format:"%H:%M:%S %d/%m/%Y"|default:'never'} {if !empty($v.lastrun)}({$v.lastrun|api_misc_timeformat}){/if}</td>
													<td style="text-align: center">mn<strong>({$v.minute})</strong> h<strong>({$v.hour})</strong> dom<strong>({$v.dayofmonth})</strong> mth<strong>({$v.month})</strong> dow<strong>({$v.dayofweek})</strong></td>
													<td style="width: 50px; text-align: center;"><a href="admin_cron.php?id={$k}&amp;action=delete&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></a></td>
												</tr>
{/foreach}
{else}
												<tr>
													<td colspan="3">No cron tasts</td>
												</tr>
{/if}
											</tbody>
										</table>

										<form action="?" method="post" class="common-form">
											<fieldset>
												<legend>New cron</legend>
												<div class="inner">
													<div class="field">
														<label>Task name:</label>
														<input name="name" value="" type="text" class="textbox" maxlength="50" />
														<p class="help">Maximum 50 characters</p>
													</div>
													<div class="form-controls">
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<button type="submit" name="submit">Create</button>
													</div>
												</div>
											</fieldset>
										</form>

									</div>
