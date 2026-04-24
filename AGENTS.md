# AGENTS.md

## Project Overview

Minimal PHP framework - early stage, no dependencies yet.

## Structure

- `index.php` - Application entry point
- `router.php` - Router class (stub)

## Running

Serve with PHP's built-in server:
```bash
php -S localhost:8000
```

## Notes

- No composer.json yet - pure PHP, no external dependencies
- No tests, linting, or CI configured
- Router class is currently a stub with only doc comments
- **Skills**: Install skills in project (`.agents/skills/`), not globally
- **Agent folders** (`.claude/`, `.windsurf/`): Do NOT create unless user explicitly requests them