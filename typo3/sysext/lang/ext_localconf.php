<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Register cache t3lib_l10n
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n'] = array();
}
if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n']['backend'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n']['backend'] = 'TYPO3\\CMS\\Core\\Cache\\Backend\\FileBackend';
}
if (isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['cache']['clear_menu']) && $GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['cache']['clear_menu']) {
	// Register Clear Cache Menu hook
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearLangCache'] = 'EXT:lang/hooks/clearcache/class.tx_lang_clearcachemenu.php:&TYPO3\\CMS\\Lang\\ClearLanguageCacheMenuItem';
} else {
	// Clear l10n cache when the user clears all caches
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['clearLangCache'] = 'EXT:lang/hooks/clearcache/class.tx_lang_clearcache.php:TYPO3\\CMS\\Lang\\LanguageCacheClearer->clearCache';
}
// Register Ajax call
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['lang::clearCache'] = 'EXT:lang/hooks/clearcache/class.tx_lang_clearcache.php:TYPO3\\CMS\\Lang\\LanguageCacheClearer->clearCache';
?>