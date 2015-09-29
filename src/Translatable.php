<?php

/*
 * This file is part of Laravel Translator.
 *
 * (c) Vincent Klaiber <hello@vinkla.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Vinkla\Translator;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * This is the translatable trait.
 *
 * @author Vincent Klaiber <hello@vinkla.com>
 */
trait Translatable
{
    /**
     * Get a translation.
     *
     * @param string|null $locale
     *
     * @throws \Vinkla\Translator\TranslatorException
     *
     * @return mixed
     */
    public function translate($locale = null)
    {
        $locale = $locale ?: $this->getLocale();

        $translation = $this->getTranslation($locale);

        if (!$translation) {
            $translation = $this->getTranslation($this->getFallback());
        }

        return $translation;
    }

    /**
     * Get the translations relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany($this->translator);
    }

    /**
     * Get a translation.
     *
     * @param string $locale
     *
     * @return mixed
     */
    protected function getTranslation($locale)
    {
        return $this->translations()->where('locale', $locale)->first();
    }

    /**
     * Get the locale.
     *
     * @return string
     */
    protected function getLocale()
    {
        return App::getLocale();
    }

    /**
     * Get the fallback locale.
     *
     * @return string
     */
    protected function getFallback()
    {
        return Config::get('app.fallback_locale');
    }
}
