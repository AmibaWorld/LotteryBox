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
        header("Content-type:text/html;charset=utf-8");
        //检查是否已经登录
        require './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2Fquizzes%2Findex.php%3Fc%3DQuizzes%26a%3DcollectUserInfo";
        $wechat->loginCheck($redirect_url, true);
        //获取openid
        $openid = !empty($_COOKIE['openid']) ? $_COOKIE['openid'] : die("fuck you");
        //检查抽奖资格
        require_once './Models/QuizzesModel.class.php';
        $qz = new QuizzesModel();
        if(!$qz->checkAwardPermission($openid)){
            echo "<meta http-equiv='refresh' content='3; url=./index.php' /><h2 align='center'>你没有抽奖资格，3秒后自动返回首页</h2>";
            exit();
        }
        //检查抽奖状态
        require './Models/LotteryModel.class.php';
        $lottery = new LotteryModel();
        $invflag = $lottery->checkAward();
        //获取抽奖状态
        $arr_award = $lottery->getAwardStatus();
        //载入首页视图
        require_once './Views/award.html';
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
        require './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2Fquizzes%2Findex.php%3Fc%3DQuizzes%26a%3DcollectUserInfo";
        $wechat->loginCheck($redirect_url, true);
        //获取页码
        $page = !empty($_GET['page']) ? $_GET['page'] : 1;
        //实例化模型层
        require './Models/LotteryModel.class.php';
        $lottery = new LotteryModel();
        //分页数据计算
        $perNumber = 20;    //每页显示的记录数
        $paginationNumber = 5;  //页码导航显示的页码个数
        $totalNumber  = $lottery->getTotalRecord();     //获取记录总数
        $totalPage = ceil ( $totalNumber / $perNumber );    //计算页数
        $paginationNumber = min($totalPage, $paginationNumber);  //如果定义的页码导航显示页码个数大于实际总页数时，把页码导航页码个数重置为实际页数
        $endPage = $page + floor($paginationNumber/2) <= $totalPage ? $page + floor($paginationNumber/2) : $totalPage;  //计算页码导航结束页号
        $startPage = $endPage - $paginationNumber + 1;  //计算页码导航开始页号
        if($startPage < 1) {  //处理页码导航开始页号小于1的情况
            $endPage -= $startPage - 1;  //把结束页码重置为实际最大页码
            $startPage = 1;
        }
        $startCount = ($page - 1) * $perNumber;    // 根据页码计算出开始的记录
        //获取获奖列表
        $arr_sqldata = $lottery->getAwardList($startCount, $perNumber);
        //载入获奖列表视图
        require_once './Views/awardlist.html';
    }


    /**
     * 领奖令牌
     */
    function getAwardTokenAction() {
        //检查是否已经登录
        require './Models/WechatModel.class.php';
        $wechat = new WechatModel();
        $redirect_url = "http%3A%2F%2Fwximg.gzxd120.com%2Fquizzes%2Findex.php%3Fc%3DQuizzes%26a%3DcollectUserInfo";
        $wechat->loginCheck($redirect_url, true);
        //获取个人获奖信息
        require './Models/LotteryModel.class.php';
        $lottery = new LotteryModel();
        $arr_sqldata = $lottery->awardToken();
        require_once './Views/token.html';
    }


}