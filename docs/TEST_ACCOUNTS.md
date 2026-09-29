# Test accounts (demo data)

Demo organizations for QA. They are created by `DemoDataSeeder`, flagged `is_demo = true`, and can be removed at any time with `php artisan demo:purge`.

> **Security:** every account below shares one published password. Run `php artisan demo:purge` when QA is finished, and never add real people or real data to a demo organization.

## Create / reset / remove

```bash
php artisan db:seed --class=DemoDataSeeder --force   # create, or reset to fresh data (same logins)
php artisan demo:purge                                # delete all demo orgs and everything in them (asks first)
```

Re-running the seeder is safe: logins are kept and everything inside the demo organizations is rebuilt with dates relative to today. It refuses to run if a demo slug or email belongs to a real organization.

## Organizations

| Organization | Slug | Plan | Status | People | Use it to test |
|---|---|---|---|---|---|
| Demo Startup | `demo-startup` | Free | Active | 8 + 1 pending sign-up | Free plan limits, locked Pro features, core HR |
| Demo Agency | `demo-agency` | Pro (monthly, 20 seats, active) | Active | 20 + 2 pending sign-ups | Everything, including Pro features and billing history |
| Demo Suspended Co | `demo-suspended-co` | Free | **Suspended** | 6 | The suspension block |

Demo Suspended Co has 6 people rather than 5, because every organization needs one login per role (6 roles).

## Accounts

Password for every account: **`Test@12345`**

| Organization | Role | Email | Password |
|---|---|---|---|
| Demo Startup | owner | `owner@demo-startup.test` | `Test@12345` |
| Demo Startup | admin | `admin@demo-startup.test` | `Test@12345` |
| Demo Startup | hr | `hr@demo-startup.test` | `Test@12345` |
| Demo Startup | team_lead | `team_lead@demo-startup.test` | `Test@12345` |
| Demo Startup | employee | `employee@demo-startup.test` | `Test@12345` |
| Demo Startup | viewer | `viewer@demo-startup.test` | `Test@12345` |
| Demo Startup | employee | `employee2@demo-startup.test` | `Test@12345` |
| Demo Startup | employee | `employee3@demo-startup.test` | `Test@12345` |
| Demo Startup | viewer (pending approval) | `pending1@demo-startup.test` | `Test@12345` |
| Demo Agency | owner | `owner@demo-agency.test` | `Test@12345` |
| Demo Agency | admin | `admin@demo-agency.test` | `Test@12345` |
| Demo Agency | hr | `hr@demo-agency.test` | `Test@12345` |
| Demo Agency | team_lead | `team_lead@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee@demo-agency.test` | `Test@12345` |
| Demo Agency | viewer | `viewer@demo-agency.test` | `Test@12345` |
| Demo Agency | team_lead | `team_lead2@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee2@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee3@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee4@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee5@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee6@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee7@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee8@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee9@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee10@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee11@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee12@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee13@demo-agency.test` | `Test@12345` |
| Demo Agency | employee | `employee14@demo-agency.test` | `Test@12345` |
| Demo Agency | viewer (pending approval) | `pending1@demo-agency.test` | `Test@12345` |
| Demo Agency | viewer (pending approval) | `pending2@demo-agency.test` | `Test@12345` |
| Demo Suspended Co | owner | `owner@demo-suspended-co.test` | `Test@12345` |
| Demo Suspended Co | admin | `admin@demo-suspended-co.test` | `Test@12345` |
| Demo Suspended Co | hr | `hr@demo-suspended-co.test` | `Test@12345` |
| Demo Suspended Co | team_lead | `team_lead@demo-suspended-co.test` | `Test@12345` |
| Demo Suspended Co | employee | `employee@demo-suspended-co.test` | `Test@12345` |
| Demo Suspended Co | viewer | `viewer@demo-suspended-co.test` | `Test@12345` |

Pending sign-ups can already sign in with the Viewer role; they appear under **Settings → Pending** for the owner of their own organization.

## What each role should and should not be able to do

Pro-only features (Fairness, AI, Increments, Reports, HR Reports, Blockers, Peer Feedback) only work in **Demo Agency**. In **Demo Startup** the same links are hidden, and opening them directly shows the "included in Pro" upgrade screen.

### Owner — `owner@…`
**Should be able to**
- Everything inside their organization: projects, team, invites, remove members, change member roles
- Approve or reject pending sign-ups (**Settings → Pending**)
- Billing: see plan, transaction history and receipts; upgrade (Startup); renew or switch to Free (Agency)
- Organization settings, modules, GitHub settings, delete the organization
- Approve or reject any leave request; manage leave types
- Post announcements
- Pro (Agency): Fairness (run, confirm, dismiss flags), AI, increment policy and reviews, all reports, HR reports

**Should NOT be able to**
- See or change any other organization's data
- Open Settings → Platform → Organizations, the Super Admin panel or platform settings (403)
- Approve anyone into the `super_admin` role

### Admin — `admin@…`
**Should be able to**
- Projects (create, edit — not delete), team, invites, remove members
- Billing (same as owner)
- Approve or reject any leave request; manage leave types; post announcements
- Pro (Agency): Fairness, AI, increment reviews, team reports, HR reports

**Should NOT be able to**
- Open the Admin panel (Users, Roles, Permissions, **Pending sign-ups**) — needs `manage_roles`, which only the owner has
- Change member roles, delete projects, change organization settings or modules
- Edit the increment policy (owner only)
- See other organizations or any platform page

### HR — `hr@…`
**Should be able to**
- Employee directory, including editing employee HR profiles
- Approve or reject any leave request; manage leave types; export leave data
- Documents, onboarding checklists
- Pro (Agency): HR reports

**Should NOT be able to**
- Post announcements (see Known gaps)
- Billing, organization settings, projects management, Fairness, AI
- Anything outside their organization

### Team lead — `team_lead@…`, `team_lead2@demo-agency.test`
**Should be able to**
- Create and edit projects, manage tasks and sprints, invite members
- Approve or reject leave **only for people in the team they lead** (`team_lead` → Web Delivery / Product Team, `team_lead2` → Client Success)
- Resolve and escalate blockers; write feedback for their reports; post announcements
- Pro (Agency): Fairness (run, confirm, dismiss), AI questions

**Should NOT be able to**
- Approve leave for people outside their team
- Billing, settings, member removal, role changes, HR reports

### Employee — `employee@…`, `employee2@…` etc.
**Should be able to**
- Log work, update their tasks, report blockers
- Apply for leave, cancel their own pending leave
- Read announcements, documents, the directory
- Pro (Agency): My Increment, My Report, AI assistant

**Should NOT be able to**
- Approve leave, invite or remove people, see billing or settings
- See other employees' increments, feedback or reports

### Viewer — `viewer@…`, `pending…@…`
**Should be able to**
- View the dashboard, projects, team and announcements
- Apply for their own leave

**Should NOT be able to**
- Create or edit projects or blockers, invite anyone, approve anything
- Billing, settings, Pro features

### Anyone in Demo Suspended Co
**Should NOT be able to** sign in. Every account shows: *"Your organization's account is suspended. Contact support."*
A super admin can activate it from **Settings → Platform → Organizations** — then the same accounts can sign in.

## Test scenarios the data is set up for

| Scenario | Where | Expected |
|---|---|---|
| Suspension block | Log in as any `@demo-suspended-co.test` user | Refused with the suspension message |
| Suspend a live org | Super admin suspends Demo Startup, then log in as `employee@demo-startup.test` | Signed out / refused; activate again to restore |
| Free user limit | `owner@demo-startup.test` → invite 2 people | First invite works (9 → 10 places), second is refused with the Free-limit message |
| Locked Pro feature | `owner@demo-startup.test` → open `/reports/my` | "Reports is included in Pro" with an inline upgrade |
| Leave approvals | `hr@demo-agency.test` → Leaves | 2 pending, 2 approved, 1 rejected; `employee@demo-agency.test` is on leave today |
| Team-lead scope | `team_lead@demo-agency.test` → approve `employee2`'s leave | Allowed (own team) |
| Pending sign-ups | `owner@demo-agency.test` → Settings → Pending | `pending1` and `pending2` listed; real orgs' sign-ups never appear |
| Fairness flags | `owner@demo-agency.test` → Fairness → Run analysis | "Unresolved blockers" and "Meeting overload" flags |
| Increments | `owner@demo-agency.test` → Increment → calculate a month, then Reviews | Scores per person from ~3 months of tasks, work logs and blockers |
| Billing history | `owner@demo-agency.test` → Billing | Pro active, 2 paid payments with receipts, 1 failed attempt, no refund button (payments are older than 7 days) |
| Org management | Super admin → Settings → Platform → Organizations | 3 demo orgs with owners, plans, user counts |

## Known gaps (current behaviour, not caused by the demo data)

- **Admin can't approve sign-ups.** The Pending page needs `manage_roles`, which only the owner role has.
- **HR can't post announcements.** HR has the `create_announcements` permission, but the announcement form only allows owner, admin and team lead.
- **Fairness shows at most two kinds of flags.** Other checks (uneven workload, task difficulty, work-log imbalance) score below the engine's 0.95 confidence cutoff, so they never appear. The demo data includes uneven workload for when this is fixed.
- **Command Center can't be opened** by any demo account; it requires a `ceo` role that doesn't exist.
- **Demo payments can't be refunded.** Their Razorpay IDs are fake. Test refunds with a real Razorpay test-mode payment.
