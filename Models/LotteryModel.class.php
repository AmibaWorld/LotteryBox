<?php
/**
 * Created by Greatfar
 * Date: 2017/5/21
 * Time: 11:24
 */

class LotteryModel{

    /**
     * AwardModel constructor.
     */
    function __construct(){
    }


    /**
     * 检查是否已经分配奖项
     * @return mixed 邀请标记
     */
    function checkAward() {
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //检查cookie参数
        $openid = !empty($_COOKIE[openid]) ? $_COOKIE[openid] : exit();
        //查询数据库
        $sql_code = "select * from lottery_userinfo where openid=?";
        $sth = $pdo->prepare($sql_code);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        $arr_flag = $sth->fetch();
        //如果还没有分配奖项
        if($arr_flag['isaward'] == "false"){
            $this->distributeAward($openid, $pdo);
        }
    }


    /**
     * 分配奖品
     * @param $openid 微信openid
     */
    function distributeAward($openid, $pdo) {
        //奖品数组，0-->iphone 7，1-->三星，2-->小米
        $arr_award = array(2,1,2,2,2,2,2,2,2,2,2,2,2,2,2,1,2,2,2,2,2,2,2,2,2,2,2,0,2,2,
            2,2,2,2,2,2,2,2,2,1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,2,2,2,0,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,1,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,2,2,2,2,1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,2,2,2,2,2,2,2,2,2,2,1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
            2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,1,2,2,2,2
        );
        // 并发访问，php文件锁,加锁
        $fp = fopen('./Public/file/a.lock', 'r'); // php的文件锁和表没关系，随便一个文件即可
        if(flock($fp, LOCK_EX)){    //排他锁
            //查询award表记录总数
            $sql_total = "select count(*) from lottery_award";
            $sth = $pdo->prepare($sql_total);
            $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
            $arr_total_award = $sth->fetch();                          //匹配成数组
            $totalNumber = $arr_total_award[0];                        //获取数组第一个元素，即为记录总数
            if($totalNumber > 300){                                   //抽奖人数超过300人后停止抽奖
                $totalNumber = 300;
            }
            $award = $arr_award[$totalNumber];
            //向lottery_award表插入记录
            $sql_award_ins = "INSERT INTO lottery_award (openid, award) values (?, ?)";
            $sth = $pdo->prepare($sql_award_ins);
            $sth->execute(array($openid, $award)) or die("数据库错误: " . $sth->errorInfo()[2]);
            // php的文件锁，释放锁
            flock($fp, LOCK_UN);
        }else{
            echo "action cancel : locking file error! please try again!";
        }
        //关闭文件
        fclose($fp);
        //更新分配奖项标记isaward
        $sql_upd_flag = "update lottery_userinfo set isaward='true' where openid=?";
        $sth = $pdo->prepare($sql_upd_flag);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
    }


    /**
     * 获取抽奖状态
     * @return mixed 抽奖状态
     */
    function getAwardStatus() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //检查cookie参数
        $openid = !empty($_COOKIE[openid]) ? $_COOKIE[openid] : exit();
        //查询数据库
        $sql_code = "select * from lottery_award where openid=?";
        $sth = $pdo->prepare($sql_code);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetch();
    }


    /**
     * 标记为已抽奖状态
     */
    function markAwardStatus() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //获取openid
        $openid = !empty($_COOKIE[openid]) ? $_COOKIE[openid] : exit();
        //更新award表
        $adate = date("Y-m-d H:i:s");
        $sql_award_upd = "update lottery_award set adate = ?, ishidden='true' where openid = ?";
        $sth = $pdo->prepare($sql_award_upd);
        $sth->execute(array($adate, $openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
    }


    /**
     * 查询获奖列表
     * @return mixed 获奖列表数组
     */
    function getAwardList() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //内联查询语句，查询出已抽奖的人，aflag = true
        $sql_award_uniquery = "SELECT lottery_userinfo.headimgurl, lottery_userinfo.nickname, lottery_userinfo.sex, lottery_award.award, lottery_award.adate FROM lottery_award JOIN lottery_userinfo ON lottery_userinfo.openid = lottery_award.openid AND lottery_award.ishidden = 'true'";
        $sth = $pdo->prepare($sql_award_uniquery);
        $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetchAll();    //返回查询结果集
    }


    /**
     * 获取个人奖品信息
     * @return mixed 个人奖品信息
     */
    function awardToken() {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //获取openid
        $openid = !empty($_COOKIE[openid]) ? $_COOKIE[openid] : exit();
        //联合查询-内联
        $sql_award_uniquery = "SELECT lottery_userinfo.headimgurl, lottery_userinfo.nickname, lottery_userinfo.sex, lottery_award.award, lottery_award.adate FROM lottery_award INNER JOIN lottery_userinfo ON lottery_userinfo.openid = lottery_award.openid AND lottery_award.openid = ?";
        $sth = $pdo->prepare($sql_award_uniquery);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetch();    //返回查询结果数组
    }


}