<div class="productsuggestion"> 
    {foreach from=$suggestions item="suggestion"}
        <h3>{$suggestion.category}</h3>
        <div class="products row">
            {foreach from=$suggestion.products item="product"}
                <div class="col-md-3">
                    {block name='product_miniature'}
                        {include file='catalog/_partials/miniatures/product.tpl' product=$product}
                    {/block}    
                </div>
            {/foreach}
        </div>
    {/foreach} 
</div>