{{-- Suspend / Activate button that opens the matching dialog. Needs: $org --}}
@if($org->isSuspended())
    <button type="button" class="po-btn po-btn-secondary"
            data-org-action="activate"
            data-url="{{ route('settings.organizations.activate', $org) }}"
            data-org-name="{{ $org->name }}">Activate</button>
@else
    <button type="button" class="po-btn po-btn-danger-outline"
            data-org-action="suspend"
            data-url="{{ route('settings.organizations.suspend', $org) }}"
            data-org-name="{{ $org->name }}">Suspend</button>
@endif
