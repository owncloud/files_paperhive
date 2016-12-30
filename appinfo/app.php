<?php

// only load text editor if the user is logged in
if (\OCP\User::isLoggedIn()) {
	$eventDispatcher = \OC::$server->getEventDispatcher();
	$eventDispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
		OCP\Util::addStyle('files_paperhive', 'style');
		OCP\Util::addscript('files_paperhive', 'paperhive');
	});
}

