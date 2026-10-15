# AGENTS.md

Guidelines for AI agents working in this repository.

## Project

PHP library for checking PHP code syntax. Namespace: `Syntax\`.

## Commands

- Build: `composer install`
- Test: `vendor/bin/phpunit`
- Lint: `vendor/bin/php-cs-fixer fix --dry-run`

## Conventions

- PHP >= 8.2
- PSR-4 autoloading: `Syntax\` -> `src/`, `Syntax\Tests\` -> `tests/`
- Code style is enforced by PHP-CS-Fixer (`php-cs-fixer.dist.php`)
- Follow existing code style
- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/)
