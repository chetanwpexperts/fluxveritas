Show recent Laravel log entries for OutraqHQ.

Run:
```
tail -100 /Applications/XAMPP/xamppfiles/htdocs/fluxveritas/storage/logs/laravel.log
```

Report:
- Last 20 log entries (ERROR and WARNING prioritized first, then INFO)
- Group by log level: EMERGENCY / ALERT / CRITICAL / ERROR / WARNING / NOTICE / INFO / DEBUG
- Show timestamp, level, and message for each
- Flag any repeated errors (same message 3+ times)

If $ARGUMENTS is provided (e.g. "billing", "auth", "500"), filter log lines containing that keyword.
