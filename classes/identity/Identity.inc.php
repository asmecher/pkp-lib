<?php

/**
 * @defgroup identity Identity
 * Implements an abstract identity underlying e.g. User and Author records.
 */

/**
 * @file classes/identity/Identity.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Identity
 * @ingroup identity
 *
 * @brief Basic class providing common functionality for users and authors in the system.
 */

namespace PKP\identity;

use APP\core\Application;
use APP\i18n\AppLocale;

class Identity extends \PKP\core\DataObject
{
    public const IDENTITY_SETTING_GIVENNAME = 'givenName';
    public const IDENTITY_SETTING_FAMILYNAME = 'familyName';

    /**
     * Get a piece of data for this object, localized to the current
     * locale if possible.
     *
     * @param $key string
     * @param $preferredLocale string
     */
    public function &getLocalizedData($key, $preferredLocale = null)
    {
        if (is_null($preferredLocale)) {
            $preferredLocale = AppLocale::getLocale();
        }
        $localePrecedence = [$preferredLocale];
        // the users register for the site, thus
        // the site primary locale is the default locale
        $site = Application::get()->getRequest()->getSite();
        if (!in_array($site->getPrimaryLocale(), $localePrecedence)) {
            $localePrecedence[] = $site->getPrimaryLocale();
        }
        // for settings other than givenName, familyName and affiliation (that are required for registration)
        // consider also the context primary locale
        if (!in_array(AppLocale::getPrimaryLocale(), $localePrecedence)) {
            $localePrecedence[] = AppLocale::getPrimaryLocale();
        }
        foreach ($localePrecedence as $locale) {
            if (empty($locale)) {
                continue;
            }
            $value = & $this->getData($key, $locale);
            if (!empty($value)) {
                return $value;
            }
            unset($value);
        }

        // Fallback: Get the first available piece of data.
        $data = & $this->getData($key, null);
        foreach ((array) $data as $dataValue) {
            if (!empty($dataValue)) {
                return $dataValue;
            }
        }

        // No data available; return null.
        unset($data);
        $data = null;
        return $data;
    }

    /**
     * Get the identity's localized complete name.
     * Includes given name and family name.
     *
     * @param $preferred boolean If the preferred public name should be used, if exist
     * @param $familyFirst boolean False / default: Givenname Familyname
     * 	If true: Familyname, Givenname
     * @param $defaultLocale string
     *
     * @return string
     */
    public function getFullName($preferred = true, $familyFirst = false, $defaultLocale = null)
    {
        $locale = AppLocale::getLocale();
        if ($preferred) {
            $preferredPublicName = $this->getPreferredPublicName($locale);
            if (!empty($preferredPublicName)) {
                return $preferredPublicName;
            }
        }
        $givenName = $this->getGivenName($locale);
        if (empty($givenName)) {
            if (is_null($defaultLocale)) {
                // the users register for the site, thus
                // the site primary locale is the default locale
                $site = Application::get()->getRequest()->getSite();
                $defaultLocale = $site->getPrimaryLocale();
            }
            $locale = $defaultLocale;
            $givenName = $this->getGivenName($locale);
        }
        $familyName = $this->getFamilyName($locale);
        if ($familyFirst) {
            return ($familyName != '' ? "${familyName}, " : '') . $givenName;
        } else {
            return $givenName . ($familyName != '' ? " ${familyName}" : '');
        }
    }

    /**
     * Get given name.
     *
     * @param $locale string
     *
     * @return string|array
     */
    public function getGivenName($locale)
    {
        return $this->getData(self::IDENTITY_SETTING_GIVENNAME, $locale);
    }

    /**
     * Set given name.
     *
     * @param $givenName string
     * @param $locale string
     */
    public function setGivenName($givenName, $locale)
    {
        $this->setData(self::IDENTITY_SETTING_GIVENNAME, $givenName, $locale);
    }

    /**
     * Get the localized given name
     *
     * @param null|mixed $defaultLocale
     *
     * @return string
     */
    public function getLocalizedGivenName($defaultLocale = null)
    {
        return $this->getLocalizedData(self::IDENTITY_SETTING_GIVENNAME, $defaultLocale);
    }

    /**
     * Get family name.
     *
     * @param $locale string
     *
     * @return string|array
     */
    public function getFamilyName($locale)
    {
        return $this->getData(self::IDENTITY_SETTING_FAMILYNAME, $locale);
    }

    /**
     * Set family name.
     *
     * @param $familyName string
     * @param $locale string
     */
    public function setFamilyName($familyName, $locale)
    {
        $this->setData(self::IDENTITY_SETTING_FAMILYNAME, $familyName, $locale);
    }

    /**
     * Get the localized family name
     * Return family name for the locale first name exists in
     *
     * @param $defaultLocale string
     *
     * @return string
     */
    public function getLocalizedFamilyName($defaultLocale = null)
    {
        // Prioritize the current locale, then the default locale.
        $localePriorityList = [AppLocale::getLocale()];
        if (!is_null($defaultLocale)) {
            $localePriorityList[] = $defaultLocale;
        }

        foreach ($localePriorityList as $locale) {
            $givenName = $this->getGivenName($locale);
            // Only use the family name if a given name exists (to avoid mixing locale data)
            if (!empty($givenName)) {
                return $this->getFamilyName($locale);
            }
        }

        // Fall back on the site locale if nothing else was found. (May mix locale data.)
        $site = Application::get()->getRequest()->getSite();
        $locale = $site->getPrimaryLocale();
        return $this->getFamilyName($locale);
    }

    /**
     * Get preferred public name.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getPreferredPublicName($locale)
    {
        return $this->getData('preferredPublicName', $locale);
    }

    /**
     * Set preferred public name.
     *
     * @param $preferredPublicName string
     * @param $locale string
     */
    public function setPreferredPublicName($preferredPublicName, $locale)
    {
        $this->setData('preferredPublicName', $preferredPublicName, $locale);
    }

    /**
     * Get affiliation (position, institution, etc.).
     *
     * @param $locale string
     *
     * @return string
     */
    public function getAffiliation($locale)
    {
        return $this->getData('affiliation', $locale);
    }

    /**
     * Set affiliation.
     *
     * @param $affiliation string
     * @param $locale string
     */
    public function setAffiliation($affiliation, $locale)
    {
        $this->setData('affiliation', $affiliation, $locale);
    }

    /**
     * Get the localized affiliation
     */
    public function getLocalizedAffiliation()
    {
        return $this->getLocalizedData('affiliation');
    }

    /**
     * Get email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->getData('email');
    }

    /**
     * Set email address.
     *
     * @param $email string
     */
    public function setEmail($email)
    {
        $this->setData('email', $email);
    }

    /**
     * Get ORCID identifier
     *
     * @return string
     */
    public function getOrcid()
    {
        return $this->getData('orcid');
    }

    /**
     * Set ORCID identifier.
     *
     * @param $orcid string
     */
    public function setOrcid($orcid)
    {
        $this->setData('orcid', $orcid);
    }

    /**
     * Get country code (ISO 3166-1 two-letter codes)
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->getData('country');
    }

    /**
     * Get localized country
     *
     * @return string?
     */
    public function getCountryLocalized()
    {
        $countryCode = $this->getCountry();
        if (!$countryCode) {
            return null;
        }
        $isoCodes = new \Sokil\IsoCodes\IsoCodesFactory();
        $country = $isoCodes->getCountries()->getByAlpha2($countryCode);
        return $country ? $country->getLocalName() : null;
    }

    /**
     * Set country code (ISO 3166-1 two-letter codes)
     *
     * @param $country string
     */
    public function setCountry($country)
    {
        $this->setData('country', $country);
    }

    /**
     * Get URL.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->getData('url');
    }

    /**
     * Set URL.
     *
     * @param $url string
     */
    public function setUrl($url)
    {
        $this->setData('url', $url);
    }

    /**
     * Get the localized biography
     *
     * @return string
     */
    public function getLocalizedBiography()
    {
        return $this->getLocalizedData('biography');
    }

    /**
     * Get biography.
     *
     * @param $locale string
     *
     * @return string
     */
    public function getBiography($locale)
    {
        return $this->getData('biography', $locale);
    }

    /**
     * Set biography.
     *
     * @param $biography string
     * @param $locale string
     */
    public function setBiography($biography, $locale)
    {
        $this->setData('biography', $biography, $locale);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\PKP\identity\Identity', '\Identity');
    foreach (['IDENTITY_SETTING_GIVENNAME', 'IDENTITY_SETTING_FAMILYNAME'] as $constantName) {
        define($constantName, constant('\Identity::' . $constantName));
    }
}
