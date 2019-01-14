PHPUNIT_BIN = ./vendor/bin/phpunit
PHPCS_BIN = ./vendor/bin/phpcs
PHPSTAN_BIN = ./vendor/bin/phpstan
PHPMD_BIN = ./vendor/bin/phpmd
SOURCE_FOLDERS = Controller DependencyInjection Exception ParamConverter Resources Subscriber
.PHONY: test

analysis:
	php -l .
	$(PHPCS_BIN) --standard=PSR2 $(SOURCE_FOLDERS)
	$(PHPSTAN_BIN) analyse $(SOURCE_FOLDERS) --level=7
	$(foreach FOLDER, $(SOURCE_FOLDERS), $(shell $(PHPMD_BIN) $(FOLDER) text cleancode,codesize,controversial,design,naming,unusedcode))

test:
	$(PHPUNIT_BIN)
