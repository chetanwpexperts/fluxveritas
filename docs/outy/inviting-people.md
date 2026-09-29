# Inviting people and importing employees

## Invite one person (owners, admins, team leads)
1. Open the **Team** page (from the link on your Dashboard, or go to `/team`) and click **Invite**.
2. Enter their email and choose a role.
3. Send. They get an email with a link valid for 7 days; you can also copy the link and share it.

## Invite many people
On the same **Team** page use **Bulk invite**, paste email addresses (comma or new line separated) and choose one role for all.

## Import from a spreadsheet or another HR system (owners, admins, HR)
1. Go to **Directory → Import employees** (also on the **Team** page, or `/import/employees`).
2. Choose where the file is from — Keka, Zoho People, greytHR, Darwinbox, BambooHR, the OutraqHQ template, or Custom — and upload a CSV or Excel (.xlsx) file.
3. **Match columns**: each column in your file is matched to an OutraqHQ field (name, email, department, team, designation, reporting manager, date of joining, phone, employment type, location, status, and leave balances). Automatic matches are shown in green — check them and fix any that are wrong.
4. Choose the options: skip or update people who are already in your organization, send invitations now or later, how to read dates (DD/MM or MM/DD), and whether to skip former employees.
5. **Check**: see how many people will be added or updated, which departments, teams and designations will be created, and every row with a problem. Download the full list as CSV.
6. **Import**. Large files (more than 500 rows) run in the background with a progress bar — you can leave the page.
7. When it finishes, download the error report and send invitations if you chose "later". Each new person gets an email with a link to set their password (valid for 7 days).

Notes:
- Reporting managers are linked after everyone is created, by email (or by name when it is unique).
- An email that belongs to an account in another organization can't be imported.
- HR can give the roles employee, team lead and viewer; owners and admins can also give HR and admin.

## Sign-ups waiting for approval
People who sign up without an invitation wait for approval. The owner approves them under **Settings → Pending** and picks their role.

## Free plan limit
The Free plan includes up to 10 people, counting pending invitations. Upgrade to Pro to add more.
