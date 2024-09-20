                                <!-- Main Common Content Block -->
                                <div class="main-common-block">

                                	{include file=$search_notification_pagination_template}

                                	<table class="campaignTableCenter common-object-table" width="600px">
                                		<thead><tr><th>Group Name</th><th style="width: 50px; text-align: center;">Delete</th></tr></thead>
                                		<tbody>
{if !empty($elements)}
	{foreach from=$elements key=k item=v}
                                			<tr>
                                				<td><a href="admin_listsurveyweight.php?name={$v|urlencode}">{$v|escape:html|highlight:$search nofilter}</a></td>
                                				<td style="width: 50px; text-align: center;"><a href="?id={$k}&amp;action=delete&amp;csrftoken={$smarty.session.csrftoken|default:''}" onclick="javascript: if(!confirm('Confirm delete?')) {ldelim} return false; {rdelim}"><span class="famfam" style="background-position: -280px -160px;" title="Delete" alt="Delete"></span></a></td>
                                			</tr>
	{/foreach}
{else}
                                			<tr>
                                				<td colspan="2">No Weightings</td>
                                			</tr>
{/if}
                                		</tbody>
                                	</table>

                                	<form action="?" method="post" class="common-form">
                                		<fieldset>
                                			<legend>Add a weighting</legend>
                                			<div class="inner">
                                				<div class="field">
                                					<label>Weighting name:</label>
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

									<form action="?" method="post" class="common-form">
										<fieldset>
											<legend>Generate national survey weighting</legend>
											<div class="inner">
												<div class="column">
													<div class="field">
														<label>Campaign search term:</label>
														<input name="searchterm" value="" type="text" class="textbox" placeholder="Seven-11October16-National" maxlength="100" />
														<input type="hidden" name="action" value="nationalsurveyweight" />
														<p class="help">The search term to use to locate the campaigns to report on.</p>
													</div>
													<div class="form-controls">
														<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
														<button type="submit" name="submit">Generate</button>
													</div>
												</div>

												<div class="clear-both"></div>
											</div>
										</fieldset>
									</form>

                                	<p>Weighting data should primarily be sourced from the <a href="http://www.aec.gov.au/Enrolling_to_vote/Enrolment_stats/elector_count/" target="_blank">AEC website</a>.</p>
                                        <p>Another source for other regions is the <a href="http://www.ausstats.abs.gov.au/ausstats/nrpmaps.nsf/NEW+GmapPages/national+regional+profile" target="_blank">ABS website</a>.</p>

                                </div>