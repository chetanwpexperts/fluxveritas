Scaffold a new feature for OutraqHQ following the project's patterns.

Usage: /feature [feature name and brief description]
Example: /feature PayslipController - generate monthly payslips per employee

Based on $ARGUMENTS, plan and implement:

1. **Controller** in app/Http/Controllers/ — use existing controllers as pattern reference (check BillingController.php or LeaveController.php for style)
2. **Routes** in routes/web.php — add under the appropriate middleware group (auth + check.onboarding). Check if a module gate is needed (middleware: module:feature_name)
3. **View** in resources/views/ — extend layouts.app, use fv-card / fv-btn / fv-table CSS classes consistent with other views
4. **Migration** if new DB table needed — follow existing migration naming convention
5. **Model** if needed — add to app/Models/ with proper $fillable and casts()

Before starting: read the relevant existing controller and a nearby view to match exact code style. Do not add features beyond what was asked.
