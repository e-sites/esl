esl
===

ESL is our internally developed code library with commonly used functions.

We have the latest version of ESL deployed on all of our servers, which we occasionaly update with bugfixes and new features.
All applications on our servers can use any of the ESL packages by adding one single line to the code;
<code lang="php">require 'ESLv1/bootstrap.php';</code>
'ESLv1' is a symlink on PHP's include_path linking to the latest version available on the server.