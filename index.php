<?php
	session_start();
	require('settings.php');
	require('pdo.php');

	require('engine.php');
	$result = new Search($settings);

	require('view.php');
	$display = new Serp(
		$result,
		$settings['preference']
	);

	echo $display->data;

	//echo microtime(true)-$benchmark;
	unset($_SESSION['dictionary']);
?>