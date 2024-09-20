                                <!-- Main Common Content Block -->
                                <div class="main-common-block">


                                	<!-- Main Header -->
                                	<div class="main-common-header">
                                		<h2>{$title|default:" "}</h2>
                                	</div>
                                	<!-- /Main Header -->

                                	<!-- Notification -->
                                	{if !empty($smarty_notifications)}{include file=$notification_template}{/if}
                                	<!-- /Notification -->
									<div class="flash flash-message"><b>**Invoicing is a deprecated feature. The look up feature is for looking up old invoices.</b></div>
                                	<form action="?" method="post" class="common-form" name="invoicelookup" id="invoicelookup" onsubmit="javascript: var output = $('#invoicelookup input:radio[name=outputmethod]:checked').val(); if(output == 'display') {ldelim} $('#invoicelookupinvoice').attr('target', '_blank'); {rdelim} else {ldelim} $('#invoicelookupinvoice').removeAttr('target'); {rdelim} if(output == 'email') {ldelim} var check = prompt('This will email an invoice to the customer. Have you checked it before sending?\n\nPlease type OK to continue.'); if((check != null) && (check.toLowerCase() == 'ok')) {ldelim} return true; {rdelim} else {ldelim} return false; {rdelim} {rdelim}">
                                		<fieldset>
                                			<legend>Invoice Lookup</legend>
                                			<div class="inner">
                                				<div class="column">
                                					<div class="field">
                                						<label>Invoice Number:</label>
                                						<input name="invoicenumber" type="text" class="textbox" maxlength="25" />
                                						<p class="help">Enter the full invoice number</p>
                                					</div>
                                					<div class="field">
                                						<label>Output method:</label>
                                						<ul class="option-group">
                                							<li><input type="radio" name="outputmethod" value="display" id="display" checked="checked"><label for="display">Display</label></input></li>
                                							<li><input type="radio" name="outputmethod" value="pdf" id="pdf"><label for="pdf">PDF</label></input></li>
                                							<li><input type="radio" name="outputmethod" value="email" id="email"><label for="email">Email</label></input></li>
                                						</ul>
                                						<div class="clear-both"/>
                                						<p class="help">Choose whether to download a PDF or display the results to screen</p>
                                					</div>
                                					<div class="field">
                                						<label>Send to Xero:</label>
                                						<ul class="option-group">
                                							<li><input type="checkbox" name="sendtoxero" value="yes" id="sendtoxero"><label for="sendtoxero"></label></input></li>
                                						</ul>
                                						<div class="clear-both"/>
                                						<p class="help">Choose whether to submit the invoice to Xero</p>
                                					</div>
                                					<div class="form-controls">
                                						<input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                                						<button type="submit" name="action" value="invoicelookup">Search</button>
                                					</div>
                                				</div>
                                				<div class="column">
                                				</div>
                                			</div>
                                		</fieldset>
                                	</form>

                                </div>

                                <script type="text/javascript">

                                	var counter = 0;

                                	function invoiceAddRow(){ldelim}

	                                	counter++;
    	                            	$('#invoiceRows').append('<tr id="extra' + counter + '"><td><input name="otherinvoiceitems[name][]" value="" type="text" maxlength="100" style="text-align: left; width: 100%;" /></td><td><select name="otherinvoiceitems[type][]" style="width: 100%;"><option value="phone">Voice usage</option><option value="voice service">Voice service</option><option value="sms usage">SMS usage</option><option value="sms service">SMS service</option><option value="email">Email usage</option><option value="email service">Email service</option><option value="portal service">Portal service</option><option value="portal set up">Portal set up</option><option value="data validation">Data validation</option><option value="other">Other</option></select></td><td><input name="otherinvoiceitems[units][]" style="text-align: center; width: 100%;" value="1" type="text" maxlength="20" /></td><td><input name="otherinvoiceitems[price][]" style="width: 100%; text-align: right;" value="0.00" type="text" maxlength="10" /></td><td><span class="famfam" style="background-position: -280px -160px;" onclick="javascript: invoiceDeleteRow(' + counter + ');"></span></td></tr>');

                                	{rdelim}

                                	function invoiceDeleteRow(row){ldelim}

                                		$('#extra' + row).remove();

                                	{rdelim}

                                </script>