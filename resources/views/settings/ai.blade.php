@extends('layouts.app')
@section('content')
<div class="page-wrapper">

    @include('settings.partials.tabs')

    <div style="display:flex;flex-direction:column;gap:20px;">

            {{-- AI Provider Status --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">AI Provider Configuration</h2>
                    <p style="font-size:0.8rem;color:#71717a;margin-top:4px;">Configure which AI providers are available for each plan tier</p>
                </div>
                <div style="padding:24px;display:flex;flex-direction:column;gap:20px;">

                    {{-- Rule Engine --}}
                    <div style="border:1px solid #e4e4e7;border-radius:8px;padding:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <div style="font-size:0.875rem;font-weight:700;color:#09090b;">Rule-Based Engine</div>
                                <div style="font-size:0.78rem;color:#71717a;margin-top:4px;">PHP logic engine — always available, zero cost. Used for Free plan.</div>
                            </div>
                            <span style="background:#f0fdf4;color:#16a34a;font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:99px;white-space:nowrap;flex-shrink:0;">✓ Active</span>
                        </div>
                    </div>

                    {{-- Ollama --}}
                    @php
                    $ollamaUrl = env('OLLAMA_URL', 'http://localhost:11434');
                    try {
                        $ch = curl_init($ollamaUrl . '/api/tags');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
                        $result = curl_exec($ch);
                        $ollamaActive = !curl_error($ch) && $result !== false;
                        curl_close($ch);
                    } catch (Exception $e) {
                        $ollamaActive = false;
                    }
                    @endphp
                    <div style="border:1px solid #e4e4e7;border-radius:8px;padding:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <div>
                                <div style="font-size:0.875rem;font-weight:700;color:#09090b;">Ollama (Local AI)</div>
                                <div style="font-size:0.78rem;color:#71717a;margin-top:4px;">Llama 3 running locally. Free, private. Used for Pro plan.</div>
                            </div>
                            <span style="background:{{ $ollamaActive ? '#f0fdf4' : '#fef2f2' }};color:{{ $ollamaActive ? '#16a34a' : '#dc2626' }};font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:99px;white-space:nowrap;flex-shrink:0;">
                                {{ $ollamaActive ? '✓ Connected' : '✗ Not Running' }}
                            </span>
                        </div>
                        <div style="background:#fafafa;border-radius:6px;padding:12px;font-size:0.78rem;color:#71717a;">
                            <strong>URL:</strong> {{ $ollamaUrl }}<br>
                            <strong>Model:</strong> {{ env('OLLAMA_MODEL', 'llama3') }}
                            @if(!$ollamaActive)
                            <br><span style="color:#dc2626;margin-top:6px;display:block;">To start Ollama: <code>ollama serve</code> then <code>ollama pull llama3</code></span>
                            @endif
                        </div>
                    </div>

                    {{-- OpenAI --}}
                    @php
                    $openAiKey    = env('OPENAI_API_KEY', '');
                    $openAiActive = !empty($openAiKey);
                    @endphp
                    <div style="border:1px solid #e4e4e7;border-radius:8px;padding:16px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <div>
                                <div style="font-size:0.875rem;font-weight:700;color:#09090b;">OpenAI GPT-4o</div>
                                <div style="font-size:0.78rem;color:#71717a;margin-top:4px;">Best quality AI summaries. Paid API. Used for Enterprise plan.</div>
                            </div>
                            <span style="background:{{ $openAiActive ? '#f0fdf4' : '#fefce8' }};color:{{ $openAiActive ? '#16a34a' : '#ca8a04' }};font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:99px;white-space:nowrap;flex-shrink:0;">
                                {{ $openAiActive ? '✓ Key Set' : '⚠ No Key' }}
                            </span>
                        </div>
                        <div style="background:#fafafa;border-radius:6px;padding:12px;font-size:0.78rem;color:#71717a;">
                            <strong>Model:</strong> {{ env('OPENAI_MODEL', 'gpt-4o') }}<br>
                            <strong>API Key:</strong> {{ $openAiActive ? '••••••••' . substr($openAiKey, -4) : 'Not configured' }}
                            @if(!$openAiActive)
                            <br><span style="color:#ca8a04;margin-top:6px;display:block;">Add OPENAI_API_KEY to your .env file to enable GPT-4o for Enterprise orgs.</span>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            {{-- Plan AI Mapping --}}
            <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
                <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                    <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Plan → AI Provider Mapping</h2>
                </div>
                <div style="padding:0;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="background:#fafafa;">
                                <th style="padding:12px 20px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#a1a1aa;border-bottom:1px solid #e4e4e7;">Plan</th>
                                <th style="padding:12px 20px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#a1a1aa;border-bottom:1px solid #e4e4e7;">AI Provider</th>
                                <th style="padding:12px 20px;text-align:left;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#a1a1aa;border-bottom:1px solid #e4e4e7;">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['Free',       'Rule-Based PHP Engine',    'Zero cost'],
                                ['Pro',        'Ollama Llama 3 (Local)',   'Zero cost (self-hosted)'],
                                ['Enterprise', 'OpenAI GPT-4o',            'Pay per token'],
                            ] as [$plan, $provider, $cost])
                            <tr>
                                <td style="padding:14px 20px;font-size:0.875rem;font-weight:600;color:#09090b;border-bottom:1px solid #f4f4f5;">{{ $plan }}</td>
                                <td style="padding:14px 20px;font-size:0.875rem;color:#3f3f46;border-bottom:1px solid #f4f4f5;">{{ $provider }}</td>
                                <td style="padding:14px 20px;font-size:0.875rem;color:#71717a;border-bottom:1px solid #f4f4f5;">{{ $cost }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- Outy Intent Management (super_admin only) --}}
        @role('super_admin')
        <div style="background:white;border:1px solid #e4e4e7;border-radius:10px;overflow:hidden;">
            <div style="padding:18px 24px;border-bottom:1px solid #e4e4e7;">
                <h2 style="font-size:0.95rem;font-weight:700;color:#09090b;">Outy Intent Triggers</h2>
                <p style="font-size:0.8rem;color:#71717a;margin-top:4px;">Manage the phrases that trigger each intent in Outy's classifier. Changes take effect immediately.</p>
            </div>
            <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
                @forelse($intents ?? [] as $intent)
                <div class="intent-row">
                    <div class="intent-header">
                        <span class="intent-name">{{ $intent->display_name }}</span>
                        <span class="intent-desc">{{ $intent->intent_name }}</span>
                    </div>
                    <div class="intent-triggers">
                        @foreach($intent->triggers as $trigger)
                        <span class="intent-trigger-tag">
                            {{ $trigger }}
                            <form method="POST" action="{{ route('settings.ai.intent.remove-trigger', $intent->id) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <input type="hidden" name="trigger" value="{{ $trigger }}">
                                <button type="submit" class="intent-trigger-remove" title="Remove trigger">&times;</button>
                            </form>
                        </span>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('settings.ai.intent.add-trigger', $intent->id) }}" class="intent-add-form">
                        @csrf
                        <input type="text" name="trigger" class="intent-add-input" placeholder="Add trigger phrase…" required minlength="2" maxlength="80">
                        <button type="submit" style="padding:6px 14px;background:#18181b;color:#fff;border:none;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;">Add</button>
                    </form>
                </div>
                @empty
                <p style="font-size:0.85rem;color:#71717a;">No intents found. Run <code>php artisan db:seed --class=AgentIntentSeeder</code> to seed them.</p>
                @endforelse
            </div>
        </div>
        @endrole

    </div>

</div>
@endsection
