<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2011 Oliver Klee (typo3-coding@oliverklee.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for the t3lib_TCEmain class in the TYPO3 core.
 *
 * @package TYPO3
 * @subpackage t3lib
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Tolleiv Nietsch <info@tolleiv.de>
 */
class t3lib_tcemainTest extends tx_phpunit_testcase {

	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * a backup of the global database
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseBackup = NULL;

	/**
	 * @var \TYPO3\CMS\Core\DataHandler\DataHandler
	 */
	private $fixture;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication a mock logged-in back-end user
	 */
	private $backEndUser;

	public function setUp() {
		$this->databaseBackup = $GLOBALS['TYPO3_DB'];
		$this->backEndUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$this->fixture = new \TYPO3\CMS\Core\DataHandler\DataHandler();
		$this->fixture->start(array(), '', $this->backEndUser);
	}

	public function tearDown() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::purgeInstances();
		$GLOBALS['TYPO3_DB'] = $this->databaseBackup;
		unset($this->fixture->BE_USER, $this->fixture, $this->backEndUser, $this->databaseBackup);
	}

	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////
	/**
	 * @test
	 */
	public function fixtureCanBeCreated() {
		$this->assertTrue($this->fixture instanceof \TYPO3\CMS\Core\DataHandler\DataHandler);
	}

	//////////////////////////////////////////
	// Test concerning checkModifyAccessList
	//////////////////////////////////////////
	/**
	 * @test
	 */
	public function adminIsAllowedToModifyNonAdminTable() {
		$this->fixture->admin = TRUE;
		$this->assertTrue($this->fixture->checkModifyAccessList('tt_content'));
	}

	/**
	 * @test
	 */
	public function nonAdminIsNorAllowedToModifyNonAdminTable() {
		$this->fixture->admin = FALSE;
		$this->assertFalse($this->fixture->checkModifyAccessList('tt_content'));
	}

	/**
	 * @test
	 */
	public function nonAdminWithTableModifyAccessIsAllowedToModifyNonAdminTable() {
		$this->fixture->admin = FALSE;
		$this->backEndUser->groupData['tables_modify'] = 'tt_content';
		$this->assertTrue($this->fixture->checkModifyAccessList('tt_content'));
	}

	/**
	 * @test
	 */
	public function adminIsAllowedToModifyAdminTable() {
		$this->fixture->admin = TRUE;
		$this->assertTrue($this->fixture->checkModifyAccessList('be_users'));
	}

	/**
	 * @test
	 */
	public function nonAdminIsNotAllowedToModifyAdminTable() {
		$this->fixture->admin = FALSE;
		$this->assertFalse($this->fixture->checkModifyAccessList('be_users'));
	}

	/**
	 * @test
	 */
	public function nonAdminWithTableModifyAccessIsNotAllowedToModifyAdminTable() {
		$this->fixture->admin = FALSE;
		$this->backEndUser->groupData['tables_modify'] = 'be_users';
		$this->assertFalse($this->fixture->checkModifyAccessList('be_users'));
	}

	/**
	 * @test
	 */
	public function evalCheckValueDouble2() {
		$testData = array(
			'-0,5' => '-0.50',
			'1000' => '1000.00',
			'1000,10' => '1000.10',
			'1000,0' => '1000.00',
			'600.000.000,00' => '600000000.00',
			'60aaa00' => '6000.00'
		);
		foreach ($testData as $value => $expectedReturnValue) {
			$returnValue = $this->fixture->checkValue_input_Eval($value, array('double2'), '');
			$this->assertSame($returnValue['value'], $expectedReturnValue);
		}
	}

	///////////////////////////////////////////
	// Tests concerning checkModifyAccessList
	///////////////////////////////////////////
	/**
	 * Tests whether a wrong interface on the 'checkModifyAccessList' hook throws an exception.
	 *
	 * @test
	 * @expectedException UnexpectedValueException
	 * @see t3lib_TCEmain::checkModifyAccessList()
	 */
	public function doesCheckModifyAccessListThrowExceptionOnWrongHookInterface() {
		$hookClass = uniqid('tx_coretest');
		eval(('class ' . $hookClass) . ' {}');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
		$this->fixture->checkModifyAccessList('tt_content');
	}

	/**
	 * Tests whether the 'checkModifyAccessList' hook is called correctly.
	 *
	 * @test
	 * @see t3lib_TCEmain::checkModifyAccessList()
	 */
	public function doesCheckModifyAccessListHookGetsCalled() {
		$hookClass = uniqid('tx_coretest');
		$hookMock = $this->getMock('TYPO3\\CMS\\Core\\DataHandling\\DataHandlerCheckModifyAccessListHookInterface', array('checkModifyAccessList'), array(), $hookClass);
		$hookMock->expects($this->once())->method('checkModifyAccessList');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
		$GLOBALS['T3_VAR']['getUserObj'][$hookClass] = $hookMock;
		$this->fixture->checkModifyAccessList('tt_content');
	}

	/**
	 * Tests whether the 'checkModifyAccessList' hook modifies the $accessAllowed variable.
	 *
	 * @test
	 * @see t3lib_TCEmain::checkModifyAccessList()
	 */
	public function doesCheckModifyAccessListHookModifyAccessAllowed() {
		$hookClass = uniqid('tx_coretest');
		eval(('
			class ' . $hookClass) . ' implements \\TYPO3\\CMS\\Core\\DataHandling\\DataHandlerCheckModifyAccessListHookInterface {
				public function checkModifyAccessList(&$accessAllowed, $table, \\TYPO3\\CMS\\Core\\DataHandler\\DataHandler $parent) { $accessAllowed = TRUE; }
			}
		');
		$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkModifyAccessList'][] = $hookClass;
		$this->assertTrue($this->fixture->checkModifyAccessList('tt_content'));
	}

	/////////////////////////////////////
	// Tests concerning process_datamap
	/////////////////////////////////////
	/**
	 * @test
	 */
	public function processDatamapForFrozenNonZeroWorkspaceReturnsFalse() {
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\DataHandler\\DataHandler', array('newlog'));
		$this->backEndUser->workspace = 1;
		$this->backEndUser->workspaceRec = array('freeze' => TRUE);
		$fixture->BE_USER = $this->backEndUser;
		$this->assertFalse($fixture->process_datamap());
	}

	/**
	 * @test
	 */
	public function processDatamapWhenEditingRecordInWorkspaceCreatesNewRecordInWorkspace() {
		$GLOBALS['TYPO3_DB'] = $this->getMock('TYPO3\\CMS\\Core\\Database\\DatabaseConnection');
		$fixture = $this->getMock('TYPO3\\CMS\\Core\\DataHandler\\DataHandler', array('newlog', 'checkModifyAccessList', 'tableReadOnly', 'checkRecordUpdateAccess'));
		$fixture->bypassWorkspaceRestrictions = FALSE;
		$fixture->datamap = array(
			'pages' => array(
				'1' => array(
					'header' => 'demo'
				)
			)
		);
		$fixture->expects($this->once())->method('checkModifyAccessList')->with('pages')->will($this->returnValue(TRUE));
		$fixture->expects($this->once())->method('tableReadOnly')->with('pages')->will($this->returnValue(FALSE));
		$fixture->expects($this->once())->method('checkRecordUpdateAccess')->will($this->returnValue(TRUE));
		$backEndUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backEndUser->workspace = 1;
		$backEndUser->workspaceRec = array('freeze' => FALSE);
		$backEndUser->expects($this->once())->method('workspaceAllowAutoCreation')->will($this->returnValue(TRUE));
		$backEndUser->expects($this->once())->method('workspaceCannotEditRecord')->will($this->returnValue(TRUE));
		$backEndUser->expects($this->once())->method('recordEditAccessInternals')->with('pages', 1)->will($this->returnValue(TRUE));
		$fixture->BE_USER = $backEndUser;
		$createdTceMain = $this->getMock('TYPO3\\CMS\\Core\\DataHandler\\DataHandler', array());
		$createdTceMain->expects($this->once())->method('start')->with(array(), array(
			'pages' => array(
				1 => array(
					'version' => array(
						'action' => 'new',
						'treeLevels' => -1,
						'label' => 'Auto-created for WS #1'
					)
				)
			)
		));
		$createdTceMain->expects($this->never())->method('process_datamap');
		$createdTceMain->expects($this->once())->method('process_cmdmap');
		\TYPO3\CMS\Core\Utility\GeneralUtility::addInstance('TYPO3\\CMS\\Core\\DataHandler\\DataHandler', $createdTceMain);
		$fixture->process_datamap();
	}

	/////////////////////////////////////
	// Tests concerning log
	/////////////////////////////////////
	/**
	 * @test
	 */
	public function logCallsWriteLogOfBackendUserIfLoggingIsEnabled() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->expects($this->once())->method('writelog');
		$this->fixture->enableLogging = TRUE;
		$this->fixture->BE_USER = $backendUser;
		$this->fixture->log('', 23, 0, 42, 0, 'details');
	}

	/**
	 * @test
	 */
	public function logDoesNotCallWriteLogOfBackendUserIfLoggingIsDisabled() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$backendUser->expects($this->never())->method('writelog');
		$this->fixture->enableLogging = FALSE;
		$this->fixture->BE_USER = $backendUser;
		$this->fixture->log('', 23, 0, 42, 0, 'details');
	}

	/**
	 * @test
	 */
	public function logAddsEntryToLocalErrorLogArray() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$this->fixture->BE_USER = $backendUser;
		$this->fixture->enableLogging = TRUE;
		$this->fixture->errorLog = array();
		$logDetailsUnique = uniqid('details');
		$this->fixture->log('', 23, 0, 42, 1, $logDetailsUnique);
		$this->assertStringEndsWith($logDetailsUnique, $this->fixture->errorLog[0]);
	}

	/**
	 * @test
	 */
	public function logFormatsDetailMessageWithAdditionalDataInLocalErrorArray() {
		$backendUser = $this->getMock('TYPO3\\CMS\\Core\\Authentication\\BackendUserAuthentication');
		$this->fixture->BE_USER = $backendUser;
		$this->fixture->enableLogging = TRUE;
		$this->fixture->errorLog = array();
		$logDetails = uniqid('details');
		$this->fixture->log('', 23, 0, 42, 1, ('%1s' . $logDetails) . '%2s', -1, array('foo', 'bar'));
		$expected = ('foo' . $logDetails) . 'bar';
		$this->assertStringEndsWith($expected, $this->fixture->errorLog[0]);
	}

}

?>