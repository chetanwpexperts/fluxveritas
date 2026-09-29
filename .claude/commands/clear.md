Run all Laravel cache-clearing commands for OutraqHQ and report what was cleared.

Execute these in order:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan config:clear && php artisan cache:clear && php artisan view:clear && php artisan route:clear && php artisan event:clear
```

Report each step as "✓ cleared" or show the error if one fails.
