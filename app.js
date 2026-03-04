/* app.js — Concrete Mix Design App */

document.addEventListener('DOMContentLoaded', function () {
  loadThemePreference();
  initNavToggle();
  initThemeSelector();
});

/* ── Navigation Toggle ── */
function initNavToggle() {
  const toggle  = document.querySelector('.nav-toggle');
  const sidebar = document.getElementById('sidebar');
  if (!toggle || !sidebar) return;

  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    const isOpen = sidebar.classList.toggle('open');
    document.body.classList.toggle('sidebar-open', isOpen);
    toggle.setAttribute('aria-expanded', isOpen);
  });

  sidebar.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      sidebar.classList.remove('open');
      document.body.classList.remove('sidebar-open');
    });
  });

  document.addEventListener('click', function (e) {
    if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
      sidebar.classList.remove('open');
      document.body.classList.remove('sidebar-open');
    }
  });
}

/* ── Theme Management ── */
function applyTheme(theme) {
  document.body.classList.toggle('dark-theme', theme === 'Dark');
}

function saveThemePreference(theme) {
  localStorage.setItem('concrete_theme', theme);
}

function loadThemePreference() {
  const t = localStorage.getItem('concrete_theme');
  if (t) applyTheme(t);
}

function initThemeSelector() {
  const sel = document.getElementById('themeSelect');
  if (sel) {
    sel.value = localStorage.getItem('concrete_theme') || 'Light';
    sel.addEventListener('change', function () {
      applyTheme(this.value);
      saveThemePreference(this.value);
    });
  }

  const tog = document.getElementById('darkModeToggle');
  if (tog) {
    const saved = localStorage.getItem('concrete_theme') || 'Light';
    tog.checked = (saved === 'Dark');
    updateThemePickers(saved);
    tog.addEventListener('change', function () {
      const t = this.checked ? 'Dark' : 'Light';
      applyTheme(t); saveThemePreference(t); updateThemePickers(t);
    });
  }
}

function pickTheme(theme) {
  applyTheme(theme);
  saveThemePreference(theme);
  const tog = document.getElementById('darkModeToggle');
  if (tog) tog.checked = (theme === 'Dark');
  updateThemePickers(theme);
}

function updateThemePickers(theme) {
  const light = document.getElementById('pickLight');
  const dark  = document.getElementById('pickDark');
  if (light) light.classList.toggle('selected', theme === 'Light');
  if (dark)  dark.classList.toggle('selected',  theme === 'Dark');
}

/* ── Saved Designs Helpers (for index.html) ── */
function updateSavedDisplay() {
  const list = document.getElementById('savedList');
  if (!list) return;
  list.innerHTML = '<li style="color:var(--text-muted);font-size:14px">Loading…</li>';
  fetch('save_design.php')
    .then(r => r.json())
    .then(data => {
      const saved = data.success ? (data.data||[]) : [];
      list.innerHTML = '';
      if (saved.length === 0) {
        list.innerHTML = '<li style="color:var(--text-muted);font-size:14px">No saved designs yet.</li>';
        return;
      }
      saved.slice(0,5).forEach((d, i) => {
        const date = d.created_at ? new Date(d.created_at).toLocaleDateString() : '—';
        list.innerHTML += `<li class="design-item">
          <div class="design-info">
            <h4>${d.name || 'Mix Design #'+(i+1)}</h4>
            <p>${d.grade || ''} • Saved: ${date}</p>
          </div>
          <span class="design-badge">${d.grade || 'M20'}</span>
        </li>`;
      });
    })
    .catch(() => {
      list.innerHTML = '<li style="color:var(--error);font-size:14px">Could not load designs.</li>';
    });
}
