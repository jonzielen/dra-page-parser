<?php
  $urlToParse = 'http://ryanadams.shopfirebrand.com/collections/all';
  $baseURL = 'http://ryanadams.shopfirebrand.com'; // helps with links

  require_once 'classes/class.parse-page.php';
  $results = new jon\PageParse($urlToParse, $baseURL);
?>
