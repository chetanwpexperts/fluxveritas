<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FluxVeritas — AI-Powered Workplace Fairness | No Politics, Just Truth</title>
    <meta name="description" content="FluxVeritas is an AI-powered platform that eliminates workplace politics, tracks real employee output, and gives leaders direct truth. Built in PHP. Protecting jobs, not replacing them.">
    <meta name="keywords" content="AI workplace fairness, employee tracking, no office politics, PHP AI platform, team management, productivity tracking, fair promotions">
    <meta name="author" content="FluxVeritas">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:title" content="FluxVeritas — AI-Powered Workplace Fairness">
    <meta property="og:description" content="Eliminate workplace politics. Track real output. Promote fairly. Built in PHP.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://fluxveritas.com">
    <meta property="og:image" content="https://fluxveritas.com/assets/og-image.jpg">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FluxVeritas — Truth in Motion">
    <meta name="twitter:description" content="No manager can lie. No worker is invisible. Power sees truth. Truth sees power.">

    <!-- Canonical -->
    <link rel="canonical" href="https://fluxveritas.com">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f172a;
            --secondary: #1e293b;
            --accent: #3b82f6;
            --accent-glow: rgba(59, 130, 246, 0.3);
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gradient-1: #3b82f6;
            --gradient-2: #8b5cf6;
            --gradient-3: #06b6d4;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated background */
        .bg-animation {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(59, 130, 246, 0.08) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 20%, rgba(139, 92, 246, 0.06) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 80%, rgba(6, 182, 212, 0.05) 0%, transparent 50%);
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%233b82f6' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: drift 120s linear infinite;
        }

        @keyframes drift {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-50px, -50px); }
        }

        /* Navigation */
        nav {
            position: fixed;
            top: 0; width: 100%;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            z-index: 1000;
            padding: 1rem 0;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .logo span { font-weight: 300; }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 0.9rem;
        }

        .nav-links a:hover { color: var(--text); }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
            box-shadow: 0 4px 20px var(--accent-glow);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px var(--accent-glow);
        }

        .btn-outline {
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--text);
            background: transparent;
        }

        .btn-outline:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.4);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8rem 2rem 4rem;
            position: relative;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 100px;
            font-size: 0.85rem;
            color: var(--accent);
            margin-bottom: 2rem;
        }

        .hero-badge .pulse {
            width: 8px; height: 8px;
            background: var(--success);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .hero h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .hero h1 .gradient {
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2), var(--gradient-3));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .hero-cta {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }

        .hero-stats {
            display: flex;
            gap: 3rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stat {
            text-align: center;
        }

        .stat-number {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Problem Section */
        .section {
            padding: 6rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(59, 130, 246, 0.1);
            color: var(--accent);
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .section h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(1.8rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section p {
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Problem Cards */
        .problem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .problem-card {
            background: var(--secondary);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 2rem;
            transition: all 0.3s;
        }

        .problem-card:hover {
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-4px);
        }

        .problem-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: rgba(239, 68, 68, 0.1);
        }

        .problem-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .problem-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Solution Section */
        .solution-section {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 50%, var(--primary) 100%);
        }

        .solution-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .solution-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .solution-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 3px;
            background: linear-gradient(90deg, var(--gradient-1), var(--gradient-2));
        }

        .solution-card .icon {
            width: 56px; height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .solution-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
        }

        .solution-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        /* How It Works */
        .steps {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .step {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .step-number {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            color: rgba(59, 130, 246, 0.2);
            line-height: 1;
            flex-shrink: 0;
            width: 80px;
        }

        .step-content h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .step-content p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* PHP Section */
        .php-section {
            background: var(--secondary);
            border-radius: 24px;
            padding: 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .php-section::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
        }

        .php-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(119, 123, 180, 0.2);
            border: 1px solid rgba(119, 123, 180, 0.4);
            border-radius: 100px;
            color: #8892bf;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .php-quote {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(1.2rem, 3vw, 2rem);
            font-weight: 600;
            font-style: italic;
            max-width: 700px;
            margin: 0 auto 1.5rem;
            line-height: 1.4;
        }

        .php-quote .highlight {
            background: linear-gradient(135deg, #8892bf, #4f5b93);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Pricing */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .pricing-card {
            background: var(--secondary);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            transition: all 0.3s;
        }

        .pricing-card.featured {
            border-color: var(--accent);
            position: relative;
        }

        .pricing-card.featured::before {
            content: 'Most Popular';
            position: absolute;
            top: -12px; left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            color: white;
            padding: 0.25rem 1rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .pricing-card:hover {
            transform: translateY(-8px);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .pricing-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .price {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin: 1rem 0;
        }

        .price span {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 400;
        }

        .pricing-features {
            list-style: none;
            text-align: left;
            margin: 2rem 0;
        }

        .pricing-features li {
            padding: 0.5rem 0;
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pricing-features li::before {
            content: '✓';
            color: var(--success);
            font-weight: 700;
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 6rem 2rem;
        }

        .cta-section h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: clamp(1.8rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-section p {
            color: var(--text-muted);
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        /* Footer */
        footer {
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 3rem 2rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .footer-links {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover { color: var(--text); }

        /* Scroll Animation */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Mobile */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .hero { padding-top: 6rem; }
            .step { flex-direction: column; gap: 1rem; }
            .step-number { font-size: 2rem; width: auto; }
            .php-section { padding: 2rem; }
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>

    <!-- Navigation -->
    <nav>
        <div class="nav-container">
            <a href="#" class="logo">Flux<span>Veritas</span></a>
            <ul class="nav-links">
                <li><a href="#problem">Problem</a></li>
                <li><a href="#solution">Solution</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#php">Built in PHP</a></li>
                <li><a href="#pricing">Pricing</a></li>
            </ul>
            <a href="#early-access" class="btn btn-primary">Get Early Access</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div>
            <div class="hero-badge">
                <span class="pulse"></span>
                Now in Private Beta
            </div>
            <h1>
                No Manager Can Lie.<br>
                No Worker Is <span class="gradient">Invisible.</span>
            </h1>
            <p class="hero-subtitle">
                FluxVeritas is an AI-powered platform that tracks real employee output, 
                eliminates office politics, and gives leaders the direct truth — 
                with proof. Your job is safe. Your work speaks.
            </p>
            <div class="hero-cta">
                <a href="#early-access" class="btn btn-primary">Start Free Trial</a>
                <a href="#how-it-works" class="btn btn-outline">See How It Works</a>
            </div>
            <div class="hero-stats">
                <div class="stat">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Output Tracked</div>
                </div>
                <div class="stat">
                    <div class="stat-number">0</div>
                    <div class="stat-label">Meetings Needed</div>
                </div>
                <div class="stat">
                    <div class="stat-number">&lt;1%</div>
                    <div class="stat-label">False Flags</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Problem Section -->
    <section class="section" id="problem">
        <div class="section-header fade-in">
            <span class="section-tag">The Problem</span>
            <h2>Workplaces Are Broken</h2>
            <p>The best employees are leaving. Not because of pay — because of politics.</p>
        </div>
        <div class="problem-grid">
            <div class="problem-card fade-in">
                <div class="problem-icon">🎭</div>
                <h3>Manager Favoritism</h3>
                <p>Easy tasks go to favorites. Hard work goes unnoticed. Promotions depend on who you know, not what you deliver.</p>
            </div>
            <div class="problem-card fade-in">
                <div class="problem-icon">🗣️</div>
                <h3>Endless Meetings</h3>
                <p>Status updates, alignment calls, sync meetings — hours wasted talking about work instead of doing it.</p>
            </div>
            <div class="problem-card fade-in">
                <div class="problem-icon">🕳️</div>
                <h3>The Invisibility Gap</h3>
                <p>Remote workers, quiet contributors, and junior devs do the work but never get the credit. Talkers win. Builders lose.</p>
            </div>
            <div class="problem-card fade-in">
                <div class="problem-icon">🤖</div>
                <h3>Fear of AI</h3>
                <p>Employees worry AI will replace them. Companies buy AI tools that fire people. Nobody wins.</p>
            </div>
        </div>
    </section>

    <!-- Solution Section -->
    <section class="section solution-section" id="solution">
        <div class="section-header fade-in">
            <span class="section-tag">The Solution</span>
            <h2>Truth in Motion</h2>
            <p>FluxVeritas doesn't replace people. It replaces politics.</p>
        </div>
        <div class="solution-grid">
            <div class="solution-card fade-in">
                <div class="icon">📊</div>
                <h3>Real Output Tracking</h3>
                <p>Connect GitHub, Jira, Slack. AI measures actual work — code quality, PR impact, bug fixes — not hours logged or stories told.</p>
            </div>
            <div class="solution-card fade-in">
                <div class="icon">🛡️</div>
                <h3>Fairness Engine</h3>
                <p>5-layer AI verification detects favoritism, credit theft, and overload before it destroys morale. Evidence attached to every flag.</p>
            </div>
            <div class="solution-card fade-in">
                <div class="icon">👁️</div>
                <h3>Direct Visibility</h3>
                <p>Leaders see truth without meetings. Employees get credit without begging. One dashboard. Zero politics.</p>
            </div>
            <div class="solution-card fade-in">
                <div class="icon">🔒</div>
                <h3>Job Protection</h3>
                <p>AI handles the busywork. You handle the brilliance. Companies need you more, not less. Your job is safe.</p>
            </div>
            <div class="solution-card fade-in">
                <div class="icon">⚡</div>
                <h3>Zero Meetings</h3>
                <p>AI updates stakeholders automatically. Async progress reports. Direct alerts only when action is needed.</p>
            </div>
            <div class="solution-card fade-in">
                <div class="icon">🔍</div>
                <h3>Accountability for All</h3>
                <p>Power + Accountability together. Leaders are watched too. No one escapes. No one is above the system.</p>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="section" id="how-it-works">
        <div class="section-header fade-in">
            <span class="section-tag">How It Works</span>
            <h2>Three Steps to Fairness</h2>
            <p>No complex setup. No employee training. Work normally, get tracked fairly.</p>
        </div>
        <div class="steps">
            <div class="step fade-in">
                <div class="step-number">01</div>
                <div class="step-content">
                    <h3>Connect Your Tools</h3>
                    <p>Link GitHub, Jira, Slack in under 10 minutes. One-click OAuth. Read-only access — we never modify your code or data.</p>
                </div>
            </div>
            <div class="step fade-in">
                <div class="step-number">02</div>
                <div class="step-content">
                    <h3>AI Learns Your Team</h3>
                    <p>For 2 weeks, FluxVeritas silently observes patterns — who does what, how well, how fair. No flags. No alerts. Just learning.</p>
                </div>
            </div>
            <div class="step fade-in">
                <div class="step-number">03</div>
                <div class="step-content">
                    <h3>Truth Emerges</h3>
                    <p>Leaders see real output. Employees see fair scores. AI flags issues with evidence. Decisions become data, not drama.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- PHP Section -->
    <section class="section" id="php">
        <div class="php-section fade-in">
            <div class="php-badge">
                <span>🐘</span> Built in PHP 8.3 + Laravel
            </div>
            <p class="php-quote">
                "You abandoned PHP for trends. <span class="highlight">We built the future in PHP.</span> Your loss, our gain."
            </p>
            <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto 2rem;">
                FluxVeritas is 100% PHP — proving PHP handles AI, scale, and modern architecture. 
                Join the PHP renaissance.
            </p>
            <div style="display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <div style="font-family: 'Space Grotesk'; font-size: 2rem; font-weight: 700; color: #8892bf;">8.3</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">PHP Version</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-family: 'Space Grotesk'; font-size: 2rem; font-weight: 700; color: #8892bf;">10K+</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Users Scalable</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-family: 'Space Grotesk'; font-size: 2rem; font-weight: 700; color: #8892bf;">&lt;50ms</div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Response Time</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="section" id="pricing">
        <div class="section-header fade-in">
            <span class="section-tag">Pricing</span>
            <h2>Simple, Transparent Pricing</h2>
            <p>No hidden fees. No enterprise sales calls. Start free, scale when ready.</p>
        </div>
        <div class="pricing-grid">
            <div class="pricing-card fade-in">
                <h3>Starter</h3>
                <div class="price">$29<span>/month</span></div>
                <p style="color: var(--text-muted); font-size: 0.9rem;">For small teams getting started</p>
                <ul class="pricing-features">
                    <li>Up to 15 team members</li>
                    <li>GitHub integration</li>
                    <li>Basic activity tracking</li>
                    <li>7-day data history</li>
                    <li>Email support</li>
                </ul>
                <a href="#" class="btn btn-outline" style="width: 100%;">Get Started</a>
            </div>
            <div class="pricing-card featured fade-in">
                <h3>Pro</h3>
                <div class="price">$99<span>/month</span></div>
                <p style="color: var(--text-muted); font-size: 0.9rem;">For growing teams that need fairness</p>
                <ul class="pricing-features">
                    <li>Up to 50 team members</li>
                    <li>GitHub + Jira + Slack</li>
                    <li>Advanced fairness engine</li>
                    <li>30-day data history</li>
                    <li>Custom reports</li>
                    <li>Priority support</li>
                </ul>
                <a href="#" class="btn btn-primary" style="width: 100%;">Start Free Trial</a>
            </div>
            <div class="pricing-card fade-in">
                <h3>Enterprise</h3>
                <div class="price">Custom</div>
                <p style="color: var(--text-muted); font-size: 0.9rem;">For organizations that need control</p>
                <ul class="pricing-features">
                    <li>Unlimited team members</li>
                    <li>Self-hosted option</li>
                    <li>Custom AI training</li>
                    <li>Unlimited data history</li>
                    <li>SLA guarantee</li>
                    <li>Dedicated support</li>
                </ul>
                <a href="#" class="btn btn-outline" style="width: 100%;">Contact Sales</a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section" id="early-access">
        <div class="fade-in">
            <h2>Ready for Truth?</h2>
            <p>Join 50+ teams already using FluxVeritas to build fairer workplaces. No credit card required.</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 2rem;">
                <a href="#" class="btn btn-primary">Get Early Access — Free</a>
                <a href="#" class="btn btn-outline">Schedule Demo</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-links">
            <a href="#">Privacy</a>
            <a href="#">Terms</a>
            <a href="#">Security</a>
            <a href="#">API Docs</a>
            <a href="#">Blog</a>
            <a href="#">Contact</a>
        </div>
        <p>© 2026 FluxVeritas. Built with 💙 in PHP. All rights reserved.</p>
        <p style="margin-top: 0.5rem; font-size: 0.75rem;">Truth in Motion.</p>
    </footer>

    <script>
        // Scroll animation
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.fade-in').forEach(el => observer.observe(el));

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>