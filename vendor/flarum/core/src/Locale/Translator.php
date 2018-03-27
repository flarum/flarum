<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    const REFERENCE_REGEX = '/^=>\s*([a-z0-9_\-\.]+)$/i';

    /**
     * {@inheritdoc}
     */
    public function getCatalogue($locale = null)
    {
        if (null === $locale) {
            $locale = $this->getLocale();
        } else {
            $this->assertValidLocale($locale);
        }

        $parse = ! isset($this->catalogues[$locale]);

        $catalogue = parent::getCatalogue($locale);

        if ($parse) {
            $this->parseCatalogue($catalogue);

            $fallbackCatalogue = $catalogue;
            while ($fallbackCatalogue = $fallbackCatalogue->getFallbackCatalogue()) {
                $this->parseCatalogue($fallbackCatalogue);
            }
        }

        return $catalogue;
    }

    /**
     * @param MessageCatalogueInterface $catalogue
     */
    private function parseCatalogue(MessageCatalogueInterface $catalogue)
    {
        foreach ($catalogue->all() as $domain => $messages) {
            foreach ($messages as $id => $translation) {
                if (preg_match(self::REFERENCE_REGEX, $translation, $matches)) {
                    $catalogue->set($id, $this->getTranslation($catalogue, $id, $domain), $domain);
                }
            }
        }
    }

    /**
     * @param MessageCatalogueInterface $catalogue
     * @param string $id
     * @param string $domain
     * @return string
     */
    private function getTranslation(MessageCatalogueInterface $catalogue, $id, $domain)
    {
        $translation = $catalogue->get($id, $domain);

        if (preg_match(self::REFERENCE_REGEX, $translation, $matches)) {
            return $this->getTranslation($catalogue, $matches[1], $domain);
        }

        return $translation;
    }
}
