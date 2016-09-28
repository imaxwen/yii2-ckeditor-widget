<?php

/*
 * CKFinder
 * ========
 * http://cksource.com/ckfinder
 * Copyright (C) 2007-2015, CKSource - Frederico Knabben. All rights reserved.
 *
 * The software, this file and its contents are subject to the CKFinder
 * License. Please read the license.txt file before using, installing, copying,
 * modifying or distribute this file or part of its contents. The contents of
 * this file is part of the Source Code of CKFinder.
 */

namespace CKSource\CKFinder;

/**
 * Exception class
 *
 * @copyright 2015 CKSource - Frederico Knabben
 */
class Translator
{
    /**
     * Array with translations
     *
     * @var array $translations
     */
    protected $translations;

    public function __construct()
    {
        $locale = isset($_GET['langCode']) ? (string) $_GET['langCode'] : 'en';

        $this->setLocale($locale);
    }

    /**
     * Sets locale for translations
     *
     * @param string $locale
     */
    public function setLocale($locale)
    {
        if (null === $locale || !preg_match('/^[a-z\-]+$/', $locale) || !file_exists(__DIR__ . "/locales/{$locale}.php")) {
            $locale = 'en';
        }

        if (null === $this->translations) {
            $this->translations = require __DIR__ . "/locales/{$locale}.php";
        }
    }

    /**
     * Translates error message for given error code
     *
     * @param int   $errorNumber  error number
     * @param array $replacements array of replacements to use in translated message
     *
     * @return string
     */
    public function translateErrorMessage($errorNumber, $replacements)
    {
        $errorMessage = '';

        if ($errorNumber) {
            if (isset($this->translations['Errors'][$errorNumber])) {
                $errorMessage = $this->translations['Errors'][$errorNumber];

                $replacementsCount = count($replacements);

                for ($i = 0; $i < $replacementsCount; $i++) {
                    $errorMessage = str_replace('%' . ($i + 1), $replacements[$i], $errorMessage);

                }
            } else {
                $errorMessage = str_replace("%1", $errorNumber, $this->translations['ErrorUnknown']);
            }
        }

        return $errorMessage;
    }
}
