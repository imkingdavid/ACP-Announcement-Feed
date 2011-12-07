<?php
/**
*
*    ACP Announcements Feed 1.0.1
*    MOD to parse the RSS/ATOM Feed from the phpBB Announcements forums, allowing
*    news and announcements to be accessed via the phpBB ACP.
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
	$news_array = get_feed();
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
            // NOTE: This language is hardcoded because it is the expected result.
            // If the title is not equivalent to that, then this isn't the right feed and/or something went wrong
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

function get_feed()
{
    // If the server disallows remote URLs in file opening, we'll need to try something else
    // We'll use phpBB's get_remote_file to open the file and return it as an XML string directly
    // into a SimpleXMLElement object, exactly as if we used simplexml_load_file(). So either way
    // everyone's happy.
    $allow_url_fopen = ini_get('allow_url_fopen');
    if(empty($allow_url_fopen))
    {
        if(!function_exists('get_remote_file'))
        {
            global $phpbb_root_path, $phpEx;
            include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
        }
        // yes, we use .php instead of $phpEx here because the file is always going to be index.php
        $err_str = $err_num = null;
        return new SimpleXMLElement(get_remote_file('www.phpbb.com', '/feeds/', 'index.php', $err_str, $err_num));
    }
    else
    {
        return simplexml_load_file('http://www.phpbb.com/feeds/index.php');
    }
}
