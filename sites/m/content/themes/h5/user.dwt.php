<?php
/*
Name: 用户中心模板
Description: 这是用户中心首页
Libraries: page_menu,page_header
*/
defined('IN_ECJIA') or header("HTTP/1.0 404 Not Found");exit('404 Not Found');
?>
<!-- {extends file="ecjia-touch.dwt.php"} -->

<!-- {block name="footer"} -->
<script type="text/javascript">
	ecjia.touch.user.init();
</script>
<!-- {/block} -->

<!-- {block name="main-content"} -->
<div class="ecjia-user-info user-new-info ecjia-user">
    {if $user}
    	<a href="{url path='user/profile/init'}"><div class="user-img ecjiaf-fl"><img src="{$user_img}" alt=""></a></div>
    	<div class="ecjiaf-fl ecjia-margin-l user-rank-name">
    		<span>{$user.name}</span>
    		<span class="ecjia-user-buttom">{$user.rank_name}</span>
    	</div>
    	<a href="{url path='user/user_message/msg_list'}">
    		{if $order_num.msg_num}
    		<span class="ecjia-icon ecjia-icon ecjia-icon-num">{$order_num.msg_num}</span>
    		{/if}
    	</a>
	{else}
	   	<a href="{url path='user/privilege/login'}"><div class="no-login">登录 / 注册</div></a>
	{/if}
</div>

{if $user.id}
<div class="ecjia-user-head ecjia-user ecjia-color-green ecjia-user-marg-t">
    <ul class="ecjia-user-marg-t ecjia-list list-short ecjia-user-no-border-t">
       <li>
        	<a href="{url path='user/account/init'}">
        		<div class="icon-wallet"><img src="{$theme_url}/images/user_center/75x75_1.png"></div>
        		<span class="icon-name">{t}我的钱包{/t}</span>
        		<i class="iconfont  icon-jiantou-right"></i>
        	</a>
        </li>
    </ul>
</div>
<div class="ecjia-user-border-b">
    <ul class="ecjia-user ecjia-list bonus ecjia-nav-child-f ecjia-list-three ecjia-login-nav-bottom">
    	<li>
    		<a href="{url path='user/account/balance'}">
    		    <p>{$user.formated_user_money}</p>
    			<p>余额</p>
    		</a>
    	</li>
    	<li>
    		<a href="{url path='user/bonus/init'}">
    		    <p>{if $user.user_bonus_count eq '0'}{0}{else}{$user.user_bonus_count}{/if}</p>
    			<p>红包</p>
    		</a>
    	</li>
    	<li>
    	    <a href="{url path='user/account/init'}">
        		<p>{$user.user_points}</p>
        		<p>积分</p>
    		</a>
    	</li>
    </ul>
</div>
{else}
<div class="ecjia-user-head ecjia-user ecjia-color-green ecjia-user-marg-t">
    <ul class="ecjia-user-marg-t ecjia-list list-short ecjia-user-no-border-t">
       <li>
        	<a href="{url path='user/privilege/login'}">
        		<div class="icon-wallet"><img src="{$theme_url}/images/user_center/75x75_1.png"></div>
        		<span class="icon-name">{t}我的钱包{/t}</span>
        		<i class="iconfont  icon-jiantou-right"></i>
        	</a>
        </li>
    </ul>
</div>
<div class="ecjia-user-border-b">
    <ul class="ecjia-user ecjia-list bonus ecjia-nav-child-f ecjia-list-three ecjia-login-nav-bottom">
    	<li>
    		<a href="{url path='user/privilege/login'}">
    		    <p>{'- -'}</p>
    			<p>余额</p>
    		</a>
    	</li>
    	<li>
    		<a href="{url path='user/privilege/login'}">
    		    <p>{'- -'}</p>
    			<p>红包</p>
    		</a>
    	</li>
    	<li>
    	    <a href="{url path='user/privilege/login'}">
        		<p>{'- -'}</p>
        		<p>积分</p>
    		</a>
    	</li>
    </ul>
</div>
{/if}

<div class="ecjia-user ecjia-margin-b">
    <ul class="ecjia-list list-short">
		<li>
			<a href="{url path='user/address/address_list'}">
        		<div class="icon-address-list"><img src="{$theme_url}/images/user_center/75x75_3.png"></div>
        		<span class="icon-name">收货地址</span>
        		<i class="iconfont  icon-jiantou-right"></i>
        	</a>
		</li>
       	<li>
        	<a class="nopjax external" href="{url path='user/index/spread'}&name={$user.name}">
        		<div class="icon-expand"><img src="{$theme_url}/images/user_center/expand.png"></div>
        		<span class="icon-name">我的推广</span>
        		<i class="iconfont  icon-jiantou-right"></i>
        	</a>
        </li>
    </ul>

    <ul class="ecjia-list list-short">
        <li>
        	<a class="external" href="tel:xxx">
        		<div class="icon-website-service"><img src="{$theme_url}/images/user_center/75x75_5.png"></div>
        		<span class="icon-name">联系客服</span>
        		<span class="icon-price">{'xxx'}</span>
        		<i class="iconfont  icon-jiantou-right"></i>
        	</a>
        </li>
        <!-- <li>
        	<a class="external" href="https://ecjia.com" target="_blank">
        		<div class="icon-offical-website"><img src="{$theme_url}/images/user_center/75x75_6.png"></div>
        		<span class="icon-name">官网网站</span>
        		<span class="icon-price">{'www.ecjia.com'}</span>
        		<i class="iconfont  icon-jiantou-right"></i>
        	</a>
        </li> -->
    </ul>
   <!--  <ul class="ecjia-list list-short">
        <li>
        	<a class="external" href="{url path='article/help/init'}">
        		<div class="icon-help-center"><img src="{$theme_url}/images/user_center/help75_3.png"></div>
        		<span class="icon-name">帮助中心</span>
        		<i class="iconfont  icon-jiantou-right"></i>
        	</a>
        </li>
    </ul> -->
    <!-- <ul class="ecjia-list list-short">
            <li>
            	<a class="external" href="{RC_uri::url('article/shop/detail')}&title={$value.title}&article_id={$value.id}">
            		<div class="icon-shop-info"><img src="{$value.image}"></div>
            		<span class="icon-name">{$value.title}</span>
            		<i class="iconfont  icon-jiantou-right"></i>
            	</a>
            </li>
    </ul> -->
</div>
<!-- #BeginLibraryItem "/library/model_bar.lbi" --><!-- #EndLibraryItem -->
<!-- {/block} -->