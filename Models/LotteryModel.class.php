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
     * 检查是否已经抽奖
     * @return mixed 邀请标记
     */
    public function checkAward($openid) {
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //查询数据库
        $sql_code = "SELECT COUNT(*) FROM lottery_award WHERE openid=?";
        $sth = $pdo->prepare($sql_code);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        $str_myaward = (int)$sth->fetchColumn();
        //如果还没有抽奖
        if($str_myaward == 0){
            $this->storeAward($this->getAward(),$openid, $pdo);
        }
    }


    /**
     * 从奖池中抽取奖品
     * @return int 返回奖品代号
     */
    private function getAward() {
        $award_value = 3;  //奖品，3代表不中奖
        // 并发访问，php文件锁,加锁
        $filename = './Public/file/award_pool.json';
        $fp = fopen($filename, 'r+');
        if(flock($fp, LOCK_EX)){    //排他锁
            //读取奖池文件
            $json_award_pool = fread($fp, filesize($filename));
            $arr_award_pool = json_decode($json_award_pool);
            //判断奖池是否还有奖品
            if(0 != count($arr_award_pool)){
                //抽取奖品
                $award_key = array_rand($arr_award_pool);  //php内置函数，随机获取数组的一个key，等同于在抽奖箱随机摸一张卡片
                $award_value = $arr_award_pool[$award_key];
                unset($arr_award_pool[$award_key]);  //从奖池中删除该奖品
                $arr_award_pool = array_values($arr_award_pool);  //重建数组索引,否则转json时会被转成关联数组
                //更新奖池文件
                $json_award_pool = json_encode($arr_award_pool);
                ftruncate($fp,0);  //清空文件内容
                rewind($fp);  //把文件读写指针倒回文件头，因为上面读取了一次文件，文件读写指针目前指向文件尾部，需要倒回
                fwrite($fp, $json_award_pool);
            }
            // php的文件锁，释放锁
            flock($fp, LOCK_UN);
        }else{
            echo "获取文件锁失败";
        }
        //关闭文件
        fclose($fp);
        return $award_value;  //返回抽到的奖品
    }


    /**
     * 存储奖品到数据库
     * @param $award_value  奖品
     * @param $openid  微信openid
     * @param $pdo PDO链接对象
     */
    private function storeAward($award_value, $openid, $pdo){
        //存储奖品信息
        $sql_award_ins = "INSERT INTO lottery_award (openid, award) values (?, ?)";
        $sth = $pdo->prepare($sql_award_ins);
        $sth->bindParam(1, $openid);
        $sth->bindParam(2, $award_value, PDO::PARAM_INT);
        $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
    }


    /**
     * 获取抽奖状态
     * @return mixed 抽奖状态
     */
    public function getAwardStatus($openid) {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //查询数据库
        $sql_code = "select * from lottery_award where openid=?";
        $sth = $pdo->prepare($sql_code);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetch();
    }


    /**
     * 标记为已抽奖状态
     */
    public function markAwardStatus($openid) {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //更新award表
        $sql_award_upd = "update lottery_award set adate = ?, ishidden='false' where openid = ?";
        $sth = $pdo->prepare($sql_award_upd);
        $sth->bindParam(1, time(), PDO::PARAM_INT);
        $sth->bindParam(2, $openid);
        $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
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
        $sth = $pdo->query("SELECT count(*) FROM lottery_award WHERE ishidden = 'true' AND award <> '3'");
        return $sth->fetchColumn();     //匹配成行数，结果为字符串类型
    }


    /**
     * 查询获奖列表
     * @return mixed 获奖列表数组
     */
    public function getAwardList($startCount, $perNumber) {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //内联查询语句，查询出已抽奖的人，aflag = true
        $sql_award_uniquery = "SELECT lottery_userinfo.headimgurl, lottery_userinfo.nickname, lottery_award.award, lottery_award.adate FROM lottery_award JOIN lottery_userinfo ON lottery_award.openid=lottery_userinfo.openid AND lottery_award.ishidden = 'false' AND lottery_award.award <> '3' ORDER BY lottery_award.award ASC, lottery_award.adate ASC LIMIT $startCount,$perNumber";
        $sth = $pdo->prepare($sql_award_uniquery);
        $sth->execute() or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetchAll();    //返回获奖列表数组
    }


    /**
     * 获取个人奖品信息
     * @return mixed 个人奖品信息
     */
    public function awardToken($openid) {
        //连接数据库
        require_once './Models/DatabaseModel.class.php';
        $db = new DatabaseModel();
        $pdo = $db->connectDatabase();
        //联合查询-内联
        $sql_award_uniquery = "SELECT lottery_userinfo.headimgurl, lottery_userinfo.nickname, lottery_userinfo.sex, lottery_award.award, lottery_award.adate FROM lottery_award INNER JOIN lottery_userinfo ON lottery_userinfo.openid = lottery_award.openid AND lottery_award.openid = ?";
        $sth = $pdo->prepare($sql_award_uniquery);
        $sth->execute(array($openid)) or die("数据库错误: " . $sth->errorInfo()[2]);
        return $sth->fetch();    //返回查询结果数组
    }


}