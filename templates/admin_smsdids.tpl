{assign var='is_child_view'  value=(isset($child_view) && $child_view)}

{if !$is_child_view}
								<!-- Main Common Content Block -->
                                <div class="main-common-block">

{include file=$search_notification_pagination_template}
{/if}

									<table class="campaignTableCenter common-object-table" width="600px">
										<thead>
											<tr>
												<th>DID</th>
												<th>Use</th>
{if !$is_child_view}
												<th style="width: 50px; text-align: center;">Delete</th>
{/if}
											</tr>
										</thead>
										<tbody>
{if !empty($dids)}
{foreach from=$dids key=k item=v}
											<tr>
												<td style="width: 100px;"><a href="admin_listsmsdid.php?name={$v.name|urlencode}">{$v.name|escape:html|highlight:$search nofilter}</a></td>
												<td>{if !empty($v.use)}{$v.use}{else}<span class="soft-notice">none</span>{/if}</td>
{if !$is_child_view}
												<td style="width: 50px; text-align: center;"><span onclick="javascript: if(confirm('Confirm delete?')) {ldelim} window.location = 'admin_smsdids.php?name={$v.name|urlencode}&amp;action=delete&amp;csrftoken={$smarty.session.csrftoken|default:''}'; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></span></td>
{/if}
											</tr>
{/foreach}
{else}
                                			<tr>
                                				<td colspan="3">No SMS DIDs</td>
                                			</tr>
{/if}
										</tbody>
									</table>
{if !$is_child_view}
									<form action="?" method="post" class="common-form">
										<fieldset>
											<legend>New SMS DID</legend>
											<div class="inner">
												<div class="field">
													<label>SMS DID:</label>
													<input name="name" value="" type="text" class="textbox" />
													<p class="help">Mobile DID in alphanumeric or e164 format</p>
												</div>
												<div class="form-controls">
													<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
													<button type="submit" name="submit">Create</button>
												</div>
											</div>
										</fieldset>
									</form>
								</div>
{/if}