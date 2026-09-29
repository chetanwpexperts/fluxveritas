# OutraqHQ — Project Skills

All slash commands available in this project.
Command files live in `.claude/commands/`. Add a new `.md` file there to create a new command.

---

## Dev Workflow

| Command | Usage | What it does |
|---------|-------|-------------|
| `/clear` | `/clear` | Clears config + cache + view + route + event caches |
| `/migrate` | `/migrate` | Runs pending migrations and reports what ran |
| `/make-migration` | `/make-migration add_phone_to_users` | Creates a migration file and helps you fill it |
| `/logs` | `/logs` or `/logs billing` | Shows recent Laravel log entries, errors first |
| `/routes` | `/routes` or `/routes billing` | Lists all routes grouped by area |

## Database & State Inspection

| Command | Usage | What it does |
|---------|-------|-------------|
| `/billing-check` | `/billing-check` | Shows all orgs with plan/status/expiry, flags issues |
| `/module-check` | `/module-check` or `/module-check blockers` | Reads ModuleService, checks plan mapping consistency |
| `/org-check` | `/org-check HCL` | Full org snapshot — billing, users, roles |
| `/permissions-check` | `/permissions-check` | Lists Spatie roles/permissions, flags users with no role |

## Fixes & Repairs

| Command | Usage | What it does |
|---------|-------|-------------|
| `/session-fix` | `/session-fix` | Clears stale DB sessions + throttle cache (fixes login loops) |
| `/fix-org` | `/fix-org "HCL Technologies" pro active` | Repairs an org's billing data directly in DB |

## Quality & Launch

| Command | Usage | What it does |
|---------|-------|-------------|
| `/pr` | `/pr` or `/pr 42` or `/pr main` | Review current diff or a specific PR against OutraqHQ standards |
| `/audit-copy` | `/audit-copy` | Scans landing/tour/docs/pricing for flagged phrases, USD prices, fake names |

## Feature Development

| Command | Usage | What it does |
|---------|-------|-------------|
| `/feature` | `/feature PayslipController - generate monthly payslips` | Full scaffold: controller + routes + view + migration |

---

## Rules

- Run `/clear` after every config, view, or route change
- Run `/audit-copy` before every demo or launch
- Run `/session-fix` if login loops or 429 errors occur
- When adding a new module or workflow: add a skill here + update `CLAUDE.md`
