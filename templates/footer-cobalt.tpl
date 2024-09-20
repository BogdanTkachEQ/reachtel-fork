                                <!-- /Main Common Content Block -->


                	</div>
        	</div>
	</div>

	<!-- Left Column -->
	<div class="content-left">

		<div class="inner-wrapper">

			<!-- Common Block -->
			<div class="common-block" style="text-align: center;">
				<a href="https://www.reachtel.com.au"><img src="//static.reachtel.com.au/reachtel-150.png" title="ReachTEL" alt="ReachTEL" style="height: 150px; width: 150px;" /></a>
			</div>
			<!-- /Common Block -->

			<!-- Left Navigation -->
			<div class="common-block">
			  <h2>Command Centre</h2>
				<div class="left-navigation">
					<ul>
						<li>
							<ul>
								<li><a href="admin_campaigns.php">Campaigns</a></li>
								<li><a href="admin_dialplans.php">Dial Plans</a></li>
								<li><a href="admin_emailtemplates.php">Email Templates</a></li>
								<li><a href="admin_audio.php">Audio</a></li>
								<li><a href="admin_assets.php">Assets</a></li>
							</ul>
						</li>
					</ul>
					<ul>
						<li>
							<ul>
				       	 			<li><a href="admin_voicedids.php">Voice DIDs</a></li>
				       	 			<li><a href="admin_smsdids.php">SMS DIDs</a></li>
				        			<li><a href="admin_donotcontact.php">Do Not Contact Lists</a></li>
				        			<li><a href="admin_users.php">Users</a></li>
{if api_security_isadmin($smarty.session.userid) || api_security_check(ZONE_USER_GROUPS_LISTALL, null, true)}
				        			<li><a href="admin_groups.php">User Groups</a></li>
{/if}
				        			<li><a href="admin_lists.php">Lists</a></li>
{if api_security_isadmin($smarty.session.userid)}
                                    <li><a href="admin_stats.php">Stats</a></li>
{/if}
							</ul>
						</li>
					</ul>
					<ul>
						<li>
							<ul>
								<li><a href="admin_invoicing.php">Invoicing</a></li>
{if api_security_isadmin($smarty.session.userid)}
                                    <li><a href="admin_billing_products.php">Billing Products</a></li>
{/if}
								<li><a href="admin_system.php">System / Misc</a></li>
								<li><a href="admin_surveyweight.php">Survey Weight</a></li>
							</ul>
						</li>
					</ul>
					<ul>
						<li>
							<ul>
{if api_security_isadmin($smarty.session.userid)}
						        	<li><a href="admin_search.php">Results Search</a></li>
						        	<li><a href="admin_baddata.php">Bad data Search</a></li>
{/if}
						        	<li><a href="admin_pbxstatus.php">PBX Status</a></li>
							</ul>
						</li>
					</ul>

				</div>
			</div>
			<!-- /Left Navigation -->

			<!-- Common Info Block -->
			<div class="common-block">
				<h2>Information</h2>
				<p>
					<u>Page generation time</u>: {$load_time} msec{if !empty($profiledata)}<br />{$profiledata}{/if}<br />
					<u>Server</u>: {$server}<br />
				</p>
			</div>
			<!-- /Common Info Block -->
		</div>
	</div>
	<!-- /Left Column -->
{if !empty($javascript)}
  <script type="text/javascript">
        {$javascript}
  </script>
{/if}


</body>
</html>

