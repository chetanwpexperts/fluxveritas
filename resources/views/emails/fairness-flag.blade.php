<x-mail::message>
# Fairness Alert Detected ⚖️

Hi {{ $recipientName }},

The OutraqHQ Fairness Engine has detected a potential issue that requires your review.

<x-mail::panel>
**Flag Type:** {{ $flagType }}

**Confidence:** {{ $confidence }}%

**Details:** {{ $description }}
</x-mail::panel>

This flag was generated automatically by the AI Fairness Engine with **{{ $confidence }}% confidence** after passing 5 verification layers.

Please review this flag and take appropriate action.

<x-mail::button :url="url('/fairness')" color="dark">
Review Flag
</x-mail::button>

Thanks,
**OutraqHQ AI Agent**
</x-mail::message>
