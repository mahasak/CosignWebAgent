if ($good_signing) {
  // retrieve signing info and file
  $info = fetch_signed_file($sessionID);
  $good_signing = $info['error'] == null;
  if (!$good_signing) {$errorMessage = $info['error'];}
}
