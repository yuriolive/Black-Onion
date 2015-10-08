<?php

$fb = new Facebook\Facebook([
  'app_id' => '1629898650614051',
  'app_secret' => '6968e432fd1e8753708d37b33f65e101',
  'default_graph_version' => 'v2.4',
]);

$helper = $fb->getRedirectLoginHelper();

$permissions = ['email']; // Optional permissions
$loginUrl = $helper->getLoginUrl('fb-callback.php', $permissions);

echo '<a href="' . htmlspecialchars($loginUrl) . '">Log in with Facebook!</a>';

?>