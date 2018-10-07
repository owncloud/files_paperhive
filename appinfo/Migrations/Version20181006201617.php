<?php

namespace OCA\Files_PaperHive\Migrations;

use Doctrine\DBAL\Schema\Schema;
use OCP\Migration\ISchemaMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
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
