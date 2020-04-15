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

use Doctrine\DBAL\DBALException;
use OCP\IDBConnection;
use OCP\ILogger;

class PaperHiveMetadata {

	/**
	 * Database connection
	 *
	 * @var IDBConnection
	 */
	private $dbConn;

	/**
	 * Logger
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param IDBConnection $dbConn database connection
	 * @param ILogger $logger logger
	 */
	public function __construct(IDBConnection $dbConn, ILogger $logger) {
		$this->dbConn = $dbConn;
		$this->logger = $logger;
	}

	/**
	 * @param mixed $fileId
	 * @param mixed $docId
	 * @return bool
	 */
	public function insertBookID($fileId, $docId) {
		try {
			$result = $this->dbConn->insertIfNotExist('*PREFIX*paperhive', [
				'fileid' => $fileId,
				'bookid' => $docId,
			]);

			return ($result === 1);
		} catch (DBALException $e) {
			$this->logger->logException($e, [
				'app' => 'files_paperhive',
				'message' => 'Could not add BookID to paperhive table'
			]);

			return false;
		}
	}

	/**
	 * @param mixed $fileId
	 * @return string|null
	 */
	public function getBookID($fileId) {
		$qb = $this->dbConn->getQueryBuilder();
		$cursor = $qb->select(['bookid'])
			->from('paperhive')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
			->execute();

		while ($row = $cursor->fetch()) {
			return $row['bookid'];
		}
		$cursor->closeCursor();

		return null;
	}

	/**
	 * @param mixed $fileId
	 * @return bool
	 */
	public function deleteBookID($fileId) {
		$qb = $this->dbConn->getQueryBuilder();
		$result = $qb->delete('paperhive')
			->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
			->execute();

		return ($result === 1);
	}
}
