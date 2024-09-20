                                <!-- Main Common Content Block -->
                                <div class="main-common-block">


				{include file=$search_notification_pagination_template}

				<table class="campaignTableCenter common-object-table" width="600px">
				        <thead><tr><th>Name</th><th style="width: 50px; text-align: center;">Rows</th><th style="width: 50px; text-align: center;">Download</th><th style="width: 50px; text-align: center;">Delete</th></tr></thead>
				        <tbody>
{if !empty($elements)}
  {foreach from=$elements key=k item=v}
						<tr>
							<td>{$v.name}</td>
                                                        <td style="width: 50px; text-align: center;">{$v.rows|number_format}</td>
                                                        <td style="width: 50px; text-align: center;"><a href="?action=download&amp;name={$v.name|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}"><span class="famfam" title="Download List" alt="Download List" style="background-position: -580px -320px;"></span></a></td>
                                                        <td style="width: 50px; text-align: center;"><a href="?action=delete&amp;name={$v.name|urlencode}&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></a></td>
						</tr>
  {/foreach}
{else}
						<tr>
							<td colspan="4">No Lists</td>
						</tr>
{/if}
					</tbody>
				</table>

                                        <form action="?" method="post" enctype="multipart/form-data" class="common-form">
                                                <fieldset>
                                                        <legend>Upload new list</legend>
                                                        <input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
                                                        <div class="inner">
                                                                <div class="field">
                                                                        <label>Name:</label>
                                                                        <input type="text" name="name" value="" class="textbox" />
                                                                </div>
                                                                <div class="field">
                                                                        <label>Upload new file</label>
                                                                        <input type="file" name="file" class="file" style="width: 100%;" class="textbox" />
                                                                </div>
                                                                <div class="form-controls">
									<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                                                        <button type="submit" name="submit">Save</button>
                                                                </div>
                                                        </div>
                                                </fieldset>
                                        </form>

			</div>
