--
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT=0;

START TRANSACTION;
USE {DATABASE};

-- BEGIN: Upgrade database structure:
UPDATE `config` SET `value` = '2' WHERE `name` = 'DATABASE_REVISION' LIMIT 1;
-- END: Upgrade database structure

-- Change charset:
ALTER DATABASE `ispcp` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

COMMIT;
