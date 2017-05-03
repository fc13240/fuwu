<?php
/*
Name: 选择支付方式
Description: 选择支付方式模板
Libraries: page_menu,page_header
*/
defined('IN_ECJIA') or header("HTTP/1.0 404 Not Found");exit('404 Not Found');
?>
<!-- {extends file="ecjia-touch.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
</script>
<!-- {/block} -->

<!-- {block name="main-content"} -->
<!-- #EndLibraryItem -->
<form class="ecjia-form" id="theForm" name="theForm" action='{url path="cart/flow/checkout" args="address_id={$address_id}&rec_id={$rec_id}"}' method="post">
    <div class="ecjia-select ecjia-margin-t">
        <span class="select-title ecjia-margin-l">您总共有{$data.your_integral}积分</span>
        <div class="input">
            <input type="text" name="integral" value="{$temp.integral}" placeholder="您本单最多可以使用{$data.order_max_integral}积分">
        </div>
        <div class="ecjia-margin-t ecjia-margin-b">
            <input type="hidden" name="address_id" value="{$address_id}">
            <input type="hidden" name="rec_id" value="{$rec_id}" />
            <input type="hidden" name="integral_update" value="1" />
			<input class="btn btn-info" name="" type="submit" value="确定"/>
        </div>
    </div>
</form>
<!-- {/block} -->