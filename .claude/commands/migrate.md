Run pending Laravel migrations for OutraqHQ and report what ran.

Execute:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan migrate --force
```

After running, show:
- Which migrations ran (or "Nothing to migrate" if already up to date)
- Any errors clearly
- Current migration count: `php artisan migrate:status | grep -c "Ran"`
