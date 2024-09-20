<!-- Main Common Content Block -->
<div class="main-common-block">
    <!-- Main Header -->
    <div class="main-common-header">
        <div style="width: 250px; float: right;">
            <input id="table-search" type="text" placeholder="Search anything" style="width: 100%;" onkeyup="productSearch()"/>
        </div>
        <h2>{$title|default:" "}</h2>
    </div>
    <!-- /Main Header -->

    <!-- Notification -->
    {if !empty($smarty_notifications)}{include file=$notification_template}{/if}
    <!-- /Notification -->
    <script type="text/javascript"> 

    var search_in_progress = false;
    function productSearch() {
        value = $('#table-search').val().trim();
        search = value.toLowerCase();
        $table = $('div.ui-tabs-panel[aria-hidden=false] table.table-products');
        $rows = $table.find('tbody > tr:not(.search-empty)');
        if (!search) {
            $table.find('.search-empty').remove();
            $rows.show();
            search_in_progress = false;
        } else if (!search_in_progress) {
            $table.find('.search-empty').remove();
            search_in_progress = true;
            found = 0;
            $rows.each(function() {
              $row = $(this).show();
              if ($row.text().trim().toLowerCase().indexOf(search) >= 0) {
                  found++;
              } else {
                  $row.hide();
              }
            });
            if (!found) {
                $table.find('tbody').append(
                    '<tr class="search-empty"><td style="color: #FF3333; text-align: center;" colspan="100%"></td></tr>'
                );
                $('.search-empty td').text('No results found for search \'' + value + '\'');
            }
            search_in_progress = false;
        }
    };

    $(function() {ldelim}
        $( "#tabs" ).tabs({ldelim}
            active   : Cookies.get('activetab-product-list'),
            activate : function( event, ui ){ldelim}
                Cookies.set( 'activetab-product-list', ui.newTab.index(),{ldelim}
                        expires : 7
                    {rdelim});
                    setTimeout(function(){ productSearch(); }, 100);
                {rdelim},
                show: {ldelim}
                effect: 'fade',
                duration: 400
            {rdelim},
            ajaxOptions: {ldelim}
                error: function( xhr, status, index, anchor ) {ldelim}
                    $( anchor.hash ).html("Woops...that didn't work." );
                {rdelim}
            {rdelim}
        {rdelim});
    {rdelim});
    </script>
    <div id="tabs" style="width: 100%; border: none;">
        <ul style="width: 100%;">
            <li><a href="?action=list_products&active=1&csrftoken={$smarty.session.csrftoken|default:''}">Active products</a></li>
            <li><a href="?action=list_products&active=0&csrftoken={$smarty.session.csrftoken|default:''}">Inactive products</a></li>
        </ul>
    </div>
    <form action="?" method="post" class="common-form">
        <fieldset>
            <legend>Add a new product</legend>
            <div class="inner">
                <div class="field">
                        <label>Name:</label>
                        <input class="textbox" type="text" name="product[name]" value="{$product.name|default:""}" maxlength="50" required="required" />
                        <p class="help">Product name contains only alpha-numeric, space and dash characters. Maximum 50 characters</p>
                </div>
                <div class="field">
                        <label>Billing Type:</label>
                        <select class="selectbox" name="product[billing_type]">{html_options options=$billing_types selected=$product.billing_type_id}</select>
                </div>
                <div class="form-controls">
                    <input type="hidden" name="csrftoken" value="{$smarty.session.csrftoken|default:''}" />
                    <input type="hidden" name="action" value="create_product" />
                    <button type="submit" name="submit">Create</button>
                </div>
            </div>
        </fieldset>
    </form>
</div>
