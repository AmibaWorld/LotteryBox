<?php
/**
 * Created by Greatfar
 * Date: 2017/5/21
 * Time: 11:12
 */

class LotteryController{

    function __construct(){

    }


    /**
     * 首页
     */
    function indexAction() {
        //检查是否已经登录
        require './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2FLottery%2Findex.php%3Fc%3DLottery%26a%3DcollectUserInfo";
        $wechat->loginCheck($redirect_url, true, "snsapi_userinfo", "userinfo");
        //检查抽奖状态
        require './Models/LotteryModel.class.php';
        $lottery = new LotteryModel();
        $invflag = $lottery->checkAward();
        //获取抽奖状态
        $arr_award = $lottery->getAwardStatus();
        //载入首页视图
        require_once './Views/index.html';
    }


    /**
     * 微信登录-用户信息收集动作
     */
    function collectUserInfoAction() {
        header("Content-type:text/html;charset=utf-8");
        //调用微信模块-授权登录方法
        require_once './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $user_data = $wechat->wxOAuthLogin("lottery_userinfo");
    }


    /**
     * 标记抽奖状态
     */
    function markAwardStatusAction() {
        require_once './Models/LotteryModel.class.php';
        $lottery = new LotteryModel();
        $lottery->markAwardStatus();
    }


    /**
     * 获奖列表
     */
    function awardListAction() {
        //检查是否已经登录
//        require './Models/WechatModel.class.php';
//        $wechat = new WechatModel();
//        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2FLottery%2Findex.php%3Fc%3DLottery%26a%3DcollectUserInfo";
//        $wechat->loginCheck($redirect_url, true, "snsapi_userinfo", "userinfo");
        //获取获奖列表
        require './Models/LotteryModel.class.php';
        $lottery = new LotteryModel();
        $arr_sqldata = $lottery->getAwardList();
        require_once './Views/awardlist.html';
    }


    /**
     * 领奖令牌
     */
    function getAwardTokenAction() {
        //检查是否已经登录
        require './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2FLottery%2Findex.php%3Fc%3DLottery%26a%3DcollectUserInfo";
        $wechat->loginCheck($redirect_url, true, "snsapi_userinfo", "userinfo");
        //获取个人获奖信息
        require './Models/LotteryModel.class.php';
        $lottery = new LotteryModel();
        $arr_sqldata = $lottery->awardToken();
        require_once './Views/token.html';
    }


}