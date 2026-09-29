Check Spatie permissions and roles are correctly set up in OutraqHQ.

Run:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan tinker --execute="
echo '=== ROLES ==='.PHP_EOL;
Spatie\Permission\Models\Role::all()->each(fn(\$r) => print(\$r->name.' (guard='.\$r->guard_name.')'.PHP_EOL));
echo '=== PERMISSIONS ==='.PHP_EOL;
Spatie\Permission\Models\Permission::all()->each(fn(\$p) => print(\$p->name.PHP_EOL));
echo '=== USERS WITHOUT ROLES ==='.PHP_EOL;
App\Models\User::whereDoesntHave('roles')->get()->each(fn(\$u) => print(\$u->id.' '.\$u->email.PHP_EOL));
"
```

Report:
- All roles and their guard
- All permissions
- Any users with no role assigned (flag these — they'll hit middleware issues)
- Any users whose role doesn't match their onboarding_type
