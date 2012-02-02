<?php
/** Tests for the SimpleTwitterTimeline class */

require_once 'simpletest/autorun.php';
require_once '../twitter.class.php';

/** Set the user to run tests against */
define('DEFAULT_TWITTER_USER', 'stevegrunwell');

class TestOfTwitterClass extends UnitTestCase {

  function testClassInstantiation(){
    // Instantiate without/invalid username to make sure we're calling the set_username() method
    $this->expectError('Invalid username');
    new SimpleTwitterTimeline();

    // Ensure that options are being saved by passing (non-default) options
    $args = array(
      'exclude_replies' => true,
      'limit' => 15,
      'parse_links' => false,
      'link_class' => 'parsed-link-test'
    );
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $args);
    foreach( $args as $k=>$v ){
      $this->assertEqual($twitter->get_opt($k), $args[$k]);
    }
  }

  function testFunctionSetOpt(){
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER);
    $this->assertFalse(isset($twitter->opts['foobar']));
    $twitter->set_opt('foobar', 'baz');
    $this->assertTrue(isset($twitter->opts['foobar']));
    $this->assertEqual($twitter->opts['foobar'], 'baz');
    $this->assertIsA($twitter->opts['foobar'], 'string');
    $twitter->set_opt('foobar', array('baz'));
    $this->assertTrue(isset($twitter->opts['foobar']));
    $this->assertNotEqual($twitter->opts['foobar'], 'baz');
    $this->assertEqual($twitter->opts['foobar'], array('baz'));
    $this->assertIsA($twitter->opts['foobar'], 'array');
    $twitter->set_opt('foobar', new StdClass);
    $this->assertIsA($twitter->opts['foobar'], 'stdClass');
  }

  function testFunctionGetOpt(){
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER);
    $twitter->set_opt('foobar', 'baz');
    $this->assertEqual($twitter->get_opt('foobar'), $twitter->opts['foobar']);
    $this->assertFalse(isset($twitter->opts['baz']));
    $this->assertFalse($twitter->get_opt('baz'));
    $this->assertTrue($twitter->get_opt('baz', true));
    $this->assertEqual($twitter->get_opt('baz', 'foo'), 'foo');
  }

  function testFunctionSetUsername(){
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER);

    // Pass a valid username
    $this->assertTrue($twitter->set_username(DEFAULT_TWITTER_USER));
    $this->assertTrue($twitter->set_username('abc123'));

    // Pass a non-string
    $this->expectError('SimpleTwitterTimeline does not accept user IDs (yet)');
    $this->assertFalse($twitter->set_username(123));
    $this->expectError('SimpleTwitterTimeline does not accept user IDs (yet)');
    $this->assertFalse($twitter->set_username(true));
    $this->expectError('Invalid username');
    $this->assertFalse($twitter->set_username(array()));
    $this->expectError('Invalid username');
    $this->assertFalse($twitter->set_username(new stdClass));
  }

  function testFunctionGetUsername(){
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER);
    $this->assertIsA($twitter->get_username(), 'string');
    $this->assertEqual($twitter->get_username(), DEFAULT_TWITTER_USER);
    $this->assertNotEqual($twitter->get_username(), ucwords(DEFAULT_TWITTER_USER));
    $this->assertNotEqual($twitter->get_username(), 'SteveJobs');
    $twitter->set_username('SteveJobs');
    $this->assertEqual($twitter->get_username(), 'SteveJobs');
  }

  function testFunctionGetProfileUrl(){
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER);
    $this->assertIsA($twitter->get_profile_url(), 'string');
    $this->assertIdentical($twitter->get_profile_url(), 'https://twitter.com/#!/' . DEFAULT_TWITTER_USER);
    $this->assertTrue(filter_var($twitter->get_profile_url(), FILTER_VALIDATE_URL));
  }

  function testFunctionGetProfileLink(){
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER);
    $this->assertIsA($twitter->get_profile_link(), 'string');
    $this->assertEqual($twitter->get_profile_link(), '<a href="https://twitter.com/#!/' . DEFAULT_TWITTER_USER . '" title="Follow @' . DEFAULT_TWITTER_USER . ' on Twitter" rel="external">@' . DEFAULT_TWITTER_USER . '</a>');
  }

  function testFunctionCleanKey(){
    $this->assertEqual(SimpleTwitterTimeline::clean_key('foobar'), 'foobar');
    $this->assertEqual(SimpleTwitterTimeline::clean_key('foo-bar'), 'foo-bar');
    $this->assertEqual(SimpleTwitterTimeline::clean_key('foo_bar'), 'foo_bar');
    $this->assertEqual(SimpleTwitterTimeline::clean_key('foo1bar'), 'foo1bar');
    $this->assertEqual(SimpleTwitterTimeline::clean_key('foo bar'), 'foobar');
    $this->assertEqual(SimpleTwitterTimeline::clean_key('foo*bar'), 'foobar');
    $this->assertEqual(SimpleTwitterTimeline::clean_key('foo$bar'), 'foobar');
  }

  function testCreateArray(){
    $str = $str2 = 'foo, bar, baz';
    $str2 .= ',';
    $array = $array2 = array('foo', 'bar', 'baz');
    $this->assertIsA(SimpleTwitterTimeline::create_array($str), 'array');
    $this->assertEqual(SimpleTwitterTimeline::create_array($str), $array);
    $this->assertEqual(SimpleTwitterTimeline::create_array($str2), $array);

    // Array filtering and bypass
    $this->assertEqual(SimpleTwitterTimeline::create_array($array), $array);
    $this->assertIsA(SimpleTwitterTimeline::create_array($array), 'array');
    $array2[] = '';
    $this->assertEqual(SimpleTwitterTimeline::create_array($array2), $array);
  }

  function testFunctionGetTimeline(){
    $opts = array(
      'use_cache' => true,
      'cache_filename' => 'tweetcache-test.json',
      'cache_path' => dirname(__FILE__)
    );
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $timeline = $twitter->get_public_timeline();
    $cache = $twitter->get_timeline();
    $this->assertIsA($cache, 'array');
  }

  function testFunctionGetPublicTimeline(){
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER);
    $timeline = $twitter->get_public_timeline();
    $this->assertIsA($timeline, 'array');
    $this->assertTrue(isset($timeline['0']->text));
  }

  function testFunctionGetTweetCache(){
    $opts = array(
      'use_cache' => true,
      'cache_filename' => 'tweetcache-test.json',
      'cache_path' => dirname(__FILE__)
    );
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertIsA($twitter->get_tweet_cache(), 'array');
  }

  function testFunctionSaveTweetCache(){
    $opts = array(
      'use_cache' => true,
      'cache_filename' => 'tweetcache-test.json',
      'cache_path' => dirname(__FILE__)
    );
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $test_data = new stdClass();
    $test_data->foo = 'bar';
    $filename = sprintf('%s/%s', $twitter->get_opt('cache_path'), $twitter->get_opt('cache_filename'));
    @unlink($filename);

    $this->assertTrue($twitter->save_tweet_cache($test_data));
    $this->assertTrue(file_exists($filename));
    $this->assertTrue(filemtime($filename));
    $this->assertIsA(filemtime($filename), 'integer');
    @unlink($filename);
  }

  function testFunctionCacheValid(){
    $opts = array(
      'use_cache' => true,
      'cache_filename' => 'tweetcache-test.json',
      'cache_path', dirname(__FILE__)
    );
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $twitter->get_public_timeline();
    $this->assertIsA($twitter->cache_valid(), 'boolean');
  }

  function testFunctionParseLinks(){
    $opts = array(
      'parse_links' => true,
      'link_class' => 'parsed-link'
    );
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertEqual($twitter->parse_links('This is an example link: http://www.example.com/'), 'This is an example link: <a href="http://www.example.com/" class="parsed-link">http://www.example.com/</a>');
    $this->assertEqual($twitter->parse_links('This is an example link: https://www.example.com/'), 'This is an example link: <a href="https://www.example.com/" class="parsed-link">https://www.example.com/</a>');
    $this->assertEqual($twitter->parse_links('This is an example link: http://www.example.com/foo/bar.jpg'), 'This is an example link: <a href="http://www.example.com/foo/bar.jpg" class="parsed-link">http://www.example.com/foo/bar.jpg</a>');
    $this->assertEqual($twitter->parse_links('This is an example link: http://www.example.com/foo?bar=1'), 'This is an example link: <a href="http://www.example.com/foo?bar=1" class="parsed-link">http://www.example.com/foo?bar=1</a>');
    $this->assertEqual($twitter->parse_links('This is an example link: http:// www.example.com/'), 'This is an example link: http:// www.example.com/');
  }

  /*function testFunctionProfanityFilter(){
    $profane_tweets = array(
      0 => 'Holy fuck on a fuck-stick!',
      1 => 'Be good or Santa is going to kick your little ass!',
      2 => 'Oh my God, they killed Kenny! You bastards!',
      3 => 'Fuck this shit - http://fuckthisshit.com',
      4 => 'Justin Bieber is fucking terrible',
      5 => 'Eat shitake mushrooms!'
    );
    $nice_tweet = 'Good morning! I hope everyone has a wonderful day!';

    // Test with the default profanity filter
    $opts = array(
      'profanity_filter' => true,
    );
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertIsA($twitter->profanity_filter($nice_tweet), 'string');
    $this->assertEqual($twitter->profanity_filter($nice_tweet), $nice_tweet);
    $this->assertIsA($twitter->profanity_filter($profane_tweets['0']), 'string');
    $this->assertEqual($twitter->profanity_filter($profane_tweets['0']), 'Holy **** on a ****-stick!');
    $this->assertEqual($twitter->profanity_filter($profane_tweets['1']), $profane_tweets['1']);
    $this->assertEqual($twitter->profanity_filter($profane_tweets['2']), $profane_tweets['2']);
    $this->assertEqual($twitter->profanity_filter($profane_tweets['3']), '**** this shit - http://fuckthisshit.com');
    $this->assertEqual($twitter->profanity_filter($profane_tweets['4']), 'Justin Bieber is ****ing terrible');

    // Skip over profane tweets
    $opts['profanity_filter_method'] = 'skip';
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertIsA($twitter->profanity_filter($nice_tweet), 'string');
    $this->assertEqual($twitter->profanity_filter($nice_tweet), $nice_tweet);
    $this->assertIsA($twitter->profanity_filter($profane_tweets['0']), 'bool');
    $this->assertFalse($twitter->profanity_filter($profane_tweets['0']));
    $this->assertEqual($twitter->profanity_filter($profane_tweets['1']), $profane_tweets['1']);
    $this->assertEqual($twitter->profanity_filter($profane_tweets['2']), $profane_tweets['2']);
    $this->assertFalse($twitter->profanity_filter($profane_tweets['3']));

    // Add to the default list
    $opts['profanity_blacklist'] = 'Bieber,Gaga';
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertFalse($twitter->profanity_filter($profane_tweets['4']));

    // Custom blacklist of words (note: we're still on "skip" mode!)
    $opts['profanity_blacklist_overwrite'] = true;
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertEqual($twitter->profanity_filter($profane_tweets['0']), $profane_tweets['0']);
    $this->assertFalse($twitter->profanity_filter($profane_tweets['4']));

    // Switch back to 'censor' mode
    $opts['profanity_filter_method'] = 'censor';
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertEqual($twitter->profanity_filter($profane_tweets['4']), 'Justin ****** is fucking terrible');

    // Test our whitelist
    $opts['profanity_blacklist_overwrite'] = false;
    $opts['profanity_blacklist'] = 'shit'; // This should be a default but we'll define it just to be sure
    $opts['profanity_whitelist'] = 'shitake'; // Again, should be default
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertEqual($twitter->profanity_filter($profane_tweets['5']), $profane_tweets['5']);

    // Whitelist overwrites blacklist
    $opts['profanity_blacklist'] = 'bastard';
    $opts['profanity_whitelist'] = 'bastard';
    $twitter = new SimpleTwitterTimeline(DEFAULT_TWITTER_USER, $opts);
    $this->assertEqual($twitter->profanity_filter($profane_tweets['2']), $profane_tweets['2']);
  }*/
}

?>