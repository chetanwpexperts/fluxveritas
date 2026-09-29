Show the current module plan mapping and meta for OutraqHQ.

Read app/Services/ModuleService.php and report:

1. **planModules** — which modules are in each plan (free/pro/enterprise) as a table
2. **modulesMeta** — each module's label, plan_required, and icon
3. **Consistency check** — flag any module where plan_required doesn't match its position in planModules (e.g. a module in 'free' planModules but plan_required='pro')

If $ARGUMENTS is a module name (e.g. "blockers"), show only that module's details.
