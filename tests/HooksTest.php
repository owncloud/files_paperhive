<?php
/**
 * @author Piotr Mrowczynski <piotr.mrowczynski@yahoo.com>
 *
 * @copyright Copyright (c) 2017, Piotr Mrowczynski.
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

namespace OCA\Files_PaperHive\Tests;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use OC\Files\View;
use OCA\Files_PaperHive\Hooks;
use OCA\Files_PaperHive\PaperHiveMetadata;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ILogger;
use Test\TestCase;

/**
 * @package OCA\Files_PaperHive\Tests
 */
class HooksTest extends TestCase {

	/**
	 * @var PaperHiveMetadata | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $metadata;

	/**
	 * @var View | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $view;

	/**
	 * @var Hooks
	 */
	private $hooks;

	public function setUp() {
		parent::setUp();

		$this->metadata = $this->createMock(PaperHiveMetadata::class);
		$this->view = $this->createMock(View::class);

		$this->hooks = new Hooks($this->view, $this->metadata);
	}

	public function deleteMetadataProvider() {
		return array (
			array([ "fileid" => "abcd" ], true),
			array(null, false),
		);
	}
	/**
	 * @dataProvider deleteMetadataProvider
	 * @param array $fileInfo
	 * @param bool $deleteExpected
	 */
	public function testDeleteMetadata($fileInfo, $deleteExpected) {
		$path = "/test/path";

		$this->view->expects($this->once())
			->method('getFileInfo')
			->willReturn($fileInfo);

		if ($deleteExpected) {
			$this->metadata->expects($this->once())
				->method('deleteBookID')->with($fileInfo["fileid"])
				->willReturn($fileInfo);
		} else {
			$this->metadata->expects($this->never())
				->method('deleteBookID');
		}

		$this->hooks->deleteBookMetadata($path);
	}
}
