Inspect the billing and plan state for all organizations in OutraqHQ.

Run:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan tinker --execute="App\Models\Organization::select('id','name','plan','billing_status','billing_period','seats','plan_expires_at','downgrade_scheduled_at')->get()->each(function(\$o){ echo \$o->id.' | '.\$o->name.' | plan='.\$o->plan.' (effective '.\$o->effectivePlan().') | status='.(\$o->billing_status ?? 'null').' | '.(\$o->billing_period ?? '-').' | seats='.(\$o->seats ?? '-').' | expires='.(\$o->plan_expires_at ?? 'null').(\$o->downgrade_scheduled_at ? ' | downgrade scheduled' : '').PHP_EOL; }); echo PHP_EOL.'Recent payments:'.PHP_EOL; App\Models\Payment::latest()->take(15)->get()->each(function(\$p){ echo \$p->id.' | org '.\$p->organization_id.' | '.\$p->description().' | '.App\Services\BillingService::inr(\$p->amount).' | '.\$p->statusLabel().' | '.(\$p->paid_at ?? \$p->created_at).PHP_EOL; });"
```

Report each org as a table row: ID | Name | Plan | Billing Status | Expires At

Flag any issues:
- plan=null or missing
- billing_status=expired
- plan_expires_at in the past
- plan='pro' but billing_status != 'active'
- payment stuck at status 'created' for more than an hour (browser closed before verify — check the Razorpay webhook is configured)
- refund status 'Refund failed' (retry from the Razorpay dashboard)

If $ARGUMENTS is provided, filter to that org name or ID.
