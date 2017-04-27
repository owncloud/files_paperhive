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
	private $paperhive_api_url = '/api/documents/';

	/**
	 * Paperhive url for text API
	 */
	private $paperhive_document_url = '/documents/';

	/**
	 * Paperhive url for discussions API
	 */
	private $paperhive_discussion_api_endpoint = '/discussions';

	/**
	 * Paperhive file extension
	 */
	private $paperhive_file_extension = '.paperhive';

	/**
	 * Paperhive revision extension
	 */
	private $paperhive_rev_extension = '.rev';
	
	/**
	 * @NoAdminRequired
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IL10N $l10n
	 * @param View $view
	 * @param ILogger $logger
	 */
	public function __construct($appName,
								IRequest $request,
								IL10N $l10n,
								View $view,
								ILogger $logger,
								IClient $client) {
		parent::__construct($appName, $request);
		$this->l = $l10n;
		$this->view = $view;
		$this->logger = $logger;
		$this->client = $client;
	}

	/**
	 * Adjust paperhive extension for correct one
	 *
	 * @param string $path
	 * @param string $revision
	 * @return boolean - returns true if success or false in case of error
	 */
	private function adjustPaperHiveExtensions($path, $revision) {
		$pathWithoutPHExtension = str_replace($this->paperhive_file_extension, '', $path);
		$newPath = $pathWithoutPHExtension . $this->paperhive_rev_extension . $revision . $this->paperhive_file_extension;
		if ($this->view->rename($path, $newPath) !== false) {
			return true;
		}
		return false;
	}

	/**
	 * Load paperhive metadata from file, 
	 * adjust filename if wrong and return PaperHive ID
	 *	 *
	 * @param string $dir
	 * @param string $filename
	 * @return string/boolean - returns PaperHive ID or false in case of error
	 */
	private function loadPaperHiveIdFromFile($dir, $filename) {
		$path = $dir . '/' . $filename;
		if (!$this->view->file_exists($path)){
			return false;
		}
		
		$fileContents = $this->view->file_get_contents($path);
		if ($fileContents !== false) {
			$paperHiveObject = json_decode($fileContents, true);
			if (json_last_error() === JSON_ERROR_NONE && isset($paperHiveObject['id'])) {
				$paperHiveId = $paperHiveObject['id'];
				if($this->adjustPaperHiveExtensions($path, $paperHiveId)){
					return $paperHiveId;
				}
			}
		}
		return false;
	}

	/**
	 * load paperhive metadata and if needed, discussion count
	 *
	 * @NoAdminRequired
	 *
	 * @param string $dir
	 * @param string $filename
	 * @param boolean $fetchDiscussions
	 * @return DataResponse
	 */
	public function loadMetadata($dir, $filename, $fetchDiscussions) {
		try {
			$filenameParts = explode('.', $filename);
			if (sizeof($filenameParts) > 1) {
				// Correct, file needs filename and extension
				if (sizeof($filenameParts) === 2 || (sizeof($filenameParts) > 2 &&
						strpos('.'.$filenameParts[sizeof($filenameParts)-2],$this->paperhive_rev_extension) === false)){
					// File needs correction, since been renamed or is obsolete
					$revision = $this->loadPaperHiveIdFromFile($dir, $filename);
					if ($revision === false){
						return new DataResponse(['message' => (string)$this->l->t('File is obsolete, incorrectly renamed or cannot be read.')], Http::STATUS_BAD_REQUEST);
					}
				} else {
					// File has correct format, and revision is the second extension
					// Add extension dot since explode removed it and replace rev extension with empty string
					$revisionString = '.'.$filenameParts[sizeof($filenameParts)-2];
					$revision = str_replace($this->paperhive_rev_extension, '', $revisionString);
				}

				$disscussionCount = -1;
				if ($fetchDiscussions == "true") {
					$paperHiveString = $this->fetchDiscussions($revision);
					$paperHiveDiscussions = json_decode($paperHiveString, true);
					if (json_last_error() === JSON_ERROR_NONE && isset($paperHiveDiscussions['discussions'])) {
						$disscussionCount = count($paperHiveDiscussions['discussions']);
					}
				}

				return new DataResponse([
					'paperhive_base_url' => $this->paperhive_base_url,
					'paperhive_api_url' => $this->paperhive_api_url,
					'paperhive_document_url' => $this->paperhive_document_url,
					'paperhive_document_id' => $revision,
					'paperhive_discussion_api_endpoint' => $this->paperhive_discussion_api_endpoint,
					'paperhive_extension' => $this->paperhive_file_extension,
					'paperhive_discussion_count' => $disscussionCount
				], Http::STATUS_OK);
			} else {
				return new DataResponse(['message' => (string)$this->l->t('Invalid file path supplied.')], Http::STATUS_BAD_REQUEST);
			}

		} catch (LockedException $e) {
			$message = (string) $this->l->t('The file is locked.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		} catch (ForbiddenException $e) {
			return new DataResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
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
		$urlDiscussions = $this->paperhive_base_url . $this->paperhive_api_url . $bookID . $this->paperhive_discussion_api_endpoint;
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
		$urlDocument = $this->paperhive_base_url . $this->paperhive_api_url . $bookID;
		try {
			$response = $this->client->get($urlDocument, []);
		} catch (\Exception $e) {
			return false;
		}
		return $response->getBody();
	}

	/**
	 * Gets the information about the book for specific BookID and saves as a file in requested directory
	 *
	 * @NoAdminRequired
	 *
	 * @param string $dir
	 * @param string $bookID
	 * @return DataResponse
	 */
	public function generatePaperHiveDocument($dir, $bookID) {
		// Send request to PaperHive
		$paperHiveString = $this->fetchDiscussions($bookID);
		if ($paperHiveString === false) {
			$message = (string)$this->l->t('Problem connecting to PaperHive.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
		$paperHiveDiscussions = json_decode($paperHiveString, true);
		$discussionCount = -1;
		if (json_last_error() === JSON_ERROR_NONE && isset($paperHiveDiscussions['discussions'])) {
			$discussionCount = count($paperHiveDiscussions['discussions']);
		}
		
		$paperHiveString = $this->fetchDocument($bookID);
		if ($paperHiveString === false) {
			$message = (string)$this->l->t('Problem connecting to PaperHive.');
			return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
		}
		$paperHiveObject = json_decode($paperHiveString, true);
		

		if (json_last_error() === JSON_ERROR_NONE && isset($paperHiveObject['title'])) {
			$extension = $this->paperhive_rev_extension . $bookID . $this->paperhive_file_extension;
			$filename = $paperHiveObject['title'] . $extension;

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
				$filecontents = iconv(mb_detect_encoding($paperHiveString), "UTF-8", $paperHiveString);
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
				return new DataResponse(['path' => $path, 'filename' => $paperHiveObject['title'], 'extension' => $extension, 'discussionCount' => $discussionCount], Http::STATUS_OK);
			} catch (HintException $e) {
				$message = (string)$e->getHint();
				return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
			} catch (\Exception $e) {
				$message = (string)$this->l->t('An internal server error occurred.');
				return new DataResponse(['message' => $message], Http::STATUS_BAD_REQUEST);
			}
		} else {
			$message = (string)$this->l->t('Received wrong response from PaperHive.');
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
			'paperhive_base_url' => $this->paperhive_base_url,
			'paperhive_api_url' => $this->paperhive_api_url,
			'paperhive_document_url' => $this->paperhive_document_url,
			'paperhive_discussion_api_endpoint' => $this->paperhive_discussion_api_endpoint,
			'paperhive_extension' => $this->paperhive_file_extension
		], Http::STATUS_OK);
	}
}
