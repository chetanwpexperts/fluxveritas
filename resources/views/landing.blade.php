<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OutraqHQ — AI-Powered Performance Intelligence Platform</title>
    <meta name="description" content="OutraqHQ is an AI-powered team intelligence platform that makes contributions visible, calculates fair increments from real work data, and gives every employee a clear voice — across every department.">
    <meta name="keywords" content="AI performance management, employee performance tracking, fairness engine, increment management, HR software, bias detection, team performance platform">
    <meta property="og:title" content="OutraqHQ — AI-Powered Performance Intelligence Platform">
    <meta property="og:description" content="Track performance, detect bias, and reward hard work automatically with OutraqHQ.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://outraqhq.com">
    <link rel="canonical" href="https://outraqhq.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.github.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;">
</head>
<body>

{{-- NAVBAR --}}
<nav class="lnav" id="lnav">
    <a href="/" class="lnav-logo">
        <div class="lnav-mark">OQ</div>
        <span class="lnav-name">OutraqHQ</span>
    </a>
    <div class="lnav-links">
        <a href="#features"  class="lnav-link">Features</a>
        <a href="/tour"      class="lnav-link">Tour</a>
        <a href="#pricing"   class="lnav-link">Pricing</a>
        <a href="/docs"      class="lnav-link">Docs</a>
    </div>
    <div class="lnav-actions">
        <a href="/login"    class="lbtn-ghost">Log in</a>
        <a href="/register" class="lbtn-em">Get Started Free</a>
    </div>
    <button class="lnav-ham" onclick="toggleMobileMenu()" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
</nav>
<div class="lnav-mobile" id="lnav-mobile">
    <a href="#features">Features</a>
    <a href="/tour">Tour</a>
    <a href="#pricing">Pricing</a>
    <a href="/docs">Docs</a>
    <div class="lnav-mobile-btns">
        <a href="/login"    class="lbtn-ghost">Log in</a>
        <a href="/register" class="lbtn-em">Get Started Free</a>
    </div>
</div>

{{-- HERO --}}
<section class="lhero">
<div class="lhero-inner">

    {{-- Floating department icon boxes — positioned inside 1200px container --}}
    <div class="lhero-icon" style="top:100px;left:0px;animation-delay:0s;">
        <span class="lic-e">🔧</span><span class="lic-l">Engineering</span>
    </div>
    <div class="lhero-icon" style="top:300px;left:20px;animation-delay:1s;">
        <span class="lic-e">🧠</span><span class="lic-l">HR</span>
    </div>
    <div class="lhero-icon" style="top:480px;left:0px;animation-delay:2s;">
        <span class="lic-e">⚙️</span><span class="lic-l">Operations</span>
    </div>
    <div class="lhero-icon" style="top:640px;left:40px;animation-delay:1.3s;">
        <span class="lic-e">🤖</span><span class="lic-l">AI Agent</span>
    </div>
    <div class="lhero-icon" style="top:100px;right:0px;animation-delay:0.5s;">
        <span class="lic-e">💼</span><span class="lic-l">Sales</span>
    </div>
    <div class="lhero-icon" style="top:300px;right:10px;animation-delay:1.5s;">
        <span class="lic-e">💰</span><span class="lic-l">Finance</span>
    </div>
    <div class="lhero-icon" style="top:490px;right:0px;animation-delay:0.8s;">
        <span class="lic-e">📊</span><span class="lic-l">Analytics</span>
    </div>
    <div class="lhero-icon" style="top:660px;right:30px;animation-delay:0.3s;">
        <span class="lic-e">🛡️</span><span class="lic-l">Fairness</span>
    </div>

    <div class="lhero-content">
        <div class="lhero-badge">🛡️ AI-Powered Human Intelligence Platform</div>

        <h1 class="lhero-h1">
            Where Hard Work Is<br>
            Always Seen. Always <span class="lgrad">Protected.</span>
        </h1>

        <p class="lhero-sub">
            OutraqHQ makes every contribution visible, calculates fair increments
            from real work data, and gives every employee a clear voice — powered
            by AI that works alongside your team.
        </p>

        <div class="lhero-ctas">
            <a href="/register" class="lbtn-hero">Start Free Trial →</a>
            <a href="/tour" class="lbtn-hero-ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                Watch Tour
            </a>
        </div>
        <p class="lhero-fine">No credit card required · Setup in 5 minutes</p>
    </div>

    {{-- Dashboard Mockup --}}
    <div class="lmockup lanim">
        <div class="lmock-bar">
            <div class="ldot ldot-r"></div>
            <div class="ldot ldot-y"></div>
            <div class="ldot ldot-g"></div>
            <span class="lmock-ttl">OutraqHQ — Team Overview</span>
        </div>
        <div class="lmock-body">
            <div class="lmock-left">
                <div class="lmock-stats">
                    <div class="lmock-stat">
                        <div class="lmock-stat-val">142</div>
                        <div class="lmock-stat-lbl">Total Tasks</div>
                    </div>
                    <div class="lmock-stat">
                        <div class="lmock-stat-val em">94%</div>
                        <div class="lmock-stat-lbl">Fairness Score</div>
                    </div>
                    <div class="lmock-stat">
                        <div class="lmock-stat-val in">2</div>
                        <div class="lmock-stat-lbl">AI Alerts</div>
                    </div>
                </div>
                <div class="lmock-chart-wrap">
                    <div class="lmock-chart-ttl">Output by Department</div>
                    <div class="lmock-bar-row">
                        <span class="lmock-bar-lbl">Engineering</span>
                        <div class="lmock-bar-track"><div class="lmock-bar-fill" style="width:84%;background:#10b981;"></div></div>
                        <span style="font-size:.65rem;color:var(--text-3);min-width:28px;text-align:right;">84%</span>
                    </div>
                    <div class="lmock-bar-row">
                        <span class="lmock-bar-lbl">Sales</span>
                        <div class="lmock-bar-track"><div class="lmock-bar-fill" style="width:71%;background:#6366f1;"></div></div>
                        <span style="font-size:.65rem;color:var(--text-3);min-width:28px;text-align:right;">71%</span>
                    </div>
                    <div class="lmock-bar-row">
                        <span class="lmock-bar-lbl">HR</span>
                        <div class="lmock-bar-track"><div class="lmock-bar-fill" style="width:67%;background:#f59e0b;"></div></div>
                        <span style="font-size:.65rem;color:var(--text-3);min-width:28px;text-align:right;">67%</span>
                    </div>
                    <div class="lmock-bar-row">
                        <span class="lmock-bar-lbl">Finance</span>
                        <div class="lmock-bar-track"><div class="lmock-bar-fill" style="width:79%;background:#ec4899;"></div></div>
                        <span style="font-size:.65rem;color:var(--text-3);min-width:28px;text-align:right;">79%</span>
                    </div>
                </div>
            </div>
            <div class="lmock-right">
                <div class="lmock-feed-ttl">Recent Activity</div>
                <div class="lmock-feed-item">
                    <div class="lmock-feed-dot" style="background:#10b981;"></div>
                    <div>
                        <div class="lmock-feed-text">Engineering completed Sprint #4 ahead of schedule</div>
                        <div class="lmock-feed-time">3 minutes ago</div>
                    </div>
                </div>
                <div class="lmock-feed-item">
                    <div class="lmock-feed-dot" style="background:#f59e0b;"></div>
                    <div>
                        <div class="lmock-feed-text">AI Agent surfaced a workload imbalance to review</div>
                        <div class="lmock-feed-time">1 hour ago</div>
                    </div>
                </div>
                <div class="lmock-feed-item">
                    <div class="lmock-feed-dot" style="background:#6366f1;"></div>
                    <div>
                        <div class="lmock-feed-text">Increment calculated for Q1 · 18 employees</div>
                        <div class="lmock-feed-time">2 hours ago</div>
                    </div>
                </div>
                <div class="lmock-feed-item">
                    <div class="lmock-feed-dot" style="background:#10b981;"></div>
                    <div>
                        <div class="lmock-feed-text">Fairness check complete — all departments balanced</div>
                        <div class="lmock-feed-time">4 hours ago</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /.lhero-inner --}}
</section>

{{-- UPGRADE 3: SECURITY / TRUST SECTION --}}
<section class="lsec-trust lanim">
    <div class="ltrust-inner">
        <span class="ltrust-label">Security</span>
        <div class="ltrust-grid">
            <div class="ltrust-card">
                <div class="ltrust-ico">🏠</div>
                <div class="ltrust-ttl">Your data, only yours</div>
                <p class="ltrust-txt">All integrations and data in OutraqHQ are visible only to your organization. Complete data isolation per tenant.</p>
            </div>
            <div class="ltrust-card">
                <div class="ltrust-ico">🔒</div>
                <div class="ltrust-ttl">Secure by design</div>
                <p class="ltrust-txt">All data is transmitted securely over encrypted HTTPS connections, and every organization's data is fully isolated from others.</p>
            </div>
            <div class="ltrust-card">
                <div class="ltrust-ico">🔌</div>
                <div class="ltrust-ttl">Revoke anytime</div>
                <p class="ltrust-txt">Remove any integration or team member instantly. All associated data is purged immediately. Your control, always.</p>
            </div>
        </div>
    </div>
</section>

{{-- FEATURE: WORK TRACKING --}}
<section id="features" style="padding:0;border-top:1px solid #1c1c1e;">
    <div class="lfeat lsec">
        <div class="lfeat-grid lanim" style="padding:0;">
            <div class="lfeat-col">
                <p class="lfeat-lbl">Work Tracking</p>
                <h2 class="lfeat-h">Finally, a task manager built for every department — not just developers.</h2>
                <p class="lfeat-sub">Log daily work, manage sprints, track blockers, and see real output across Engineering, Sales, HR, Finance and Operations — all in one place. No more invisible work.</p>
            </div>
            <div class="lpanel">
                <div class="lpanel-bar">
                    <div class="ldot ldot-r"></div><div class="ldot ldot-y"></div><div class="ldot ldot-g"></div>
                    <span class="lpanel-ttl">Task Board — Sprint #12</span>
                </div>
                <div class="lpanel-body">
                    <div class="lkanban">
                        <div>
                            <div class="lkancol-hd">To Do <span class="lkancol-cnt">3</span></div>
                            <div class="lkan-card">
                                <div class="lkan-card-ttl">Fix payment API endpoint</div>
                                <div class="lkan-card-foot">
                                    <div class="lkan-avatar">AK</div>
                                    <span class="lkan-pri high">High</span>
                                </div>
                            </div>
                            <div class="lkan-card">
                                <div class="lkan-card-ttl">Review Q2 sales targets</div>
                                <div class="lkan-card-foot">
                                    <div class="lkan-avatar">RS</div>
                                    <span class="lkan-pri med">Med</span>
                                </div>
                            </div>
                            <div class="lkan-card">
                                <div class="lkan-card-ttl">Onboard new hire</div>
                                <div class="lkan-card-foot">
                                    <div class="lkan-avatar">PP</div>
                                    <span class="lkan-pri low">Low</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="lkancol-hd" style="color:#f59e0b;">In Progress <span class="lkancol-cnt">2</span></div>
                            <div class="lkan-card" style="border-color:#f59e0b;border-left-width:2px;">
                                <div class="lkan-card-ttl">Deploy auth module to staging</div>
                                <div class="lkan-card-foot">
                                    <div class="lkan-avatar" style="background:#f59e0b;color:#fff;">SC</div>
                                    <span class="lkan-pri high">High</span>
                                </div>
                            </div>
                            <div class="lkan-card" style="border-color:#f59e0b;border-left-width:2px;">
                                <div class="lkan-card-ttl">Update HR policy document</div>
                                <div class="lkan-card-foot">
                                    <div class="lkan-avatar">MR</div>
                                    <span class="lkan-pri med">Med</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="lkancol-hd" style="color:var(--accent);">Done <span class="lkancol-cnt">4</span></div>
                            <div class="lkan-card" style="border-color:var(--accent);border-left-width:2px;opacity:.75;">
                                <div class="lkan-card-ttl" style="text-decoration:line-through;opacity:.7;">Design system update</div>
                                <div class="lkan-card-foot">
                                    <div class="lkan-avatar" style="background:var(--accent);color:#fff;">TK</div>
                                    <span class="lkan-pri low">Done</span>
                                </div>
                            </div>
                            <div class="lkan-card" style="border-color:var(--accent);border-left-width:2px;opacity:.75;">
                                <div class="lkan-card-ttl" style="text-decoration:line-through;opacity:.7;">Q1 team sync meeting</div>
                                <div class="lkan-card-foot">
                                    <div class="lkan-avatar" style="background:var(--accent);color:#fff;">JP</div>
                                    <span class="lkan-pri low">Done</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="ldiv"></div>

{{-- FEATURE: FAIRNESS ENGINE --}}
<section style="padding:0;">
    <div class="lfeat lsec">
        <div class="lfeat-grid rev lanim" style="padding:0;">
            <div class="lfeat-col">
                <p class="lfeat-lbl">Fairness Engine</p>
                <h2 class="lfeat-h">5-layer AI bias detection that keeps your workplace truly fair.</h2>
                <p class="lfeat-sub">Our Fairness Engine monitors task distribution, workload balance, promotion patterns, and manager decisions — flagging bias before it damages your team culture and morale.</p>
            </div>
            <div class="lpanel">
                <div class="lpanel-bar">
                    <div class="ldot ldot-r"></div><div class="ldot ldot-y"></div><div class="ldot ldot-g"></div>
                    <span class="lpanel-ttl">Fairness Analysis — May 2025</span>
                </div>
                <div class="lpanel-body">
                    <div class="lfair-circle-wrap">
                        <div class="lfair-circle">
                            <div class="lfair-inner">
                                <div class="lfair-num">94</div>
                                <div class="lfair-num-lbl">Score</div>
                            </div>
                        </div>
                        <div class="lfair-score-lbl" class="mt-sm">Fairness Score</div>
                    </div>
                    <div class="mt-md">
                        <div class="lprog-row">
                            <span class="lprog-lbl">Workload Balance</span>
                            <div class="lprog-track"><div class="lprog-fill" style="width:88%;"></div></div>
                            <span class="lprog-pct">88%</span>
                        </div>
                        <div class="lprog-row">
                            <span class="lprog-lbl">Task Distribution</span>
                            <div class="lprog-track"><div class="lprog-fill" style="width:92%;"></div></div>
                            <span class="lprog-pct">92%</span>
                        </div>
                        <div class="lprog-row">
                            <span class="lprog-lbl">Recognition Fairness</span>
                            <div class="lprog-track"><div class="lprog-fill" style="width:96%;"></div></div>
                            <span class="lprog-pct">96%</span>
                        </div>
                        <div class="lprog-row">
                            <span class="lprog-lbl">Promotion Equity</span>
                            <div class="lprog-track"><div class="lprog-fill" style="width:91%;"></div></div>
                            <span class="lprog-pct">91%</span>
                        </div>
                        <div class="lprog-row">
                            <span class="lprog-lbl">Compensation Parity</span>
                            <div class="lprog-track"><div class="lprog-fill" style="width:89%;"></div></div>
                            <span class="lprog-pct">89%</span>
                        </div>
                    </div>
                    <div class="text-center">
                        <span class="lfair-badge">🟢 No bias detected this week</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="ldiv"></div>

{{-- FEATURE: INCREMENT SYSTEM --}}
<section style="padding:0;">
    <div class="lfeat lsec">
        <div class="lfeat-grid lanim" style="padding:0;">
            <div class="lfeat-col">
                <p class="lfeat-lbl">Increment System</p>
                <h2 class="lfeat-h">Fair increments, calculated automatically from real contribution data.</h2>
                <p class="lfeat-sub">10-layer anti-gaming system evaluates real contributions — task completion, quality, collaboration, attendance, peer feedback — and recommends salary increments that reflect actual performance.</p>
            </div>
            <div class="lpanel">
                <div class="lpanel-bar">
                    <div class="ldot ldot-r"></div><div class="ldot ldot-y"></div><div class="ldot ldot-g"></div>
                    <span class="lpanel-ttl">Q1 Increment Recommendation</span>
                </div>
                <div class="lpanel-body">
                    <div class="linc-emp">
                        <div class="linc-av">SJ</div>
                        <div>
                            <div class="linc-name">Sarah Johnson</div>
                            <div class="linc-role">Senior Developer · Engineering</div>
                        </div>
                    </div>
                    <div class="linc-score-row">
                        <span class="linc-score-lbl">Output Quality</span>
                        <span class="linc-score-val" style="color:var(--accent);">9.2 / 10</span>
                    </div>
                    <div class="linc-score-row">
                        <span class="linc-score-lbl">Collaboration</span>
                        <span class="linc-score-val">8.8 / 10</span>
                    </div>
                    <div class="linc-score-row">
                        <span class="linc-score-lbl">Consistency</span>
                        <span class="linc-score-val" style="color:var(--accent);">9.5 / 10</span>
                    </div>
                    <div class="linc-score-row">
                        <span class="linc-score-lbl">Peer Feedback</span>
                        <span class="linc-score-val">8.9 / 10</span>
                    </div>
                    <div class="linc-score-row">
                        <span class="linc-score-lbl">Innovation</span>
                        <span class="linc-score-val">8.1 / 10</span>
                    </div>
                    <div class="linc-rec">
                        <div class="linc-rec-lbl">Recommended Increment</div>
                        <div class="linc-rec-val">18%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="ldiv"></div>

{{-- FEATURE: AI GUARDIAN --}}
<section style="padding:0;">
    <div class="lfeat lsec">
        <div class="lfeat-grid rev lanim" style="padding:0;">
            <div class="lfeat-col">
                <p class="lfeat-lbl">AI Guardian</p>
                <h2 class="lfeat-h">An AI agent that keeps leadership informed with automated digest reports.</h2>
                <p class="lfeat-sub">The OutraqHQ AI Agent analyzes your team's work data to detect anomalies, flag overloaded employees, and generate digest reports that give leadership clear, data-based visibility into real output.</p>
            </div>
            <div class="lpanel">
                <div class="lpanel-bar" style="justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div class="ldot ldot-r"></div><div class="ldot ldot-y"></div><div class="ldot ldot-g"></div>
                        <span class="lpanel-ttl">AI Agent — Last 24 Hours</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;font-size:.68rem;color:var(--accent);font-weight:600;">
                        <span class="lpulse-dot"></span> Live
                    </div>
                </div>
                <div class="lpanel-body">
                    <div class="lai-row">
                        <span class="lai-icon">🔍</span>
                        <span class="lai-text">Analyzed 847 work logs across 6 departments</span>
                        <span class="lai-time">12 min ago</span>
                    </div>
                    <div class="lai-row">
                        <span class="lai-icon">⚠️</span>
                        <span class="lai-text">Flagged workload imbalance — Sarah (Dev Team)</span>
                        <span class="lai-time">1h ago</span>
                    </div>
                    <div class="lai-row">
                        <span class="lai-icon">📧</span>
                        <span class="lai-text">Sent automated weekly digest to CEO</span>
                        <span class="lai-time">3h ago</span>
                    </div>
                    <div class="lai-row">
                        <span class="lai-icon">✅</span>
                        <span class="lai-text">Increment calculations updated for Q1 · 18 employees</span>
                        <span class="lai-time">6h ago</span>
                    </div>
                    <div class="lai-row">
                        <span class="lai-icon">🛡️</span>
                        <span class="lai-text">Bias pattern detected in task assignment — flagged for review</span>
                        <span class="lai-time">9h ago</span>
                    </div>
                    <div class="lai-row">
                        <span class="lai-icon">📊</span>
                        <span class="lai-text">Fairness score recalculated — all teams clean</span>
                        <span class="lai-time">12h ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- UPGRADE 4: INTEGRATIONS GRID LAYOUT --}}
<div class="ldiv"></div>
<section class="lint" style="padding:80px 24px; text-align:center; background:#ffffff;">
    <div style="max-width:1200px; margin:0 auto;">
        <p class="ltag">Integrations</p>
        <h2 style="font-size:2.2rem;font-weight:800;color:var(--text);letter-spacing:-0.03em;line-height:1.2;margin-bottom:14px;">Connect Your Ecosystem</h2>
        <p style="font-size:1rem;color:var(--text-2);line-height:1.75;max-width:580px;margin:0 auto 36px;font-weight:500;">OutraqHQ connects with GitHub for automatic code telemetry, Gmail for executive digests, and enterprise tools arriving soon.</p>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:18px; max-width:1000px; margin:0 auto;" class="lanim lanim-d1">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left; transition:transform 0.2s;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.8rem;">🐙</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:99px;">LIVE SYNC</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">GitHub</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Automated code commit & PR telemetry tracking.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.8rem;">📧</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#d1fae5; color:#065f46; padding:2px 8px; border-radius:99px;">LIVE SYNC</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">Gmail</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">30-second CEO morning AI digests & magic tokens.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left; opacity:0.8;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.8rem;">💬</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:99px;">SOON</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">Slack</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Instant blocker alerts and 5:00 PM auto-draft Nudges.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left; opacity:0.8;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.4rem; font-weight:800; color:#0f172a;">N</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:99px;">SOON</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">Notion</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Documentation center & onboarding checklist sync.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left; opacity:0.8;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.4rem; font-weight:800; color:#0052cc;">Jira</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:99px;">SOON</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">Jira Software</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Sprint story point telemetry & velocity analytics.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left; opacity:0.8;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.2rem; font-weight:800; color:#2ca01c;">QB</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:99px;">SOON</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">QuickBooks</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Mini ERP payroll payout & expense sync.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left; opacity:0.8;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.4rem;">📁</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:99px;">SOON</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">Google Drive</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Non-tech asset document verification.</div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:20px; text-align:left; opacity:0.8;" class="ltrust-card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <span style="font-size:1.4rem; font-weight:800; color:#0079bf;">Trello</span>
                    <span style="font-size:0.65rem; font-weight:800; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:99px;">SOON</span>
                </div>
                <div style="font-weight:800; font-size:1rem; color:#0f172a;">Trello</div>
                <div style="font-size:0.78rem; color:#64748b; margin-top:2px;">Kanban task completion telemetry.</div>
            </div>
        </div>
    </div>
</section>

{{-- COMING SOON --}}
<div class="ldiv"></div>
<section style="padding:80px 24px;">
    <div class="lsec lsec-head lanim">
        <p class="ltag">Roadmap</p>
        <h2 class="lh2">Coming soon</h2>
        <p class="lsub">We're just getting started. Here's what we're building next.</p>
    </div>
    <div class="lsec lsoon-grid lanim">

        <div class="lsoon-card">
            <div style="font-size:2rem;margin-bottom:14px;">📱</div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <h3>Mobile App</h3>
                <span class="lsoon-badge">Soon</span>
            </div>
            <p>OutraqHQ on iOS and Android — log work, approve leaves, and check your team from anywhere.</p>
        </div>

        <div class="lsoon-card">
            <div style="font-size:2rem;margin-bottom:14px;">🧠</div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <h3>Advanced AI Insights</h3>
                <span class="lsoon-badge">Soon</span>
            </div>
            <p>Deeper trends and analytics across teams and departments, surfaced automatically.</p>
        </div>

        <div class="lsoon-card">
            <div style="font-size:2rem;margin-bottom:14px;">💰</div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <h3>Payroll &amp; Compliance</h3>
                <span class="lsoon-badge">Soon</span>
            </div>
            <p>Increment data flowing into payroll, with regional compliance support.</p>
        </div>

    </div>
</section>

{{-- BELIEF --}}
<div class="ldiv"></div>
<section class="ltesti">
    <div class="lsec lsec-head lanim" style="text-align:center">
        <h2 class="lh2">Built on one belief</h2>
        <p class="lsub">Hard work should always be seen, fairly measured, and fairly rewarded — for every person on every team.</p>
    </div>
</section>

{{-- PRICING --}}
<div class="ldiv"></div>
<section class="lprice" id="pricing">
    <div class="lsec lsec-head lanim">
        <p class="ltag">Pricing</p>
        <h2 class="lh2">Simple, transparent pricing</h2>
        <p class="lsub">Start free. Scale as you grow. Cancel anytime.</p>
    </div>
    <div class="lprice-grid lsec">
        <div class="lprice-card lanim">
            <div class="lprice-plan">Free <span class="lprice-badge-free">Free Forever</span></div>
            <div class="lprice-amount">
                <span class="lprice-num">₹0</span>
            </div>
            <p class="lprice-desc">The daily-use basics for any team. No credit card needed.</p>
            <div class="lprice-div"></div>
            <div class="lprice-feat">What's included</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> GitHub Sync</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Employee Directory</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Leave Management</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Document Center</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Onboarding Checklists</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Announcements</div>
            <a href="/register" class="lprice-cta lprice-cta-gh">Get Started Free</a>
        </div>

        <div class="lprice-card feat lanim lanim-d1">
            <div class="lprice-badge-pop">Most Popular</div>
            <div class="lprice-plan">Pro</div>
            <div class="lprice-amount">
                <span class="lprice-num" style="color:var(--accent);">₹199</span>
                <span class="lprice-per">/ user / month</span>
            </div>
            <p class="lprice-desc">The merit and fairness engine for teams that reward real contribution.</p>
            <div class="lprice-div"></div>
            <div class="lprice-feat">Everything in Free, plus</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Fairness Engine</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> AI Intelligence</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Increment Calculator</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Reports &amp; Analytics</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> HR Reports</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Blockers &amp; Dependencies</div>
            <a href="/register" class="lprice-cta lprice-cta-em">Get Started →</a>
        </div>

        <div class="lprice-card lanim lanim-d2">
            <div class="lprice-plan">Enterprise</div>
            <div class="lprice-amount">
                <span class="lprice-num">Custom</span>
            </div>
            <p class="lprice-desc">Leadership visibility and full control for large organizations.</p>
            <div class="lprice-div"></div>
            <div class="lprice-feat">Everything in Pro, plus</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Command Center (CEO view)</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> API Access</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> SSO / SAML</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Audit Logs</div>
            <div class="lprice-item"><span class="lprice-check">✓</span> Priority Support</div>
            <a href="{{ route('contact', ['plan' => 'enterprise']) }}" class="lprice-cta lprice-cta-gh">Contact Sales</a>
        </div>
    </div>
</section>

{{-- FINAL CTA --}}
<section class="lcta lanim">
    <h2 class="lcta-h">Start protecting your team today.</h2>
    <p class="lcta-sub">Join companies that believe hard work should always be seen, always protected, and always rewarded fairly.</p>
    <a href="/register" class="lbtn-lg">Get Started Free →</a>
    <p class="lcta-fine">No credit card required</p>
</section>

{{-- FOOTER --}}
<footer class="lfooter">
    <div class="lfooter-grid">
        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
                <div class="lnav-mark">OQ</div>
                <span style="font-size:0.95rem;font-weight:800;color:var(--text);letter-spacing:-0.02em;">OutraqHQ</span>
            </div>
            <p class="lfooter-brand-tag">Where hard work is always seen, always protected, always rewarded. AI-powered team intelligence for the modern workplace.</p>
            <p class="lfooter-copy">© 2025 OutraqHQ by OutraqHQ.<br>All rights reserved.</p>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Product</div>
            <a href="#features">Features</a>
            <a href="/tour">Tour</a>
            <a href="#pricing">Pricing</a>
            <a href="/docs">Docs</a>
            <a href="#">Changelog</a>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Company</div>
            <a href="#">About</a>
            <a href="#">Blog</a>
            <a href="#">Careers</a>
            <a href="#">Contact</a>
        </div>
        <div class="lfooter-col">
            <div class="lfooter-col-ttl">Legal</div>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Security</a>
        </div>
    </div>
    <div class="lfooter-bottom">
        <p style="font-size:0.75rem;color:#52525b;">Built with ❤️ for teams that deserve better.</p>
        <div class="lfooter-socials">
            <a href="#" class="lfooter-social" title="Twitter/X">𝕏</a>
            <a href="#" class="lfooter-social" title="LinkedIn">in</a>
            <a href="#" class="lfooter-social" title="GitHub">⌥</a>
        </div>
    </div>
</footer>

<script src="{{ asset('js/landing.js') }}"></script>
</body>
</html>
