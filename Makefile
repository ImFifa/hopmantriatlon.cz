all:
	@awk 'BEGIN {FS = ":.*##"; printf "Usage:\n  make \033[36m<target>\033[0m\n\nTargets:\n"}'
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# QA

cs: ## Check PHP files coding style
	mkdir -p var/build-tools/PHP_CodeSniffer
	"vendor/bin/phpcs" app --standard=build/phpcs.xml $(ARGS)

csf: ## Fix PHP files coding style
	mkdir -p var/build-tools/PHP_CodeSniffer
	"vendor/bin/phpcbf" app --standard=build/phpcs.xml $(ARGS)

phpstan: ## Analyse code with PHPStan
	mkdir -p var/build-tools
	"vendor/bin/phpstan" analyse app -c build/phpstan.src.neon $(ARGS)

