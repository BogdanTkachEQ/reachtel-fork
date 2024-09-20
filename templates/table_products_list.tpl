<table class="table-products common-object-table" width="100%">
    <thead>
        <tr>
            <th style="width: 25px;">Code</th>
            <th>Product Name</th>
            <th>Category</th>
            <th style="width: 120px;">Created date</th>
            <th style="width: 120px;">Last update</th>
            <th style="width: 50px;">Actions</th>
        </tr>
    </thead>
    <tbody>
    {if isset($products) && !empty($products)}
        {foreach from=$products key=id item=product}
        <tr id="product-row-id-{$id}">
            <td>{$product.code}</td>
            <td>
                <a href="admin_listproduct.php?id={$id}">
                    {$product.name}
                </a>
            </td>
            <td>{$product.category_name}</td>
            <td><small>{$product.created|date_format:"%a %e %b at %l:%M %p"}</small></td>
            <td><small>{$product.updated|date_format:"%a %e %b at %l:%M %p"}</small></td>
            <td style="text-align: center;">
            <img src="/img/ajax-loading-bar-18.gif" class="loader" style="display: none;">
            <span class="button famfam"
                  style="background-position: {if $active}-260px -140px{else}-400px -460px{/if};"
                  alt="{if $active}Disable{else}Enable{/if} this product"
                  title="{if $active}Disable{else}Enable{/if} this product"
                  onclick="javascript: if(confirm('Please confirm you want to {if $active}disable{else}enable{/if} product \'{$product.name}\' (code {$product.code})')) {ldelim} $(this).hide(); $('#product-row-id-{$id} img.loader').css('display', 'block'); jQuery.post('', {ldelim}'action': 'product_set_status', 'id': '{$id}', 'activate': {if $active}0{else}1{/if}, 'csrftoken': '{$smarty.session.csrftoken|default:''}'{rdelim}, function() {ldelim} $('#product-row-id-{$id}').remove(); {rdelim}); {rdelim}"
            ></span>
            </td>
        </tr>
        {/foreach}
    {else}
        <tr>
            <td style="text-align: center;" colspan="100%">
                No {if !$active}in{/if}active products
            </td>
        </tr>
    {/if}
    </tbody>
</table>