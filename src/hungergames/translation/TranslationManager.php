<?php

namespace hungergames\translation;

use pocketmine\utils\TextFormat;
use hungergames\HungerGames;
use hungergames\utils\Utils;

class TranslationManager {

    /** @var array */
    private $data;

    /**
     * TranslationManager constructor.
     */
    public function __construct() {
        $this->data = Utils::getJsonContents(HungerGames::getInstance()->getFile() . 'src/hungergames/translation/files/file.json');
    }

    /**
     * @param string $key
     * @param array $args
     * @param array $data
     * @return string
     */
    public function translateString(string $key, array $args = [], array $data = []): string {
        if(empty($data)) {
            $data = $this->data;
        }

        $text = $data[$key] ?? $key;

        if($text === ''){
            return $key;
        }

        foreach($args as $i => $v){
            $text = str_replace('{%' . $i . '}', $v, $text);
        }

        return TextFormat::colorize($text);
    }
}