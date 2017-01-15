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

//TODO: ->will($this->throwException(new Exception()));

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

	private function fakeDiscussions() {
		$this->responseMock->expects($this->any())
			->method('getBody')
			->willReturn('{' . '"discussions" : [ "blabla", "blabla" ]' .'}');

		$this->clientMock->expects($this->any())
			->method('get')
			->willReturn($this->responseMock);
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

	/**
	 * @dataProvider dataTestLoad
	 *
	 * @param string $filename
	 * @param string|boolean $fileContent
	 * @param integer $expectedStatus
	 * @param string $expectedMessage
	 */
	public function testLoad($filename, $fileContent, $expectedStatus, $expectedMessage, $discussionCount) {
		$this->viewMock->expects($this->any())
			->method('file_get_contents')
			->willReturn($fileContent);

		if ($discussionCount != -1) {
			$this->fakeDiscussions();
		}

		$result = $this->controller->load('/', $filename, "true");
		$data = $result->getData();
		$status = $result->getStatus();
		$this->assertSame($status, $expectedStatus);
		if ($status === 200) {
			$this->assertArrayHasKey('paperhive_document', $data);
			$this->assertArrayHasKey('paperhive_discussion_count', $data);
			$this->assertSame($data['paperhive_discussion_count'], $discussionCount);
			$this->assertSame($data['paperhive_document'], $fileContent);
		} else {
			$this->assertArrayHasKey('message', $data);
			$this->assertSame($expectedMessage, $data['message']);
		}
	}

	public function dataTestLoad() {
		return array(
			array('test.txt', 'file content', 200, '', -1),
			array('test.txt', '{' . '"id" : "Ra5WnkxImoOE"' .'}', 200, '', 2),
			array('test.txt', '', 200, '', -1),
			array('test.txt', '0', 200, '', -1),
			array('', 'file content', 400, 'Invalid file path supplied.', -1),
			array('test.txt', false, 400, 'Cannot read the file.', -1),
		);
	}

	public function dataExceptionWithException() {
		return [
			[new \Exception(), 'An internal server error occurred.'],
			[new HintException('error message', 'test exception'), 'test exception'],
			[new ForbiddenException('firewall', false), 'firewall'],
			[new LockedException('secret/path/https://github.com/owncloud/files_texteditor/pull/96'), 'The file is locked.'],
		];
	}

	/**
	 * @dataProvider dataExceptionWithException
	 * @param \Exception $exception
	 * @param string $expectedMessage
	 */
	public function testLoadExceptionWithException(\Exception $exception, $expectedMessage) {

		$this->viewMock->expects($this->any())
			->method('file_get_contents')
			->willReturnCallback(function() use ($exception) {
				throw $exception;
			});

		$result = $this->controller->load('/', 'test.txt', "true");
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame($expectedMessage, $data['message']);
	}

	public function testFileTooBig() {
		$this->viewMock->expects($this->any())
			->method('filesize')
			->willReturn(4194304 + 1);

		$result = $this->controller->load('/', 'foo.bar', "true");
		$data = $result->getData();
		$status = $result->getStatus();
		$this->assertSame(400, $status);
		$this->assertArrayHasKey('message', $data);
		$this->assertSame('This file is too big to be opened. Please download the file instead.', $data['message']);
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
		if ($expectedStatus === 200) {
			$title = "Borderland City in New India";
			$path = $dir . $title. '.paperhive';
			$contents = $this->fakeAll($bookID, $title);
			$this->viewMock->expects($this->once())
				->method('file_put_contents')->with($path, $contents);
		} else {
			$this->viewMock->expects($this->never())->method(('file_put_contents'));
		}

		$result = $this->controller->getPaperHiveDocument($dir, $bookID);
		$status = $result->getStatus();
		$data = $result->getData();

		$this->assertSame($expectedStatus, $status);
		if ($status === 200) {
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

		$result = $this->controller->getPaperHiveDocument($dir, $bookID);
		$data = $result->getData();

		$this->assertSame(400, $result->getStatus());
		$this->assertArrayHasKey('message', $data);
		$this->assertSame($expectedMessage, $data['message']);
	}

	public function dataTestSave() {
		return array (
			array('/', 'Ra5WnkxImoOE', true, true, 200, '')
		);
	}
}
