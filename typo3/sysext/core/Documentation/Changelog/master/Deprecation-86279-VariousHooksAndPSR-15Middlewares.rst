.. include:: ../../Includes.txt

==========================================================
Deprecation: #86279 - Various Hooks and PSR-15 Middlewares
==========================================================

See :issue:`86279`

Description
===========

The new PSR-15-based middleware concept allows for a more fine-grained "hooking" mechanism when enhancing the HTTP
Request or Response object.

The following hooks have therefore been marked as deprecated:
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['tslib_fe-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['initFEuser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preBeUser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest']`

On top, some middlewares have only been introduced in order to execute these hooks, or due to, and are marked for
internal use:

* typo3/cms-core/normalized-params-attribute
* typo3/cms-backend/legacy-document-template
* typo3/cms-backend/output-compression
* typo3/cms-backend/response-headers
* typo3/cms-frontend/timetracker
* typo3/cms-frontend/preprocessing
* typo3/cms-frontend/eid
* typo3/cms-frontend/content-length-headers
* typo3/cms-frontend/tsfe
* typo3/cms-frontend/output-compression
* typo3/cms-frontend/prepare-tsfe-rendering
* typo3/cms-frontend/shortcut-and-mountpoint-redirect

As these middlewares are marked as internal, it is recommended not to reference them directly, as these might get removed
in TYPO3 v10.0.


Impact
======

Making use of one of the hooks in an extension will trigger a deprecation warning.


Affected Installations
======================

TYPO3 instances with extensions using one of the hooks.


Migration
=========

Use a custom PSR-15 middleware instead.

.. index:: PHP-API, FullyScanned, ext:frontend
