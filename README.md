# Simple Twitter Timeline

There are a ton of Twitter classes out there. Some simply return tweets and others are full-blown Twitter applications. SimpleTwitterTimeline strives to fall in the middle - retrieve tweets from the public timeline, apply some basic filters, then let the developer decide what to do with them.

There are a number of filters built into the class:

* Link Parser: Automatically create `<a></a>` elements from links in tweets
* Profanity Filter: Automatically censor blacklisted words/profanity (not yet complete)

## Usage

The following example will grab the five latest tweets from $username's public timeline (excluding replies to other users), auto-parse the links, then cache the results in `my-tweet-cache.json`.

    <?php
      $username = 'YOUR TWITTER HANDLE';
      $args = array(
        'exclude_replies' => true,
        'limit' => 5,
        'parse_links' => true,
        'use_cache' => true,
        'cache_filename' => 'my-tweet-cache.json'
      );
      $twitter = new SimpleTwitterTimeline($username, $args);
    ?>

    <?php foreach( $twitter->get_timeline() as $tweet ): ?>

      <div class="tweet">
        <?php echo $tweet['text']; ?>
        <time><?php echo date('M jS @ g:ia', strtotime($tweet['created_at'])); ?></time>
      </div>

    <?php endforeach; ?>

## Settings

Below is a list of all available options that can be passed as part of the $args array:

**exclude_replies**: (bool) Don't count tweets that are replies to other tweets (according to Twitter) (default: false)

**limit**: (int) The maximum number of tweets to return (default: 5)

### Tweet filters:

**parse_links**: (bool) Wrap links within the tweets within `<a>` tags (default: true)

**link_class**: (str) A class to apply to any parsed links (default: "parsed-link")

**profanity_filter**: (bool) Use the built-in profanity filter? *note: the profanity filter is not yet complete!*

**profanity_filter_method**: (str) Censor the profanity or skip the tweet entirely (censor|skip, default: censor)

**profanity_blacklist**: (str|array) Additional words to mark as profane (beyond the system defaults, default: null)

**profanity_blacklist_overwrite**: (bool) Overwrite the default blacklist or append to it (default: false)

**profanity_whitelist**: (str|array) Words that contain blacklisted words but should remain uncensored (example: "shitake"; default: null)

### Caching options:

**use_cache**: (bool) Use timeline caching? (default: false)

**cache_filename**: (str) The filename to use for the cache (default: tweets-{username}.json

**cache_path**: (str) Where to store the cache file (default: dirname(__FILE__))

**cache_expiry**: (int) Number of seconds before a cache is considered invalid

## Roadmap

* Write more tests!
* More elegant error handling
* Finish the profanity filter
* Complete documentation
* Additional modules?