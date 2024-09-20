				<!-- Main Common Content Block -->
				<div class="main-common-block">

					{include file=$search_notification_pagination_template}

					<div class="breadcrumbs">
						<ol>
							<li class="first"><a href="admin_users.php?action=inactive">Inactive users</a></li>
						</ol>
						<div class="clear-both"></div>
					</div>

					<table class="campaignTableCenter common-object-table" width="600px">
						<thead>
							<tr>
								<th>User name</th><th style="width: 50px; text-align: center;">Active</th>
							</tr>
						</thead>
						<tbody>
{if !empty($users)}
{foreach from=$users key=k item=v}
							<tr>
								<td>
									<a href="admin_listuser.php?name={$v.username|urlencode}">{$v.username|escape:html|highlight:$search nofilter}</a>
								</td>
								<td style="width: 50px; text-align: center;">
									<span class="famfam" style="background-position: {if $v.status == "1"}0px 0px{else}-100px -420px{/if};"></span>
								</td>
							</tr>
{/foreach}
{else}
							<tr>
								<td colspan="2">No Users</td>
							</tr>
{/if}
						</tbody>
					</table>

					<form action="?" method="post" class="common-form">
						<fieldset>
							<legend>Add a user</legend>
							<div class="inner">
								<div class="field">
									<label>User name:</label>
									<input name="name" value="" type="text" class="textbox" maxlength="25" />
									<p class="help">Maximum 25 characters</p>
								</div>
								<div class="field">
									<label>Duplicate</label>
									<select class="mediumdata selectbox" name="duplicate">
										<option label="-- NO DUPLICATION --" value="" selected="selected">-- NO DUPLICATION --</option>
										{html_options options=$dupeoptions}
									</select>
								</div>

								<div class="form-controls">
									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
									<button type="submit" name="submit">Create</button>
								</div>
							</div>
						</fieldset>
					</form>


					<form enctype="multipart/form-data" action="?" method="post" class="common-form">
						<fieldset>
							<legend>Bulk create users</legend>
							<div class="inner">
								<div class="field">
									<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
									<label>Upload new users:</label>
									<input name="uploadedfile" type="file" class="file" />
									<p class="help">A CSV file with a header row with columns <i>username</i>, <i>firstname</i>, <i>lastname</i>, <i>emailaddress</i>.</p>
								</div>
								<div class="field">
									<label>Duplicate</label>
									<select class="mediumdata selectbox" name="duplicate">
										<option label="-- Select a user --" value="" selected="selected">-- Select a user --</option>
										{html_options options=$dupeoptions}
									</select>
								</div>
								<div class="field">
									<label>Send password reset requests?</label>
									<input name="sendpasswordreset" type="checkbox" />
									<p class="help">If checked, email password reset requests will be sent to the new user</p>
								</div>
								<div class="form-controls">
									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
									<input type="hidden" name="action" value="bulkcreate" />
									<button type="submit" name="submit">Create users</button>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
