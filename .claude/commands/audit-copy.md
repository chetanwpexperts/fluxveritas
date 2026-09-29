Scan OutraqHQ public-facing views for overstated or flagged marketing copy.

Search these files:
- resources/views/landing.blade.php
- resources/views/tour.blade.php
- resources/views/docs.blade.php
- resources/views/pricing.blade.php
- resources/views/contact.blade.php

Flag any lines containing these patterns:
- "24/7" or "24 hours"
- "every hour" or "runs every"
- "AES-256" or "bank.grade" or "military"
- "unfiltered truth" or "doesn't lie" or "AI doesn't"
- "No politics" or "No favoritism" or "No filters"
- "watches everyone" or "tracks everyone"
- "Real Data" or "Live" (in mock/demo UI labels)
- Fake personal names used in demo data (Sarah, Alex, Marcus, Priya, Rahul, Neha)
- Any hardcoded prices in USD ($) — should be ₹ INR only
- "Growth" plan name — should be "Pro" everywhere

Report file:line and the flagged text. If nothing flagged, say "✓ Copy looks clean."
