# GitHub sync

Brings commits and pull requests into OutraqHQ for engineering teams. Optional — other departments don't need it. Available on every plan.

## Set it up
1. **Everyone with code**: add your GitHub username under **Profile → GitHub**. Activity is matched to people by this username.
2. **Owner or admin**: open **Settings → GitHub** → **Organization connection** and paste a GitHub token from an account that can read your repositories (a fine-grained token with read-only "Contents" and "Pull requests" access is enough). It is checked with GitHub before it's saved, and stored encrypted.
   Without your own token, only **public** repositories can be synced.
3. Link each project to its repository: open the project and fill in the GitHub owner and repository name (see "projects").

## When it syncs
- Automatically every night at 2:00 AM IST, for the last 30 days.
- On demand: owners, admins and team leads can click **Sync GitHub** on the Dashboard or **Sync now** in Settings → GitHub. It syncs the whole organization, not just the person clicking.

## What you see
Settings → GitHub shows the last sync: when it ran, how many commits and pull requests were found, and any repositories that couldn't be read (for example a private repo without your own token, or an expired token).

GitHub activity counts toward increment scores only for technical roles. Both opened and merged pull requests are counted.
