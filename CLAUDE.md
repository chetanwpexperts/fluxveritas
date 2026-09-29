# OutraqHQ — FluxVeritas CLAUDE.md

Project-level context for Claude Code. Loaded automatically every session.

## Project

- **Name:** OutraqHQ / FluxVeritas
- **Stack:** Laravel 12, PHP 8.2, MySQL, Blade, Spatie Laravel-Permission
- **URL:** http://localhost/fluxveritas/public (XAMPP, macOS)
- **Path:** /Applications/XAMPP/xamppfiles/htdocs/fluxveritas

## Plans & Modules

Three tiers — names must be consistent everywhere (DB, views, copy):

| Plan | Price | Key modules |
|------|-------|-------------|
| `free` | ₹0, up to 10 people (incl. pending invites) | GitHub Sync, Employee Directory, Leave Management, Document Center, Onboarding, Announcements, Work Log, Tasks |
| `pro` | ₹199/user/mo monthly or ₹149/user/mo yearly, min 5 users, + GST | + Fairness Engine, AI Intelligence, Increment Calculator, Peer Feedback, Reports, HR Reports, Blockers |
| `enterprise` | Custom, from ₹349/user/mo, 50+ users, yearly | + Command Center, Audit Logs, Priority Support (API/SSO not built yet) |

- Prices, GST, refund window, free user limit: `config/plans.php` — never hardcode prices in views
- Module lists per plan: `app/Services/ModuleService.php` (`$planModules` + `$modulesMeta`)
- All billing logic: `app/Services/BillingService.php` (quote, order, markPaid, refund, downgrade, free seat limit)
- Plans are prepaid, no auto-renew. Renewal starts when the current period ends.
- Refund: full, within 7 days of a payment, from Billing page; removes that paid period.
- Downgrade: "Switch to Free" keeps Pro until `plan_expires_at` (`downgrade_scheduled_at` set).
- Use `$org->effectivePlan()` (treats an expired paid plan as free), not `$org->plan`, for access checks.
- Org admins can't enable modules outside their plan; only a super_admin grant unlocks them.
- Razorpay webhook: `POST /billing/webhook` (CSRF-exempt, signature-verified) — set it up in the Razorpay dashboard for payment.captured, order.paid, payment.failed, refund.processed, refund.failed.
- Payment: Razorpay, INR only — never USD

## Role Hierarchy

```
super_admin → owner → admin → manager → team_lead → hr → employee
```

- `super_admin` — internal/platform only. Never expose on public pages (tour, landing, docs).
- `org_creator` — onboarding_type for users setting up a new org (not a Spatie role)
- Role assignment via Spatie. Scope: `manageableUserIds()` on User model for manager chains.

## Key Middleware (global web stack)

| Middleware | Purpose |
|---|---|
| `EnsureOrganizationAccess` | Blocks users with no org; redirects org_creators to organization.create |
| `SessionTimeout` | 2hr timeout, flushes session |
| `CheckOnboardingStatus` | Routes pending/rejected/incomplete users |
| `CheckModule` | Shows `billing/upgrade-required` view (403) for locked modules |
| `SecurityHeaders` | Single CSP source of truth — do not add meta CSP tags |

## Layouts

| Layout | Used by |
|---|---|
| `layouts/app` | All authenticated app pages (has sidebar) |
| `layouts/public` | Public marketing pages: landing, pricing, tour, docs, contact |

## Copy Rules (pre-launch polish)

- Currency: INR (₹) only. No USD ($).
- Plan names: Free / Pro / Enterprise. Never "Starter", "Growth".
- No fake personal names in demo data (Sarah, Alex, Rahul, Marcus, Priya, Neha).
- No absolutist claims: "24/7", "runs every hour", "unfiltered truth", "AI doesn't lie", "No politics", "watches everyone".
- AI agent copy: capability-based only — "analyzes", "surfaces", "generates digest reports". Not delivery guarantees.
- No fake testimonials.
- Encryption: "Secure by design / HTTPS" only. Not "AES-256 at rest" (unverified).
- Run `/audit-copy` before any launch or demo to catch regressions.

## DB / Sessions

- Session driver: `database` (table: `sessions`)
- Cache driver: `database`
- If login loops occur: run `/session-fix` (clears sessions + throttle cache)

## After Every Change

Always run:
```
php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear
```

Or just use: `/clear`

## Project Skills (slash commands)

All skills live in `.claude/commands/`. Add new `.md` files there as the project grows.

| Command | When to use |
|---|---|
| `/clear` | After any config, view, or route change |
| `/migrate` | After creating a migration |
| `/billing-check` | Inspect all org billing state |
| `/module-check` | Verify ModuleService plan mapping |
| `/org-check [name]` | Full org snapshot — users, roles, billing |
| `/routes [filter]` | List routes by area |
| `/logs [keyword]` | Recent Laravel log entries |
| `/audit-copy` | Scan public views for flagged copy before launch/demo |
| `/make-migration [name]` | Scaffold + edit a new migration |
| `/permissions-check` | List Spatie roles/permissions, flag users with no role |
| `/session-fix` | Fix login loops — clears stale DB sessions + throttle |
| `/feature [description]` | Full feature scaffold (controller + routes + view + migration) |
| `/fix-org [name] [plan] [status]` | Repair org billing data in DB |

**Keep skills updated:** whenever a new module, middleware, or workflow pattern is added, update the relevant skill or add a new one.

## Completed Modules (as of June 2026)

Work Log, Tasks/Sprints, GitHub Sync, Employee Directory, Leave Management,
Document Center, Onboarding Checklists, Announcements, HR Reports,
Fairness Engine, AI Intelligence, Increment Calculator, Reports,
Blockers & Dependencies, Billing (Razorpay), Module Gating, Employee Import,
Bulk Invite, Team Management, Org Chart, Peer Feedback, Help Agent,
Contact Form, Super Admin Panel, Command Center (Enterprise).

## Pending / In Progress

- Public layout (layouts/public) — switching all public pages to it
- Upgrade gate page (billing/upgrade-required) — done
- Contact messages admin view — not yet built
- Mobile app — roadmap item
