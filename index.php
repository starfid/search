<?php
	session_start();
	require('settings.php');

	require('engine.php');
	$result = new Search($settings);

	require('view.php');
	$display = new Serp($result, $settings['preference']);

	echo $display->content;

	//echo microtime(true)-$benchmark;
	//unset($_SESSION['dictionary']);
?>
