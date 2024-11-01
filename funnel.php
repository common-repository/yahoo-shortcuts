<?php

$o = '';
foreach ($_POST as $k => $v) {
	$o .= "$k=".utf8_encode($v).'&';
}
$post_data = substr($o, 0, -1);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://shortcuts.yahoo.com/annotate');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
ob_start();
curl_exec($ch);
$s = ob_get_clean();
curl_close($ch);
echo $s;

?>