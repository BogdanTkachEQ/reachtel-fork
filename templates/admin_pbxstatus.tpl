                                <!-- Main Common Content Block -->
                                <div class="main-common-block">


                                        <!-- Main Header -->
                                        <div class="main-common-header">
						<p style="float: right;"><span id="totalcalls" style="font-weight: bold;">?</span> calls - <span id="ringcalls" style="font-weight: bold;">?</span> ringing and <span id="upcalls" style="font-weight: bold;">?</span> up.<br /><span id="pbxstatusloading" style="display: block; float: right;"><img src="/img/ajax-loading-bar-18.gif" title="Loading..." alt="Loading..." /></span></p>
                                                <h2>{$title|default:" "}</h2>
                                        </div>
                                        <!-- /Main Header -->

                                        <!-- Notification -->
                                        {if !empty($smarty_notifications)}{include file=$notification_template}{/if}
                                        <!-- /Notification -->

					<script type="text/javascript">

						loadPBXStatus();

				   		setInterval(function() {ldelim}
							loadPBXStatus();

			   			{rdelim}, 5000);

					</script>

					<table class="campaignTableCenter common-object-table" style="width: 700px; text-align: center;">
						<thead><tr><th style="width: 80px; text-align: center;">Source</th><th style="width: 80px; text-align: center;">Destination</th><th style="text-align: center;">Context</th><th style="width: 50px; text-align: center;">Duration</th><th style="width: 50px; text-align: center;">State</th><th style="width: 50px; text-align: center;">Hangup</th></tr></thead>
						<tbody id="calls">
							<tr><td id="call_message" colspan='6'></td></tr>
							<tr id="call_row_template"><td class="source"></td><td class="destination"></td><td class="context"></td><td class="duration"></td><td class="state"></td><td class="hangup"></td></tr>
						</tbody>
					</table>


				</div>
