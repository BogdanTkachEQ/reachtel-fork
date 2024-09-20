	                                <!-- Main Common Content Block -->
	                                <div class="main-common-block">

	                                	{include file=$search_notification_pagination_template}

	                                	<table class="campaignTableCenter common-object-table" width="800px">
	                                		<thead>
	                                			<tr>
	                                				<th>List</th>
													<th style="width: 50px; text-align: center;">Owner</th>
	                                				<th style="width: 50px; text-align: center;">Count</th>
	                                				<th style="width: 50px; text-align: center;">Download</th>
	                                				<th style="width: 50px; text-align: center;">Delete</th>
	                                			</tr>
	                                		</thead>
	                                		<tbody>
{if !empty($lists)}
{foreach from=$lists key=k item=v}
	                                			<tr>
	                                				<td>{$v.name|escape:html|highlight:$search nofilter}</td>
													<td style="width: 100px; text-align: center;">{$v.groupownername|escape:html}</td>
	                                				<td style="width: 50px; text-align: center;">{$v.count|number_format}</td>
	                                				<td style="width: 50px; text-align: center;"><a href="?action=download&amp;listid={$k}&amp;csrftoken={$smarty.session.csrftoken|default:''}"><span class="famfam" title="Download List" alt="Download List" style="background-position: -580px -320px;"></span></a></td>
	                                				<td style="width: 50px; text-align: center;"><a href="?action=delete&amp;listid={$k}&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></a></td>
	                                			</tr>
{/foreach}
{else}
	                                			<tr>
	                                				<td colspan="4">No Lists</td>
	                                			</tr>
{/if}
	                                		</tbody>
	                                	</table>

	                                	<form action="?" method="post" class="common-form">
	                                		<fieldset>
	                                			<legend>New Do Not Contact list</legend>
	                                			<div class="inner">
	                                				<div class="field">
	                                					<label>List name:</label>
	                                					<input name="name" value="" type="text" class="textbox" maxlength="25" />
	                                					<p class="help">Maximum 25 characters</p>
	                                				</div>
													<div class="field">
														<label>Owner</label>
														<select name="groupownerid" class="selectbox">
															<option value="">-- Select a group owner --</option>
															{html_options options=$user_groups values=$user_groups }
														</select>

													</div>
	                                				<div class="form-controls">
	                                					<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
	                                					<button type="submit" name="submit">Create</button>
	                                				</div>
	                                			</div>
	                                		</fieldset>
	                                	</form>

	                                	<form action="?" method="post" class="common-form">
	                                		<fieldset>
	                                			<legend>Do Not Contact list number management</legend>
	                                			<div class="inner">
	                                				<div class="column">
	                                					<div class="field">
	                                						<label>Destination</label>
	                                						<input name="destination" value="" type="text" class="textbox" />
	                                						<p class="help">Up to 250 characters</p>
	                                					</div>
	                                					<div class="field">
	                                						<label>Action</label>
	                                						<select name="operation" class="selectbox">
                                                                <option value="add" title="Add">Add</option>
                                                                <option value="remove" title="Remove">Remove</option>
                                                                <option value="check" title="Check">Check</option>
                                                            </select>
	                                					</div>
	                                					<div class="field">
	                                						<label>Do Not Contact list</label>
	                                						<select class="mediumdata selectbox" name="listid">{html_options options=$lists2}</select>
	                                					</div>
	                                					<div class="form-controls">
	                                						<input type="hidden" name="action" value="management" />
	                                						<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
	                                						<button type="submit" name="submit">Add</button>
	                                					</div>
	                                				</div>
	                                				<div class="column">
	                                					<div class="field">
	                                						<label>Type</label>
	                                						<select name="type" class="selectbox"><option value="phone" title="Phone">Phone</option><option value="email" title="Email">Email</option></select>
                                                            <p class="help">&nbsp;</p>
	                                					</div>
	                                					<div class="field">
	                                						<label>Region</label>
	                                						<select name="region" class="selectbox"><option value="AU" title="Australia">Australia</option><option value="NZ" title="New Zealand">New Zealand</option></select>
	                                					</div>
	                                				</div>
	                                			</div>
	                                		</fieldset>
	                                	</form>

										<form action="?" method="post" class="common-form">
											<fieldset>
												<legend>Do Not Contact List Owner</legend>
												<div class="inner">
													<div class="column">
														<div class="field">
															<label>Do Not Contact list</label>
															<select class="mediumdata selectbox" name="listid">{html_options options=$lists2}</select>
														</div>
														<div class="form-controls">
															<input type="hidden" name="action" value="management" />
															<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
															<input type="hidden" name="operation" value="group-owner" />
															<button type="submit" name="submit">Update</button>
														</div>
													</div>
													<div class="column">
														<div class="field">
															<label>List Group Owner</label>
															<select name="groupownerid" class="selectbox">
																<option value="">-- Select a group owner --</option>
																{html_options options=$user_groups values=$user_groups }
															</select>
														</div>
													</div>
												</div>
											</fieldset>
										</form>

	                                	<form enctype="multipart/form-data" action="?" method="post" class="common-form">
	                                		<fieldset>
	                                			<legend>Upload DNC data</legend>
	                                			<div class="inner">
	                                				<div class="column">
	                                					<div class="field">
	                                						<label>Do Not Contact list</label>
	                                						<select class="mediumdata selectbox" name="listid">{html_options options=$lists2}</select>
	                                					</div>
	                                					<div class="field">
	                                						<label>Type</label>
	                                						<select name="type" class="selectbox"><option value="phone" title="Phone">Phone</option><option value="email" title="Email">Email</option></select>
	                                					</div>
	                                					<div class="field">
	                                						<label>Region</label>
	                                						<select name="region" class="selectbox"><option value="AU" title="Australia">Australia</option><option value="NZ" title="New Zealand">New Zealand</option></select>
	                                					</div>
	                                					<div class="form-controls">
	                                						<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
	                                						<button type="submit" name="submit">Upload File</button>
	                                					</div>
	                                				</div>
	                                				<div class="column">
	                                					<div class="field">
	                                						<input type="hidden" name="MAX_FILE_SIZE" value="50000000" />
	                                						<input type="hidden" name="profile" value="1" />
	                                						<label>Upload DNC data:</label>
	                                						<input name="uploadedfile" type="file" class="file" />
	                                					</div>
	                                				</div>
	                                			</div>
	                                		</fieldset>
	                                	</form>

	                                </div>
