@extends('layouts.public')

@section('full_title', 'OutraqHQ — HR and performance software, measured on real work')

@section('meta')
    <meta name="description" content="OutraqHQ brings leave, documents, onboarding, work logs and tasks into one place, and turns real work data into fair, explainable increment recommendations. Free for up to 10 people.">
    <meta property="og:title" content="OutraqHQ — HR and performance, measured on real work">
    <meta property="og:description" content="People operations, work tracking and data-backed increment reviews in one workspace. Free for up to 10 people.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <link rel="canonical" href="{{ url('/') }}">
@endsection

@php
    $inr        = fn (int $p) => \App\Services\BillingService::inr($p);
    $proMonthly = config('plans.pro.periods.monthly.price_per_user');
    $proYearly  = config('plans.pro.periods.yearly.price_per_user');
    $freeUsers  = config('plans.free.max_users');
    $minSeats   = config('plans.pro.min_seats');
    $refundDays = config('plans.refund_window_days');
@endphp

@section('content')
<main class="lp">

    {{-- ── Hero ─────────────────────────────────────────────────────── --}}
    <section class="lp-hero">
        <div class="lp-wrap lp-hero-inner">
            <h1 class="lp-h1">HR and performance,<br><span>measured on real work.</span></h1>
            <p class="lp-lead">
                Run leave, documents and onboarding in one place — and make increment decisions
                backed by what your team actually delivered.
            </p>
            <div class="lp-ctas">
                <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">Start free</a>
                <a href="{{ route('contact') }}" class="lp-btn lp-btn-secondary">Book a demo</a>
            </div>
            <p class="lp-fine">Free for up to {{ $freeUsers }} people · No card needed</p>
        </div>

        <div class="lp-wrap">
            <div class="lp-window" aria-hidden="true">
                <div class="lp-window-bar"><span></span><span></span><span></span></div>
                <div class="lp-app">
                    <div class="lp-app-side">
                        <div class="lp-app-nav is-active">Dashboard</div>
                        <div class="lp-app-nav">Work log</div>
                        <div class="lp-app-nav">Tasks</div>
                        <div class="lp-app-nav">Leave</div>
                        <div class="lp-app-nav">Directory</div>
                        <div class="lp-app-nav">Increments</div>
                    </div>
                    <div class="lp-app-main">
                        <div class="lp-app-title">Team overview</div>
                        <div class="lp-app-stats">
                            <div class="lp-app-stat"><b>48</b><span>Active people</span></div>
                            <div class="lp-app-stat"><b>41</b><span>Logged work today</span></div>
                            <div class="lp-app-stat"><b>3</b><span>Open blockers</span></div>
                            <div class="lp-app-stat"><b>2</b><span>Leave requests</span></div>
                        </div>
                        <div class="lp-app-grid">
                            <div class="lp-app-panel">
                                <div class="lp-app-panel-title">Work logged this week</div>
                                @foreach([['Engineering', 92], ['Sales', 84], ['Operations', 78], ['Finance', 71]] as [$dept, $pct])
                                    <div class="lp-bar"><span>{{ $dept }}</span><i><em style="width:{{ $pct }}%"></em></i><small>{{ $pct }}%</small></div>
                                @endforeach
                            </div>
                            <div class="lp-app-panel">
                                <div class="lp-app-panel-title">Needs attention</div>
                                <div class="lp-app-item"><i class="lp-dot lp-dot-amber"></i>Uneven workload in Engineering</div>
                                <div class="lp-app-item"><i class="lp-dot lp-dot-blue"></i>2 leave requests awaiting approval</div>
                                <div class="lp-app-item"><i class="lp-dot lp-dot-green"></i>Q3 increment reviews ready</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Trust strip ──────────────────────────────────────────────── --}}
    <section class="lp-strip">
        <div class="lp-wrap lp-strip-inner">
            <span>Built for Indian companies</span>
            <span>Billing in INR</span>
            <span>Each company’s data kept separate</span>
            <span>Role-based access</span>
        </div>
    </section>

    {{-- ── Product pillars ──────────────────────────────────────────── --}}
    <section class="lp-section" id="product">
        <div class="lp-wrap">
            <div class="lp-head">
                <p class="lp-eyebrow">Product</p>
                <h2 class="lp-h2">One workspace for your people and their work</h2>
                <p class="lp-sub">Replace the spreadsheets, chat threads and separate tools your team uses today.</p>
            </div>

            <div class="lp-pillars">
                <article class="lp-pillar">
                    <h3>Run HR</h3>
                    <p>Everyday people operations, without the paperwork.</p>
                    <ul>
                        <li>Employee directory &amp; org chart</li>
                        <li>Leave requests, balances &amp; approvals</li>
                        <li>Document center</li>
                        <li>Onboarding checklists</li>
                        <li>Announcements</li>
                    </ul>
                </article>
                <article class="lp-pillar">
                    <h3>Track work</h3>
                    <p>See what’s getting done across every department — not just engineering.</p>
                    <ul>
                        <li>Daily work logs</li>
                        <li>Tasks &amp; sprints</li>
                        <li>Blockers &amp; dependencies</li>
                        <li>GitHub activity for tech teams</li>
                        <li>Team &amp; individual reports</li>
                    </ul>
                </article>
                <article class="lp-pillar lp-pillar-accent">
                    <h3>Reward fairly</h3>
                    <p>Performance decisions you can explain, with the evidence attached.</p>
                    <ul>
                        <li>Increment calculator</li>
                        <li>Fairness checks on workload</li>
                        <li>Manager &amp; peer feedback</li>
                        <li>Employee statements &amp; appeals</li>
                        <li>AI summaries for leadership</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- ── How it works ─────────────────────────────────────────────── --}}
    <section class="lp-section lp-alt">
        <div class="lp-wrap">
            <div class="lp-head">
                <p class="lp-eyebrow">How it works</p>
                <h2 class="lp-h2">Up and running in an afternoon</h2>
            </div>
            <ol class="lp-steps">
                <li>
                    <span class="lp-step-num">1</span>
                    <h3>Bring your team in</h3>
                    <p>Import employees from a CSV, Excel or JSON file. Departments, teams and reporting managers are set up for you.</p>
                </li>
                <li>
                    <span class="lp-step-num">2</span>
                    <h3>Work as usual</h3>
                    <p>People log work, update tasks and apply for leave. Tech teams can connect GitHub.</p>
                </li>
                <li>
                    <span class="lp-step-num">3</span>
                    <h3>Review with evidence</h3>
                    <p>Monthly scores, workload checks and feedback come together when it’s time for reviews and increments.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- ── Deep dive: increments ────────────────────────────────────── --}}
    <section class="lp-section">
        <div class="lp-wrap lp-split">
            <div class="lp-split-text">
                <p class="lp-eyebrow">Increments</p>
                <h2 class="lp-h2">Increment recommendations you can explain</h2>
                <p class="lp-sub">Each month, OutraqHQ scores contribution against criteria you choose, and builds an annual recommendation from those scores.</p>
                <ul class="lp-checks">
                    <li>Set your own criteria and weights — tasks, work logs, blockers resolved, code activity</li>
                    <li>Criteria that don’t apply to a role are left out, not counted as zero</li>
                    <li>Scores are adjusted for approved leave</li>
                    <li>Employees see their breakdown and can submit an appeal</li>
                </ul>
            </div>
            <div class="lp-card-preview" aria-hidden="true">
                <div class="lp-cp-head">
                    <div><b>Monthly contribution score</b><span>Sales · Senior</span></div>
                    <div class="lp-cp-score">78</div>
                </div>
                @foreach([['Tasks completed', 82, '30%'], ['Work logs', 76, '25%'], ['Blocker resolution', 90, '20%'], ['Task complexity', 68, '25%']] as [$label, $score, $weight])
                    <div class="lp-cp-row">
                        <span>{{ $label }}</span>
                        <i><em style="width:{{ $score }}%"></em></i>
                        <small>{{ $score }}</small>
                        <small class="lp-cp-w">{{ $weight }}</small>
                    </div>
                @endforeach
                <div class="lp-cp-row lp-cp-skip"><span>Code activity</span><small>Not applicable to this role</small></div>
                <div class="lp-cp-foot">Adjusted for 2 days of approved leave</div>
            </div>
        </div>
    </section>

    {{-- ── Deep dive: fairness ──────────────────────────────────────── --}}
    <section class="lp-section lp-alt">
        <div class="lp-wrap lp-split lp-split-rev">
            <div class="lp-split-text">
                <p class="lp-eyebrow">Fairness checks</p>
                <h2 class="lp-h2">Spot uneven workload before it becomes a problem</h2>
                <p class="lp-sub">OutraqHQ analyzes task distribution, blockers and work logs, and surfaces patterns for managers to review.</p>
                <ul class="lp-checks">
                    <li>Flags uneven task load and difficulty across a team</li>
                    <li>Highlights blockers that stay open too long</li>
                    <li>Accounts for people on leave before raising a flag</li>
                    <li>Managers confirm or dismiss every flag — nothing is decided automatically</li>
                </ul>
            </div>
            <div class="lp-card-preview" aria-hidden="true">
                <div class="lp-cp-head"><div><b>Flags to review</b><span>This week</span></div></div>
                @foreach([
                    ['Uneven workload', 'Engineering — 3 people hold most open tasks', 'amber'],
                    ['Long-open blocker', 'Operations — waiting on another team', 'blue'],
                    ['Work log imbalance', 'Finance — logging dropped this week', 'amber'],
                ] as [$title, $detail, $tone])
                    <div class="lp-flag">
                        <i class="lp-dot lp-dot-{{ $tone }}"></i>
                        <div><b>{{ $title }}</b><span>{{ $detail }}</span></div>
                        <div class="lp-flag-actions"><em>Confirm</em><em>Dismiss</em></div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Works with ───────────────────────────────────────────────── --}}
    <section class="lp-section">
        <div class="lp-wrap">
            <div class="lp-head">
                <p class="lp-eyebrow">Works with</p>
                <h2 class="lp-h2">Start with the data you already have</h2>
            </div>
            <div class="lp-tiles">
                <div class="lp-tile"><h3>Spreadsheets</h3><p>Import employees from CSV, Excel or JSON — with a preview before anything is saved.</p></div>
                <div class="lp-tile"><h3>GitHub</h3><p>Bring in commits and pull requests for engineering teams.</p></div>
                <div class="lp-tile"><h3>Email</h3><p>Invitations, approvals and digest reports go to your team’s inbox.</p></div>
            </div>
        </div>
    </section>

    {{-- ── Pricing summary ──────────────────────────────────────────── --}}
    <section class="lp-section lp-alt" id="pricing">
        <div class="lp-wrap">
            <div class="lp-head">
                <p class="lp-eyebrow">Pricing</p>
                <h2 class="lp-h2">Simple pricing in rupees</h2>
                <p class="lp-sub">Start free. Pay per person when you need the performance tools.</p>
            </div>
            <div class="lp-plans">
                <div class="lp-plan">
                    <h3>Free</h3>
                    <div class="lp-plan-price">₹0</div>
                    <p>Core HR and work tracking for up to {{ $freeUsers }} people.</p>
                    <a href="{{ route('register') }}" class="lp-btn lp-btn-secondary">Start free</a>
                </div>
                <div class="lp-plan lp-plan-featured">
                    <h3>Pro</h3>
                    <div class="lp-plan-price">{{ $inr($proYearly) }}<small>/user/month</small></div>
                    <p>Billed yearly, or {{ $inr($proMonthly) }}/user monthly. Increments, fairness checks, reports and AI.</p>
                    <a href="{{ route('pricing') }}" class="lp-btn lp-btn-primary">See Pro</a>
                </div>
                <div class="lp-plan">
                    <h3>Enterprise</h3>
                    <div class="lp-plan-price">Custom</div>
                    <p>Command Center, audit logs, priority support and help moving your data in — for larger organizations.</p>
                    <a href="{{ route('contact', ['plan' => 'enterprise']) }}" class="lp-btn lp-btn-secondary">Talk to us</a>
                </div>
            </div>
            <p class="lp-plans-note">Prices exclude GST. <a href="{{ route('pricing') }}">Compare all features →</a></p>
        </div>
    </section>

    {{-- ── Security ─────────────────────────────────────────────────── --}}
    <section class="lp-section">
        <div class="lp-wrap">
            <div class="lp-head">
                <p class="lp-eyebrow">Security</p>
                <h2 class="lp-h2">Your data stays yours</h2>
            </div>
            <div class="lp-tiles">
                <div class="lp-tile"><h3>Separate by company</h3><p>Every organization’s data is kept apart and visible only to its own members.</p></div>
                <div class="lp-tile"><h3>Secure by design</h3><p>All traffic is encrypted over HTTPS, and sessions time out after inactivity.</p></div>
                <div class="lp-tile"><h3>Access by role</h3><p>Owners, admins, managers, HR and employees each see only what their role allows.</p></div>
            </div>
        </div>
    </section>

    {{-- ── FAQ ──────────────────────────────────────────────────────── --}}
    <section class="lp-section lp-alt">
        <div class="lp-wrap lp-faq-wrap">
            <div class="lp-head">
                <p class="lp-eyebrow">FAQ</p>
                <h2 class="lp-h2">Common questions</h2>
            </div>
            <div class="lp-faq">
                <details>
                    <summary>Is the Free plan really free?</summary>
                    <p>Yes. Free covers core HR and work tracking for up to {{ $freeUsers }} people, with no time limit and no card required.</p>
                </details>
                <details>
                    <summary>How does per-user pricing work?</summary>
                    <p>Pro is billed for the active people in your organization, with a minimum of {{ $minSeats }} users. You pay monthly or yearly in advance, and plans don’t renew automatically.</p>
                </details>
                <details>
                    <summary>Can I cancel and get a refund?</summary>
                    <p>Yes. You can cancel a Pro payment within {{ $refundDays }} days for a full refund, straight from the Billing page. After that, your plan stays active until the end of the period you paid for. <a href="{{ route('refund-policy') }}">Read the refund policy</a>.</p>
                </details>
                <details>
                    <summary>Do we need to use GitHub?</summary>
                    <p>No. GitHub is optional and only useful for engineering teams. Work logs, tasks and blockers work for every department.</p>
                </details>
                <details>
                    <summary>Can we bring in our existing employee data?</summary>
                    <p>Yes. Upload a CSV, Excel or JSON file, review the preview, and OutraqHQ creates everyone along with their departments, teams and managers.</p>
                </details>
            </div>
        </div>
    </section>

    {{-- ── Final CTA ────────────────────────────────────────────────── --}}
    <section class="lp-final">
        <div class="lp-wrap">
            <h2 class="lp-h2">Give your team a fairer way to work</h2>
            <p class="lp-sub">Set up your organization in minutes. Free for up to {{ $freeUsers }} people.</p>
            <div class="lp-ctas">
                <a href="{{ route('register') }}" class="lp-btn lp-btn-primary">Start free</a>
                <a href="{{ route('contact') }}" class="lp-btn lp-btn-secondary">Book a demo</a>
            </div>
        </div>
    </section>

</main>
@endsection
