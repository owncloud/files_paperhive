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

namespace OCA\Files_PaperHive\Controller;

use OCP\Http\Client\IClient;
use OC\Files\View;
use OC\HintException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\ForbiddenException;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Lock\LockedException;

class PaperHiveController extends Controller{

	/** @var IL10N */
	private $l;

	/** @var View */
	private $view;

	/** @var ILogger */
	private $logger;

	/** @var \OCP\Http\Client\IClient */
	private $client;

	/**
	 * Paperhive base URL
	 */
	private $paperhive_base_url = 'https://paperhive.org';

	/**
	 * Paperhive url for document API
	 */
	private $paperhive_api_documents = '/api/document-items/';

	/**
	 * Paperhive url for discussions API
	 */
	private $paperhive_api_discussions = '/api/discussions?documentItem=';

	/**
	 * Paperhive url for document text in browser
	 */
	private $paperhive_base_document_url = '/documents/items/';

	/**
	 * Paperhive file extension
	 */
	private $paperhive_file_extension = '.paperhive';

	/**
	 * Paperhive BookID example
	 */
	private $paperhive_bookid_example = 'ZYY0r21rJbqr';

	/**
	 * @NoAdminRequired
	 *
	 * @param string $AppName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param View $view
	 * @param ILogger $logger
	 */
	public function __construct($AppName,
								IRequest $request,
								IL10N $l10n,
								View $view,
								ILogger $logger,
								IClient $client) {
		parent::__construct($AppName, $request);
		$this->l = $l10n;
		$this->view = $view;
		$this->logger = $logger;
		$this->client = $client;
	}
	
	/**
	 * load text file
	 *
	 * @NoAdminRequired
	 *
	 * @param string $dir
	 * @param string $filename
	 * @param boolean $fetchDiscussions
	 * @return DataResponse
	 */
	public function load($dir, $filename, $fetchDiscussions) {
		try {
			if (!empty($filename)) {
				$path = $dir . '/' . $filename;
				$fileContents = $this->view->file_get_contents($path);
				if ($fileContents !== false) {
					$encoding = mb_detect_encoding($fileContents . "a", "UTF-8, WINDOWS-1252, ISO-8859-15, ISO-8859-1, ASCII", true);
					if ($encoding == "") {
						// set default encoding if it couldn't be detected
						$encoding = 'ISO-8859-15';
					}
					$fileContents = iconv($encoding, "UTF-8", $fileContents);

					$disscussionCount = -1;
					if ($fetchDiscussions == "true") {
						$paperHiveObject = json_decode($fileContents, true);

						if (json_last_error() === JSON_ERROR_NONE && isset($paperHiveObject['id'])) {
							$paperHiveString = $this->fetchDiscussions($paperHiveObject['id']);
							$paperHiveDiscussions = json_decode($paperHiveString, true);
							if (json_last_error() === JSON_ERROR_NONE && isset($paperHiveDiscussions['discussions'])) {
								$disscussionCount = count($paperHiveDiscussions['discussions']);
							}

						}
					}

					return new DataResponse([
						'paperhive_document' => $fileContents,
						'paperhive_base_url' => $this->paperhive_base_url,
						'paperhive_base_document_url' => $this->paperhive_base_document_url,
						'paperhive_api_documents' => $this->paperhive_api_documents,
						'paperhive_api_discussions' => $this->paperhive_api_discussions,
						'paperhive_extension' => $this->paperhive_file_extension,
						'paperhive_discussion_count' => $disscussionCount
					], Http::STATUS_OK);
				} else {
					return new DataResponse(['message' => (string)$this->l->t('Cannot read the file.')], Http::STATUS_BAD_REQUEST);
				}
			} else {
				return new DataResponse(['message' => (string)$this->l->t('Invalid file path supplied.')], Http::STATUS_BAD_REQUEST);
			}

		} catch (LockedException $e) {
			$message = (string) $this->l->t('The file is locked.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		} catch (ForbiddenException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			$message = (string)$this->l->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Does the call to PaperHive Discussions API and returns disussions for specific it for specific BookID
	 *
	 * @NoAdminRequired
	 *
	 * @param string $bookID
	 * @return string
	 */
	private function fetchDiscussions($bookID) {
		$urlDiscussions = $this->paperhive_base_url . $this->paperhive_api_discussions . $bookID;
		try {
			$response = $this->client->get($urlDiscussions, []);
		} catch (\Exception $e) {
			return false;
		}
		return $response->getBody();
	}

	/**
	 * Does the call to PaperHive Documents API and returns the JSON for a book for specific BookID
	 *
	 * @NoAdminRequired
	 *
	 * @param string $bookID
	 * @return string/boolean
	 */
	private function fetchDocument($bookID) {
		$urlDocument = $this->paperhive_base_url . $this->paperhive_api_documents . $bookID;
		try {
			$response = $this->client->get($urlDocument, []);
		} catch (\Exception $e) {
			return false;
		}
		return $response->getBody();
	}

	/**
	 * Gets the informations about the book for specific BookID and saves as a file
	 *
	 * @NoAdminRequired
	 *
	 * @param string $dir
	 * @param string $bookID
	 * @return DataResponse
	 */
	public function getPaperHiveDocument($dir, $bookID) {
		// Try to get the document
		$paperHiveObjectString = $this->fetchDocument($bookID);
		if ($paperHiveObjectString === false) {
			$message = (string)$this->l->t('Problem connecting to PaperHive.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
		$paperHiveObject = json_decode($paperHiveObjectString, true);

		// Check if correct response has been returned
		if (json_last_error() != JSON_ERROR_NONE) {
			$message = (string)$this->l->t('Received wrong response from PaperHive.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}

		// Check if document is found
		if (!(isset($paperHiveObject['metadata']) && isset($paperHiveObject['metadata']['title']))) {
			$message = (string)$this->l->t('Document with this BookID cannot be found');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}

		// Try fetching discussions
		$paperHiveDiscussionsString = $this->fetchDiscussions($bookID);
		if ($paperHiveDiscussionsString === false) {
			$message = (string)$this->l->t('Problem connecting to PaperHive.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
		$paperHiveDiscussions = json_decode($paperHiveDiscussionsString, true);
		$discussionCount = -1;
		if (json_last_error() === JSON_ERROR_NONE && isset($paperHiveDiscussions['discussions'])) {
			$discussionCount = count($paperHiveDiscussions['discussions']);
		}

		// Save the file
		$title = $paperHiveObject['metadata']['title'];
		$filename = $title . $this->paperhive_file_extension;
		if($dir == '/') {
			$path = $dir . $filename;
		} else {
			$path = $dir . '/' . $filename;
		}

		try {
			$exists = $this->view->file_exists($path);
			if ($exists) {
				$message = (string) $this->l->t('The file already exists.');
				return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
			}

			$filecontents = iconv(mb_detect_encoding($paperHiveObjectString), "UTF-8", $paperHiveObjectString);
			try {
				$this->view->file_put_contents($path, $filecontents);
			} catch (LockedException $e) {
				$message = (string) $this->l->t('The file is locked.');
				return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
			} catch (ForbiddenException $e) {
				return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
			}
			// Clear statcache
			clearstatcache();
			return new DataResponse(['path' => $path, 'filename' => $paperHiveObject['title'], 'extension' => $this->paperhive_file_extension, 'discussionCount' => $discussionCount], Http::STATUS_OK);
		} catch (HintException $e) {
			$message = (string)$e->getHint();
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		} catch (\Exception $e) {
			$message = (string)$this->l->t('An internal server error occurred.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
	}
	
	/**
	 * Returns all required PaperHive setting
	 *
	 * @NoAdminRequired
	 *
	 * @param string $bookID
	 * @return DataResponse
	 */
	public function getPaperHiveDetails() {
		return new DataResponse([
			'paperhive_bookid_example' => $this->paperhive_bookid_example,
			'paperhive_base_url' => $this->paperhive_base_url,
			'paperhive_base_document_url' => $this->paperhive_base_document_url,
			'paperhive_api_documents' => $this->paperhive_api_documents,
			'paperhive_api_discussions' => $this->paperhive_api_discussions,
			'paperhive_extension' => $this->paperhive_file_extension,
		], Http::STATUS_OK);
	}
}
