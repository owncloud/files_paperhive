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

namespace OCA\Files_PaperHive\Tests\Controller;

use OC\HintException;
use OCA\Files_PaperHive\Controller\PaperHiveController;
use OCP\Files\ForbiddenException;
use OCP\Lock\LockedException;
use Test\TestCase;

class PaperHiveControllerTest extends TestCase {

	/** @var PaperHiveController */
	protected $controller;

	/** @var string */
	protected $appName;

	/** @var \OCP\IRequest | \PHPUnit_Framework_MockObject_MockObject */
	protected $requestMock;

	/** @var \OCP\Http\Client\IResponse | \PHPUnit_Framework_MockObject_MockObject */
	protected $responseMock;

	/** @var \OCP\IL10N | \PHPUnit_Framework_MockObject_MockObject */
	private $l10nMock;

	/** @var \OCP\ILogger | \PHPUnit_Framework_MockObject_MockObject */
	private $loggerMock;

	/** @var \OC\Files\View | \PHPUnit_Framework_MockObject_MockObject */
	private $viewMock;

	/** @var \OCP\Http\Client\IClient | \PHPUnit_Framework_MockObject_MockObject */
	private $clientMock;
	
	public function setUp() {
		parent::setUp();
		$this->appName = 'files_paperhive';
		$this->requestMock = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->responseMock = $this->getMockBuilder('OCP\Http\Client\IResponse')
			->disableOriginalConstructor()
			->getMock();
		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->loggerMock = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$this->viewMock = $this->getMockBuilder('OC\Files\View')
			->disableOriginalConstructor()
			->getMock();
		$this->clientMock = $this->getMockBuilder('OCP\Http\Client\IClient')
			->disableOriginalConstructor()
			->getMock();
		
		$this->l10nMock->expects($this->any())->method('t')->willReturnCallback(
			function($message) {
				return $message;
			}
		);

		$this->controller = new PaperHiveController(
			$this->appName,
			$this->requestMock,
			$this->l10nMock,
			$this->viewMock,
			$this->loggerMock,
			$this->clientMock);
	}

	private function fakeAll($bookID, $title) {
		$contentsDoc = '{' . '"id" : "'.$bookID .'", "title" : "'. $title .'" }';
		$contentsDis = '{' . '"discussions" : [ "blabla", "blabla" ]' .'}';
		$this->responseMock->expects($this->any())
			->method('getBody')
			->willReturnOnConsecutiveCalls($contentsDis, $contentsDoc);
		$this->clientMock->expects($this->any())
			->method('get')
			->willReturn($this->responseMock);

		return $contentsDoc;
	}

	/**
	 * Test if the basic parameters has changed
	 */
	public function testPaperHiveDetails() {
		$result = $this->controller->getPaperHiveDetails();
		$data = $result->getData();
		$status = $result->getStatus();
		$this->assertSame($status, 200);
		$this->assertArrayHasKey('paperhive_base_url', $data);
		$this->assertSame($data['paperhive_base_url'], 'https://paperhive.org');
		$this->assertArrayHasKey('paperhive_api_url', $data);
		$this->assertSame($data['paperhive_api_url'], '/api/documents/');
		$this->assertArrayHasKey('paperhive_document_url', $data);
		$this->assertSame($data['paperhive_document_url'], '/documents/');
		$this->assertArrayHasKey('paperhive_discussion_api_endpoint', $data);
		$this->assertSame($data['paperhive_discussion_api_endpoint'], '/discussions');
		$this->assertArrayHasKey('paperhive_extension', $data);
		$this->assertSame($data['paperhive_extension'], '.paperhive');
	}


	public function dataExceptionWithException() {
		return [
			[new \Exception(), 'An internal server error occurred.'],
			[new ForbiddenException('firewall', false), 'firewall'],
			[new LockedException('secret/path/https://github.com/owncloud/files_texteditor/pull/96'), 'The file is locked.'],
		];
	}

	/**
	 * @dataProvider dataExceptionWithException
	 * @param \Exception $exception
	 * @param string $expectedMessage
	 */
	public function testLoadMetadataForRenamedDocumentExceptions(\Exception $exception, $expectedMessage) {
		$dir = '';
		$bookID = 'Ra5WnkxImoOE';
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title. '.renamed.paperhive';
		$contents = $this->fakeAll($bookID, $title);

		$this->viewMock->expects($this->once())
			->method('file_exists')
			->willReturn(true);

		$this->viewMock->expects($this->any())
			->method('file_get_contents')
			->willReturnCallback(function() use ($exception) {
				throw $exception;
			});

		$this->viewMock->expects($this->any())
			->method('rename')
			->willReturn(false);

		$result = $this->controller->loadMetadata('/', $path, "true");
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame($expectedMessage, $data['message']);
	}

	public function testLoadMetadataForRenamedDocumentNoExtension() {
		$dir = '';
		$bookID = 'Ra5WnkxImoOE';
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title;
		$contents = $this->fakeAll($bookID, $title);

		$result = $this->controller->loadMetadata('/', $path, "true");
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame('Invalid file path supplied.', $data['message']);
	}

	public function testLoadMetadataForRenamedDocumentNotExisting() {
		$dir = '';
		$bookID = 'Ra5WnkxImoOE';
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title. '.renamed.paperhive';
		$contents = $this->fakeAll($bookID, $title);

		$this->viewMock->expects($this->once())
			->method('file_exists')
			->willReturn(false);

		$result = $this->controller->loadMetadata('/', $path, "true");
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame('File is obsolete, incorrectly renamed or cannot be read.', $data['message']);
	}

	public function testLoadMetadataForRenamedDocumentWrongContent() {
		$dir = '';
		$bookID = 'Ra5WnkxImoOE';
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title. '.renamed.paperhive';
		$contents = $this->fakeAll($bookID, $title);

		$this->viewMock->expects($this->once())
			->method('file_exists')
			->willReturn(true);

		$this->viewMock->expects($this->once())
			->method('file_get_contents')
			->willReturn('wrong content');

		$result = $this->controller->loadMetadata('/', $path, "true");
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame('File is obsolete, incorrectly renamed or cannot be read.', $data['message']);
	}

	public function testLoadMetadataForRenamedDocumentFailRename() {
	$dir = '';
	$bookID = 'Ra5WnkxImoOE';
	$title = "Borderland City in New India";
	$path = $dir . '/'. $title. '.renamed.paperhive';
	$contents = $this->fakeAll($bookID, $title);

	$this->viewMock->expects($this->once())
		->method('file_exists')
		->willReturn(true);

	$this->viewMock->expects($this->once())
		->method('file_get_contents')
		->willReturn($contents);

		$this->viewMock->expects($this->once())
			->method('rename')
			->willReturn(false);

	$result = $this->controller->loadMetadata('/', $path, "true");
	$data = $result->getData();

	$this->assertSame(400, $result->getStatus());
	$this->assertArrayHasKey('message', $data);
	$this->assertSame('File is obsolete, incorrectly renamed or cannot be read.', $data['message']);
}

	public function testLoadMetadataForRenamedDocument() {
		$dir = '';
		$bookID = 'Ra5WnkxImoOE';
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title. '.renamed.paperhive';
		$contents = $this->fakeAll($bookID, $title);

		$this->viewMock->expects($this->once())
			->method('file_exists')
			->willReturn(true);

		$this->viewMock->expects($this->once())
			->method('file_get_contents')
			->willReturn($contents);

		$this->viewMock->expects($this->once())
			->method('rename')
			->willReturn(true);

		$result = $this->controller->loadMetadata('/', $path, "true");
		$data = $result->getData();

		$this->assertSame(200, $result->getStatus());
		$this->assertArrayHasKey('paperhive_base_url', $data);
		$this->assertArrayHasKey('paperhive_api_url', $data);
		$this->assertArrayHasKey('paperhive_document_url', $data);
		$this->assertArrayHasKey('paperhive_document_id', $data);
		$this->assertArrayHasKey('paperhive_discussion_api_endpoint', $data);
		$this->assertArrayHasKey('paperhive_extension', $data);
		$this->assertArrayHasKey('paperhive_discussion_count', $data);
		$this->assertSame($bookID, $data['paperhive_document_id']);
		$this->assertSame(2, $data['paperhive_discussion_count']);
	}

	public function testLoadMetadataWithoutDiscussions() {
		$dir = '';
		$bookID = 'Ra5WnkxImoOE';
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title. '.rev'.$bookID.'.paperhive';

		$result = $this->controller->loadMetadata('/', $path, "false");
		$data = $result->getData();

		$this->assertSame(200, $result->getStatus());
		$this->assertArrayHasKey('paperhive_base_url', $data);
		$this->assertArrayHasKey('paperhive_api_url', $data);
		$this->assertArrayHasKey('paperhive_document_url', $data);
		$this->assertArrayHasKey('paperhive_document_id', $data);
		$this->assertArrayHasKey('paperhive_discussion_api_endpoint', $data);
		$this->assertArrayHasKey('paperhive_extension', $data);
		$this->assertArrayHasKey('paperhive_discussion_count', $data);
		$this->assertSame($bookID, $data['paperhive_document_id']);
		$this->assertSame(-1, $data['paperhive_discussion_count']);
	}

	public function testLoadMetadata() {
		$dir = '';
		$bookID = 'Ra5WnkxImoOE';
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title. '.rev'.$bookID.'.paperhive';
		$contents = $this->fakeAll($bookID, $title);

		$result = $this->controller->loadMetadata('/', $path, "true");
		$data = $result->getData();

		$this->assertSame(200, $result->getStatus());
		$this->assertArrayHasKey('paperhive_base_url', $data);
		$this->assertArrayHasKey('paperhive_api_url', $data);
		$this->assertArrayHasKey('paperhive_document_url', $data);
		$this->assertArrayHasKey('paperhive_document_id', $data);
		$this->assertArrayHasKey('paperhive_discussion_api_endpoint', $data);
		$this->assertArrayHasKey('paperhive_extension', $data);
		$this->assertArrayHasKey('paperhive_discussion_count', $data);
		$this->assertSame($bookID, $data['paperhive_document_id']);
		$this->assertSame(2, $data['paperhive_discussion_count']);
	}

	public function dataTestSave() {
		return array (
			array('', 'Ra5WnkxImoOE', true, true, 200, ''),
			array('/test', 'Ra5WnkxImoOE', true, true, 200, ''),
			array('', 'Ra5WnkxImoOE', true, true, 400, 'The file already exists.')
		);
	}

	/**
	 * @dataProvider dataTestSave
	 *
	 * @param $dir
	 * @param $bookID
	 * @param $correctDiscussions
	 * @param $correctDocument
	 * @param $expectedStatus
	 * @param $expectedMessage
	 */
	public function testGetPaperHiveDocument($dir, $bookID, $correctDiscussions, $correctDocument, $expectedStatus, $expectedMessage) {
		$title = "Borderland City in New India";
		$path = $dir . '/'. $title. '.rev'.$bookID.'.paperhive';
		$contents = $this->fakeAll($bookID, $title);

		if ($expectedStatus === 200) {
			$this->viewMock->expects($this->once())
				->method('file_put_contents')->with($path, $contents);
		} else {
			$this->viewMock->expects($this->once())
				->method('file_exists')->with($path)
				->willReturn(true);
			$this->viewMock->expects($this->never())->method(('file_put_contents'));
		}

		$result = $this->controller->generatePaperHiveDocument($dir, $bookID);
		$status = $result->getStatus();
		$data = $result->getData();

		$this->assertSame($expectedStatus, $status);
		if ($status === 200) {
			$this->assertArrayHasKey('path', $data);
			$this->assertArrayHasKey('filename', $data);
			$this->assertArrayHasKey('extension', $data);
			$this->assertArrayHasKey('discussionCount', $data);
			$this->assertSame(2, $data['discussionCount']);
			$this->assertSame('.rev'.$bookID.'.paperhive', $data['extension']);
		} else {
			$this->assertArrayHasKey('message', $data);
			$this->assertSame($expectedMessage, $data['message']);
		}
	}

	/**
	 * @dataProvider dataExceptionWithException
	 * @param \Exception $exception
	 * @param string $expectedMessage
	 */
	public function testGetDocumentExceptionWithException(\Exception $exception, $expectedMessage) {
		$title = "Borderland City in New India";
		$dir = '/';
		$bookID = 'Ra5WnkxImoOE';
		$contents = $this->fakeAll($bookID, $title);
		$this->viewMock->expects($this->any())
			->method('file_put_contents')
			->willReturnCallback(function() use ($exception) {
				throw $exception;
			});

		$result = $this->controller->generatePaperHiveDocument($dir, $bookID);
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame($expectedMessage, $data['message']);
	}
}
