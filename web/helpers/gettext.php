<?php
class GettextHelper
{
	public function getPluralRule($locale) {
		static $cache;
		if(!is_array($cache)) {
		    $cache = array();
		}
		if(!array_key_exists($locale, $cache)) {
			$fh = Loader::helper('file');
			/* @var $fh FileHelper */
			try {
				$tempFilePOT = @tempnam($fh->getTemporaryDirectory(), 'cil');
				if($tempFilePOT === false) {
					throw new Exception(t('Unable to create a temporary file'));
				}
				if(@file_put_contents($tempFilePOT, <<<EOT
msgid ""
msgstr ""
"Project-Id-Version: \\n"
"Report-Msgid-Bugs-To: \\n"
"POT-Creation-Date: 2010-01-01 00:00+0000\\n"
"PO-Revision-Date: 2010-01-01 00:00+0000\\n"
"Last-Translator: \\n"
"Language-Team: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
EOT
				) === false) {
					throw new Exception(t('Unable to write to a temporary file'));
				}
				$tempFilePO = @tempnam($fh->getTemporaryDirectory(), 'cil');
				if($tempFilePO === false) {
					throw new Exception(t('Unable to create a temporary file'));
				}
				@exec('msginit --input=' . escapeshellarg($tempFilePOT) . ' --output-file=' . escapeshellarg($tempFilePO) . ' --locale=' . escapeshellarg($locale) . ' --no-translator --no-wrap 2>&1', $output, $rc);
				@unlink($tempFilePOT);
				if($rc !== 0) {
				    throw new Exception('msginit failed: ' . implode(PHP_EOL, $output));
				}
				Loader::library('3rdparty/autoload', 'integrated_localization');
				$translations = \Gettext\Extractors\Po::fromFile($tempFilePO);
				@unlink($tempFilePO);
				$pluralRule = $translations->getHeader('Plural-Forms');
				if(!(is_string($pluralRule) && strlen($pluralRule))) {
				    throw new Exception(t('Unrecognized locale: %s', $locale));
				}
				$cache[$locale] = $pluralRule;
			}
			catch(Exception $x) {
				if(isset($tempFilePO) && is_file($tempFilePO)) {
					@unlink($tempFilePO);
				}
				if(isset($tempFilePOT) && is_file($tempFilePOT)) {
					@unlink($tempFilePOT);
				}
				throw $x;
			}
		}
		return $cache[$locale];
	}
}