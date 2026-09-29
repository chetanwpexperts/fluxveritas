Inspect the billing and plan state for all organizations in OutraqHQ.

Run:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan tinker --execute="App\Models\Organization::select('id','name','plan','billing_status','plan_expires_at')->get()->each(function(\$o){ echo \$o->id.' | '.\$o->name.' | plan='.\$o->plan.' | status='.(\$o->billing_status ?? 'null').' | expires='.(\$o->plan_expires_at ?? 'null').PHP_EOL; });"
```

Report each org as a table row: ID | Name | Plan | Billing Status | Expires At

Flag any issues:
- plan=null or missing
- billing_status=expired
- plan_expires_at in the past
- plan='pro' but billing_status != 'active'

If $ARGUMENTS is provided, filter to that org name or ID.
