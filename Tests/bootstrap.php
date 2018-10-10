<?php

	setlocale(LC_ALL, 'en_US.utf8');
	date_default_timezone_set('America/New_York');

	if (extension_loaded('xdebug')) {
		xdebug_enable();
	} else {
		echo("xdebug extension not found, please configure and retry\n");
	}

	require('./vendor/autoload.php');
