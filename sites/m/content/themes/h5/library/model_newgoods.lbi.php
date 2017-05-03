<?php
/*
Name: 新品推荐
Description: 这是首页的新品推荐模块
*/
defined('IN_ECJIA') or header("HTTP/1.0 404 Not Found");exit('404 Not Found');
?>
<div class="ecjia-mod ecjia-promotion-model ecjia-margin-t">
	<div class="hd ecjia-sales-hd ecjia-new-goods">
		<h2><i class="icon-goods-new"></i>新品推荐<a href="{$more_news}" class="more_info">更多</a></h2>
	</div>
	<div class="swiper-container swiper-promotion">
		<div class="swiper-wrapper">
			<!-- {foreach from=$new_goods item=val} 循环商品 -->
			<div class="swiper-slide">
				<a class="list-page-goods-img" href="{RC_Uri::url('goods/index/show')}&goods_id={$val.id}">
					<span class="goods-img"><img src="{$val.img.thumb}" alt="{$val.name}"></span>
					<span class="list-page-box">
						<span class="goods-name">{$val.name}</span>
						<span class="list-page-goods-price">
							<!--{if $val.promote_price}-->
							<span>{$val.promote_price}</span>
							<!--{else}-->
							<span>{$val.shop_price}</span>
							<!--{/if}-->
						</span>
					</span>
				</a>
			</div>
			<!-- {/foreach} -->
		</div>
	</div>
</div>
