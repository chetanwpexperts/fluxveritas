Fix stale sessions and throttle cache in OutraqHQ (use when login loops or 429s occur).

Run these in order:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan tinker --execute="DB::table('sessions')->delete(); echo 'Sessions cleared: OK';"
```
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan cache:clear
```

Report how many sessions were cleared, then confirm cache is clean.

Common causes this fixes:
- Login loops (EnsureOrganizationAccess hitting stale session)
- 429 Too Many Requests (login throttle from failed attempts)
- "Session expired" after DB restart
