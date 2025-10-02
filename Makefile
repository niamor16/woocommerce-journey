DOCKER_PHP = wp-env run cli
PLUGINS_DIR = /var/www/html/wp-content/plugins

start:
	npm run start

restart: 
	npm run restart
	
stop: 
	npm run stop
	
clean: 
	npm run clean
	
cli: 
	npm run cli

i18n:
	@if [ -z "$(filter-out $@,$(MAKECMDGOALS))" ]; then \
		echo "Usage: make i18n <plugin>"; \
		exit 1; \
	fi
	@plugin=$(filter-out $@,$(MAKECMDGOALS)); \
	echo "[INFO] Génération du .pot pour $$plugin"; \
	$(DOCKER_PHP) wp i18n make-pot \
		$(PLUGINS_DIR)/$$plugin \
		$(PLUGINS_DIR)/$$plugin/languages/$$plugin.pot \
		--exclude=vendor,node_modules,tests

# Astuce pour ne pas traiter l'argument comme une vraie cible
%:
	@: