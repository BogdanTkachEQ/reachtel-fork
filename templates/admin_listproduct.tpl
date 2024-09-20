<!-- Main Common Content Block -->
<div class="main-common-block">

	<!-- Main Header -->
	<div class="main-common-header">
			<h2>{$title|default:"Products"}</h2>
	</div>
	<!-- /Main Header -->

	<!-- Notification -->
	{if !empty($smarty_notifications)}{include file=$notification_template}{/if}
	<!-- /Notification -->

	<h3 class="secondary-header">{$product.name} ({$id})</h3>

	<div id="product">
        <form action="?id={$id}" method="post" class="common-form">
            <fieldset>
                <legend>General details</legend>
                <div class="inner">
                    <table style="width: 100%;">
                        <tr>
                            <td colspan="100%;">
                                SELCOMM product code <strong>{$product.code}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 50%;">
                                <div class="field">
                                    <label>Name:</label>
                                    <input class="textbox" type="text" name="product[name]" value="{$product.name|default:""}" maxlength="50" required="required" />
                                    <p class="help">
                                        Only alpha-numeric, space and dash characters. Maximum 50 characters
                                    </p>
                                </div>
                            </td>
                            <td style="width: 50%;">
                                <div class="field">
                                    <label>Billing Type:</label>
                                    <select class="selectbox" name="product[billing_type]">{html_options options=$billing_types selected=$product.billing_type_id}</select>
                                    <p class="help">
                                        Product billing yype
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div class="form-controls">
                        <input type="hidden" name="action" value="save_product" />
                        <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                        <button type="submit" name="submit">Save</button>
                    </div>
                </div>
            </fieldset>
        </form>
	</div>
</div>
