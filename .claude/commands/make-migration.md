Create a new Laravel migration for OutraqHQ.

Usage: /make-migration [description]
Example: /make-migration add_phone_to_users_table

Run:
```
cd /Applications/XAMPP/xamppfiles/htdocs/fluxveritas && php artisan make:migration $ARGUMENTS
```

After creating the file:
1. Show the generated filename and path
2. Open the migration file and display its contents
3. Wait for the user to specify the schema changes, then edit the `up()` method accordingly
4. Remind to run /migrate when ready
