<?php
/**
*
*	ACP Announcements Feed
*
*	MOD to parse the RSS/ATOM Feed from the phpBB Announcements forums, allowing
*	news and announcements to be accessed via the phpBB ACP.
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class announcement_feed
{
	var $server = 'www.phpbb.com';
	var $feed_path = '/feeds/';
	var $feed;
	function __construct()
	{
		global $template;
		$this->feed = $this->get_feed();
		if (!$this->feed)
		{
			$template->assign_var('S_NEWS_CONNECT_FAIL', true);
		}
		$news_array = $this->parse_feed();
		
		if (!$news_array)
		{
			return false;
		}
		$this->set_template_variables($news_array);
	}
	
	/*
	Return is either false (on failure) or the feed content
	*/
	function get_feed()
	{
		global $phpbb_root_path, $phpEx;
		if (!function_exists('get_remote_file'))
		{
			include("{$phpbb_root_path}includes/functions_admin.$phpEx");
		}
		$index_file = "index.$phpEx";
		$err_str = '';
		$errno = 0;
		$feed = get_remote_file($this->server, $this->feed_path, $index_file, $err_str, $errno);
		if (!$feed)
		{
			return false;
		}
		return $feed;
	}
	
	/*
	Parse the feed for reading in the ACP.
	
	The single parameter is optional, and if set, it will override $this->feed.
	
	This will return false if it fails at any point
	It will return an array of the items like so:
		[0] => array(
			'title'			=> 'Announcement Title',
			'author'		=> 'Author name',
			'link'			=> 'http://...',
			'pubDate'		=> $timestamp,
			'description'	=> $text, // NOTE: Truncated for a short description, off by default
		);
	*/
	function parse_feed($feed = '')
	{
		// First make sure $feed is not set. If it is, use it no matter what. However, put it in $this->feed so that that is used
		// If neither are set, return false
		// Otherwise, just skip this step and use the contents of $this->feed
		if (!empty($feed))
		{
			$this->feed = $feed;
		}
		else if (empty($feed) && empty($this->feed))
		{
			return false;
		}
		// Put the contents of <item></item> into an array, do it for all of them
		preg_match_all("'<item>(.*?)</item>'si", $this->feed, $matches);
		if (!sizeof($matches))
		{
			return false;
		}
		$matches = array_unique($matches);
		$items = array();
		$num = 0;
		foreach($matches[0] as $item)
		{
			// now split it up. We need a preg_match for each of the child tags:
			// 	author, title, pubDate, link, description
			preg_match("'<title>(.*?)</title>'si", $item, $title);
			$items[$num]['title'] = $title[1];
			
			preg_match("'<author>(.*?)</author>'si", $item, $author);
			$items[$num]['author'] = $author[1];
			
			preg_match("'<pubDate>(.*?)</pubDate>'si", $item, $pubDate);
			$items[$num]['pubDate'] = strtotime($pubDate[1]);
			
			preg_match("'<link>(.*?)</link>'si", $item, $link);
			$items[$num]['link'] = $link[1];
			
			preg_match("'<description>(.*?)</description>'si", $item, $description);
			$items[$num]['description'] = $description[1];
			
			// Increment the counter
			$num++;
		}
		return $items;
	}
		
	function set_template_variables(array $input_array)
	{
		global $template, $user;
		
		if (!sizeof($input_array))
		{
			return false;
		}
		
		foreach($input_array as $news)
		{
			$template->assign_block_vars('news_feed', array(
					'TITLE'		=> $news['title'],
					'DATE'		=> $user->format_date($news['pubDate']),
					'U_NEWS'	=> $news['link'],
					'AUTHOR'	=> $news['author'],
					'U_AUTHOR'	=> 'http://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;un=' . $news['author'],
			));
		}
		
		return true;
	}
}