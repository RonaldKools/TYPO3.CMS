<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Responsible for generates URLs to pages which are NOT bound to any permissions or frontend restrictions.
 *
 * If a page is built with a site in the root line, the base of the site (+ language) is used
 * and the &L parameter is then dropped explicitly.
 *
 * @internal as this might change until TYPO3 v9 LTS
 * @todo: check handling of MP parameter.
 */
class PageUriBuilder implements SingletonInterface
{
    /**
     * Generates an absolute URL
     */
    public const ABSOLUTE_URL = 'url';

    /**
     * Generates an absolute path
     */
    public const ABSOLUTE_PATH = 'path';

    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    /**
     * PageUriBuilder constructor.
     */
    public function __construct()
    {
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
    }

    /**
     * Main entrypoint for generating an Uri for a page.
     *
     * @param int $pageId
     * @param array $queryParameters
     * @param string $fragment
     * @param array $options ['language' => 123, 'rootLine' => etc.]
     * @param string $referenceType
     * @return UriInterface
     */
    public function buildUri(int $pageId, array $queryParameters = [], string $fragment = null, array $options = [], string $referenceType = self::ABSOLUTE_PATH): UriInterface
    {
        // Resolve site
        $site = $options['site'] ?? null;
        $siteLanguage = null;
        $languageOption = $options['language'] ?? null;
        $languageQueryParameter = isset($queryParameters['L']) ? (int)$queryParameters['L'] : null;

        if (!($site instanceof Site)) {
            try {
                $site = $this->siteFinder->getSiteByPageId($pageId, $options['rootLine'] ?? null);
            } catch (SiteNotFoundException $e) {
                // no site found, must be an pseudo site site
            }
        }
        if ($languageOption) {
            if ($languageOption instanceof SiteLanguage) {
                $siteLanguage = $languageOption;
                $languageOption = $languageOption->getLanguageId();
            } else {
                $languageOption = (int)$languageOption;
            }
        }
        $languageId = $languageOption ?? $languageQueryParameter ?? null;

        // Resolve language (based on the options / query parameters, and remove it
        // from GET variables, as the language is determined by the language path
        if ($site) {
            if ($languageId !== null) {
                try {
                    $siteLanguage = $site->getLanguageById($languageId);
                    unset($queryParameters['L']);
                } catch (\InvalidArgumentException $e) {
                    // No Language found, so do fallback linking
                }
            } else {
                $siteLanguage = $site->getDefaultLanguage();
                unset($queryParameters['L']);
            }
        }

        // If something is found, use /en/?id=123&additionalParams
        // Only if a language is configured for the site, build a URL with a site prefix / base
        if ($site && $siteLanguage) {
            // Ensure to fetch the path segment / slug if it exists
            $pageRecord = BackendUtility::getRecord('pages', $pageId);
            if ($siteLanguage->getLanguageId() > 0) {
                $pageLocalizations = BackendUtility::getRecordLocalization('pages', $pageId, $siteLanguage->getLanguageId());
                $pageRecord = $pageLocalizations[0] ?? $pageRecord;
            }
            $prefix = (string)$siteLanguage->getBase();
            if (!empty($pageRecord['slug'] ?? '')) {
                $prefix = rtrim($prefix, '/') . '/' . ltrim($pageRecord['slug'], '/');
            } else {
                $prefix .= '?id=' . $pageId;
            }
        } else {
            // If nothing is found, use index.php?id=123&additionalParams
            // This usually kicks in with "PseudoSites" where no language object can be determined.
            $prefix = $referenceType === self::ABSOLUTE_URL ? GeneralUtility::getIndpEnv('TYPO3_SITE_URL') : '';
            $prefix .= 'index.php?id=' . $pageId;
            if ($languageId !== null) {
                $queryParameters['L'] = $languageId;
            }
        }

        // Add the query parameters as string
        $queryString = http_build_query($queryParameters, '', '&', PHP_QUERY_RFC3986);
        $prefix = rtrim($prefix, '?');
        if (!empty($queryString)) {
            if (strpos($prefix, '?') === false) {
                $prefix .= '?';
            } else {
                $prefix .= '&';
            }
        }
        $uri = new Uri($prefix . $queryString);
        if ($fragment) {
            $uri = $uri->withFragment($fragment);
        }
        if ($referenceType === self::ABSOLUTE_PATH) {
            $uri = $uri->withScheme('')->withHost('')->withPort(null);
        }
        return $uri;
    }
}
