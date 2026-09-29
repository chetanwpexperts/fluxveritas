{{-- Confirmation dialogs; wired up by public/js/platform-orgs.js --}}
<dialog class="po-dialog" id="suspend-org-dialog" aria-labelledby="suspend-org-title">
    <form method="POST" action="">
        @csrf
        <h2 id="suspend-org-title">Suspend <span data-org-name></span>?</h2>
        <ul>
            <li>Every member is signed out and can’t sign in until the organization is activated again.</li>
            <li>They’ll see: “{{ \App\Models\Organization::SUSPENDED_MESSAGE }}”</li>
            <li>No data is deleted, and billing is not changed or refunded.</li>
            <li>This action and your reason are recorded in the audit log.</li>
        </ul>
        <label for="suspend-reason">Reason (required)</label>
        <textarea id="suspend-reason" name="reason" required minlength="10" maxlength="1000"></textarea>
        <p class="po-dialog-hint">At least 10 characters. Visible to platform admins only.</p>
        <div class="po-dialog-actions">
            <button type="button" class="po-btn po-btn-secondary" data-dialog-close>Cancel</button>
            <button type="submit" class="po-btn po-btn-danger">Suspend organization</button>
        </div>
    </form>
</dialog>

<dialog class="po-dialog" id="activate-org-dialog" aria-labelledby="activate-org-title">
    <form method="POST" action="">
        @csrf
        <h2 id="activate-org-title">Activate <span data-org-name></span>?</h2>
        <ul>
            <li>Members who had access before the suspension can sign in again.</li>
            <li>This action is recorded in the audit log.</li>
        </ul>
        <label for="activate-note">Note (optional)</label>
        <textarea id="activate-note" name="note" maxlength="1000"></textarea>
        <div class="po-dialog-actions">
            <button type="button" class="po-btn po-btn-secondary" data-dialog-close>Cancel</button>
            <button type="submit" class="po-btn po-btn-primary">Activate organization</button>
        </div>
    </form>
</dialog>
