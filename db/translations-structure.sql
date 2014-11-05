CREATE TABLE Locales (
  lID varchar(12) NOT NULL COMMENT 'Locale identifier',
  lName varchar(100) NOT NULL COMMENT 'Locale name',
  lIsSource tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '1 for en_US (source language)',
  lPluralCount tinyint(1) unsigned NOT NULL COMMENT 'Number of plural forms',
  lPluralRule varchar(255) NOT NULL COMMENT 'Plural rules',
  PRIMARY KEY (lID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Available languages for localization';

INSERT INTO Locales (lID, lName, lIsSource, lPluralCount, lPluralRule) VALUES
	('ar', 'Arabic', 0, 6, 'n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5'),
	('ast_ES', 'Asturian (Spain)', 0, 2, '(n != 1)'),
	('bg_BG', 'Bulgarian (Bulgaria)', 0, 2, '(n != 1)'),
	('bs_BA', 'Bosnian (Bosnia and Herzegovina)', 0, 3, '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'),
	('ca', 'Catalan', 0, 2, '(n != 1)'),
	('cs_CZ', 'Czech (Czech Republic)', 0, 3, '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'),
	('da_DK', 'Danish (Denmark)', 0, 2, '(n != 1)'),
	('de_DE', 'German (Germany)', 0, 2, '(n != 1)'),
	('el_GR', 'Greek (Greece)', 0, 2, '(n != 1)'),
	('en_GB', 'English (United Kingdom)', 0, 2, '(n != 1)'),
	('en_US', 'English (United States)', 1, 2, '(n != 1)'),
	('es_AR', 'Spanish (Argentina)', 0, 2, '(n != 1)'),
	('es_ES', 'Spanish (Spain)', 0, 2, '(n != 1)'),
	('es_MX', 'Spanish (Mexico)', 0, 2, '(n != 1)'),
	('es_PE', 'Spanish (Peru)', 0, 2, '(n != 1)'),
	('et_EE', 'Estonian (Estonia)', 0, 2, '(n != 1)'),
	('fa_IR', 'Persian (Iran)', 0, 1, '0'),
	('fi_FI', 'Finnish (Finland)', 0, 2, '(n != 1)'),
	('fr_FR', 'French (France)', 0, 2, '(n > 1)'),
	('he_IL', 'Hebrew (Israel)', 0, 2, '(n != 1)'),
	('hi_IN', 'Hindi (India)', 0, 2, '(n != 1)'),
	('hr_HR', 'Croatian (Croatia)', 0, 3, 'n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2'),
	('hu_HU', 'Hungarian (Hungary)', 0, 2, '(n != 1)'),
	('id_ID', 'Indonesian (Indonesia)', 0, 1, '0'),
	('it_IT', 'Italian (Italy)', 0, 2, '(n != 1)'),
	('ja_JP', 'Japanese (Japan)', 0, 1, '0'),
	('km_KH', 'Khmer (Cambodia)', 0, 1, '0'),
	('ko_KR', 'Korean (Korea)', 0, 1, '0'),
	('ku', 'Kurdish', 0, 2, '(n != 1)'),
	('lt_LT', 'Lithuanian (Lithuania)', 0, 3, '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2)'),
	('lv_LV', 'Latvian (Latvia)', 0, 3, '(n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)'),
	('mk_MK', 'Macedonian (Macedonia)', 0, 2, '(n % 10 == 1 && n % 100 != 11) ? 0 : 1'),
	('ml_IN', 'Malayalam (India)', 0, 2, '(n != 1)'),
	('my_MM', 'Burmese (Myanmar)', 0, 1, '0'),
	('nb_NO', 'Norwegian Bokmål (Norway)', 0, 2, '(n != 1)'),
	('nl_NL', 'Dutch (Netherlands)', 0, 2, '(n != 1)'),
	('pl_PL', 'Polish (Poland)', 0, 3, '(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'),
	('pt_BR', 'Portuguese (Brazil)', 0, 2, '(n > 1)'),
	('pt_PT', 'Portuguese (Portugal)', 0, 2, '(n != 1)'),
	('ro_RO', 'Romanian (Romania)', 0, 3, '(n==1?0:(((n%100>19)||((n%100==0)&&(n!=0)))?2:1))'),
	('ru_RU', 'Russian (Russia)', 0, 3, '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'),
	('sk_SK', 'Slovak (Slovakia)', 0, 3, '(n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2'),
	('sl_SI', 'Slovenian (Slovenia)', 0, 4, '(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3)'),
	('sr_RS', 'Serbian (Serbia)', 0, 3, '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'),
	('sv_SE', 'Swedish (Sweden)', 0, 2, '(n != 1)'),
	('ta_IN', 'Tamil (India)', 0, 2, '(n != 1)'),
	('th_TH', 'Thai (Thailand)', 0, 1, '0'),
	('tr_TR', 'Turkish (Turkey)', 0, 1, '0'),
	('uk_UA', 'Ukrainian (Ukraine)', 0, 3, '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'),
	('vi_VN', 'Vietnamese (Viet Nam)', 0, 1, '0'),
	('zh_CN', 'Chinese (China)', 0, 1, '0'),
	('zh_TW', 'Chinese (Taiwan)', 0, 1, '0');


CREATE TABLE Translatables (
  tID int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Translatable identifier',
  tHash char(32) NOT NULL COMMENT 'Translation hash',
  tContext varchar(80) NULL COMMENT 'Translation context',
  tText text NOT NULL COMMENT 'Translatable string',
  tPlural text NULL COMMENT 'Translatable plural',
  PRIMARY KEY (tID),
  UNIQUE KEY tHash (tHash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='List of translatable strings';

SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER Translatables_before_insert BEFORE INSERT ON Translatables FOR EACH ROW IF (NEW.tContext IS NULL) OR (LENGTH(NEW.tContext) = 0) THEN
	SET NEW.tHash = MD5(NEW.tText);
ELSE
	SET NEW.tHash = MD5(CONCAT(NEW.tContext, CHAR(4), NEW.tText));
END IF//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='';
DELIMITER //
CREATE TRIGGER Translatables_before_update BEFORE UPDATE ON Translatables FOR EACH ROW IF (NEW.tContext IS NULL) OR (LENGTH(NEW.tContext) = 0) THEN
	SET NEW.tHash = MD5(NEW.tText);
ELSE
	SET NEW.tHash = MD5(CONCAT(NEW.tContext, CHAR(4), NEW.tText));
END IF//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

INSERT INTO Translatables (tID, tContext, tText, tPlural) VALUES
	(1, '', 'OK', ''),
	(2, '', 'Cancel', ''),
	(3, 'Default locale', '** Default', ''),
	(4, '', '%d page', '%d pages');


CREATE TABLE TranslatablePlaces (
  tpID int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Translatable place identifier',
  tpTranslatable int unsigned NOT NULL COMMENT 'Translatable identifier',
  tpPackage varchar(64) NOT NULL COMMENT 'Package handle or ''-'' for core',
  tpVersion varchar(32) NOT NULL COMMENT 'Package/code version (''dev-'' for GitHub versions)',
  tpFile varchar(300) NOT NULL COMMENT 'Path to the file where the translatable string is defined',
  tpLine int unsigned NULL COMMENT 'Line of the file (may be null in case the translatable string is the file name itself, like for custom block templates)',
  tpComment text NULL COMMENT 'Comment for the translation',
  PRIMARY KEY (tpID),
  KEY tpTranslatable_tpPackage_tpVersion (tpTranslatable,tpPackage,tpVersion),
  CONSTRAINT FK_TranslatablePlaces_Translatables FOREIGN KEY (tpTranslatable) REFERENCES Translatables (tID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Places where translatables are defined';

CREATE TABLE Translations (
  tLocale varchar(12) NOT NULL COMMENT 'Locale identifier',
  tTranslatable int unsigned NOT NULL COMMENT 'Translatable identifier',
  tFuzzy tinyint(1) unsigned NOT NULL COMMENT 'Fuzzy (1), approved (0)',
  tText0 text NOT NULL COMMENT 'Translation (singular / plural 0)',
  tText1 text NULL COMMENT 'Translation (plural 1)',
  tText2 text NULL COMMENT 'Translation (plural 2)',
  tText3 text NULL COMMENT 'Translation (plural 3)',
  tText4 text NULL COMMENT 'Translation (plural 4)',
  tText5 text NULL COMMENT 'Translation (plural 5)',
  PRIMARY KEY (tLocale,tTranslatable),
  KEY FK_Translations_Translatables (tTranslatable),
  CONSTRAINT FK_Translations_Translatables FOREIGN KEY (tTranslatable) REFERENCES Translatables (tID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT FK_Translations_Locales FOREIGN KEY (tLocale) REFERENCES Locales (lID) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Translated strings';
