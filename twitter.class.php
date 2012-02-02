<?php
/**
 * Twitter parsing class
 * @var str $username Twitter handle
 * @var public array $opts - Configuration options
 * @author Steve Grunwell <stevegrunwell@gmail.com>
 */

class SimpleTwitterTimeline {

  public $username;
  public $opts;

  /**
   * Class constructor
   *
   * Available options for $opts:
   * exclude_replies: (bool) Don't count tweets that are replies to other tweets (according to Twitter) (default: false)
   * limit: (int) The maximum number of tweets to return (default: 5)
   *
   * Tweet filters:
   * parse_links: (bool) Wrap links within the tweets within <a> tags (default: true)
   * link_class: (str) A class to apply to any parsed links (default: "parsed-link")
   * profanity_filter: (bool) Use the built-in profanity filter?
   * profanity_filter_method: (str) Censor the profanity or skip the tweet entirely (censor|skip, default: censor)
   * profanity_blacklist: (str|array) Additional words to mark as profane (beyond the system defaults, default: null)
   * profanity_blacklist_overwrite: (bool) Overwrite the default blacklist or append to it (default: false)
   * profanity_whitelist: (str|array) Words that contain blacklisted words but should remain uncensored (example: "shitake"; default: null)
   *
   * Caching options:
   * use_cache: (bool) Use timeline caching? (default: false)
   * cache_filename: (str) The filename to use for the cache (default: tweets-{username}.json
   * cache_path: (str) Where to store the cache file (default: dirname(__FILE__))
   * cache_expiry: (int) Number of seconds before a cache is considered invalid
   *
   * @param str $username Twitter handle/username
   * @param array $opts Array of options
   * @return void
   */
  public function __construct($username='', $opts=array()){
    $this->set_username($username);
    $default_opts = array(
      'exclude_replies' => false,
      'limit' => 5,
      'parse_links' => true,
      'link_class' => 'parsed-link',
      'profanity_filter' => false,
      'profanity_filter_method' => 'censor',
      'profanity_blacklist' => null,
      'profanity_blacklist_overwrite' => false,
      'profanity_whitelist' => false,
      'use_cache' => false,
      'cache_filename' => sprintf('tweetcache-%s.json', $this->get_username()),
      'cache_path' => dirname(__FILE__),
      'cache_expiry' => 60
    );
    $this->opts = array_merge($default_opts, $opts);
    return;
  }

  /**
   * Set an option in the $opts property
   * @param str $key The key of the $opts array to set
   * @param mixed $val The value to set for $key
   * @return void
   */
  public function set_opt($key, $val=''){
    $key = $this->clean_key($key);
    $this->opts[$key] = $val;
    return;
  }

  /**
   * Get an value from the $opts property
   * @param str $key The key to retrieve
   * @param mixed $default The value to return if $key isn't set
   * @return mixed
   */
  public function get_opt($key, $default=false){
    $key = $this->clean_key($key);
    return ( isset($this->opts[$key]) ? $this->opts[$key] : $default );
  }

  /**
   * Set the $username class property
   * @param str $user The username to set
   * @return void
   */
  public function set_username($user){
    $user = trim(filter_var($user, FILTER_SANITIZE_STRING));
    if( intval($user) > 0 ){
      trigger_error('SimpleTwitterTimeline does not accept user IDs (yet)', E_USER_WARNING);
      return false;
    } else if( $user !== '' ){
      $this->username = $user;
      return true;
    } else {
      trigger_error('Invalid username', E_USER_WARNING);
      return false;
    }
  }

  /**
   * Get the username property
   * @return str
   */
  public function get_username(){
    return $this->username;
  }

  /**
   * Get a link to $this->username's profile
   * @return str
   */
  public function get_profile_url(){
    return sprintf('https://twitter.com/#!/%s', $this->get_username());
  }

  /**
   * Generate an HTML link to $this->username's profile
   * The link will be in the format of <a href="{url}" title="Follow @{user} on Twitter" rel="external">@{user}</a>
   * @return str
   */
  public function get_profile_link(){
    $user = $this->get_username();
    return '<a href="' . $this->get_profile_url() . '" title="' . sprintf('Follow @%s on Twitter', $user) . '" rel="external">@' . $user . '</a>';
  }

  /**
   * Sanitize a key and make sure it only contains alphanumeric characters, dashes, and/or underscores
   * @param str $key
   * @return str
   */
  public function clean_key($key){
    return preg_replace('/[^A-Za-z0-9-_]/i', '', $key);
  }

  /**
   * Helper function: convert a comma-separated list into an array. Will leave arrays untouched except for filtering empty values
   * @param mixed $list
   * @return array
   */
  public function create_array($list){
    if( !is_array($list) ){
      if( is_string($list) ){
        $list = array_map('trim', explode(',', $list));
      } else {
        trigger_error(sprintf('Unable to convert %s to array', gettype($list)));
      }
    }
    return array_filter($list);
  }

  /**
   * Get a user's public timeline
   * @return object
   */
  public function get_timeline(){

    // Determine if we're using the cached version or making an API call
    if( !$this->get_opt('use_cache') || !$this->cache_valid() ){
      $tweets = $this->get_public_timeline();

      // TODO: Apply filters

      // Save the cache
      if( $this->get_opt('use_cache') ){
        $this->save_tweet_cache($tweets);
      }

    // Use the cache
    } else {
      $tweets = $this->get_tweet_cache();
    }
    return $tweets;
  }

  /**
   * Fetch a fresh copy from the Twitter API
   * @return array
   */
  public function get_public_timeline(){
    $feed = new stdClass;
    $url = sprintf('https://api.twitter.com/1/statuses/user_timeline.json?screen_name=%s', $this->get_username());
    $url .= sprintf('&count=%d', $this->get_opt('limit'));
    $url .= sprintf('&exclude_replies=%d',$this->get_opt('exclude_replies'));

    if( $json = file_get_contents($url) ){
      $feed = json_decode($json, true);
    }
    return $feed;
  }

  /**
   * Retrieve the tweet cache
   * @return mixed
   */
  public function get_tweet_cache(){
    $file = sprintf('%s/%s', $this->get_opt('cache_path'), $this->get_opt('cache_filename'));
    if( file_exists($file) ){
      $cache = file_get_contents($file, true);
      return json_decode($cache, true);
    } else {
      return false;
    }
  }

  /**
   * Save a cache of the public timeline
   * @param object The twitter object to save
   * @return bool
   */
  public function save_tweet_cache($tweets){
    $file = sprintf('%s/%s', $this->get_opt('cache_path'), $this->get_opt('cache_filename'));
    if( $fh = fopen($file, 'a') ){
      fwrite($fh, json_encode($tweets));
      fclose($fh);
      return true;
    } else {
      return false;
    }
  }

  /**
   * Determine whether or not the tweet cache is valid
   * @return bool
   */
  public function cache_valid(){
    $file = sprintf('%s/%s', $this->get_opt('cache_path'), $this->get_opt('cache_filename'));
    return ( file_exists($file) && filemtime($file) + $this->get_opt('cache_expiry', 60) <= time() );
  }

/** Tweet filters */

  /**
   * Parse a tweet and activate any links
   * @param str $tweet The tweet's content
   * @return str
   */
  public function parse_links($tweet){
    preg_match_all('/(http(?:s?):\/\/\S+)/i', $tweet, $links);
    if( isset($links['1']) ){
      foreach( $links['1'] as $url ){
        // Only proceed if PHP's built-in filter sees it as a URL
        if( filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED) ){
          $tweet = str_replace($url, sprintf('<a href="%s" class="%s">%s</a>', $url, $this->get_opt('link_class'), $url), $tweet);
        }
      }
    }
    return $tweet;
  }

  /**
   * Profanity filter
   * @param str $tweet The tweet's content
   * @return str|bool If $this->opts['filter_method'] == 'skip' the profane tweets will be skipped and will return false
   */
  public function profanity_filter($tweet){
    $default_blacklist = array('shit', 'piss', 'cunt', 'fuck', 'cocksucker', 'motherfucker', 'tits');
    $default_whitelist = array('shitake', 'pissed');
    $words = array();

    foreach( array('blacklist', 'whitelist') as $key ){
      $default_list = sprintf('default_%s', $key);
      $user_list = sprintf('user_%s', $key);
      $$user_list = ( $this->get_opt(sprintf('profanity_%s', $key), false) ? $this->create_array($this->get_opt(sprintf('profanity_%s', $key), false)) : array() );
      $$key = array_map('strtolower', ( $this->get_opt(sprintf('profanity_%s_overwrite', $key), false) ? $$user_list : array_merge($$default_list, $$user_list) ));
    }

    foreach( $blacklist as $word ){
      if( preg_match_all('/\S*(' . $word . ')\S*/i', $tweet, $matches) ){
        foreach( $matches['1'] as $k=>$v ){
          if( !in_array(strtolower($matches['0'][$k]), $user_whitelist) && !in_array(strtolower($matches['1'][$k]), $user_whitelist) && !filter_var($matches['0'][$k], FILTER_VALIDATE_URL) ){
            $words[] = $v;
          }
        }
      }
    }

    // Skip offending tweets
    if( strtolower($this->get_opt('profanity_filter_method')) === 'skip' ){
      if( !empty($words) ){ // We have at least one match
        $tweet = false;
      }

    // Censor the tweet
    } else {
      foreach( $words as $word ){
        $tweet = str_replace($word, str_repeat('*', strlen($word)), $tweet);
      }
    }

    return $tweet;
  }
}