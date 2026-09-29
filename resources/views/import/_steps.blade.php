{{-- Step indicator. Needs: $current (1–4) --}}
<ol class="im-steps">
    @foreach(['Upload', 'Match columns', 'Check', 'Import'] as $i => $label)
        <li class="{{ $current === $i + 1 ? 'is-current' : '' }}">{{ $i + 1 }}. {{ $label }}</li>
    @endforeach
</ol>
