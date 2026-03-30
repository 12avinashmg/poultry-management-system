<?php
session_start();
require_once(__DIR__ . '/../includes/database.php');
require_once(__DIR__ . '/../includes/config.php');
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') { header('Location: ../auth/login.php'); exit(); }

// --- EMPLOYEE PAYROLL DATA (we keep it for later pages even if we don't show chart here) ---
$payroll_data = $pdo->query("SELECT CONCAT(FirstName, ' ', LastName) AS name, job, Salary FROM employee")->fetchAll(PDO::FETCH_ASSOC);
$payroll_labels = [];
$payroll_values = [];
foreach ($payroll_data as $row) {
    $payroll_labels[] = $row['name'];
    $payroll_values[] = $row['Salary'];
}
$total_payroll = array_sum($payroll_values);

// --- ADMIN PROFILE IMAGE (OPTIONAL STATIC) ---
$admin_img = "https://cdn-icons-png.flaticon.com/512/3135/3135715.png";

// --- INVENTORY BUTTONS ---
$inventory_options = [
    [
        'name' => 'Birds Inventory',
        'icon' => '🐦',
        'link' => 'inventory_birds.php',
        'color' => 'btn-outline-primary'
    ],
    [
        'name' => 'Feed Inventory',
        'icon' => '🥦',
        'link' => 'inventory_feed.php',
        'color' => 'btn-outline-success'
    ],
    [
        'name' => 'Egg Production',
        'icon' => '🥚',
        'link' => 'inventory_eggs.php',
        'color' => 'btn-outline-warning'
    ],
    [
        'name' => 'Payroll',
        'icon' => '💰',
        'link' => 'payroll.php',
        'color' => 'btn-outline-danger'
    ],
    [
        'name' => 'Analytics',
        'icon' => '📊',
        'link' => 'inventory_dashboard.php',
        'color' => 'btn-outline-info'
    ],
    [
        'name' => 'Purchases',
        'icon' => '🛒',
        'link' => 'admin_purchases.php',
        'color' => 'btn-outline-dark'
    ],
    [
        'name' => 'Biosecurity Management',
        'icon' => '🦠',
        'link' => 'admin_biosecurity.php',
        'color' => 'btn-outline-warning'
    ],
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard | Poultry Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .sidebar {
            background: linear-gradient(135deg, #4f8cff 0%, #92f2ee 100%);
            min-height: 100vh;
            color: #fff;
            box-shadow: 2px 0 8px rgba(79,140,255,0.12);
        }
        .sidebar .nav-link, .sidebar .nav-link.active {
            color: #fff;
            font-weight: 500;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(0,0,0,0.07);
            color: #fff;
        }
        .dashboard-main {
            padding: 40px 30px;
        }
        .profile-img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            margin-bottom: 10px;
        }
        .inventory-card {
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            transition: transform 0.18s;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .inventory-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 6px 24px rgba(79,140,255,0.14);
        }
        .inventory-card .card-body {
            text-align: center;
            padding: 1.25rem 0.75rem;
            width: 100%;
        }
        .inventory-btn {
            margin-top: 12px;
            width: 90%;
            font-size: 1.07rem;
        }
        .brand-title {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-weight: 700;
            color: #2563eb;
        }
        .footer-copyright {
            margin-top: 30px;
            margin-bottom: 16px;
            color: #555;
            font-weight: 500;
            font-size: 1.07rem;
            text-align: center;
            letter-spacing: 0.7px;
        }

        /* Chatbot widget styles */
        /* Floating button (icon-only) */
        #bot-btn {
          position: fixed;
          bottom: 22px;
          right: 22px;
          width: 64px;
          height: 64px;
          border-radius: 50%;
          background: linear-gradient(135deg,#2b6ee6,#29c0ff);
          display:flex; align-items:center; justify-content:center;
          border:none; cursor:pointer;
          box-shadow: 0 12px 30px rgba(37,99,235,0.25);
          transition: transform .18s, box-shadow .18s;
          z-index:2147483647;
        }
        #bot-btn:hover { transform: translateY(-6px) scale(1.03); }

        /* notification dot */
        #bot-dot {
          position: absolute; top: -6px; right: -6px;
          width:18px; height:18px; border-radius:50%;
          background:#ff3b30; color:#fff; display:none;
          align-items:center; justify-content:center; font-size:11px; font-weight:700; z-index:2147483648;
        }

        /* Chat window */
        #bot-box {
          display:none;
          position: fixed;
          right: 22px;
          bottom: 100px;
          width: 400px;
          max-width: calc(100% - 44px);
          height: 520px;
          background: #ffffff;
          border-radius: 14px;
          box-shadow: 0 24px 60px rgba(10,30,60,0.15);
          overflow: hidden;
          z-index:2147483646;
          display:none; flex-direction:column;
        }

        /* Header */
        #bot-header {
          background: linear-gradient(90deg,#2b6ee6,#29c0ff);
          color:#fff;
          padding:12px;
          display:flex;
          gap:10px;
          align-items:center;
          cursor: move;
          user-select:none;
        }
        .header-title { font-weight:700; font-size:15px; }
        .header-sub { font-size:12px; opacity:0.95; }

        /* body */
        #bot-body { padding:12px; overflow-y:auto; background: linear-gradient(180deg,#f8fbff,#f3f9ff); flex:1; }

        /* footer */
        #bot-footer { padding:10px; display:flex; gap:8px; align-items:center; border-top:1px solid #eef6ff; }
        #bot-input { flex:1; padding:10px 12px; border-radius:10px; border:1px solid #dbeefe; font-size:14px; }
        .bot-ico-btn { width:44px; height:44px; border-radius:10px; border:none; background:#2b6ee6; color:#fff; display:flex; align-items:center; justify-content:center; cursor:pointer; }

        /* messages */
        .msg-row { margin:8px 0; display:flex; }
        .msg-user { justify-content:flex-end; }
        .msg-user .bubble { background:#dff0ff; padding:10px 14px; border-radius:14px; max-width:78%; }
        .msg-bot { justify-content:flex-start; gap:8px; align-items:flex-start; }
        .msg-bot .avatar { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:linear-gradient(90deg,#fff,#f2fbff); box-shadow: 0 1px 0 rgba(0,0,0,0.03);}
        .msg-bot .bubble { background:#ffffff; padding:10px 14px; border-radius:12px; box-shadow:0 2px 10px rgba(40,70,120,0.04); max-width:78%; }

        /* typing */
        .typing { display:flex; gap:6px; align-items:center; }
        .typing .dot { width:7px; height:7px; border-radius:50%; background:#cbd5e1; animation: blink 1.1s infinite; }
        .typing .dot:nth-child(2){ animation-delay:.16s } .typing .dot:nth-child(3){ animation-delay:.32s }
        @keyframes blink { 0%{opacity:.3}50%{opacity:1}100%{opacity:.3} }

        /* responsive */
        @media(max-width:480px){
          #bot-box { right:12px; left:12px; width:auto; bottom:90px; height:68vh; }
          #bot-btn { right:12px; bottom:12px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row gx-0">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar d-flex flex-column align-items-center py-5">
            <img src="<?= htmlspecialchars($admin_img) ?>" alt="Admin" class="profile-img shadow">
            <div class="mt-2 mb-4 text-center">
                <span class="fs-5 fw-bold"><?= htmlspecialchars($_SESSION['Username']) ?></span><br>
                <span class="badge bg-warning text-dark">Admin</span>
            </div>
            <nav class="nav flex-column w-100 px-3">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='admin_dashboard.php'?'active':'' ?>" href="admin_dashboard.php">🏠 Dashboard Home</a>
                <a class="nav-link" href="inventory_birds.php">🐦 Birds Inventory</a>
                <a class="nav-link" href="inventory_feed.php">🥦 Feed Inventory</a>
                <a class="nav-link" href="inventory_eggs.php">🥚 Egg Production</a>
                <a class="nav-link" href="payroll.php">💰 Payroll</a>
                <a class="nav-link" href="inventory_dashboard.php">📊 Inventory Analytics</a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='admin_biosecurity.php'?'active':'' ?>" href="admin_biosecurity.php">🦠 Biosecurity Management</a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF'])=='admin_purchases.php'?'active':'' ?>" href="admin_purchases.php">🛒 Purchases</a>
               <a class="nav-link" href="<?= BASE_URL ?>auth/logout.php">📒 Logout</a>
            </nav>
            <div class="mt-auto text-center mb-3">
                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" width="48" alt="Profile">
                <div class="mt-2 brand-title">Poultry Management</div>
            </div>
        </div>

        <!-- Main dashboard area -->
        <div class="col-md-9 dashboard-main">
            <div class="row g-4">
                <div class="col-12">
                    <h2 class="mb-2" style="color:#3b82f6;font-weight:700;">
                        <img src="https://img.icons8.com/color/48/000000/dashboard-layout.png" width="36" style="vertical-align:-7px;">
                        Admin Dashboard
                    </h2>
                    <hr style="border-color:#3b82f6;">
                </div>

                <!-- Inventory option cards -->
                <?php foreach($inventory_options as $opt): ?>
                <div class="col-sm-6 col-lg-4 mt-2">
                    <div class="card inventory-card">
                        <div class="card-body">
                            <div style="font-size:2rem;"><?= $opt['icon'] ?></div>
                            <div class="fw-semibold fs-5 mb-2"><?= htmlspecialchars($opt['name']) ?></div>
                            <a href="<?= htmlspecialchars($opt['link']) ?>" class="btn <?= htmlspecialchars($opt['color']) ?> inventory-btn shadow-sm">
                                Go to <?= htmlspecialchars($opt['name']) ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer -->
            <div class="footer-copyright">
                &copy; 2025 Poultry Management. All rights reserved.
            </div>
        </div>
    </div>
</div>

<!-- ===== Poultry Assistant Widget (single, clean) ===== -->
<button id="bot-btn" aria-label="Open Poultry Assistant" title="Poultry Assistant">
  <svg width="36" height="36" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="img">
    <defs><linearGradient id="g1" x1="0%" x2="100%"><stop offset="0%" stop-color="#2b6ee6"/><stop offset="100%" stop-color="#29c0ff"/></linearGradient></defs>
    <circle cx="32" cy="32" r="30" fill="url(#g1)"/>
    <path d="M40 26c1-2 0-5-2-6-2-1-4-1-6 0-3 1-5 3-6 6-1 3 0 6 4 8 4 1 7 1 10-1 3-2 3-5 0-7z" fill="#fff8e6"/>
    <circle cx="36.5" cy="24.5" r="2" fill="#1f2937"/>
    <path d="M30 33c-1 1-6 4-8 6-2 2-2 5 2 6 4 1 8 0 12-2 4-2 5-6 3-9" fill="#fff2d6" opacity="0.9"/>
    <g transform="translate(42,8)"><rect x="0" y="6" width="14" height="10" rx="3" ry="3" fill="#fff" opacity="0.95"/><circle cx="5" cy="3" r="2.2" fill="#fff" /><circle cx="2.5" cy="8.6" r="1.6" fill="#fff"/><path d="M2 14c1 0 2 0 3-1" stroke="#fff" stroke-width="0.6" fill="none"/></g>
  </svg>
  <div id="bot-dot" aria-hidden="true">!</div>
</button>

<div id="bot-box" role="dialog" aria-label="Poultry Assistant" aria-hidden="true">
  <div id="bot-header" title="Drag to move">
    <div class="msg-avatar" aria-hidden="true">
      <svg width="28" height="28" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><circle cx="32" cy="32" r="30" fill="#fff"/><path d="M38 28c1-2 0-4-2-5-2-1-4-1-6 0-3 1-5 3-6 5-1 3 0 6 4 7 4 1 7 1 10-1 3-2 3-5 0-6z" fill="#ffdf9b"/><circle cx="34.5" cy="25.5" r="2.2" fill="#1f2937"/></svg>
    </div>
    <div style="flex:1">
      <div class="header-title">Poultry Assistant</div>
      <div class="header-sub">Admin quick insights — ask anything</div>
    </div>
    <button id="bot-close" style="background:none;border:none;color:#fff; font-weight:700; cursor:pointer" aria-label="Close">✕</button>
  </div>

  <div id="bot-body" aria-live="polite"></div>

  <div id="bot-footer">
    <input id="bot-input" placeholder="What would you like to check?" aria-label="Message input">
    <button id="bot-voice" class="bot-ico-btn" title="Voice">🎤</button>
    <button id="bot-send" class="bot-ico-btn" title="Send">➤</button>
  </div>
</div>


<script>
// Client chatbot script - menu-driven and pretty buttons
(async function(){
  const botBtn = document.getElementById('bot-btn');
  const botBox = document.getElementById('bot-box');
  const botBody = document.getElementById('bot-body');
  const botInput = document.getElementById('bot-input');
  const botSend = document.getElementById('bot-send');
  const botClose = document.getElementById('bot-close');
  const botDot = document.getElementById('bot-dot');

  function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c])); }
  function addUser(t){ botBody.insertAdjacentHTML('beforeend', `<div class="msg-row msg-user"><div class="bubble" style="background:#dff4ff;">${escapeHtml(t)}</div></div>`); botBody.scrollTop = botBody.scrollHeight; }
  function addBot(t){ 
    const html = /\n/.test(t) || /\s\|\s/.test(t) 
      ? `<pre style="background:#fbfcff;padding:10px;border-radius:10px;border:1px solid #eef5ff;">${escapeHtml(t)}</pre>`
      : `<div class="bubble" style="background:#fff">${escapeHtml(t).replace(/\n/g,'<br>')}</div>`;
    botBody.insertAdjacentHTML('beforeend', `<div class="msg-row msg-bot"><div class="avatar"></div>${html}</div>`);
    botBody.scrollTop = botBody.scrollHeight;
  }
  function showTyping(){ removeTyping(); botBody.insertAdjacentHTML('beforeend', `<div id="typing" class="msg-row msg-bot"><div class="avatar"></div><div class="typing"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div></div>`); botBody.scrollTop = botBody.scrollHeight; }
  function removeTyping(){ const t=document.getElementById('typing'); if(t) t.remove(); }

  async function backendSend(message) {
    const r = await fetch('chatbot.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      credentials: 'same-origin',
      body: JSON.stringify({ message })
    });
    return r.json();
  }

  // Render menu buttons (nice-looking cards)
  async function showMenu() {
    showTyping();
    try {
      const data = await backendSend('__MENU__');
      removeTyping();
      if (!data.ok) { addBot(data.reply || 'Unable to load menu'); return; }
      addBot(data.reply || 'Choose one of the options:');
      const container = document.createElement('div');
      container.style.display = 'grid';
      container.style.gridTemplateColumns = 'repeat(2,1fr)';
      container.style.gap = '10px';
      container.style.marginTop = '8px';
      container.style.marginBottom = '8px';

      (data.menu || []).forEach(m => {
        const btn = document.createElement('button');
        btn.textContent = m.title;
        btn.style.padding = '10px';
        btn.style.borderRadius = '12px';
        btn.style.border = '1px solid rgba(40,90,255,0.12)';
        btn.style.background = 'linear-gradient(90deg,#ffffff,#f3f9ff)';
        btn.style.boxShadow = '0 6px 18px rgba(41,128,255,0.06)';
        btn.style.cursor = 'pointer';
        btn.style.fontWeight = 600;
        btn.addEventListener('click', ()=> onMenuClick(m.id, m.title));
        container.appendChild(btn);
      });

      botBody.appendChild(container);
      botBody.scrollTop = botBody.scrollHeight;
    } catch (e) {
      removeTyping();
      addBot('Network error while loading menu.');
      console.error(e);
    }
  }

  async function onMenuClick(id, title) {
    addUser(title);
    showTyping();
    try {
      const data = await backendSend('INTENT:' + id);
      removeTyping();
      if (!data.ok) { addBot(data.reply || 'No response'); return; }

      if (data.suggestions && data.suggestions.length) {
        addBot(data.reply || 'Choose a date:');
        const wrap = document.createElement('div');
        wrap.style.display = 'flex';
        wrap.style.flexWrap = 'wrap';
        wrap.style.gap = '6px';
        wrap.style.marginTop = '8px';

        data.suggestions.forEach(sg => {
          (sg.dates || []).slice(0,12).forEach(d => {
            const b = document.createElement('button');
            b.textContent = d;
            b.className = 'btn btn-sm';
            b.style.border = '1px solid #dceeff';
            b.style.background = '#fff';
            b.style.padding = '6px 8px';
            b.style.borderRadius = '8px';
            b.style.cursor = 'pointer';
            b.addEventListener('click', ()=> {
              // map table->intent_by_date naming used in server
              let intentMap = {
                'birdsmortality':'mortality_by_date',
                'birdspurchase':'purchase_birds_by_date',
                'production':'production_by_date',
                'sales':'sales_by_date',
                'feedpurchase':'feed_purchase_by_date',
                'feedconsumption':'feed_consumption_by_date',
                'biosecurity_logs':'biosecurity_by_date'
              };
              const table = sg.table;
              const intent = intentMap[table] || (id + '_by_date');
              sendIntentDate(intent, d);
            });
            wrap.appendChild(b);
          });
        });
        botBody.appendChild(wrap);
        botBody.scrollTop = botBody.scrollHeight;
        return;
      }

      if (data.rows && data.rows.length) {
        // show table formatted text
        const rows = data.rows;
        const cols = Object.keys(rows[0] || {});
        let txt = cols.join(' | ') + "\n" + cols.map(()=> '---').join(' | ') + "\n";
        rows.forEach(r => { txt += cols.map(c => String(r[c] ?? '')).join(' | ') + "\n"; });
        addBot(txt);
        return;
      }

      addBot(data.reply || 'No data found.');
    } catch (e) {
      removeTyping();
      addBot('Network error — could not reach server.');
      console.error(e);
    }
  }

  async function sendIntentDate(intent, date) {
    addUser(date);
    showTyping();
    try {
      const data = await backendSend('INTENT:' + intent + ':' + date);
      removeTyping();
      if (!data.ok) { addBot(data.reply || 'No response'); return; }
      if (data.rows && data.rows.length) {
        const rows = data.rows;
        const cols = Object.keys(rows[0] || {});
        let txt = cols.join(' | ') + "\n" + cols.map(()=> '---').join(' | ') + "\n";
        rows.forEach(r => { txt += cols.map(c => String(r[c] ?? '')).join(' | ') + "\n"; });
        addBot(txt);
        return;
      }
      addBot(data.reply || 'No records for this date.');
    } catch (e) {
      removeTyping();
      addBot('Network error — could not reach server.');
      console.error(e);
    }
  }

  // Free-text sending
  async function sendMessage() {
    const text = (botInput.value || '').trim();
    if (!text) return;
    addUser(text);
    botInput.value = '';
    showTyping();
    try {
      const data = await backendSend(text);
      removeTyping();
      if (!data.ok) { addBot(data.reply || 'No response'); return; }

      if (data.menu) {
        addBot(data.reply || 'Choose an option:');
        const container = document.createElement('div');
        container.style.display = 'flex';
        container.style.gap = '8px';
        container.style.flexWrap = 'wrap';
        data.menu.forEach(m => {
          const b = document.createElement('button');
          b.textContent = m.title;
          b.className = 'btn btn-outline-primary btn-sm';
          b.addEventListener('click', ()=> onMenuClick(m.id,m.title));
          container.appendChild(b);
        });
        botBody.appendChild(container);
        return;
      }

      if (data.suggestions && data.suggestions.length) {
        addBot(data.reply || 'Select a date:');
        const wrap = document.createElement('div');
        wrap.style.display='flex'; wrap.style.flexWrap='wrap'; wrap.style.gap='6px';
        data.suggestions.forEach(sg => (sg.dates||[]).slice(0,12).forEach(d => {
          const b = document.createElement('button');
          b.textContent = d;
          b.className='btn btn-outline-secondary btn-sm';
          b.addEventListener('click', ()=> sendIntentDate('mortality_by_date', d));
          wrap.appendChild(b);
        }));
        botBody.appendChild(wrap);
        return;
      }

      if (data.rows && data.rows.length) {
        const rows = data.rows;
        const cols = Object.keys(rows[0] || {});
        let txt = cols.join(' | ') + "\n" + cols.map(()=> '---').join(' | ') + "\n";
        rows.forEach(r => txt += cols.map(c => String(r[c] ?? '')).join(' | ') + "\n");
        addBot(txt);
        return;
      }

      addBot(data.reply || 'No results.');
    } catch (e) {
      removeTyping();
      addBot('Network error — could not reach server.');
      console.error(e);
    }
  }

  // UI wiring
  botBtn.addEventListener('click', () => {
    const open = (botBox.style.display === 'flex' || botBox.style.display === 'block');
    botBox.style.display = open ? 'none' : 'flex';
    if (!open) {
      botBody.innerHTML = ''; // clear chat each open or keep depending on preference
      setTimeout(()=> showMenu(), 120);
      botInput.focus();
    }
  });
  if (botClose) botClose.addEventListener('click', ()=> botBox.style.display='none');
  if (botSend) botSend.addEventListener('click', sendMessage);
  if (botInput) botInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });

})();
</script>




</body>
</html>
