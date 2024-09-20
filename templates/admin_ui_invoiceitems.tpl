<table class="subinfo common-object-table" width="750px">
	<thead>
		<tr>
			<th class="subinfoHeader">Charge Date</th>
			<th class="subinfoHeader">Description</th>
			<th class="subinfoHeader">Adhoc Product</th>
			<th class="subinfoHeader">Units</th>
			<th class="subinfoHeader">&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<tr class="invoiceitems">
			<td style="width: 100px;"><input type="text" style="text-align: left; width: 100%;" id="chargedate" onkeydown="if (event.keyCode == 13) document.getElementById('invoiceadd').click();" /></td>
			<td style="width: 350px;"><input type="text" style="text-align: left; width: 100%;" id="itemname" onkeydown="if (event.keyCode == 13) document.getElementById('invoiceadd').click();" /></td>
			<td style="width: 150px;">
                <select class="selectbox" name="type" id="type" style="width: 100%;">
                    {html_options options=$active_adhoc_products}
                </select>
            </td>
			<td style="width: 50px;"><input type="text" style="text-align: center; width: 100%;" id="units" value="1" maxlength="6" onkeydown="if (event.keyCode == 13) document.getElementById('invoiceadd').click();" /></td>
			<td style="width: 25px;"><img id="invoiceadd" onclick="javascript: addItem(); return false;"  src="{$TE_IMAGE_LOCATION}icons/add.png" height="16px" width="16px" alt="Add item" title="Add item" style="border: none;"/></td>
		</tr>
{if !empty($invoiceitems)}
{foreach from=$invoiceitems key=k item=v}
		<tr>
			<td style="text-align: center;">{$v.chargedate|date_format:"%d/%m/%Y"|default:''}</td>
			<td>{$v.itemname}</td>
			<td style="text-align: center; {if isset($v.type) && !isset($active_adhoc_products[$v.type])}color: red;" title="WARNING: This adhoc product is disabled"{else}"{/if}>{if isset($v.type)}{$adhoc_products[$v.type]}{/if}</td>
			<td style="text-align: center;">{$v.units}</td>
			{if in_array($k, $invoice_items_past_billing_run)}
				<td><img src="{$TE_IMAGE_LOCATION}icons/tick.png" height="16px" width="16px" alt="Billing run completed" title="Billing run completed"></span></td>
			{else}
				<td><span onclick="javascript: jQuery.post('?', {ldelim}'name' : '{$name|default:''}', 'action' : 'deleteinvoiceitem', 'itemid' : '{$k}', 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function() {ldelim} $('#tabs').tabs('load', $('#tabs').tabs('option', 'active')); {rdelim}); return false;"><span class="famfam" style="background-position: -280px -160px;" alt="Delete item" title="Delete item"></span></span></td>
			{/if}
		</tr>
{/foreach}
{else}
		<tr>
			<td colspan="6">No items</td>
		</tr>
{/if}
	</tbody>
</table>
<script type="text/javascript">
{literal}
function addItem() {
	var chargedate = $('#chargedate').val();
	var itemname = $('#itemname').val().trim();
	var type = $('#type').val();
	var units = $('#units').val().trim();

	// check required
	if (
		chargedate === ''
		|| itemname === ''
		|| type === ''
		|| units === ''
	) {
		alert('ERROR: Please fill in all fields');
		return false;
	}

{/literal}
	jQuery.post(
		'?',
		{ldelim}
			'name': '{$name|default:''}',
			'action': 'addinvoiceitem',
			'chargedate': chargedate,
			'itemname': itemname,
			'type': type,
			'units': units,
			'csrftoken': '{$smarty.session.csrftoken|default:''}'
		{rdelim}
	).done(function() {ldelim}
			$('#tabs').tabs('load', $('#tabs').tabs('option', 'active'));
		{rdelim}
	).fail(function(jqXHR) {ldelim}
			var response = false;
			try {
				response = JSON.parse(jqXHR.responseText);
			} catch(e) {}
			if (response) {ldelim}
				alert(response.message);
			{rdelim} else {ldelim}
				alert('ERROR: Something went wrong.');
			{rdelim}
		{rdelim}
	);
{literal}
}

$(function() {
	$('#chargedate').datepicker({
		dateFormat: 'dd/mm/yy',
		minDate: 0
	})
});
{/literal}
</script>
