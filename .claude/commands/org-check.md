Inspect a specific organization and its users/roles in OutraqHQ.

Usage: /org-check [org name or id]

Run:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan tinker --execute="
\$org = App\Models\Organization::where('name','like','%$ARGUMENTS%')->orWhere('id',$ARGUMENTS)->first();
if(!\$org){ echo 'Not found'; return; }
echo 'Org: '.\$org->name.' (id='.\$org->id.')'.PHP_EOL;
echo 'Plan: '.\$org->plan.' | Status: '.\$org->billing_status.' | Expires: '.\$org->plan_expires_at.PHP_EOL;
echo 'Owner: '.\$org->owner_id.' | Status: '.\$org->status.PHP_EOL;
\$org->users->each(function(\$u){ echo '  - '.\$u->name.' <'.\$u->email.'> | roles: '.implode(',',(array)\$u->getRoleNames()).PHP_EOL; });
"
```

Show: org details, billing, all users with roles, and flag any user with no role or mismatched organization_id.
