                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

								{include file=$search_notification_pagination_template}

								<table class="campaignTableCenter common-object-table" width="600px">
									<thead>
										<tr>
											<th>Template</th>
										</tr>
									</thead>
									<tbody>
{if !empty($emailtemplates)}
{foreach from=$emailtemplates key=k item=v}
										<tr>
											<td><a href="admin_listemailtemplate.php?name={$v|urlencode}">{$v|escape:html|highlight:$search nofilter}</a></td>
										</tr>
{/foreach}
{else}
										<tr>
											<td>No Email Templates</td>
										</tr>
{/if}
									</tbody>
								</table>

								<form action="?" method="post" class="common-form">
									<fieldset>
										<legend>New Email Template</legend>
										<div class="inner">
											<div class="field">
												<label>Template name:</label>
												<input name="name" value="" type="text" class="textbox" maxlength="50" />
												<p class="help">Maximum 50 characters</p>
											</div>
											<div class="field">
												<label>Group owner:</label>
												<select name="groupowner" style="width: 350px;">{html_options options=$user_groups values=$user_groups}</select>
											</div>
											<div class="form-controls">
												<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
												<button type="submit" name="submit">Create</button>
											</div>
										</div>
									</fieldset>
								</form>

							</div>
