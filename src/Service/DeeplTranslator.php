<?php

namespace App\Service;

use DeepL\DeepLException;
use DeepL\Translator;

class DeeplTranslator
{
    private string $authKey = 'd5b89854-5f65-bfd2-c2ce-a7473af4f25e:fx';
    public Translator $translator;

     public function __construct()
    {
        try {
            $this->translator = new Translator($this->authKey);
        } catch (DeepLException $e) {
        }
    }


}