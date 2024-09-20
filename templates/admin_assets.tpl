                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

									{include file=$search_notification_pagination_template}

									<table class="campaignTableCenter common-object-table" width="600px">
										<thead>
											<tr>
												<th style="width: 50px;">Type</th>
												<th>Name</th>
												<th>Size</th>
												<th style="width: 50px; text-align: center;">Delete</th>
											</tr>
										</thead>
										<tbody>

{if !empty($files)}
{foreach from=$files key=k item=v}
											<tr>
												<td><img src="{$TE_IMAGE_LOCATION}icons/{$v.type}.png" alt="{$v.type|upper}" title="{$v.type|upper}" style="height: 16px; wigth: 16px;" /></td>
												<td><a href="admin_assets.php?assetid={$k}">{$v.name|escape:html|highlight:$search nofilter}</a></td>
												<td>{$v.size}</td>
												<td style="width: 50px; text-align: center;"><a href="?deleteasset={$k}&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" title="Delete File" alt="Delete File" style="background-position: -280px -160px;"></span></a></td>
											</tr>
{/foreach}
{else}
												<tr>
													<td colspan="4">No Assets</td>
												</tr>
{/if}
										</tbody>
									</table>

									<form action="?" enctype="multipart/form-data" method="post" class="common-form">
								        <fieldset>
									        <legend>Upload an asset</legend>
									        <div class="inner">
									            <div class="field">
							                        <label>Asset:</label>
							                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" /><input name="uploadedfile1" type="file" />
							                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" /><input name="uploadedfile2" type="file" />
							                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" /><input name="uploadedfile3" type="file" />
													<br />
							                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" /><input name="uploadedfile4" type="file" />
							                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" /><input name="uploadedfile5" type="file" />
							                        <input type="hidden" name="MAX_FILE_SIZE" value="10000000" /><input name="uploadedfile6" type="file" />
							                        <p class="help">Maximum 10MB per file</p>
							                	</div>
							                	<div class="form-controls">
													<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
							                        <button type="submit" name="submit">Upload</button>
									            </div>
									        </div>
								        </fieldset>
									</form>

								</div>
