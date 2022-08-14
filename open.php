<?php
$users						= [];
$followedUsers				= [];

$retweet					= [];
$retweetWithComment			= [];
$comment					= [];

$retweetUsers				= [];
$retweetWithCommentUsers	= [];
$commentUsers				= [];

foreach(['TweetDetail_v2', 'retweeters', 'adaptive'] as $file){
	$data = file_get_contents("data/$file.json");

	foreach(explode("\n", $data) as $line){
		$data = json_decode($line, true);
		
		switch($file){
			case 'adaptive':
				foreach($data['globalObjects']['users'] as $u){
					$users[$u['screen_name']] = $u;
					$retweetWithCommentUsers[] = $u['screen_name'];
				}
				
				foreach($data['timeline']['instructions'] as $i){
					if(!isset($i['addEntries'])) continue;
					
					foreach($i['addEntries']['entries'] as $e){
						if(!isset($e['content']['item'])) continue;
						//$retweetWithComment[] = $data['globalObjects']['tweets'][$e['content']['item']['content']['tweet']['id']];
						$retweetWithComment[] = [
							'user'		=> $data['globalObjects']['users'][$data['globalObjects']['tweets'][$e['content']['item']['content']['tweet']['id']]['user_id']]['screen_name'],
							'id'		=> $e['content']['item']['content']['tweet']['id'],
							'content'	=> $data['globalObjects']['tweets'][$e['content']['item']['content']['tweet']['id']]['full_text']
						];
					}
				}
				
				
				break;
			
			case 'retweeters':
				foreach($data['data']['retweeters_timeline']['timeline']['instructions'] as $i){
					if($i['type'] != 'TimelineAddEntries') continue;
					
					foreach($i['entries'] as $e){
						if(!isset($e['content']['itemContent']['user_results']['result']['legacy'])) continue;
						
						$u = $e['content']['itemContent']['user_results']['result']['legacy'];
						$users[$u['screen_name']] = $u;
						$retweetUsers[] = $u['screen_name'];
						
						$retweet[] = $u['screen_name'];
					}
				}
				break;
			
			case 'TweetDetail_v2':
				foreach($data['data']['threaded_conversation_with_injections_v2']['instructions'] as $i){
					if($i['type'] != 'TimelineAddEntries') continue;
					
					foreach($i['entries'] as $e){
						if($e['content']['entryType'] != 'TimelineTimelineModule') continue;
						
						foreach($e['content']['items'] as $i){
							if($i['item']['itemContent']['itemType'] != 'TimelineTweet') continue;
							if(($i['item']['itemContent']['tweet_results']['result']['__typename'] ?? '') === 'TweetWithVisibilityResults'){
								$i['item']['itemContent']['tweet_results']['result'] = $i['item']['itemContent']['tweet_results']['result']['tweet'];
							}
							
							$u = $i['item']['itemContent']['tweet_results']['result']['core']['user_results']['result']['legacy'];
							$users[$u['screen_name']] = $u;
							$commentUsers[] = $u['screen_name'];
							
							//$comment[] = $i['item']['itemContent']['tweet_results']['result']['legacy'];
							$comment[] = [
								'user'		=> $u['screen_name'],
								'id'		=> $i['item']['itemContent']['tweet_results']['result']['rest_id'],
								'content'	=> $i['item']['itemContent']['tweet_results']['result']['legacy']['full_text']
							];
						}
					}
				}
				break;
		}
	}
}

$followedUsers				= array_keys(array_filter($users, function($v, $k){ return $v['followed_by']; }, ARRAY_FILTER_USE_BOTH));
$retweetUsers				= array_values(array_unique($retweetUsers));
$retweetWithCommentUsers	= array_values(array_unique($retweetWithCommentUsers));
$commentUsers				= array_values(array_unique($commentUsers));
$luckyUsers					= array_intersect($followedUsers, array_values(array_unique(array_merge($retweetUsers, $retweetWithCommentUsers))), $commentUsers);
$ineligibleUsers			= array_diff(array_values(array_unique(array_merge($followedUsers, array_values(array_unique(array_merge($retweetUsers, $retweetWithCommentUsers))), $commentUsers))) , $luckyUsers);
$winner						= array_map(function($v)use($luckyUsers){ return $luckyUsers[$v]; }, array_rand($luckyUsers, 20));

$ineligible = [];
foreach($ineligibleUsers as $u){
	if(!in_array($u, $followedUsers))			$ineligible[$u][] = 'followed';
	if(!in_array($u, $retweetUsers) && !in_array($u, $retweetWithCommentUsers))			$ineligible[$u][] = 'retweet';
	if(!in_array($u, $commentUsers))			$ineligible[$u][] = 'comment';
}

$result = implode("\r\n", [
	'开奖结果：',
	'总参与人数：' . count($users),
	'符合条件/不符合条件人数：' . count($luckyUsers) . '/' . count($ineligible),
	'评论/转发/关注人数：' . count($comment) . '/' . (count($retweet) + count($retweetWithComment)) . '/' . count($followedUsers),
	'',
	"中奖名单：",
	implode("\r\n", array_map(function($v, $k)use($luckyUsers){ return ($k + 1) . ". https://twitter.com/@$v"; }, $winner, array_keys($winner)))
]);

echo 'count(users)                   = ' . count($users) . "\r\n";
echo 'count(followedUsers)           = ' . count($followedUsers) . "\r\n";
echo 'count(luckyUsers)              = ' . count($luckyUsers) . "\r\n";
echo 'count(ineligible)              = ' . count($ineligible) . "\r\n\r\n";
echo 'count(retweet)                 = ' . count($retweet) . "\r\n";
echo 'count(retweetWithComment)      = ' . count($retweetWithComment) . "\r\n";
echo 'count(comment)                 = ' . count($comment) . "\r\n\r\n";
echo 'count(retweetUsers)            = ' . count($retweetUsers) . "\r\n";
echo 'count(retweetWithCommentUsers) = ' . count($retweetWithCommentUsers) . "\r\n";
echo 'count(commentUsers)            = ' . count($commentUsers) . "\r\n\r\n";
echo 'Winner: ' . implode(', ', $winner);
echo "\r\n\r\n--------------------------------------------\r\n\r\n";
echo $result;

file_put_contents('result.txt', 					$result);
file_put_contents('output/users_data.json',			json_encode($users, JSON_UNESCAPED_UNICODE));
file_put_contents('output/ineligible.json',			json_encode($ineligible, JSON_UNESCAPED_UNICODE));
file_put_contents('output/retweet.json',			json_encode($retweet, JSON_UNESCAPED_UNICODE));
file_put_contents('output/retweetWithComment.json',	json_encode($retweetWithComment, JSON_UNESCAPED_UNICODE));
file_put_contents('output/comment.json',			json_encode($comment, JSON_UNESCAPED_UNICODE));
file_put_contents('output/users.json',				json_encode([
	'retweet'				=> $retweetUsers,
	'retweetWithComment'	=> $retweetWithCommentUsers,
	'comment'				=> $commentUsers,
	'followed'				=> $followedUsers,
	'lucky'					=> $luckyUsers,
	'winner'				=> $winner
], JSON_UNESCAPED_UNICODE));