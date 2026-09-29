Review code changes for OutraqHQ. Works on a specific PR or the current uncommitted diff.

Usage:
- `/pr` — review current working changes (git diff)
- `/pr 42` — review GitHub PR #42
- `/pr main` — review everything ahead of main branch

---

## Step 1 — Get the diff

If $ARGUMENTS is a number, run:
```
gh pr view $ARGUMENTS --json title,body,additions,deletions,changedFiles && gh pr diff $ARGUMENTS
```

If $ARGUMENTS is a branch name, run:
```
git diff $ARGUMENTS...HEAD
```

If no $ARGUMENTS, run:
```
git diff HEAD && git diff --cached
```

---

## Step 2 — Review against OutraqHQ standards

Check for:

**Correctness**
- Logic bugs, off-by-one errors, wrong conditions
- Missing null checks on organization_id, plan, billing_status
- DB queries missing ->where('organization_id', ...) scope (data leakage between orgs)

**Security**
- Mass assignment — are new fillable fields safe?
- User input going into queries without validation
- Any route missing auth or check.onboarding middleware
- CSP — no new <meta http-equiv="Content-Security-Policy"> tags (SecurityHeaders.php is the only CSP source)

**Laravel patterns**
- Controllers should not contain business logic — move to Service classes
- Use firstOrCreate / updateOrCreate instead of find+save pairs
- Migrations must be reversible (down() method correct)
- New models need $fillable and casts() defined

**Plan/module gating**
- Any new Pro/Enterprise feature must be behind module: middleware in routes
- New modules must be added to ModuleService $planModules AND $modulesMeta

**Views**
- Must extend layouts.app (authenticated) or layouts.public (marketing pages)
- No inline <meta CSP> tags
- No hardcoded USD prices — INR (₹) only
- No fake personal names in demo data
- No overstated AI claims ("24/7", "runs every hour", "unfiltered truth")
- Plan names: Free / Pro / Enterprise only (not Starter/Growth)

**Roles**
- super_admin must never appear on public-facing pages
- New routes with role checks should use hasAnyRole / hasRole correctly

---

## Step 3 — Report

Format findings as:

### PR Review — [title or "Working Changes"]

**Summary:** [1-2 sentence overview of what changed]

**Issues** (fix before merging):
- [file:line] — [what's wrong and why]

**Warnings** (worth considering):
- [file:line] — [potential issue]

**Looks good:**
- [what's done well]

**Verdict:** Ready / Needs fixes / Needs discussion
