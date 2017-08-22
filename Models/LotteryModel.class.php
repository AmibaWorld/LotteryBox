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
        $sql_code = "SELECT COUNT(*) FROM quizzes_award WHERE openid=?";
        $sth = $pdo->prepare($sql_code);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        $str_myaward = (int)$sth->fetchColumn();
        //如果还没有分配奖项
        if($str_myaward == 0){
            $this->distributeAward($openid, $pdo);
        }
    }


    /**
     * 分配奖品
     * @param $openid 微信openid
     */
    function distributeAward($openid, $pdo) {
        //奖品数组，0-->一等奖 7，1-->二等奖，2-->三等奖，3-->不中奖
        $arr_award = array(2,3,1,2,3,1,3,2,3,0,3,2,3,1,3,3,2,3,3,2,3,3,1,3,2,3,3,0,3,1,
            3,1,3,2,3,3,1,0,3,3,1,3,3,2,3,2,1,3,3,2,3,3,3,2,3,3,3,1,3,3,
            3,3,3,1,3,2,3,1,3,3,2,3,0,3,3,3,1,3,3,2,3,3,1,3,3,2,3,3,2,3,
            1,3,0,3,3,1,3,3,2,3,3,2,3,3,1,3,3,2,3,3,2,3,2,3,3,1,3,2,3,3,
            3,3,1,3,2,3,3,1,3,2,3,3,3,2,3,2,2,3,3,0,3,3,3,2,3,3,3,2,3,2,
            3,0,3,2,3,1,3,3,2,3,3,2,1,2,2,1,2,2,2,2,2,2,2,1,1,2,1,1,2,1,
            2,2,2,1,1,2,2,0,2,1,3,1,3,2,3,2,3,2,3,2,3,2,3,1,3,3,2,3,3,3,
            3,2,3,1,3,2,3,3,3,1,3,3,3,3,3,3,3,3,2,3,3,2,3,3,3,3,3,2,3,3,
            3,3,3,3,2,3,2,3,3,3,3,2,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,
            3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3,3
        );
        // 并发访问，php文件锁,加锁
        $fp = fopen('./Public/file/a.lock', 'r'); // php的文件锁和表没关系，随便一个文件即可
        if(flock($fp, LOCK_EX)){    //排他锁
            //查询award表记录总数
            $sql_total = "select count(*) from quizzes_award";
            $sth = $pdo->prepare($sql_total);
            $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
            $arr_total_award = $sth->fetch();                          //匹配成数组
            $totalNumber = $arr_total_award[0];                        //获取数组第一个元素，即为记录总数
            if($totalNumber > 300){                                   //抽奖人数超过300人后停止抽奖
                $totalNumber = 300;
            }
            $award = $arr_award[$totalNumber];
            //向quizzes_award表插入记录
            $sql_award_ins = "INSERT INTO quizzes_award (openid, award) values (?, ?)";
            $sth = $pdo->prepare($sql_award_ins);
            $sth->execute(array($openid, $award)) or die("数据库错误: " . $sth->errorInfo()[2]);
            // php的文件锁，释放锁
            flock($fp, LOCK_UN);
        }else{
            echo "获取并发访问文件锁失败";
        }
        //关闭文件
        fclose($fp);
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
        $sql_code = "select * from quizzes_award where openid=?";
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
        $sql_award_upd = "update quizzes_award set adate = ?, ishidden='true' where openid = ?";
        $sth = $pdo->prepare($sql_award_upd);
        $sth->execute(array($adate, $openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
    }


    /**
     * 获取记录总数
     * @return string 记录数量
     */
    public function getTotalRecord(){
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //获取记录总数
        $sth = $pdo->query("SELECT count(*) FROM quizzes_award WHERE ishidden = 'true' AND award <> '3'");
        return $sth->fetchColumn();     //匹配成行数，结果为字符串类型
    }


    /**
     * 查询获奖列表
     * @return mixed 获奖列表数组
     */
    function getAwardList($startCount, $perNumber) {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //内联查询语句，查询出已抽奖的人，aflag = true
        $sql_award_uniquery = "SELECT quizzes_userinfo.headimgurl, quizzes_score.uname, quizzes_score.dept, quizzes_award.award, quizzes_award.adate FROM quizzes_award JOIN quizzes_userinfo ON quizzes_award.openid=quizzes_userinfo.openid AND quizzes_award.ishidden = 'true' AND quizzes_award.award <> '3' JOIN quizzes_score ON quizzes_award.openid=quizzes_score.openid ORDER BY quizzes_award.award ASC, quizzes_award.adate ASC LIMIT $startCount,$perNumber";
        $sth = $pdo->prepare($sql_award_uniquery);
        $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetchAll();    //返回获奖列表数组
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
        $sql_award_uniquery = "SELECT quizzes_userinfo.headimgurl, quizzes_userinfo.nickname, quizzes_userinfo.sex, quizzes_award.award, quizzes_award.adate FROM quizzes_award INNER JOIN quizzes_userinfo ON quizzes_userinfo.openid = quizzes_award.openid AND quizzes_award.openid = ?";
        $sth = $pdo->prepare($sql_award_uniquery);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetch();    //返回查询结果数组
    }


}