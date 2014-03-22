<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Google+ Domains API Sample</title>
  </head>
  <body>
<?php

  set_include_path(get_include_path() . PATH_SEPARATOR . './google-api-php-client/src');

  require_once "Google/Client.php";
  require_once "Google/Service/Directory.php";
  require_once "Google/Service/PlusDomains.php";

  const CLIENT_ID = "INSERT_YOUR_CLIENT_ID";
  const SERVICE_ACCOUNT_EMAIL = "INSERT_YOUR_SERVICE_ACCOUNT_EMAIL";
  const KEY_FILE = '/super/secret/path/to/key.p12';

  // To access the Directory API the email address of a Domain admin is necessary here
  const DOMAIN_ADMIN_EMAIL = "INSERT_YOUR_DOMAIN_ADMIN_EMAIL";

  const DOMAIN = "INSERT_YOUR_DOMAIN_HERE"; // e.g. "example.com"

  $client = new Google_Client();
  $client->setApplicationName("Google+ Domains API Sample");
  $client->setClientId(CLIENT_ID);

  $key = file_get_contents(KEY_FILE);
  $credentials = new Google_Auth_AssertionCredentials(
    SERVICE_ACCOUNT_EMAIL,
    array("https://www.googleapis.com/auth/admin.directory.user.readonly"),
    $key);

  // Set the API Client to act on behalf of the domain admin
  $credentials->sub = DOMAIN_ADMIN_EMAIL;
  $client->setAssertionCredentials($credentials);

  $directoryService = new Google_Service_Directory($client);

  // Call to the Admin Directory API to retrieve a list of all Domain Users
  $result = $directoryService->users->listUsers(array("domain" => DOMAIN));
  if (isset($result->users)) {
    printf("    <ul>\n");
    foreach ($result->users as $user) {
      printf("      <li>%s\n", $user->name->fullName);
      do_something_with_user($user->primaryEmail);
      printf("      </li>\n");
    }
    printf("    </ul>\n");
  }

  function do_something_with_user($email) {
    global $key;
    $user_client = new Google_Client();
    $user_client->setApplicationName("Google+ Domains API Sample");
    $user_client->setClientId(CLIENT_ID);

    $user_credentials = new Google_Auth_AssertionCredentials(
      SERVICE_ACCOUNT_EMAIL,
      array("https://www.googleapis.com/auth/plus.me",
            "https://www.googleapis.com/auth/plus.stream.read"),
      $key);

    // Set the API Client to act on behalf of the specified user
    $user_credentials->sub = $email;
    $user_client->setAssertionCredentials($user_credentials);

    $plusService = new Google_Service_PlusDomains($user_client);

    // Try to retrieve Google+ Profile information about the current user
    try {
      $user = $plusService->people->get("me");
    } catch (Exception $e) {
      printf("        / Error retrieving profile information<br><br>\n");
      return;
    }
    if ($user->isPlusUser) {
      printf("        / <a href=\"%s\">Google+ Profile</a>\n", $user->url);
      // Retrieve a list of Google+ activities for the current user
      $activities = $plusService->activities->listActivities("me", "user", array("maxResults" => 100));
      if (isset($activities->items)) {
        printf("        / %s activities found<br><br>\n", count($activities->items));
      } else {
        printf("        / No activities found<br><br>\n");
      }
    } else {
      printf("        / No Google+ profile<br><br>\n");
    }
  }
?>
  </body>
</html>
