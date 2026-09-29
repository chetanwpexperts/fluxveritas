/* ===== OUTRAQHQ HELP AGENT ===== */

(function () {
    'use strict';

    /* ── State ────────────────────────────────────────────────────────────── */
    var agentChatOpen  = false;
    var bubbleDismissed = false;
    var msgIndex       = 0;

    /* ── Element references (resolved after DOMContentLoaded) ─────────────── */
    var agentWrap, bubble, bubbleText, chatWindow, agentBody, pulseDot;

    /* ── Movement positions ───────────────────────────────────────────────── */
    var positions = [
        { bottom: '80px',  right: '40px'  },
        { bottom: '80px',  right: '200px' },
        { bottom: '200px', right: '40px'  },
        { bottom: '300px', right: '100px' },
        { bottom: '80px',  right: '400px' },
    ];
    var currentPos = 0;

    /* ── Proactive messages by page ───────────────────────────────────────── */
    var pageMessages = {
        'dashboard': ['👋 Need help syncing GitHub? Just ask!', '📊 Your activity chart updates after each sync', '💡 Tip: Sync GitHub daily for fresh insights'],
        'team':      ['👥 Need help inviting a team member?', '💡 Set GitHub usernames for accurate tracking', '🔍 Each member needs a GitHub username to track'],
        'fairness':  ['⚖️ Try running your first fairness analysis!', '🤖 The AI checks 5 layers before flagging anyone', '💡 Run analysis weekly for best results'],
        'blockers':  ['🚧 Blocking someone? Report it here!', '💡 Set employee status for leaves/holidays', '⚡ Resolve blockers fast to keep team moving'],
        'ai':        ['🧠 Ask me: Who contributed most this month?', '💡 Upgrade to Pro for Ollama AI summaries', '🤖 Try natural language questions about your team'],
        'projects':  ['📁 Add your GitHub repos as projects here', '💡 Each project tracks its own commits and PRs', '🔗 Connect a repo to start tracking activity'],
        'ceo':       ['👁️ Click any row to see member details', '📊 Red rows need your immediate attention', '💡 Use Quick Actions for common tasks'],
        'admin':     ['⚙️ Manage roles and permissions here', '💡 Check Pending Approvals for new users', '🔐 Owner role has full access to everything'],
        'settings':  ['⚙️ Set your GitHub username for tracking', '🔔 Enable notifications to stay informed', '💡 Use sidebar to navigate settings sections'],
    };

    /* ── Helpers ──────────────────────────────────────────────────────────── */
    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function setExpression(mood) {
        var mouth = document.getElementById('agent-mouth');
        if (!mouth) return;
        if (mood === 'happy') {
            mouth.setAttribute('d', 'M24 29 Q32 35 40 29');
        } else if (mood === 'thinking') {
            mouth.setAttribute('d', 'M26 31 Q32 31 38 31');
            if (agentBody) agentBody.style.animation = 'agentPulse 1s ease infinite';
        } else if (mood === 'excited') {
            mouth.setAttribute('d', 'M22 28 Q32 37 42 28');
            if (agentBody) {
                agentBody.style.animation = 'agentBounce 0.5s ease 3';
                setTimeout(function () { agentBody.style.animation = 'agentFloat 3s ease-in-out infinite'; }, 1500);
            }
        } else {
            mouth.setAttribute('d', 'M24 29 Q32 34 40 29');
            if (agentBody) agentBody.style.animation = 'agentFloat 3s ease-in-out infinite';
        }
    }

    function showBubble(text, duration) {
        if (agentChatOpen || !bubble || !bubbleText) return;
        duration = duration !== undefined ? duration : 6000;
        bubbleText.textContent = text;
        bubble.classList.remove('fv-agent-hidden');
        bubble.style.animation = 'speechBubblePop 0.3s ease forwards';
        if (duration > 0) {
            setTimeout(function () {
                if (!agentChatOpen && bubble) bubble.classList.add('fv-agent-hidden');
            }, duration);
        }
    }

    function moveAgent() {
        if (agentChatOpen || !agentWrap || !agentBody) return;
        var newPos = Math.floor(Math.random() * positions.length);
        while (newPos === currentPos) newPos = Math.floor(Math.random() * positions.length);
        currentPos = newPos;
        agentBody.style.animation = 'agentWalk 0.5s ease infinite';
        agentWrap.style.bottom = positions[currentPos].bottom;
        agentWrap.style.right  = positions[currentPos].right;
        setTimeout(function () { if (agentBody) agentBody.style.animation = 'agentFloat 3s ease-in-out infinite'; }, 2000);
    }

    function scheduleMove() {
        var delay = 12000 + Math.random() * 8000;
        setTimeout(function () {
            if (!agentChatOpen) moveAgent();
            scheduleMove();
        }, delay);
    }

    function addAgentMsg(text, sender) {
        var msgs = document.getElementById('fv-chat-msgs');
        if (!msgs) return;
        var isAgent = sender === 'agent';
        // Escape first: answers contain names and blocker titles typed by users
        var formatted = escapeHtml(text)
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\\n/g, '<br>')
            .replace(/\n/g, '<br>');
        var div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:8px;justify-content:' + (isAgent ? 'flex-start' : 'flex-end') + ';';
        div.innerHTML = isAgent
            ? '<div style="font-size:0.85rem;flex-shrink:0;">🤖</div><div style="background:#f4f4f5;border-radius:10px 10px 10px 3px;padding:9px 12px;font-size:0.78rem;color:#3f3f46;line-height:1.6;max-width:220px;">' + formatted + '</div>'
            : '<div style="background:#18181b;border-radius:10px 10px 3px 10px;padding:9px 12px;font-size:0.78rem;color:white;line-height:1.5;max-width:220px;">' + escapeHtml(text) + '</div>';
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
    }

    /* Quick-action buttons under an answer; clicks go through the [data-agent-action] handler */
    function addSuggestions(items) {
        var msgs = document.getElementById('fv-chat-msgs');
        if (!msgs) return;
        var wrap = document.createElement('div');
        wrap.className = 'chat-chips chat-suggestions';
        items.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chat-chip-btn';
            btn.setAttribute('data-agent-action', 'ask');
            btn.setAttribute('data-question', item.question);
            btn.textContent = item.label;
            wrap.appendChild(btn);
        });
        msgs.appendChild(wrap);
        msgs.scrollTop = msgs.scrollHeight;
    }

    function addThinkingMsg() {
        var msgs = document.getElementById('fv-chat-msgs');
        if (!msgs) return null;
        var id = 'think-' + Date.now();
        var div = document.createElement('div');
        div.id = id;
        div.style.cssText = 'display:flex;gap:8px;';
        div.innerHTML = '<div style="font-size:0.85rem;">🤖</div><div style="background:#f4f4f5;border-radius:10px;padding:12px 14px;display:flex;gap:4px;align-items:center;"><span class="thinking-dot"></span><span class="thinking-dot"></span><span class="thinking-dot"></span></div>';
        msgs.appendChild(div);
        msgs.scrollTop = msgs.scrollHeight;
        return id;
    }

    function removeThinkingMsg(id) {
        var el = document.getElementById(id);
        if (el) el.remove();
    }

    /* ── Smart auto-popup ────────────────────────────────────────────────── */
    var SESSION_KEY = 'outy_shown_' + window.location.pathname;
    var GLOBAL_KEY  = 'outy_last_shown';

    function shouldShowAutoPopup() {
        try {
            if (sessionStorage.getItem(SESSION_KEY)) return false;
            var lastShown = localStorage.getItem(GLOBAL_KEY);
            if (lastShown && (Date.now() - parseInt(lastShown)) < 10 * 60 * 1000) return false;
        } catch (_) {}
        return true;
    }

    function getPageMessage(path, userName) {
        var hour = new Date().getHours();

        if (path.indexOf('dashboard') !== -1) {
            return 'Hi ' + userName + '! Ready to check how your team is doing today?';
        }
        if (path.indexOf('worklog') !== -1 || path.indexOf('work-log') !== -1) {
            return 'Don\'t forget to log your work today ' + userName + '! It counts towards your increment score.';
        }
        if (path.indexOf('tasks') !== -1) {
            return 'You have tasks waiting, ' + userName + '! Need help prioritizing?';
        }
        if (path.indexOf('increment') !== -1) {
            return 'Curious about your increment score, ' + userName + '? Ask me anything!';
        }
        if (path.indexOf('fairness') !== -1) {
            return 'Fairness Engine is watching over your team, ' + userName + '. All good?';
        }
        if (path.indexOf('sprints') !== -1) {
            return 'Sprint planning time, ' + userName + '? I can help with team capacity!';
        }
        if (path.indexOf('blockers') !== -1 || path.indexOf('dependency') !== -1) {
            return 'Got a blocker, ' + userName + '? I can help you escalate or resolve it!';
        }
        if (path.indexOf('org-chart') !== -1) {
            return 'Viewing your org chart, ' + userName + '? Ask me about anyone on your team!';
        }
        if (path.indexOf('notifications') !== -1) {
            return 'Checking your notifications, ' + userName + '? Ask me what you missed!';
        }

        if (hour < 12) {
            return 'Good morning ' + userName + '! Ready to make today count?';
        }
        if (hour < 17) {
            return 'Hey ' + userName + '! How\'s your day going? Ask me anything!';
        }
        return 'Good evening ' + userName + '! Don\'t forget to log today\'s work!';
    }

    function getAutoPopupMessage() {
        var path     = window.location.pathname;
        var userName = agentWrap ? (agentWrap.dataset.user || 'there') : 'there';

        return fetch('/notifications/count', {
            headers: {
                'X-CSRF-TOKEN': window.FV_CSRF || (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
                'Accept': 'application/json',
            },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var unread = data.unread_count || 0;
            if (unread > 0) {
                return 'You have ' + unread + ' unread notification' + (unread > 1 ? 's' : '') + ' ' + userName + '! Ask me "what did I miss?" or check the bell icon.';
            }
            return getPageMessage(path, userName);
        })
        .catch(function () {
            return getPageMessage(path, userName);
        });
    }

    function showAutoPopup() {
        if (!shouldShowAutoPopup()) return;
        if (!agentWrap) return;

        try {
            sessionStorage.setItem(SESSION_KEY, '1');
            localStorage.setItem(GLOBAL_KEY, Date.now().toString());
        } catch (_) {}

        if (pulseDot) pulseDot.classList.remove('active');

        getAutoPopupMessage().then(function (message) {
            setExpression('happy');
            showBubble(message, 0);

            var userInteracted = false;
            var autoCloseTimer = setTimeout(function () {
                if (!agentChatOpen && !userInteracted && bubble) {
                    bubble.classList.add('fv-agent-hidden');
                }
            }, 8000);

            var inp = document.getElementById('fv-agent-input');
            if (inp) {
                inp.addEventListener('focus', function () {
                    userInteracted = true;
                    clearTimeout(autoCloseTimer);
                }, { once: true });
            }
        });
    }

    /* ── Core actions (called by event delegation) ─────────────────────────── */
    function toggleAgentChat() {
        if (!chatWindow) return;
        agentChatOpen = !agentChatOpen;
        chatWindow.style.display = agentChatOpen ? 'flex' : 'none';
        if (agentChatOpen) chatWindow.style.flexDirection = 'column';
        if (bubble) bubble.classList.add('fv-agent-hidden');
        // Hide pulse dot when chat opens
        if (pulseDot && agentChatOpen) pulseDot.classList.remove('active');
        if (agentChatOpen) {
            setExpression('excited');
            var inp = document.getElementById('fv-agent-input');
            if (inp) inp.focus();
            chatWindow.style.right = (agentWrap && agentWrap.style.right) || '40px';
        } else {
            setExpression('neutral');
        }
    }

    function closeBubble(e) {
        if (e) e.stopPropagation();
        if (bubble) bubble.classList.add('fv-agent-hidden');
        bubbleDismissed = true;
        // Record manual close so auto-popup respects 10-minute cooldown
        try { localStorage.setItem('outy_last_shown', Date.now().toString()); } catch (_) {}
    }

    function agentSend() {
        var input = document.getElementById('fv-agent-input');
        if (!input) return;
        var q = input.value.trim();
        if (!q) return;
        input.value = '';
        agentAsk(q);
    }

    function agentAsk(question) {
        if (!agentChatOpen) toggleAgentChat();
        addAgentMsg(question, 'user');
        setExpression('thinking');
        var thinkId = addThinkingMsg();
        var btn = document.getElementById('fv-send-btn');
        if (btn) btn.disabled = true;
        fetch('/help-agent/ask', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.FV_CSRF || (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
            },
            body: JSON.stringify({ question: question, page: window.FV_PAGE }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            removeThinkingMsg(thinkId);
            addAgentMsg(data.answer || 'Sorry, I had trouble. Try again!', 'agent');
            if (Array.isArray(data.suggestions) && data.suggestions.length) addSuggestions(data.suggestions);
            setExpression('happy');
            if (btn) btn.disabled = false;
        })
        .catch(function () {
            removeThinkingMsg(thinkId);
            addAgentMsg('Sorry, I had trouble. Try again!', 'agent');
            setExpression('neutral');
            if (btn) btn.disabled = false;
        });
    }

    /* ── Proactive / inactivity ───────────────────────────────────────────── */
    function startProactive() {
        var msgs = pageMessages[window.FV_PAGE] || pageMessages['dashboard'];
        setTimeout(function () {
            if (!agentChatOpen && !bubbleDismissed) { setExpression('happy'); showBubble(msgs[0], 8000); msgIndex = 1; }
        }, 8000);
        setInterval(function () {
            if (!agentChatOpen && !bubbleDismissed) {
                setExpression('happy');
                showBubble(msgs[msgIndex % msgs.length], 8000);
                msgIndex++;
                if (msgIndex > 1) moveAgent();
            }
        }, 30000);
    }

    var lastActivity = Date.now();
    ['mousemove', 'keypress', 'click'].forEach(function (ev) {
        document.addEventListener(ev, function () { lastActivity = Date.now(); });
    });

    function checkInactivity() {
        var inactive = (Date.now() - lastActivity) / 1000;
        if (inactive > 45 && !agentChatOpen && !bubbleDismissed) {
            setExpression('happy');
            showBubble('👋 Still here! Need any help navigating the system?', 8000);
        }
    }

    var scrollCount = 0;
    window.addEventListener('scroll', function () {
        scrollCount++;
        if (scrollCount === 5 && !agentChatOpen) {
            setTimeout(function () {
                if (!agentChatOpen) showBubble('🔍 Exploring the page? Ask me what anything does!', 6000);
            }, 1000);
        }
    });

    /* ── Context fetch ────────────────────────────────────────────────────── */
    function loadAgentContext() {
        fetch('/help-agent/context?page=' + (window.FV_PAGE || 'dashboard'))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var welcome = document.getElementById('fv-welcome-msg');
                if (data.welcome && welcome) welcome.textContent = data.welcome;
                if (data.proactive && !bubbleDismissed) {
                    setTimeout(function () { setExpression('excited'); showBubble('💡 ' + data.proactive.message, 10000); }, 12000);
                }
            })
            .catch(function () {});
    }

    /* ── Event delegation — replaces all inline onclick handlers ──────────── */
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-agent-action]');
        if (!el) return;
        var action = el.dataset.agentAction;
        if (action === 'toggle')      { toggleAgentChat(); }
        else if (action === 'close-bubble') { closeBubble(e); }
        else if (action === 'send')   { agentSend(); }
        else if (action === 'ask')    { agentAsk(el.dataset.question || ''); }
    });

    document.addEventListener('keypress', function (e) {
        if (e.key === 'Enter' && e.target && e.target.id === 'fv-agent-input') {
            agentSend();
        }
    });

    /* ── Init ─────────────────────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        agentWrap  = document.getElementById('fv-agent-wrap');
        bubble     = document.getElementById('fv-agent-bubble');
        bubbleText = document.getElementById('fv-bubble-text');
        chatWindow = document.getElementById('fv-chat-window');
        agentBody  = document.getElementById('fv-agent-body');
        pulseDot   = document.getElementById('outy-pulse-dot');

        if (!agentWrap) return;

        // Personalise greeting with user name from data attribute
        var userName = agentWrap.dataset.user || 'there';
        var welcome  = document.getElementById('fv-welcome-msg');
        if (welcome) {
            welcome.textContent = 'Hi ' + userName + "! I'm Outy 👋 I know everything about your work here. Ask me anything!";
        }

        // Show pulse dot immediately if auto-popup will fire
        if (pulseDot && shouldShowAutoPopup()) pulseDot.classList.add('active');

        // Auto-popup after 5 seconds
        setTimeout(showAutoPopup, 5000);

        loadAgentContext();
        startProactive();
        scheduleMove();
        setInterval(checkInactivity, 30000);
    });

}());
