SHELL := /bin/bash

.PHONY: setup up down migrate seed scout install

setup:
	@echo "Установка пороекта"
	./start.sh

up:
	./vendor/bin/sail up

down:
	./vendor/bin/sail down

migrate:
	@echo "Выполняем миграции"
	./vendor/bin/sail artisan migrate:refresh

bash:
	./vendor/bin/sail bash

seed:
	@echo "Выполняем сидеры"
	./vendor/bin/sail artisan db:seed CategorySeeder
	./vendor/bin/sail artisan db:seed ProductSeeder

scout:
	@echo "Индексация таблицы products через Scout"
	./vendor/bin/sail artisan app:meilisearch-init
	./vendor/bin/sail artisan scout:import "App\\Models\\Product" --chunk=10000

init: migrate seed scout
	@echo "✅"
