                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

{include file=$search_notification_pagination_template}

									<table class="campaignTableCenter common-object-table" width="600px">
					        			<thead>
					        				<tr>
					        					<th>Group Name</th>
					        				</tr>
					        			</thead>
					        			<tbody>
{if !empty($groups)}
{foreach from=$groups key=k item=v}
											<tr>
												<td><a href="admin_listgroup.php?name={$v|urlencode}">{$v|escape:html|highlight:$search nofilter}</a></td>
											</tr>
{/foreach}
{else}
											<tr>
												<td>No Groups</td>
											</tr>
{/if}
										</tbody>
									</table>

									<form action="?" method="post" class="common-form">
								        <fieldset>
									        <legend>Add a group</legend>
											<div class="inner">
								                <div class="field">
								                        <label>Group name:</label>
								                        <input name="name" value="" type="text" class="textbox" maxlength="25" />
								                        <p class="help">Maximum 25 characters</p>
								                </div>
								                <div class="form-controls">
													<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
								                    <button type="submit" name="submit">Create</button>
								                </div>
								        	</div>
								        </fieldset>
									</form>

								</div>