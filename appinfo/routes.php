<?php
/**
 * @author Piotr Mrowczynski <piotr.mrowczynski@yahoo.com>
 *
 * @copyright Copyright (c) 2017, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

$app = new \OCA\Files_PaperHive\AppInfo\Application();

$app->registerRoutes(
	$this,
	[
		'routes' => [
			[
				'name' => 'PaperHive#getPaperHiveDetails',
				'url' => '/getpaperhivedetails',
				'verb' => 'GET'
			],
			[
				'name' => 'PaperHive#getPaperHiveBookURL',
				'url' => '/getpaperhivebookurl',
				'verb' => 'GET'
			],
			[
				'name' => 'PaperHive#getPaperHiveBookDiscussionCount',
				'url' => '/getpaperhivebookdiscussioncount',
				'verb' => 'GET'
			],
			[
				'name' => 'PaperHive#generatePaperHiveDocument',
				'url' => '/generatepaperhivedocument',
				'verb' => 'GET'
			]
		]
	]
);