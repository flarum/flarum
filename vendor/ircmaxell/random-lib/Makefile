

.PHONY: all
all: build

.PHONY: build
build: lint cs test

lintfiles := $(shell find lib test -type f -iname '*.php')

.PHONY: ${lintfiles}
${lintfiles}:
	php -l $@

.PHONY: lint
lint: $(lintfiles)

.PHONY: cs
cs:
	vendor/bin/php-cs-fixer --quiet --no-interaction fix; true


.PHONY: test
test:
	vendor/bin/phpunit
