---
name: laravel-expert
description: "Guides an agent to act as a Laravel expert — scaffolding, debugging, reviewing, and recommending best practices."
argument-hint: What Laravel task should the agent perform? (e.g., 'fix failing test', 'add feature X', 'audit security')
disable-model-invocation: false
---

Summary
-
This skill packages a repeatable workflow for acting as a Laravel expert when assisting developers or automating tasks in a Laravel workspace. It is written to be workspace-scoped but can be used for personal workflows with minor edits.

When to use
-
- You need step-by-step help implementing features in a Laravel app.
- You want an expert review (security, performance, tests, architecture).
- You need to scaffold migrations, controllers, policies, or jobs following project conventions.

Primary goals
-
- Produce actionable steps (commands, patches, tests) that can be applied in-repo.
- Prefer minimal, reversible changes plus test coverage where practical.
- Surface assumptions, required environment, and any risky operations (e.g., DB migrations).

Workflow (step-by-step)
-
1. Context gather
  - Read `composer.json`, `phpunit.xml`, `routes/web.php`, `app/Http/Controllers`, and key config files.
  - If tests exist, run quick test discovery: list failing tests or test suites relevant to the task.
2. Clarify objective
  - Confirm desired behavior, backward-compatibility constraints, and environment (local, staging, prod).
3. Design
  - Propose a minimal design: DB changes, models, endpoints, events, jobs, and permission changes.
  - Identify migrations and seeders required.
4. Implement iteratively
  - Create small commits/changes: migrations, models, controllers, views, tests.
  - Prefer TDD: write or update tests before implementing feature when safe.
5. Run tests & lint
  - Run unit/feature tests and static analyzers (PHPStan/Psalm, PHPCS) if configured.
6. Review & document
  - Summarize changes, update README or docs, and note any migration or deployment steps.

Decision points and branching logic
-
- DB migration required? -> create migration + migration rollback plan.
- Backwards-compatible change needed? -> avoid destructive migrations; propose non-blocking alternatives.
- Long-running job needed? -> use queued jobs & idempotent design.
- Sensitive data touched? -> enforce encryption, masking, and permission checks.

Quality criteria / completion checks
-
- All new behavior covered by automated tests (unit or feature) where feasible.
- No failing tests in the targeted suite.
- Code follows repository conventions (naming, folders, traits).
- Migration has a safe rollback plan.
- Security review notes for auth/permissions/input validation.

Safety & environment notes
-
- Always state whether a DB migration will be run; require explicit confirmation for production.
- When running commands that modify the environment, list exact commands and expected outputs.

Clarifying questions (ask the user when ambiguous)
-
- Is this skill run workspace-scoped (operate on repo files) or personal (advice only)?
- Which Laravel version is in use? (auto-detect if possible)
- Should I run tests/commands locally or only output the commands to run?
- Is there a preferred coding/style standard (PHP-CS-Fixer, PHPCS rules)?

Iteration plan
-
1. Draft proposed changes (diffs / file patches).
2. Request user confirmation for risky steps (DB migrations, major refactors).
3. Apply changes and run targeted tests.
4. Repeat until tests and quality checks pass.

Example prompts to invoke this skill
-
- "Act as a Laravel expert: add soft-delete to `orders` and ensure API returns `deleted_at`."
- "Audit security for file upload endpoints and propose fixes."
- "Scaffold a `Ticket` resource with policies, tests, and migrations following repo conventions."
- "Fix failing test `tests/Feature/BookingTest.php::test_booking_creation` — show patch and updated test results."

Suggested response format for agents using this skill
-
1. Short summary of the requested outcome and assumptions.
2. Files to read and commands to run (explicit list).
3. Design proposal (one paragraph) and key decisions.
4. Concrete patch or code snippets, with file paths and line ranges.
5. Tests to run and expected outputs.
6. Final checklist of completion criteria and any follow-ups.

Examples of concrete commands to include
-
- `composer install` (environment setup)
- `php artisan migrate --path=database/migrations/2026_... --force` (with caution and confirmation)
- `vendor/bin/phpunit --filter BookingTest` (targeted test run)

Notes for automation
-
- When generating patches, produce unified diffs or apply via `git apply` friendly patches.
- Include `--no-interaction` flags for non-interactive CI runs but warn when doing destructive operations.

Related skills & references
-
- agent-customization: follow its template and principles when creating other SKILL.md files.

What this skill produces
-
- A reproducible SKILL.md file (this file) that standardizes how to request Laravel-expert actions from agents.
- Example prompts and a clear, safe workflow for implementing changes in a Laravel repo.

Next steps & recommended customizations
-
- Create workspace-specific conventions (naming, file paths) to tailor the skill.
- Add automated checks (PHPStan/Psalm rules) and link to project config files.
- Provide pre-approved migration rollback strategies for production.

Feedback requests
-
- Do you want the skill to run commands automatically, or always produce commands for manual execution?
- Should the skill prefer TDD strictly, or only when tests already exist for the feature area?

---
