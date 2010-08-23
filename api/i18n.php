<?php
/*
 * 
 */

// Should be singleton?

class I18n {
	var $content;

    private $NO_ID = 1;
    private $SV_ID = 2;
    private $DA_ID = 3;
    private $EN_ID = 4;

	function __construct($l = NULL) {
        $lang = api_config::getInstance()->lang;
        if (!$l) {
            $currentLang = $lang['default'];
        } else if (in_array($l, $lang['languages'])) {
            $currentlang = $l;
        } else {
            // No language what to do?
            throw exception( new Exception("Language {$l} missing"));
        }
            $langFile = PROJECT_DIR."config/locale/".$currentLang.".yml";
            $yaml = file_get_contents($langFile);
            $langYaml = sfYaml::load($yaml);
            $langArray = $langYaml[$currentLang];
			$this->content = $langArray;
	}


	function translate($name) {
		if (isset($this->content[$name])) {
			return $this->content[$name];
		} else {
			return "str: {$name}";
		}
	}
}
