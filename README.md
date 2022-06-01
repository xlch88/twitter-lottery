# twitter-lottery
推特转发关注评论抽奖脚本

# 目录结构
- data : 存放各类数据
	- adaptive.json : 引用推文数据
	- retweeters.json : 转推数据
	- tweetDetail.json : 主推文以及评论数据
- output : 
	- comment.json : 整理好的全部评论数据
	- ineligible.json : 参与但是不符合条件的人员以及不符合条件的原因
	- retweet.json : 转推人员名单(不包括引用)
	- retweetWithComment.json : 推文引用
	- users.json : 全部用户清单
		- retweet : 转推
		- retweetWithComment : 引用
		- comment : 评论
		- followed : 已关注(仅限参与过该主题互动)
		- lucky : 幸运鹅
		- winner : 中奖者
	- users_data.json : 用户的twitter原始数据

# 使用
```php
php open.php
```