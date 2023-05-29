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

use OC\Files\View;
use OC\HintException;
use OCA\Files_PaperHive\Controller\PaperHiveController;
use OCA\Files_PaperHive\PaperHiveMetadata;
use OCP\Files\ForbiddenException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Lock\LockedException;
use Test\TestCase;

class PaperHiveControllerTest extends TestCase {
	/** @var PaperHiveController */
	protected $controller;

	/** @var string */
	protected $appName;

	/** @var \OCP\IRequest | \PHPUnit\Framework\MockObject\MockObject */
	protected $requestMock;

	/** @var \OCP\Http\Client\IResponse | \PHPUnit\Framework\MockObject\MockObject */
	protected $responseMock;

	/** @var \OCP\IL10N | \PHPUnit\Framework\MockObject\MockObject */
	private $l10nMock;

	/** @var \OCP\ILogger | \PHPUnit\Framework\MockObject\MockObject */
	private $loggerMock;

	/** @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject */
	private $viewMock;

	/** @var \OCP\Http\Client\IClient | \PHPUnit\Framework\MockObject\MockObject */
	private $clientMock;

	/** @var PaperHiveMetadata | \PHPUnit\Framework\MockObject\MockObject */
	private $metaMock;
	
	public function setUp(): void {
		parent::setUp();
		$this->appName = 'files_paperhive';
		$this->requestMock = $this->getMockBuilder(IRequest::class)
			->disableOriginalConstructor()
			->getMock();
		$this->responseMock = $this->getMockBuilder(IResponse::class)
			->disableOriginalConstructor()
			->getMock();
		$this->l10nMock = $this->getMockBuilder(IL10N::class)
			->disableOriginalConstructor()
			->getMock();
		$this->loggerMock = $this->getMockBuilder(ILogger::class)
			->disableOriginalConstructor()
			->getMock();
		$this->viewMock = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();
		$this->clientMock = $this->getMockBuilder(IClient::class)
			->disableOriginalConstructor()
			->getMock();
		$this->metaMock = $this->getMockBuilder(PaperHiveMetadata::class)
			->disableOriginalConstructor()
			->getMock();
		
		$this->l10nMock->expects($this->any())->method('t')->willReturnCallback(
			function ($message) {
				return $message;
			}
		);

		$this->controller = new PaperHiveController(
			$this->appName,
			$this->requestMock,
			$this->l10nMock,
			$this->viewMock,
			$this->loggerMock,
			$this->clientMock,
			$this->metaMock
		);
	}

	/**
	 * Test if the basic parameters has changed
	 */
	public function testPaperHiveDetails() {
		$result = $this->controller->getPaperHiveDetails();
		$data = $result->getData();
		$status = $result->getStatus();
		$this->assertSame($status, 200);
		$this->assertArrayHasKey('paperhive_bookid_example', $data);
		$this->assertSame($data['paperhive_bookid_example'], 'ZYY0r21rJbqr');
		$this->assertArrayHasKey('paperhive_base_url', $data);
		$this->assertSame($data['paperhive_base_url'], 'https://paperhive.org');
		$this->assertArrayHasKey('paperhive_base_document_url', $data);
		$this->assertSame($data['paperhive_base_document_url'], '/documents/items/');
		$this->assertArrayHasKey('paperhive_api_documents', $data);
		$this->assertSame($data['paperhive_api_documents'], '/api/document-items/');
		$this->assertArrayHasKey('paperhive_api_discussions', $data);
		$this->assertSame($data['paperhive_api_discussions'], '/api/discussions?documentItem=');
		$this->assertArrayHasKey('paperhive_extension', $data);
		$this->assertSame($data['paperhive_extension'], '.paperhive');
	}

	public function dataTestSave() {
		return [
			[
				'/', '
				Ra5WnkxImoOE',
				false,
				true,
				true,
				'{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}',
				'{' . '"discussions" : [ "blabla", "blabla" ]' .'}',
				200,
				null
			],
			[
				'/test/path', '
				Ra5WnkxImoOE',
				false,
				true,
				true,
				'{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}',
				'{' . '"discussions" : [ "blabla", "blabla" ]' .'}',
				200,
				null
			],
			[
				'/', '
				Ra5WnkxImoOE',
				true,
				null,
				null,
				'{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}',
				'{' . '"discussions" : [ "blabla", "blabla" ]' .'}',
				400,
				'The file already exists.'
			],
			[
				'/', '
				Ra5WnkxImoOE',
				false,
				false,
				null,
				'{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}',
				'{' . '"discussions" : [ "blabla", "blabla" ]' .'}',
				400,
				'Could not save document.'
			],
			[
				'/', '
				Ra5WnkxImoOE',
				false,
				true,
				false,
				'{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}',
				'{' . '"discussions" : [ "blabla", "blabla" ]' .'}',
				400,
				'Could not save document metadata.'
			],
			[
				'/', '
				Ra5WnkxImoOE',
				null,
				null,
				null,
				false,
				null,
				400,
				'Problem connecting to PaperHive.'
			],
			[
				'/', '
				Ra5WnkxImoOE',
				null,
				null,
				null,
				'{ ',
				null,
				400,
				'Received wrong response from PaperHive.'
			],
			[
				'/', '
				Ra5WnkxImoOE',
				null,
				null,
				null,
				'{ }',
				null,
				400,
				'Document with this BookID cannot be found'
			],
			[
				'/', '
				Ra5WnkxImoOE',
				null,
				null,
				null,
				'{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}',
				false,
				400,
				'Problem connecting to PaperHive to fetch discussions.'
			],
			[
				'/', '
				Ra5WnkxImoOE',
				null,
				null,
				null,
				'{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}',
				'{ ',
				400,
				'Problem connecting to PaperHive to fetch discussions.'
			],
		];
	}

	/**
	 * @dataProvider dataTestSave
	 *
	 * @param $dir
	 * @param $bookID
	 * @param $exists
	 * @param $paperHiveObjectString
	 * @param $created
	 * @param $inserted
	 * @param $paperHiveDiscussionsString
	 * @param $expectedStatus
	 * @param $expectedResponseMessage
	 */
	public function testGeneratePaperHiveDocument($dir, $bookID, $exists, $created, $inserted, $paperHiveObjectString, $paperHiveDiscussionsString, $expectedStatus, $expectedResponseMessage) {
		$title = "some title";
		if ($dir == '/') {
			$path = $dir . $title. '.paperhive';
		} else {
			$path = $dir . '/' . $title. '.paperhive';
		}
		$fileinfo = [ "fileid" => 1 ];
		$this->viewMock->expects($this->any())
			->method('file_exists')->with($path)->willReturn($exists);
		$this->viewMock->expects($this->any())
			->method('touch')->with($path)->willReturn($created);
		$this->viewMock->expects($this->any())
			->method('getFileInfo')->with($path)->willReturn($fileinfo);

		$contentsDoc = $paperHiveObjectString;
		$contentsDis = $paperHiveDiscussionsString;
		$this->responseMock->expects($this->any())
			->method('getBody')
			->willReturnOnConsecutiveCalls($contentsDoc, $contentsDis);
		$this->clientMock->expects($this->any())
			->method('get')
			->willReturn($this->responseMock);
		$this->metaMock->expects($this->any())
			->method('insertBookID')->with(1, $bookID)->willReturn($inserted);

		$result = $this->controller->generatePaperHiveDocument($dir, $bookID);
		$status = $result->getStatus();
		$data = $result->getData();

		$this->assertSame($expectedStatus, $status);
		if ($status === 200) {
			$this->assertArrayHasKey('path', $data);
			$this->assertArrayHasKey('filename', $data);
			$this->assertArrayHasKey('extension', $data);
			$this->assertArrayHasKey('discussionCount', $data);
			$this->assertSame($expectedResponseMessage, null);
		} else {
			$this->assertArrayHasKey('message', $data);
			$this->assertSame($expectedResponseMessage, $data['message']);
		}
	}

	public function dataGetBook() {
		return [
			['/', [ "fileid" => 1 ], 200],
			['/test/path', [ "fileid" => 1 ], 200],
			['/test/path', false, 400],
		];
	}

	/**
	 * @dataProvider dataGetBook
	 *
	 * @param $dir
	 * @param $fileInfo
	 * @param $expectedStatus
	 */
	public function testGetPaperHiveBook($dir, $fileInfo, $expectedStatus) {
		$title = "some title";

		if ($dir == '/') {
			$path = $dir . $title;
		} else {
			$path = $dir . '/' . $title;
		}
		$this->viewMock->expects($this->any())
			->method('getFileInfo')->with($path)->willReturn($fileInfo);
		$this->metaMock->expects($this->any())
			->method('getBookID')->with(1)->willReturn("abcd");

		$result = $this->controller->getPaperHiveBookURL($dir, $title);
		$status = $result->getStatus();
		$this->assertSame($status, $expectedStatus);
	}

	public function dataGetDiscussions() {
		return [
			[null, '{' . '"discussions" : [ "blabla", "blabla" ]' .'}', 200, 2],
			[null, '{ ', 200, -1], // silently ignore
			[null, '{ }', 200, -1], // silently ignore
			[new \Exception, null, 200, -1], // silently ignore
		];
	}

	/**
	 * @dataProvider dataGetDiscussions
	 *
	 * @param $clientException
	 * @param $discussions
	 * @param $expectedStatus
	 * @param $expectedData
	 */
	public function testGetDiscussions($clientException, $discussions, $expectedStatus, $expectedData) {
		$title = "some title";
		$dir = '/';
		$fileInfo = [ "fileid" => 1 ];
		$path = $dir . $title;

		if ($clientException) {
			$this->clientMock->expects($this->any())
				->method('get')
				->willThrowException($clientException);
		} else {
			;
			$this->responseMock->expects($this->any())
				->method('getBody')
				->willReturn($discussions);
			$this->clientMock->expects($this->any())
				->method('get')
				->willReturn($this->responseMock);
		}
		$this->viewMock->expects($this->any())
			->method('getFileInfo')->with($path)->willReturn($fileInfo);
		$this->metaMock->expects($this->any())
			->method('getBookID')->with(1)->willReturn("abcd");

		$result = $this->controller->getPaperHiveBookDiscussionCount($dir, $title);
		$status = $result->getStatus();
		$data = $result->getData();
		$this->assertSame($status, $expectedStatus);
		$this->assertSame($data, [$expectedData]);
	}

	public function dataExceptionWithException() {
		return [
			[null, new \Exception(), 'An internal server error occurred.'],
			[null, new ForbiddenException('firewall', false), 'firewall'],
			[null, new LockedException('/test/path locked'), 'The file is locked.'],
			[new \Exception(), null, 'Problem connecting to PaperHive.'],
		];
	}

	/**
	 * @dataProvider dataExceptionWithException
	 *
	 * @param $clientException
	 * @param $dbException
	 * @param $expectedExceptionMessage
	 */
	public function testGeneratePaperHiveDocumentWithException($clientException, $dbException, $expectedExceptionMessage) {
		$bookID = 1;
		$title = "some title";
		$path = '/' . $title. '.paperhive';

		if ($clientException) {
			$this->clientMock->expects($this->any())
				->method('get')
				->willThrowException($clientException);
		} else {
			$contentsDoc = '{ "metadata": { ' . '"id" : "'. "some id" .'", "title" : "'. "some title" .'" }}';
			$contentsDis = '{' . '"discussions" : [ "blabla", "blabla" ]' .'}';
			$this->responseMock->expects($this->any())
				->method('getBody')
				->willReturnOnConsecutiveCalls($contentsDoc, $contentsDis);
			$this->clientMock->expects($this->any())
				->method('get')
				->willReturn($this->responseMock);
		}

		$this->viewMock->expects($this->any())
			->method('file_exists')->with($path)->willReturn(false);

		if ($dbException) {
			$this->viewMock->expects($this->any())
				->method('touch')->with($path)->willThrowException($dbException);
			$this->viewMock->expects($this->never())
				->method('getFileInfo');
		}

		$result = $this->controller->generatePaperHiveDocument('/', $bookID);
		$status = $result->getStatus();
		$data = $result->getData();

		$this->assertSame(400, $status);
		$this->assertArrayHasKey('message', $data);
		$this->assertSame($expectedExceptionMessage, $data['message']);
	}
}
