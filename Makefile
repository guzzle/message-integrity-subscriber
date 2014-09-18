all: clean coverage

test:
	vendor/bin/phpunit $(TEST)

coverage:
	vendor/bin/phpunit --coverage-html=build/artifacts/coverage $(TEST)

view-coverage:
	open build/artifacts/coverage/index.html

clean:
	rm -rf build/artifacts/*

.PHONY: coverage
