<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('help', 'txtsconfighelpM1', '', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'mod1/');
}
?>