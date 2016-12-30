<?php

// only load text editor if the user is logged in
if (\OCP\User::isLoggedIn()) {
	$eventDispatcher = \OC::$server->getEventDispatcher();
	$eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
		OCP\Util::addscript('files_paperhive', 'paperhive');
	});
}

