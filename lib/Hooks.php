<?php
/**
 * @author Piotr Mrowczynski <piotr.mrowczynski@yahoo.com>
 *
 * @copyright Copyright (c) 2018, Piotr Mrowczynski.
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

/**
 * This class handles hooks.
 */
namespace OCA\Files_PaperHive;

use OC\Files\View;

class Hooks {

	/**
	 * @var View
	 */
	private $view;

	/**
	 * @var PaperHiveMetadata
	 */
	private $paperHiveMetadata;

	/**
	 */
	public function __construct(View $view, PaperHiveMetadata $paperHiveMetadata) {
		$this->paperHiveMetadata = $paperHiveMetadata;
		$this->view = $view;
	}

	/**
	 * Delete book metadata from file
	 *
	 * @param $path
	 */
	public function deleteBookMetadata($path) {
		if ($fileInfo = $this->view->getFileInfo($path)) {
			$this->paperHiveMetadata->deleteBookID($fileInfo['fileid']);
		}
	}

	/**
	 * Hook to remove metadata from file before deleting
	 * @param array $params
	 */
	public static function delete_metadata_hook($params) {
		$path = $params[\OC\Files\Filesystem::signal_param_path];
		if ($path<>'') {
			$hook = self::createForStaticLegacyCode();
			$hook->deleteBookMetadata($path);
		}
	}

	/**
	 * @var Hooks
	 */
	private static $instance;

	/**
	 * @deprecated use DI
	 * @return Hooks
	 */
	public static function createForStaticLegacyCode() {
		if (!self::$instance) {
			$user = \OC::$server->getUserSession()->getUser();
			if ($user) {
				$uid = $user->getUID();
			} else {
				throw new \BadMethodCallException('no user logged in');
			}

			self::$instance = new Hooks(
				new View(
					'/' . $uid . '/files/'
				),
				new PaperHiveMetadata(
					\OC::$server->getDatabaseConnection(),
					\OC::$server->getLogger()
				)
			);
		}
		return self::$instance;
	}

}
