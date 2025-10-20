SHELL := bash
.SHELLFLAGS := -eu -o pipefail -c
MAKEFLAGS += --warn-undefined-variables
DOCKER_COMPOSE := docker compose

include .env

help:
	@echo -e "\033[0;32m Usage: make [target] "
	@echo
	@echo -e "\033[1m targets:\033[0m"
	@egrep '^(.+):*\ ##\ (.+)' ${MAKEFILE_LIST} | column -t -c 2 -s ':#'
.PHONY: help

<---docker------->: ## -----------------------------------------------------------------------
start: ## Start all database containers and wait until healthy
	@echo "Starting database containers..."
	@$(DOCKER_COMPOSE) --profile test up -d mysql mariadb postgres
	@echo "Waiting for databases to be healthy..."
	@for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do \
		HEALTHY_COUNT=0; \
		if $(DOCKER_COMPOSE) ps --format json 2>/dev/null | grep -q '"Health":"healthy"'; then \
			HEALTHY_COUNT=$$($(DOCKER_COMPOSE) ps --format json 2>/dev/null | grep -c '"Health":"healthy"' || echo "0"); \
		elif $(DOCKER_COMPOSE) ps 2>/dev/null | grep -q "(healthy)"; then \
			HEALTHY_COUNT=$$($(DOCKER_COMPOSE) ps 2>/dev/null | grep -c "(healthy)" || echo "0"); \
		fi; \
		if [ "$$HEALTHY_COUNT" -ge 3 ]; then \
			echo "✓ All databases are healthy!"; \
			exit 0; \
		fi; \
		echo "  Waiting... ($$i/20) - $$HEALTHY_COUNT/3 databases healthy"; \
		sleep 3; \
	done; \
	echo "✗ Timeout: Not all databases became healthy in time"; \
	echo "Container status:"; \
	$(DOCKER_COMPOSE) ps; \
	echo "Container logs:"; \
	$(DOCKER_COMPOSE) logs --tail=50; \
	exit 1
.PHONY: start

stop: ## Stop and remove all containers
	@echo "Stopping and removing all containers..."
	@$(DOCKER_COMPOSE) --profile test down --remove-orphans
	@echo "All containers stopped and removed."
.PHONY: stop

restart: stop start ## Restart all containers
.PHONY: restart

status: ## Show status of all containers
	@echo "Container status:"
	@$(DOCKER_COMPOSE) ps -a
.PHONY: status

<---composer----->: ## -----------------------------------------------------------------------
install: ## Run composer install (requires: make start)
	$(DOCKER_COMPOSE) run --rm --no-deps phpcli composer install --no-cache
.PHONY: install

update: ## Run composer update (requires: make start)
	$(DOCKER_COMPOSE) run --rm --no-deps -e XDEBUG_MODE=off phpcli composer update
.PHONY: update

autoload: ## Run composer dump-autoload (requires: make start)
	$(DOCKER_COMPOSE) run --rm --no-deps phpcli composer dumpautoload
.PHONY: autoload

<---qa tools----->: ## -----------------------------------------------------------------------
phpunit: ## Run all tests (requires: make start)
	$(DOCKER_COMPOSE) run --rm phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests
.PHONY: phpunit

phpunit-reports: ## Run all tests with reports (requires: make start)
	$(DOCKER_COMPOSE) run --rm -e XDEBUG_MODE=coverage phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests --coverage-clover tests/reports/clover.xml --coverage-xml tests/reports/coverage-xml
.PHONY: phpunit-reports

phpunit-coverage: ## Run all tests with coverage text (requires: make start)
	$(DOCKER_COMPOSE) run --rm -e XDEBUG_MODE=coverage phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests --coverage-text
.PHONY: phpunit-coverage

phpunit-coverage-html: ## Run all tests with HTML coverage (requires: make start)
	$(DOCKER_COMPOSE) run --rm -e XDEBUG_MODE=coverage phpcli vendor/bin/phpunit --bootstrap ./tests/bootstrap.php /app/tests --coverage-html tests/reports/coverage-html
.PHONY: phpunit-coverage-html

phpstan: ## Run PHPStan analysis (requires: make start)
	$(DOCKER_COMPOSE) run --rm phpcli vendor/bin/phpstan analyse /app/src -c phpstan.neon
.PHONY: phpstan

phpcs: ## Run coding standards (requires: make start)
	$(DOCKER_COMPOSE) run --rm phpcli vendor/bin/phpcs /app/src
.PHONY: phpcs

<---development----->: ## -----------------------------------------------------------------------
shell: ## Run a shell inside the phpcli container (requires: make start)
	$(DOCKER_COMPOSE) run --rm -it phpcli sh
.PHONY: shell

logs: ## Show logs from all containers
	$(DOCKER_COMPOSE) logs -f
.PHONY: logs

<---cleanup----->: ## -----------------------------------------------------------------------
clean: ## Stop containers and clean up volumes
	@echo "Cleaning up containers and volumes..."
	@$(DOCKER_COMPOSE) down -v --remove-orphans
	@echo "Cleanup complete."
.PHONY: clean

remove: ## Stops and removes containers, images, network and caches
	@echo "Removing all Docker resources..."
	@$(DOCKER_COMPOSE) down --volumes --remove-orphans --rmi "all"
	@docker images --filter dangling=true -q 2>/dev/null | xargs -r docker rmi 2>/dev/null || true
	@echo "Complete removal done."
.PHONY: remove

<---ssh -------->: ## -----------------------------------------------------------------------
ssh-agent: ## Get SSH agent ready
	eval `ssh-agent -s`
	ssh-add
.PHONY: ssh-agent
