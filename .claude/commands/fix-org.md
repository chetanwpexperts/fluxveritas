Fix an organization's billing/plan data directly in the OutraqHQ database.

Usage: /fix-org [org name] [plan] [status]
Example: /fix-org "HCL Technologies" pro active

Supported plans: free, pro, enterprise
Supported statuses: free, active, expired

Run:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan tinker --execute="
\$org = App\Models\Organization::where('name','like','%[ORG NAME FROM ARGS]%')->firstOrFail();
\$org->update([
  'plan' => '[PLAN FROM ARGS]',
  'billing_status' => '[STATUS FROM ARGS]',
  'plan_expires_at' => '[STATUS FROM ARGS]' === 'active' ? now()->addMonthNoOverflow() : null,
]);
echo 'Updated: '.\$org->name.' → plan='.\$org->plan.' status='.\$org->billing_status.' expires='.\$org->plan_expires_at.PHP_EOL;
"
```

Parse $ARGUMENTS to extract org name, plan, and status. Confirm the update by showing the org's new state.
