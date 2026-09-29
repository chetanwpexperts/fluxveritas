@extends('layouts.public')
@section('title', 'Interactive Product Tour — OutraqHQ')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tour.css') }}">
@endpush

@section('content')

<!-- Progress Bar -->
<div id="tour-progress"></div>

<!-- Dot Navigation -->
<div id="tour-dots">
    <button class="tour-dot active" data-section="0" title="Intro"></button>
    <button class="tour-dot" data-section="1" title="The Problem"></button>
    <button class="tour-dot" data-section="2" title="Zero-Touch Telemetry"></button>
    <button class="tour-dot" data-section="3" title="CEO Co-Pilot"></button>
    <button class="tour-dot" data-section="4" title="Fairness Engine"></button>
    <button class="tour-dot" data-section="5" title="AI Agent Outy"></button>
    <button class="tour-dot" data-section="6" title="Mini ERP Suite"></button>
    <button class="tour-dot" data-section="7" title="Cryptographic Badge"></button>
    <button class="tour-dot" data-section="8" title="5-Hub Layout"></button>
    <button class="tour-dot" data-section="9" title="Role Guides"></button>
    <button class="tour-dot" data-section="10" title="Get Started"></button>
</div>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 0 — HERO INTRO (ODD - BLURRED WHITE GRAPHIC BG) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-odd" id="section-0" style="padding-top:140px; padding-bottom:100px;">
    <div class="tour-inner">
        <div class="anim-hidden anim-scale mb-md">
            <div style="width:72px; height:72px; background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.25); border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:1.3rem; font-weight:800; color:#10b981;">OQ</div>
            <div class="tour-chip">INTERACTIVE PRODUCT TOUR</div>
        </div>

        <h1 class="tour-h1 anim-hidden">
            Where Hard Work Is Always Seen. <br><span style="color:#10b981;">Always Protected.</span>
        </h1>
        <p class="tour-lead anim-hidden delay-1" style="margin:0 auto 40px;">
            Experience the future of team intelligence — from zero-touch telemetry and 30-second CEO digests to mathematical fairness and built-in Mini ERP.
        </p>

        <!-- Graphic Mockup: Mac Dashboard Preview -->
        <div class="anim-hidden delay-2" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; box-shadow:0 20px 50px -10px rgba(15,23,42,0.1); overflow:hidden; max-width:880px; margin:0 auto 36px; text-align:left;">
            <div style="background:#f8fafc; padding:14px 20px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:8px;">
                <span style="width:12px; height:12px; border-radius:50%; background:#ef4444; display:inline-block;"></span>
                <span style="width:12px; height:12px; border-radius:50%; background:#f59e0b; display:inline-block;"></span>
                <span style="width:12px; height:12px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                <span style="font-size:0.75rem; font-weight:700; color:#64748b; margin-left:12px;">OutraqHQ — Autonomous Workspace Intelligence</span>
            </div>
            <div style="padding:28px; display:grid; grid-template-columns:2fr 1fr; gap:20px; background:#ffffff;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:18px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                        <span style="font-size:0.8rem; font-weight:800; color:#0f172a;">⚡ Daily Telemetry Feed</span>
                        <span style="font-size:0.7rem; font-weight:800; color:#065f46; background:#d1fae5; padding:3px 10px; border-radius:12px;">AUTO-DRAFTED</span>
                    </div>
                    <div style="font-size:0.85rem; color:#334155; margin-bottom:14px; line-height:1.6;">
                        • <strong>Commit #8401:</strong> Implemented Z-Score Fairness Calculator<br>
                        • <strong>Task Done:</strong> Integrated Stripe Payment Gateways<br>
                        • <strong>Timesheet:</strong> Clocked in at 09:00 AM IST (8.2h Logged)
                    </div>
                    <div style="background:#10b981; color:white; font-size:0.78rem; font-weight:800; padding:8px 16px; border-radius:8px; display:inline-block;">1-Click Confirm Log ✓</div>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:18px; text-align:center; display:flex; flex-direction:column; justify-content:center;">
                    <div style="font-size:0.72rem; font-weight:800; color:#64748b; margin-bottom:8px;">MONTHLY FAIRNESS SCORE</div>
                    <div style="font-size:2.6rem; font-weight:800; color:#10b981; line-height:1;">96.8</div>
                    <div style="font-size:0.75rem; color:#059669; font-weight:700; margin-top:6px;">+14.2% Recommended Raise</div>
                </div>
            </div>
        </div>

        <div class="anim-hidden delay-3">
            <a href="#section-1" class="tour-btn-cta">Explore Interactive Tour ↓</a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 1 — THE PROBLEM (EVEN - BLURRED SLATE GRAPHIC BG) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-even" id="section-1">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip" style="background:#fee2e2; color:#dc2626; border-color:#fecaca;">Chapter 1</div>
            <h2 class="tour-h2">The <span style="color:#dc2626;">Workplace Crisis</span> OutraqHQ Solves</h2>
            <p class="tour-lead" style="margin:0 auto;">Traditional performance management is broken by bias, manual friction, and filtered data.</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:24px;">
            <div class="tour-card anim-hidden" style="border-top:3px solid #dc2626;">
                <div style="width:48px; height:48px; background:#fee2e2; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:16px;">😤</div>
                <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Vocal Favoritism Over Output</h3>
                <p style="font-size:0.9rem; color:#64748b; line-height:1.6;">Loud employees get promoted, while quiet, introverted high-performers go unrecognized.</p>
                <div style="margin-top:16px; background:#fef2f2; border:1px solid #fecaca; padding:8px 12px; border-radius:8px; font-size:0.78rem; color:#dc2626; font-weight:700;">
                    ❌ Sarah does 3x work, Alex takes credit
                </div>
            </div>
            <div class="tour-card anim-hidden delay-1" style="border-top:3px solid #b45309;">
                <div style="width:48px; height:48px; background:#fef3c7; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:16px;">🎭</div>
                <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Sanitized Executive Reports</h3>
                <p style="font-size:0.9rem; color:#64748b; line-height:1.6;">By the time data reaches the CEO, it has been filtered through 3 management layers.</p>
                <div style="margin-top:16px; background:#fffbeb; border:1px solid #fde68a; padding:8px 12px; border-radius:8px; font-size:0.78rem; color:#b45309; font-weight:700;">
                    ❌ CEO sees reports, not reality
                </div>
            </div>
            <div class="tour-card anim-hidden delay-2" style="border-top:3px solid #dc2626;">
                <div style="width:48px; height:48px; background:#fee2e2; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:16px;">📑</div>
                <h3 style="font-size:1.1rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Tedious Manual Data Entry</h3>
                <p style="font-size:0.9rem; color:#64748b; line-height:1.6;">Employees waste hours typing manual work logs and updating fragmented tracking sheets.</p>
                <div style="margin-top:16px; background:#fef2f2; border:1px solid #fecaca; padding:8px 12px; border-radius:8px; font-size:0.78rem; color:#dc2626; font-weight:700;">
                    ❌ Hours wasted on manual spreadsheets
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 2 — ZERO-TOUCH TELEMETRY (ODD) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-odd" id="section-2">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 2</div>
            <h2 class="tour-h2">100% Zero-Touch <span style="color:#10b981;">Work Telemetry</span></h2>
            <p class="tour-lead" style="margin:0 auto;">No manual effort required. Outy auto-drafts daily work logs based on real contribution telemetry.</p>
        </div>

        <!-- Visual Telemetry Flowchart Card -->
        <div class="anim-hidden mb-lg" style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:32px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.06); max-width:900px; margin:0 auto 36px;">
            <div style="font-size:0.85rem; font-weight:800; color:#0f172a; text-transform:uppercase; letter-spacing:1px; margin-bottom:24px; text-align:center;">
                ⚙️ Autonomous Telemetry Pipeline Flowchart
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; text-align:center;">
                <div style="flex:1; min-width:150px; background:#f8fafc; border:1px solid #e2e8f0; padding:20px; border-radius:14px;">
                    <div style="font-size:1.8rem; margin-bottom:6px;">💻</div>
                    <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">GitHub & Tasks</div>
                    <div style="font-size:0.72rem; color:#64748b; margin-top:2px;">Commits & PRs</div>
                </div>
                <div style="color:#10b981; font-weight:800; font-size:1.5rem;">➔</div>
                <div style="flex:1; min-width:150px; background:#d1fae5; border:1px solid #6ee7b7; padding:20px; border-radius:14px;">
                    <div style="font-size:1.8rem; margin-bottom:6px;">🤖</div>
                    <div style="font-size:0.85rem; font-weight:800; color:#065f46;">Outy 5:00 PM Engine</div>
                    <div style="font-size:0.72rem; color:#047857; margin-top:2px;">Auto-Drafting</div>
                </div>
                <div style="color:#10b981; font-weight:800; font-size:1.5rem;">➔</div>
                <div style="flex:1; min-width:150px; background:#f8fafc; border:1px solid #e2e8f0; padding:20px; border-radius:14px;">
                    <div style="font-size:1.8rem; margin-bottom:6px;">✅</div>
                    <div style="font-size:0.85rem; font-weight:800; color:#0f172a;">1-Tap Confirmed Log</div>
                    <div style="font-size:0.72rem; color:#64748b; margin-top:2px;">Fairness Scoring</div>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px; text-align:left;">
            <div class="tour-card anim-hidden">
                <div style="background:#d1fae5; color:#065f46; font-weight:800; padding:4px 10px; border-radius:6px; font-size:0.75rem; display:inline-block;">5:00 PM IST ENGINE</div>
                <h3 style="font-size:1.1rem; font-weight:800; margin:12px 0 8px;">Auto-Drafted Work Logs</h3>
                <p style="font-size:0.88rem; color:#64748b; line-height:1.6;">At 5:00 PM daily, Outy analyzes contribution activity to auto-populate employee work logs for 1-tap approval.</p>
            </div>
            <div class="tour-card anim-hidden delay-1">
                <div style="background:#e0e7ff; color:#4338ca; font-weight:800; padding:4px 10px; border-radius:6px; font-size:0.75rem; display:inline-block;">NON-TECH TEAMS</div>
                <h3 style="font-size:1.1rem; font-weight:800; margin:12px 0 8px;">Criteria Auto-Scaling</h3>
                <p style="font-size:0.88rem; color:#64748b; line-height:1.6;">Departments without GitHub repositories (Sales, HR, Marketing) automatically scale 100% of their performance score to Work Logs, Tasks, and Attendance.</p>
            </div>
            <div class="tour-card anim-hidden delay-2">
                <div style="background:#fef3c7; color:#b45309; font-weight:800; padding:4px 10px; border-radius:6px; font-size:0.75rem; display:inline-block;">ONBOARDING</div>
                <h3 style="font-size:1.1rem; font-weight:800; margin:12px 0 8px;">Interactive 60s Checklist</h3>
                <p style="font-size:0.88rem; color:#64748b; line-height:1.6;">New employees get an interactive 3-step banner on their dashboard so they know exactly how the system works on day one.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 3 — CEO CO-PILOT (EVEN) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-even" id="section-3">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 3</div>
            <h2 class="tour-h2">Autonomous <span style="color:#10b981;">CEO Co-Pilot</span></h2>
            <p class="tour-lead" style="margin:0 auto;">30-second morning AI executive digest delivered to the CEO with 1-click magic email execution links.</p>
        </div>

        <!-- Dashboard Card: Email Digest -->
        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:20px; padding:32px; box-shadow:0 15px 35px rgba(0,0,0,0.06); text-align:left; max-width:850px; margin:0 auto;" class="anim-hidden">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:16px; margin-bottom:20px;">
                <div>
                    <div style="font-weight:800; color:#0f172a; font-size:1.1rem;">📬 30-Second Morning AI Executive Digest</div>
                    <div style="font-size:0.8rem; color:#64748b;">Delivered Daily at 8:00 AM IST to CEO Inbox</div>
                </div>
                <span style="background:#d1fae5; color:#065f46; font-weight:800; font-size:0.75rem; padding:4px 12px; border-radius:20px;">Org Health: 94/100</span>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; margin-bottom:24px;">
                <div style="background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">
                    <div style="font-size:0.75rem; color:#64748b; font-weight:700;">TOP CONTRIBUTOR</div>
                    <div style="font-weight:800; color:#0f172a; margin-top:4px;">Sarah Connor (Engineering)</div>
                </div>
                <div style="background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">
                    <div style="font-size:0.75rem; color:#64748b; font-weight:700;">ESCALATED BLOCKER</div>
                    <div style="font-weight:800; color:#dc2626; margin-top:4px;">Stalled Server API (48h)</div>
                </div>
                <div style="background:#f8fafc; padding:16px; border-radius:12px; border:1px solid #e2e8f0;">
                    <div style="font-size:0.75rem; color:#64748b; font-weight:700;">RECOMMENDED INC. POOL</div>
                    <div style="font-weight:800; color:#10b981; margin-top:4px;">+12.4% Avg Raise</div>
                </div>
            </div>

            <div style="background:#ecfdf5; border:1px solid #a7f3d0; padding:16px; border-radius:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <span style="font-size:0.88rem; color:#065f46; font-weight:600;">⚡ 1-Click Magic Action Token: Approve Monthly Increments without logging into web portal.</span>
                <span style="background:#10b981; color:white; font-weight:800; padding:8px 16px; border-radius:8px; font-size:0.82rem; cursor:pointer;">1-Click Sign Off →</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 4 — FAIRNESS ENGINE (ODD) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-odd" id="section-4">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 4</div>
            <h2 class="tour-h2">Statistical Z-Score <span style="color:#10b981;">Fairness Engine</span></h2>
            <p class="tour-lead" style="margin:0 auto;">Mathematical evaluation that detects favoritism, manager bias, and protects victimized employees.</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px; text-align:left;">
            <div class="tour-card anim-hidden">
                <div style="font-size:1.8rem; margin-bottom:12px;">📐</div>
                <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:8px;">Z-Score Normalization</h3>
                <p style="font-size:0.88rem; color:#64748b; line-height:1.6;">Normalizes performance scores relative to department standard deviation, eliminating harsh vs lenient manager rating biases.</p>
            </div>
            <div class="tour-card anim-hidden delay-1">
                <div style="font-size:1.8rem; margin-bottom:12px;">🛡️</div>
                <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:8px;">+10% Victim Protection</h3>
                <p style="font-size:0.88rem; color:#64748b; line-height:1.6;">If an employee's task is blocked by another department for $>48$ hours, the engine automatically grants a +10% fairness score compensation.</p>
            </div>
            <div class="tour-card anim-hidden delay-2">
                <div style="font-size:1.8rem; margin-bottom:12px;">🚨</div>
                <h3 style="font-size:1.1rem; font-weight:800; margin-bottom:8px;">Bias Anomaly Alerts</h3>
                <p style="font-size:0.88rem; color:#64748b; line-height:1.6;">Flag managers whose subjective feedback rating deviates significantly ($>1.5\sigma$) from objective telemetry data.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 5 — AI AGENT OUTY (EVEN) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-even" id="section-5">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 5</div>
            <h2 class="tour-h2">AI Agent <span style="color:#10b981;">Outy Co-Pilot</span></h2>
            <p class="tour-lead" style="margin:0 auto;">3-Tier Intelligence Architecture so Outy never returns blank responses or generic errors.</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; text-align:left;">
            <div class="tour-card anim-hidden">
                <div style="font-size:0.75rem; font-weight:800; color:#2563eb; text-transform:uppercase;">Tier 1 Engine</div>
                <h3 style="font-size:1rem; font-weight:800; margin:8px 0 4px;">Instant Knowledge Base</h3>
                <p style="font-size:0.85rem; color:#64748b;">Direct database and documentation query resolution without API cost.</p>
            </div>
            <div class="tour-card anim-hidden delay-1">
                <div style="font-size:0.75rem; font-weight:800; color:#7c3aed; text-transform:uppercase;">Tier 2 Engine</div>
                <h3 style="font-size:1rem; font-weight:800; margin:8px 0 4px;">Generative RAG AI</h3>
                <p style="font-size:0.85rem; color:#64748b;">OpenAI GPT-4o / Ollama provider for deep analytical answers.</p>
            </div>
            <div class="tour-card anim-hidden delay-2">
                <div style="font-size:0.75rem; font-weight:800; color:#059669; text-transform:uppercase;">Tier 3 Engine</div>
                <h3 style="font-size:1rem; font-weight:800; margin:8px 0 4px;">RuleBased Safety Net</h3>
                <p style="font-size:0.85rem; color:#64748b;">Guarantees rich responses even if OpenAI keys are offline or missing.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 6 — MINI ERP SUITE (ODD) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-odd" id="section-6">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 6</div>
            <h2 class="tour-h2">All-in-One <span style="color:#10b981;">Mini ERP Suite</span></h2>
            <p class="tour-lead" style="margin:0 auto;">Replaces fragmented office software with built-in Payroll, Expenses, Assets, and Timesheets.</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; text-align:left;">
            <div class="tour-card anim-hidden" style="border-top:3px solid #10b981;">
                <div style="font-size:1.8rem; margin-bottom:8px;">💵</div>
                <h3 style="font-size:1rem; font-weight:800; margin-bottom:6px;">Itemized Payroll</h3>
                <p style="font-size:0.85rem; color:#64748b;">Combines base salary + calculated increment % automatically.</p>
            </div>
            <div class="tour-card anim-hidden delay-1" style="border-top:3px solid #f59e0b;">
                <div style="font-size:1.8rem; margin-bottom:8px;">💳</div>
                <h3 style="font-size:1rem; font-weight:800; margin-bottom:6px;">Expense Claims</h3>
                <p style="font-size:0.85rem; color:#64748b;">Submit expense receipts for 1-click HR/Manager approvals.</p>
            </div>
            <div class="tour-card anim-hidden delay-2" style="border-top:3px solid #6366f1;">
                <div style="font-size:1.8rem; margin-bottom:8px;">💻</div>
                <h3 style="font-size:1rem; font-weight:800; margin-bottom:6px;">Asset Device Vault</h3>
                <p style="font-size:0.85rem; color:#64748b;">Track laptops & hardware with offboarding return checklists.</p>
            </div>
            <div class="tour-card anim-hidden delay-3" style="border-top:3px solid #0284c7;">
                <div style="font-size:1.8rem; margin-bottom:8px;">⏱️</div>
                <h3 style="font-size:1rem; font-weight:800; margin-bottom:6px;">Shift Timesheets</h3>
                <p style="font-size:0.85rem; color:#64748b;">Clock-in/out tracking and flexible shift roster management.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 7 — CRYPTOGRAPHIC BADGE (EVEN) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-even" id="section-7">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 7</div>
            <h2 class="tour-h2">Cryptographic <span style="color:#10b981;">Fair Workplace Badge</span></h2>
            <p class="tour-lead" style="margin:0 auto;">Publicly verifiable proof that 100% of performance reviews and salary increments are unbiased.</p>
        </div>

        <div style="background:#ffffff; border:2px solid #10b981; border-radius:20px; padding:32px; max-width:600px; margin:0 auto; box-shadow:0 10px 25px -5px rgba(16,185,129,0.15);" class="anim-hidden">
            <span style="background:#d1fae5; color:#065f46; font-weight:800; font-size:0.75rem; padding:4px 12px; border-radius:20px; display:inline-block; margin-bottom:12px;">PUBLIC AUDIT VERIFIED ✅</span>
            <h3 style="font-size:1.3rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Certified Unbiased Workplace</h3>
            <p style="font-size:0.88rem; color:#64748b; margin-bottom:20px;">Job applicants can verify your organization's mathematical fairness score live at <code>/verify-fairness/{token}</code>.</p>
            <div style="background:#f8fafc; padding:12px; border-radius:8px; font-family:monospace; font-size:0.75rem; color:#475569; word-break:break-all;">
                SHA256: e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 8 — 5-HUB LAYOUT (ODD) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-odd" id="section-8">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 8</div>
            <h2 class="tour-h2">Streamlined <span style="color:#10b981;">5-Hub Sidebar Layout</span></h2>
            <p class="tour-lead" style="margin:0 auto;">Reorganized from 22 scattered items into 5 clean, intuitive navigation hubs.</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; text-align:left;">
            <div class="tour-card anim-hidden">
                <div style="font-weight:800; color:#0f172a; margin-bottom:4px;">1. Workspace</div>
                <div style="font-size:0.8rem; color:#64748b;">Dashboard, Notifications, Profile</div>
            </div>
            <div class="tour-card anim-hidden delay-1">
                <div style="font-weight:800; color:#0f172a; margin-bottom:4px;">2. People & Org</div>
                <div style="font-size:0.8rem; color:#64748b;">Directory, Org Chart, HR Pipeline</div>
            </div>
            <div class="tour-card anim-hidden delay-2">
                <div style="font-weight:800; color:#0f172a; margin-bottom:4px;">3. Work Telemetry</div>
                <div style="font-size:0.8rem; color:#64748b;">Work Logs, Tasks, Blockers, Mini ERP</div>
            </div>
            <div class="tour-card anim-hidden delay-3">
                <div style="font-weight:800; color:#0f172a; margin-bottom:4px;">4. Reports & Intel</div>
                <div style="font-size:0.8rem; color:#64748b;">Fairness, AI Intel, Increments</div>
            </div>
            <div class="tour-card anim-hidden delay-4">
                <div style="font-weight:800; color:#0f172a; margin-bottom:4px;">5. Admin & Setup</div>
                <div style="font-size:0.8rem; color:#64748b;">CEO Center, Billing, Settings</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 9 — ROLE GUIDES (EVEN) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-even" id="section-9">
    <div class="tour-inner">
        <div style="margin-bottom:40px;">
            <div class="tour-chip">Chapter 9</div>
            <h2 class="tour-h2">Tailored Workflows for <span style="color:#10b981;">Every Role</span></h2>
            <p class="tour-lead" style="margin:0 auto;">Dedicated experiences for CEOs, Managers, HR Officers, and Employees.</p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:20px; text-align:left;">
            <div class="tour-card anim-hidden">
                <div style="font-weight:800; font-size:1.05rem; color:#0f172a; margin-bottom:6px;">👔 Owner / CEO</div>
                <p style="font-size:0.85rem; color:#64748b; line-height:1.5;">30-second morning digests, 1-click email magic links, and budget simulator.</p>
            </div>
            <div class="tour-card anim-hidden delay-1">
                <div style="font-weight:800; font-size:1.05rem; color:#0f172a; margin-bottom:6px;">📈 Managers & Leads</div>
                <p style="font-size:0.85rem; color:#64748b; line-height:1.5;">Team contribution overview, blocker routing, and expense approvals.</p>
            </div>
            <div class="tour-card anim-hidden delay-2">
                <div style="font-weight:800; font-size:1.05rem; color:#0f172a; margin-bottom:6px;">📋 HR Officers</div>
                <p style="font-size:0.85rem; color:#64748b; line-height:1.5;">Onboarding pipeline, asset vault management, and payroll processing.</p>
            </div>
            <div class="tour-card anim-hidden delay-3">
                <div style="font-weight:800; font-size:1.05rem; color:#0f172a; margin-bottom:6px;">💻 Employees</div>
                <p style="font-size:0.85rem; color:#64748b; line-height:1.5;">Auto-drafted 5:00 PM work logs, task tracking, and fair score visibility.</p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SECTION 10 — GET STARTED (ODD - BLURRED GRAPHIC CTA) -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="tour-section tour-section-odd" id="section-10" style="padding:100px 24px;">
    <div class="tour-inner">
        <div class="anim-hidden anim-scale mb-md">
            <h2 class="tour-h1">Ready to Launch Your <span style="color:#10b981;">Workplace Telemetry?</span></h2>
            <p class="tour-lead" style="margin:0 auto 36px;">Join forward-thinking companies using OutraqHQ for zero-touch work tracking and fair compensation.</p>
            <div style="display:flex; justify-content:center; gap:16px; flex-wrap:wrap;">
                <a href="{{ route('register') }}" class="tour-btn-cta">Start Free Trial →</a>
                <a href="{{ route('docs') }}" style="background:#f1f5f9; color:#0f172a; padding:14px 32px; border-radius:99px; font-weight:800; font-size:0.95rem; text-decoration:none; border:1px solid #cbd5e1; display:inline-block;">Read Documentation</a>
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sections = document.querySelectorAll('.tour-section');
    const dots = document.querySelectorAll('.tour-dot');
    const progressBar = document.getElementById('tour-progress');

    window.addEventListener('scroll', function () {
        const totalHeight = document.body.scrollHeight - window.innerHeight;
        const progress = (window.scrollY / totalHeight) * 100;
        if (progressBar) progressBar.style.width = progress + '%';

        sections.forEach((sec, idx) => {
            const rect = sec.getBoundingClientRect();
            if (rect.top <= window.innerHeight / 2 && rect.bottom >= window.innerHeight / 2) {
                dots.forEach(d => d.classList.remove('active'));
                if (dots[idx]) dots[idx].classList.add('active');
            }
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.anim-hidden').forEach(el => observer.observe(el));

    dots.forEach((dot, idx) => {
        dot.addEventListener('click', function () {
            const targetSection = document.getElementById('section-' + idx);
            if (targetSection) targetSection.scrollIntoView({ behavior: 'smooth' });
        });
    });
});
</script>
@endpush
