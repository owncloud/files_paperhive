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
use OCA\Files_PaperHive\PaperHiveMetadata;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ILogger;
use Test\TestCase;

/**
 * @package OCA\Files_PaperHive\Tests
 */
class PaperHiveMetadataTest extends TestCase {

	/**
	 * @var PaperHiveMetadata
	 */
	private $metadata;

	/**
	 * @var IDBConnection | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $connection;

	/**
	 * @var ILogger | \PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	public function setUp(): void {
		parent::setUp();

		$this->connection = $this->createMock(IDBConnection::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->metadata = new PaperHiveMetadata(
			$this->connection,
			$this->logger
		);
	}

	/**
	 * Test if the basic parameters has changed
	 */
	public function testInsertBookID() {
		$this->connection->expects($this->once())
			->method('insertIfNotExist')
			->willReturn(1);

		$result = $this->metadata->insertBookID(32, "abcd");
		$this->assertTrue($result);
	}

	/**
	 * Test if the basic parameters has changed
	 */
	public function testInsertException() {
		$e = $this->createMock(DBALException::class);
		$this->connection->expects($this->once())
			->method('insertIfNotExist')
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('logException');

		$result = $this->metadata->insertBookID(32, "abcd");
		$this->assertFalse($result);
	}

	/**
	 * Test querying for bookId
	 */
	public function testGetBookID() {
		$qb = $this->createMock(IQueryBuilder::class);
		$qbSelect = $this->createMock(IQueryBuilder::class);
		$qbExpr = $this->createMock(IExpressionBuilder::class);
		$qbFrom = $this->createMock(IQueryBuilder::class);
		$qbWhere = $this->createMock(IQueryBuilder::class);
		$cursor = $this->createMock(Statement::class);

		$qb->expects($this->once())
			->method('select')->willReturn($qbSelect);
		$qbSelect->expects($this->once())
			->method('from')->willReturn($qbFrom);
		$qbFrom->expects($this->once())
			->method('where')->willReturn($qbWhere);
		$qb->expects($this->once())
			->method('expr')->willReturn($qbExpr);
		$qbExpr->expects($this->once())
			->method('eq');
		$qbWhere->expects($this->once())
			->method('execute')->willReturn($cursor);
		$row = [];
		$row["bookid"] = "abcd";
		$cursor->expects($this->once())
			->method('fetch')->willReturn($row);

		$this->connection->expects($this->once())
			->method('getQueryBuilder')->willReturnOnConsecutiveCalls($qb);

		$result = $this->metadata->getBookID(32);
		$this->assertEquals($result, "abcd");
	}

	/**
	 * Test querying for bookId and receiving null
	 */
	public function testGetNullBookID() {
		$qb = $this->createMock(IQueryBuilder::class);
		$qbSelect = $this->createMock(IQueryBuilder::class);
		$qbExpr = $this->createMock(IExpressionBuilder::class);
		$qbFrom = $this->createMock(IQueryBuilder::class);
		$qbWhere = $this->createMock(IQueryBuilder::class);
		$cursor = $this->createMock(Statement::class);

		$qb->expects($this->once())
			->method('select')->willReturn($qbSelect);
		$qbSelect->expects($this->once())
			->method('from')->willReturn($qbFrom);
		$qbFrom->expects($this->once())
			->method('where')->willReturn($qbWhere);
		$qb->expects($this->once())
			->method('expr')->willReturn($qbExpr);
		$qbExpr->expects($this->once())
			->method('eq');
		$qbWhere->expects($this->once())
			->method('execute')->willReturn($cursor);
		$row = [];
		$cursor->expects($this->once())
			->method('fetch')->willReturn($row);

		$this->connection->expects($this->once())
			->method('getQueryBuilder')->willReturnOnConsecutiveCalls($qb);

		$result = $this->metadata->getBookID(32);
		$this->assertEquals($result, null);
	}

	/**
	 * Test querying for bookId
	 */
	public function testDeleteBookID() {
		$qb = $this->createMock(IQueryBuilder::class);
		$qbDelete = $this->createMock(IQueryBuilder::class);
		$qbWhere = $this->createMock(IQueryBuilder::class);
		$qbExpr = $this->createMock(IExpressionBuilder::class);

		$qb->expects($this->once())
			->method('delete')->willReturn($qbDelete);
		$qbDelete->expects($this->once())
			->method('where')->willReturn($qbWhere);
		$qbWhere->expects($this->once())
			->method('execute')->willReturn(1);
		$qb->expects($this->once())
			->method('expr')->willReturn($qbExpr);
		$qbExpr->expects($this->once())
			->method('eq');

		$this->connection->expects($this->once())
			->method('getQueryBuilder')->willReturnOnConsecutiveCalls($qb);

		$result = $this->metadata->deleteBookID(32);
		$this->assertEquals($result, 1);
	}
}
