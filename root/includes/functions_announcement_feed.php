<?php
/**
*
*	ACP Announcements Feed 1.0.1
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

function announcement_feed($limit = 10)
{
	global $template, $user;
	$news_array = simplexml_load_file('http://www.phpbb.com/feeds/index.php');
	if(!$news_array)
	{
		$template->assign_var('S_NEWS_CONNECT_FAIL', true);
	}
	$current = 0;
	$children = $news_array->children();
	foreach($children as $child)
	{
		foreach($child as $sub)
		{
			if($sub->title != 'Latest phpBB.com announcements' && !empty($sub->title))
			{
				if($current < $limit)
				{
					$template->assign_block_vars('news_feed', array(
							'TITLE'		=> $sub->title,
							'DATE'		=> $user->format_date(strtotime($sub->pubDate)),
							'U_NEWS'	=> $sub->link,
							'AUTHOR'	=> $sub->author,
							'U_AUTHOR'	=> 'http://www.phpbb.com/community/memberlist.php?mode=viewprofile&amp;un=' . $sub->author,
					));
					$current++;
				}
			}
		}
	}
}