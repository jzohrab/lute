# Makefile for Python tasks.

# Run tests
test: lint
	python -m pytest py_tests/ $(ARGS)

# Run tests and show print() messages
testprint:
	python -m pytest -s py_tests/

lint:
	pylint py_src/ py_tests/

### all: down build up test
### 
### build:
### 	docker-compose build
### 
### up:
### 	docker-compose up -d app
### 
### down:
### 	docker-compose down --remove-orphans
### 
### test: up
### 	docker-compose run --rm --no-deps --entrypoint=pytest app /tests/unit /tests/integration /tests/e2e
### 
### unit-tests:
### 	docker-compose run --rm --no-deps --entrypoint=pytest app /tests/unit
### 
### integration-tests: up
### 	docker-compose run --rm --no-deps --entrypoint=pytest app /tests/integration
### 
### e2e-tests: up
### 	docker-compose run --rm --no-deps --entrypoint=pytest app /tests/e2e
### 
### logs:
### 	docker-compose logs app | tail -100

