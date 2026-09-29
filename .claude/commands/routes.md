List all registered web routes for OutraqHQ, grouped by middleware or feature area.

Run:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan route:list --path="" 2>/dev/null
```

Then organize the output into sections:
- **Public** (no auth middleware)
- **Auth** (requires login)
- **Admin** (requires admin role or permission)
- **Super Admin**
- **Billing**

If $ARGUMENTS is provided (e.g. "billing", "team", "import"), filter to routes matching that keyword.
