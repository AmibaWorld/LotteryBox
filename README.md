# LotteryBox
HTML抽奖转盘，模拟现实生活的抽奖箱逻辑


# 优势 #
需求：当抽奖人数确定，并且要求在必定有固定人数抽到一等奖、二等奖..... 不能多也不能少，不能出现抽到的奖品大于奖品总数，也不能出现一个人都没有抽到（一等奖...）的情况

分析：现实生活的抽奖箱可以解决上述问题，本例就是模拟抽奖箱的方式。


# 现实抽奖箱（程序实现） #
1. 把写有奖品的卡片与没写奖品的卡片混合放到抽奖箱中（奖品代号随意放到数组中）
2. 抽奖时每个人从抽奖箱中抽取一张卡片（arrary_and()随机获取数组的一个下标）
3. 抽奖箱的内卡少了一张（uset()抽取到的元素，array_values()重新建立数组索引）
4. 下一个人抽奖（增加并发访问锁，防止两个人同时抽奖对奖池数据的污染）



# 涉及内容 #
1. PHP + MySQL
2. HTML5 canvas 绘图技术
3. MySQL联合查询
4. bootstrap前端布局
5. jQuery ajax请求
6. 微信服务号授权登录
7. 微信JS-SDK自定义分享内容
8. 奖品数组转化为json保存到文件
9. 并发访问文件锁
10. 随机获取数组中的元素


# MySQL Table #
1.lottery_award
![](./src/lottery_award.png)

2.lottery_userinfo
![](./src/lottery_userinfo.png)


# DEMO #
[http://wximg.gzxd120.com/Lottery/](http://wximg.gzxd120.com/Lottery/ "http://wximg.gzxd120.com/Lottery/")