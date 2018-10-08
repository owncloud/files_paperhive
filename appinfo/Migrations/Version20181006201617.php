<?php
/**
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
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

namespace OCA\Files_PaperHive\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

class Version20181006201617 implements ISchemaMigration {

	public function changeSchema(Schema $schema, array $options) {
		$prefix = $options['tablePrefix'];
		if (!$schema->hasTable("{$prefix}paperhive")) {
			$table = $schema->createTable("{$prefix}paperhive");

			$table->addColumn('fileid', 'integer', [
				'length' => 20,
				'notnull' => true,
				'unsigned' => true,
			]);

			$table->addColumn('bookid', 'string', [
				'length' => 64,
				'notnull' => true,
				'default' => ''
			]);

			$table->addIndex(
				['fileid'],
				'paperhive_fileid_index'
			);
		}
	}
}
