                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

									{include file=$search_notification_pagination_template}

									<div class="breadcrumbs">
										<ol>
											<li class="first"><a href="admin_listdialplan.php?name=ReachTEL-InboundPinLine">PIN line</a></li>
											<li><a href="admin_listdialplan.php?name=inbound-dids">DID management</a></li>
										</ol>
										<div class="clear-both"></div>
									</div>

									<table class="campaignTableCenter common-object-table" width="600px">
										<thead>
											<tr>
												<th>Dialplan</th>
											</tr>
										</thead>
										<tbody>
{if !empty($dialplans)}
{foreach from=$dialplans key=k item=v}
											<tr>
												<td><a href="admin_listdialplan.php?name={$v|urlencode}">{$v|escape:html|highlight:$search nofilter}</a></td>
											</tr>
{/foreach}
{else}
											<tr>
												<td>No Dialplans</td>
											</tr>
{/if}
										</tbody>
									</table>

									<form action="?" method="post" class="common-form">
								        <fieldset>
									        <legend>New dialplan</legend>
									        <div class="inner">
								                <div class="field">
							                        <label>Dialplan name:</label>
							                        <input name="name" value="" type="text" class="textbox" maxlength="75" />
							                        <p class="help">Maximum 75 characters</p>
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
