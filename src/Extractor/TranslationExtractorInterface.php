<?php
namespace Drupal\potion\Extractor;

interface TranslationExtractorInterface
{
    /**
     * @param string $path
     *
     * @return array of translations keys
     */
    public function extract($path);
}