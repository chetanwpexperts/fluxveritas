@extends('layouts.public')
@section('title', 'Documentation — OutraqHQ')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/docs.css') }}">
<script>document.body.classList.add('docs-page');</script>
@endpush

@section('content')

<div id="docs-progress"></div>

<!-- ── Topbar (Full Width Top Header) ── -->
<div id="docs-topbar">
    <div class="topbar-breadcrumb">
        <a href="/" class="topbar-brand">
            <div class="topbar-logo-mark">OQ</div>
            <span class="topbar-logo-text">OutraqHQ</span>
        </a>
        <span>›</span>
        <span id="current-section-label">Documentation</span>
    </div>
    <div class="topbar-actions">
        <a href="{{ route('tour') }}" class="topbar-btn topbar-btn-outline">🎬 Interactive Product Tour</a>
        <a href="{{ route('register') }}" class="topbar-btn topbar-btn-dark">Start Free Trial →</a>
    </div>
</div>

<!-- ── Sidebar Navigation ── -->
<aside id="docs-sidebar">
    <div class="sidebar-search">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#64748b" stroke-width="2">
            <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
        </svg>
        <input type="text" placeholder="Search documentation..." id="sidebar-search">
    </div>

    <nav>
        <div class="nav-group">
            <div class="nav-group-title">Getting Started</div>
            <a href="#overview" class="nav-link active">Platform Overview</a>
            <a href="#onboarding" class="nav-link">60-Sec Interactive Onboarding</a>
            <a href="#non-tech" class="nav-link">Non-Tech Department Support</a>
        </div>
        <div class="nav-group">
            <div class="nav-group-title">Core Systems</div>
            <a href="#telemetry" class="nav-link">5:00 PM Auto-Draft Telemetry</a>
            <a href="#ceo-copilot" class="nav-link">CEO Co-Pilot & Magic Links</a>
            <a href="#fairness" class="nav-link">Z-Score Fairness Engine</a>
            <a href="#outy-ai" class="nav-link">AI Agent Outy Architecture</a>
            <a href="#mini-erp" class="nav-link">Mini ERP Suite</a>
            <a href="#crypto-badge" class="nav-link">Cryptographic Fair Badge</a>
        </div>
        <div class="nav-group">
            <div class="nav-group-title">Role Guides</div>
            <a href="#guide-ceo" class="nav-link">CEO / Owner Guide</a>
            <a href="#guide-manager" class="nav-link">Manager & Lead Guide</a>
            <a href="#guide-hr" class="nav-link">HR Officer Guide</a>
            <a href="#guide-employee" class="nav-link">Employee Guide</a>
        </div>
        <div class="nav-group">
            <div class="nav-group-title">Reference</div>
            <a href="#ref-hubs" class="nav-link">5-Hub Navigation Map</a>
            <a href="#ref-faq" class="nav-link">Frequently Asked Questions</a>
        </div>
    </nav>
</aside>

<!-- ── Main Content Area ── -->
<main id="docs-main">
    <div class="docs-inner">

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- OVERVIEW -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="overview">
        <h1 class="doc-h1">OutraqHQ Platform Documentation</h1>
        <p class="doc-lead">
            OutraqHQ is an Autonomous Work Intelligence and Fair Performance SaaS platform designed to eliminate workplace bias, automate daily work telemetry, and deliver hands-free executive co-piloting.
        </p>

        <div class="callout callout-tip">
            <div class="callout-icon">💡</div>
            <div>
                <strong>Core Philosophy:</strong> Make employee↔organization work 100% transparent. Hard work is recognized and paid fairly based on objective data, completely free from personal or vocal bias.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- INTERACTIVE ONBOARDING -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="onboarding">
        <h2 class="doc-h2">60-Second Interactive Onboarding</h2>
        <p class="doc-p">
            To solve the "Blank Canvas Paradox", every new user is greeted with a 3-step interactive onboarding banner directly on their dashboard:
        </p>
        <ol style="margin-left:20px; line-height:1.8; color:#334155;" class="mb-md">
            <li><strong>Step 1: Check In & Review Assigned Tasks:</strong> View your active task queue.</li>
            <li><strong>Step 2: Auto-Draft Your Work Log at 5:00 PM:</strong> Outy auto-populates your work log based on commits and completed cards.</li>
            <li><strong>Step 3: Meet AI Co-Pilot Outy:</strong> Ask Outy questions in plain English anytime via the bottom-right widget.</li>
        </ol>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- NON-TECH DEPARTMENTS -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="non-tech">
        <h2 class="doc-h2">Non-Tech Department Support</h2>
        <p class="doc-p">
            OutraqHQ works seamlessly for organizations with zero GitHub repositories (e.g. Sales, HR, Marketing, Operations, Design):
        </p>
        <ul style="margin-left:20px; line-height:1.8; color:#334155;">
            <li><strong>Criteria Auto-Scaling:</strong> In <code>IncrementCalculator.php</code>, non-tech designations return <code>-1.0</code> for GitHub criteria. Their performance score is automatically rescaled 100% to Work Logs, Tasks, and Attendance.</li>
            <li><strong>Department Work Modes:</strong> Department work mode can be configured to <code>manual</code>, <code>github</code>, or <code>hybrid</code>.</li>
        </ul>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- TELEMETRY -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="telemetry">
        <h2 class="doc-h2">Zero-Touch 5:00 PM Work Telemetry</h2>
        <p class="doc-p">
            Every day at 5:00 PM IST, the automated safety net job (<code>EmployeeSafetyNetJob.php</code>) runs across all active employees:
        </p>
        <div class="callout callout-info">
            <div class="callout-icon">⚙️</div>
            <div>
                <strong>Autonomous Workflow:</strong> Outy inspects GitHub commits, completed Trello/Jira tasks, and attendance timestamps to auto-draft the employee's daily work log. The employee simply taps 1-Click Approve!
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- CEO COPILOT -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="ceo-copilot">
        <h2 class="doc-h2">Autonomous CEO Co-Pilot & 1-Click Magic Email Actions</h2>
        <p class="doc-p">
            The CEO receives a 30-second morning AI executive digest at 8:00 AM daily with cryptographically signed 1-click magic email action links:
        </p>
        <pre class="code-block">// Example 1-Click Execution Link (Valid for 24 Hours)
http://127.0.0.1:8000/api/action/execute?token=eyJpdiI6...&action=approve_increments</pre>
        <p class="doc-p">
            CEOs can sign off on monthly salary increments or resolve blocker disputes in 1 tap directly from their email inbox without ever logging into the website!
        </p>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- FAIRNESS ENGINE -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="fairness">
        <h2 class="doc-h2">Statistical Z-Score Fairness Engine</h2>
        <p class="doc-p">
            The Fairness Engine calculates objective monthly performance scores ($0-100$) using standard deviation normalization:
        </p>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Weight</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Work Logs Telemetry</strong></td>
                    <td>35%</td>
                    <td>Consistency and completeness of daily work log submissions.</td>
                </tr>
                <tr>
                    <td><strong>Task Completion Rate</strong></td>
                    <td>35%</td>
                    <td>On-time task resolution and sprint velocity.</td>
                </tr>
                <tr>
                    <td><strong>Attendance Normalization</strong></td>
                    <td>15%</td>
                    <td>Clock-in timestamps normalized against department average.</td>
                </tr>
                <tr>
                    <td><strong>GitHub Telemetry</strong></td>
                    <td>15%</td>
                    <td>Commit volume and PR code reviews (Auto-scaled for non-tech roles).</td>
                </tr>
            </tbody>
        </table>
        <div class="callout callout-warn">
            <div class="callout-icon">🛡️</div>
            <div>
                <strong>Victim Protection Adjustment:</strong> If an employee's task is blocked by another department for $>48$ hours, the engine automatically awards a +10% fairness score compensation.
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- AI AGENT OUTY -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="outy-ai">
        <h2 class="doc-h2">AI Agent Outy 3-Tier Architecture</h2>
        <p class="doc-p">
            Outy operates via a 3-tier architecture so it never returns blank or generic responses:
        </p>
        <ul style="margin-left:20px; line-height:1.8; color:#334155;">
            <li><strong>Tier 1 (Instant DB & Knowledge Base):</strong> Direct SQL lookup for direct answers (salary, manager, direct reports, assets, timesheets).</li>
            <li><strong>Tier 2 (Generative RAG AI):</strong> OpenAI GPT-4o / Ollama provider for complex analytical prompts.</li>
            <li><strong>Tier 3 (RuleBased Safety Engine):</strong> Fallback regex intent classifier that guarantees a rich response if AI keys are missing or offline.</li>
        </ul>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- MINI ERP SUITE -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="mini-erp">
        <h2 class="doc-h2">All-in-One Mini ERP Suite</h2>
        <p class="doc-p">
            OutraqHQ includes 4 integrated Mini ERP modules:
        </p>
        <ul style="margin-left:20px; line-height:1.8; color:#334155;">
            <li><strong>Itemized Payroll (<code>/payroll</code>):</strong> Combines Base Salary + OutraqHQ calculated performance increment % into monthly statements.</li>
            <li><strong>Expense Claims (<code>/expenses</code>):</strong> Submit expense receipts for 1-click HR/Manager approval.</li>
            <li><strong>Asset Vault (<code>/assets</code>):</strong> Assign laptops & devices with automated offboarding return checklists.</li>
            <li><strong>Shift Timesheets (<code>/timesheets</code>):</strong> Clock-in / clock-out tracking and shift rosters.</li>
        </ul>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- CRYPTOGRAPHIC BADGE -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="crypto-badge">
        <h2 class="doc-h2">Cryptographic Fair Workplace Verification</h2>
        <p class="doc-p">
            Organizations can generate a cryptographically signed HMAC badge certifying unbiased performance reviews:
        </p>
        <pre class="code-block">Public Verification URL: http://127.0.0.1:8000/verify-fairness/{token}</pre>
        <p class="doc-p">
            Displays your verified Fairness Index ($0-100\%$) and SHA256 audit signature to attract top global talent.
        </p>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- ROLE GUIDES -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="guide-ceo">
        <h2 class="doc-h2">CEO & Executive Leadership Guide</h2>
        <p class="doc-p">
            As a CEO or Owner, OutraqHQ gives you zero-friction visibility without micromanagement:
        </p>
        <ul style="margin-left:20px; line-height:1.8; color:#334155;">
            <li><strong>30-Second Morning AI Digest:</strong> Read daily org health scores, top contributors, and unresolved cross-department blockers.</li>
            <li><strong>1-Click Magic Tokens:</strong> Approve monthly increment pools or resolve blocker disputes directly from email.</li>
            <li><strong>CEO Command Center (<code>/ceo-center</code>):</strong> Simulate salary increment pools and view company-wide performance curves.</li>
        </ul>
    </section>

    <section class="doc-section" id="guide-manager">
        <h2 class="doc-h2">Manager & Team Lead Guide</h2>
        <p class="doc-p">
            Managers get automated contribution tracking and blocker management tools:
        </p>
        <ul style="margin-left:20px; line-height:1.8; color:#334155;">
            <li><strong>Work Log Approvals:</strong> Review 5:00 PM auto-drafted work logs submitted by team members.</li>
            <li><strong>Cross-Department Blockers:</strong> Resolve inter-department delays before they trigger friction alerts.</li>
            <li><strong>Expense Approvals:</strong> Approve team expense claims with 1 click.</li>
        </ul>
    </section>

    <section class="doc-section" id="guide-hr">
        <h2 class="doc-h2">HR Officer Guide</h2>
        <p class="doc-p">
            HR Officers manage employee onboarding, payroll generation, and company hardware assets:
        </p>
        <ul style="margin-left:20px; line-height:1.8; color:#334155;">
            <li><strong>Interactive Onboarding Checklist:</strong> Track new hire 3-step onboarding progress.</li>
            <li><strong>Itemized Payroll Generation:</strong> Generate monthly salary slips combining base pay + increment percentage.</li>
            <li><strong>Hardware Asset Vault:</strong> Assign laptops & monitor offboarding asset return checklists.</li>
        </ul>
    </section>

    <section class="doc-section" id="guide-employee">
        <h2 class="doc-h2">Employee Guide</h2>
        <p class="doc-p">
            Employees enjoy 100% zero manual friction and protected performance reviews:
        </p>
        <ul style="margin-left:20px; line-height:1.8; color:#334155;">
            <li><strong>5:00 PM Auto-Draft Logs:</strong> Simply review and tap 1-Click Approve on your auto-populated daily work log.</li>
            <li><strong>Clock-In Timesheets:</strong> Clock in and out with 1 click.</li>
            <li><strong>Protected Fairness Score:</strong> Your output is evaluated objectively by mathematical Z-score algorithms.</li>
        </ul>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- NAVIGATION HUBS -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="ref-hubs">
        <h2 class="doc-h2">5-Hub Streamlined Navigation</h2>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Hub Name</th>
                    <th>Modules Included</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>1. Workspace</strong></td>
                    <td>Dashboard, Notifications, User Profile</td>
                </tr>
                <tr>
                    <td><strong>2. People & Org</strong></td>
                    <td>Employee Directory, Org Chart, HR Onboarding Pipeline</td>
                </tr>
                <tr>
                    <td><strong>3. Work Telemetry</strong></td>
                    <td>Work Logs, Tasks, Sprints, Blockers, Mini ERP (Payroll, Expenses, Assets, Timesheets)</td>
                </tr>
                <tr>
                    <td><strong>4. Reports & Intel</strong></td>
                    <td>Fairness Engine, AI Intelligence, Performance Reports, Increment Reviews</td>
                </tr>
                <tr>
                    <td><strong>5. Admin & Setup</strong></td>
                    <td>CEO Command Center, Settings, Billing & Modules</td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- FAQ -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <section class="doc-section" id="ref-faq">
        <h2 class="doc-h2">Frequently Asked Questions</h2>
        <h3 class="doc-h3">How does Outy auto-draft work logs without GitHub?</h3>
        <p class="doc-p">Outy inspects completed task cards, timesheet timestamps, and attendance activity to auto-populate work logs for non-tech departments.</p>

        <h3 class="doc-h3">Can CEOs approve monthly salary increments from mobile?</h3>
        <p class="doc-p">Yes! The CEO simply taps the 1-Click Magic Email Execution link in their 30-Second Morning AI Digest.</p>
    </div>
</main>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.doc-section');
    const progressBar = document.getElementById('docs-progress');

    // Scroll spy
    window.addEventListener('scroll', function () {
        const totalHeight = document.body.scrollHeight - window.innerHeight;
        const progress = (window.scrollY / totalHeight) * 100;
        if (progressBar) progressBar.style.width = progress + '%';

        sections.forEach(sec => {
            const rect = sec.getBoundingClientRect();
            if (rect.top <= 140 && rect.bottom >= 140) {
                const id = sec.getAttribute('id');
                navLinks.forEach(l => {
                    l.classList.remove('active');
                    if (l.getAttribute('href') === '#' + id) {
                        l.classList.add('active');
                        const label = document.getElementById('current-section-label');
                        if (label) label.textContent = l.textContent;
                    }
                });
            }
        });
    });

    // Sidebar search filter
    const searchInput = document.getElementById('sidebar-search');
    if (searchInput) {
        searchInput.addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase();
            navLinks.forEach(l => {
                const text = l.textContent.toLowerCase();
                if (text.includes(q)) {
                    l.style.display = 'block';
                } else {
                    l.style.display = 'none';
                }
            });
        });
    }
});
</script>
@endpush
