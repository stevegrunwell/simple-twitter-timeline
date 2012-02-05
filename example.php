<?php
  require_once 'twitter.class.php';

  $username = ( isset($_GET['username']) ? filter_var($_GET['username'], FILTER_SANITIZE_STRING) : false );
  $args = array(
    'exclude_replies' => true,
    'limit' => 5,
    'parse_links' => true,
    'use_cache' => true
  );
  $twitter = new SimpleTwitterTimeline(( $username ? $username : 'stevegrunwell' ), $args);

?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Example of Simple Twitter Timeline</title>
<style type="text/css">
  html, body{font-family:sans-serif; font-size:13px; line-height:21px; color:#333;}
  body{width:960px; margin:21px auto; padding:0;}
  h1, h3, p, input, fieldset, .tweet{margin:0 0 21px; padding:0;}
  h2{font-size:16px;}
  fieldset{padding:0 12px; border:2px solid #aaa;}
  label{margin-right:14px;}
  .tweet{display:block;}
  .tweet time{display:block; font-size:11px; color:#666;}
</style>
</head>
<body>
  <h1>Simple Twitter Timeline Demo</h1>

  <form method="get" action="">
    <fieldset>
      <h2>Want to try it with your own Twitter account?</h2>
      <label for="username">Enter your Twitter username:</label>
      <input name="username" id="username" type="text" value="<?php echo $username; ?>" placeholder="Twitter username" />
    </fieldset>
  </form>

  <h2>My Latest Tweets</h2>
  <p>Follow me: <?php echo $twitter->get_profile_link(); ?></p>

<?php foreach( $twitter->get_timeline() as $tweet ): ?>

  <div class="tweet">
    <?php echo $tweet['text']; ?>
    <time><?php echo date('M jS @ g:ia', strtotime($tweet['created_at'])); ?></time>
  </div>

<?php endforeach; ?>
</body>
</html>