<?php

	$plugin_info       = array(
	'pi_name'        => 'Twittero',
	'pi_version'     => '0.7',
	'pi_author'      => 'Strawberry',
	'pi_author_url'  => 'http://strawberry.co.uk',
	'pi_description' => 'Pull in latest tweets using oAuth - Stops issue of 150 request per hour limit',
	'pi_usage'       => Twittero::usage()
	);
	
	if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	function getConnectionWithAccessToken() {
	  $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_TOKEN_SECRET);
	  return $connection;
	}
	
	function linkify($text) {
		$text= preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $text);
		$text= preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $text);
		$text= preg_replace("/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $text);
		$text= preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $text);
		return $text;
	}	
		
	function tweet_time($time){
		
		$format = TIME_FORMAT;
		$tweet_time = strtotime($time);
		
		$start_time_tag = '<time datetime="' . date('Y-m-d', $tweet_time) . '">';
		
		if (WORDED_TIMES == "true") {
		
			$timemonth = 30 * 24 * 60 * 60;
			$timeday = 24 * 60 * 60;
			$timehour = 60 * 60;
			$timemins = 60;
			$timeseconds = 1;
			
			$today = time();
			
			$dformat = "";
			$pre = "";
			
			$x = $today - $tweet_time;
			
			if ($x >= $timemonth) {
				$x = date($format, $tweet_time);
			} else if ($x >= $timeday) {
				$x = round($x / $timeday); $dformat = "days ago"; $pre = "About"; $x = round($x);
				if ($x == 1) { $dformat = "day ago"; }
			} else if ($x >= $timehour) {
				$x = round($x / $timehour); $dformat = "hours ago"; $pre = "About"; 
			} else if ($x >= $timemins) {
				$x = round($x / $timemins); $dformat = "minutes ago"; $pre = "About";
			} else if ($x >= $timeseconds) {
				$x = round($x / $timeseconds); $dformat = "seconds ago"; $pre = "About"; 
			}
			return $start_time_tag . $pre . " " . $x . " " . $dformat . "</time>";
			
		} else {
			
			return $start_time_tag . date($format, $tweet_time) . "</time>";
			
		}
		
	}
	
	function grab_new_tweets($cache_file, $format) {
		
		$connection = getConnectionWithAccessToken();
		
		$tweets = $connection->get('statuses/user_timeline', array('screen_name' => SCREEN_NAME, 'include_rts' => INCLUDE_RETWEETS, 'count' => TWEETS_TO_GRAB));
		
		global $html;
		
		$html = "";
		
		if (is_array($tweets)) {
								
			$i = 1;
			
			foreach($tweets as $tweet) {
				
				if (SHOW_REPLIES == "false") {
					if ($tweet->in_reply_to_screen_name === null) {
						$line = str_replace("{twittero_tweet}", linkify($tweet->text), $format);								
						$html .= str_replace("{twittero_time}", tweet_time($tweet->created_at), $line);
						$i++;
					}
				} else {
					$line = str_replace("{twittero_tweet}", linkify($tweet->text), $format);								
					$html .= str_replace("{twittero_time}", tweet_time($tweet->created_at), $line);
					$i++;
				}
				
				if ($i == (TWEETS_TO_DISPLAY + 1)) break;
				
			}
		
			$fh = fopen($cache_file, 'w+');
			fwrite($fh, $html);
			fclose($fh);
			
		} else {
			
			// If twitter can't be accessed, show what's already in the cache file. If it's empty, show an apology.						
			if (filesize($cache_file) == 0) {
				$html .= '<p>Sorry. Twitter seems to be unavailable at the moment.</p>';			
			} else {		
				$fh = fopen($cache_file, 'r');
				$html = fread($fh, filesize($cache_file));
				fclose($fh);
			}					
			
		}
		
		return display_tweets($cache_file, $html);
		
	}
	
	function display_tweets($cache_file, $html) {
		
		global $theTweets;
		
		if (filesize($cache_file) == 0) {
			return $html;				
		} else {		
			$fh = fopen($cache_file, 'r');
			$theTweets = fread($fh, filesize($cache_file));
			fclose($fh);
			return $theTweets;
		}
	
	}

	function twitterMagic($twittero_format) {
		
		session_start();
		require_once('twitteroauth.php');
		
		$cache_file = APPPATH . "cache/twittero.txt";
		
		// Create file if it doesn't exist
		if (!file_exists($cache_file)) {
			$cacheHandle = fopen($cache_file, 'w') or die("can't open file");
			fclose($cacheHandle);
		}
		
		$current_time = time();
		$cache_time = filemtime($cache_file);
		$time_difference = ($current_time - $cache_time) / 60;
		
		$how_often_new_tweets = CACHE_LIMIT; // TIME IN MINUTES

		//echo filesize($cache_file) . "<br />";
		//echo $current_time . "<br />";
		//echo "CL: " . CACHE_LIMIT . "<br />";
		//echo "CT: " . $cache_time . "<br />";
		//echo "TD: " . $time_difference . "<br />";
		//echo "HO: " . $how_often_new_tweets . "<br />";
		
		$format = $twittero_format;

		if (filesize($cache_file) == 0) {
			$tweets = grab_new_tweets($cache_file, $format);
		} else if ($time_difference >= $how_often_new_tweets) {
			$tweets = grab_new_tweets($cache_file, $format);	
		} else {
			$tweets = display_tweets($cache_file, null);
		}
		
		return $tweets;
	
	}
	
	class Twittero
	{

		var $return_data = '';
				
		function twittero()
		{
			
			$this->EE =& get_instance();
				
			define('SCREEN_NAME', $this->EE->TMPL->fetch_param('screen_name'));
			define('INCLUDE_RETWEETS', $this->EE->TMPL->fetch_param('include_retweets') ? $this->EE->TMPL->fetch_param('include_retweets') : 'true');
			define('SHOW_REPLIES', $this->EE->TMPL->fetch_param('show_replies') ? $this->EE->TMPL->fetch_param('show_replies') : 'true');
			define('CACHE_LIMIT', ($this->EE->TMPL->fetch_param('cache_limit') || $this->EE->TMPL->fetch_param('cache_limit') === 0) ? $this->EE->TMPL->fetch_param('cache_limit') : 30);
			define('TIME_FORMAT', $this->EE->TMPL->fetch_param('time_format') ? str_replace('%', '', $this->EE->TMPL->fetch_param('time_format')) : 'F m, Y g:i a');
			define('WORDED_TIMES', $this->EE->TMPL->fetch_param('worded_times') ? $this->EE->TMPL->fetch_param('worded_times') : 'true');
			define('TWEETS_TO_GRAB', $this->EE->TMPL->fetch_param('tweets_to_grab') ? $this->EE->TMPL->fetch_param('tweets_to_grab') : 10);
			define('TWEETS_TO_DISPLAY', $this->EE->TMPL->fetch_param('tweets_to_display') ? $this->EE->TMPL->fetch_param('tweets_to_display') : 10);
			define('CONSUMER_KEY', $this->EE->TMPL->fetch_param('consumer_key'));
			define('CONSUMER_SECRET', $this->EE->TMPL->fetch_param('consumer_secret'));
			define('OAUTH_TOKEN', $this->EE->TMPL->fetch_param('oauth_token'));
			define('OAUTH_TOKEN_SECRET', $this->EE->TMPL->fetch_param('oauth_token_secret'));
			
			$twittero_format = $this->EE->TMPL->tagdata;
			$twittero_format = $this->EE->functions->prep_conditionals($twittero_format, $this->EE->TMPL->var_single);

			$this->return_data = twitterMagic($twittero_format);	
					
		}
		
		// ----------------------------------------
		//  Plugin Usage
		// ----------------------------------------
		
		function usage()
		{
			ob_start();
			?>
			
			<ol>
			{exp:twittero screen_name="your_twitter_username"
			consumer_key="lDbqIkinxjhfddZlZz456SdsfdfgSkVa4Qg"
			consumer_secret="hvBAIee46j48dfxu1V18Hhdil80xLEXhQTaTKf3zXfzT5d6Dwb8OM"
			oauth_token="15032349-vtnAAmEe78gOgXDn5OfsddlarewdNaltSkREMbXA9aD74NR6QyHzO"
			oauth_token_secret="YqH3rdWX0IOfaSXMEpbpg546gpsdfT8lgx8j44hCnQrcehGpONIpE"}
			<li>{twittero_tweet} {twittero_time}</li>
			{/exp:twittero}
			</ol>	
			
			
			REQUIRED:	
			
			screen_name
			consumer_key
			consumer_secret
			oauth_token
			oauth_token_secret
			
			
			OPTIONS (and defaults):
			
			include_retweets - true, false (default: true)
			show_replies - true, false (default: true)
			cache_limit - In minutes. Set to 0 to disable cache (default: true)
			time_format - Supports both EE and PHP formatted dates (default: %F %m, %Y %g:%i %a)
			worded_times - true, false. If set to true, timestamps will appear as phrases like 'about 3 days ago' for tweets less than 30 days old (default: true)
			tweets_to_grab - If not showing replies, you'll need to grab more tweets than you want to display (default: 10)
			tweets_to_display - Visible tweets in HTML (default: 10)
			
			<?php
			$buffer = ob_get_contents();
			
			ob_end_clean(); 
			
			return $buffer;
		}
		
	}
