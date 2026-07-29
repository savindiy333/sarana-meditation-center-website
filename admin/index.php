<?php
/**
 * ============================================================
 *  admin/index.php — bookings dashboard
 * ============================================================
 */
require_once __DIR__ . '/auth.php';
admin_require_login();
require_once __DIR__ . '/../db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ---------- filters ----------
$statusFilter = $_GET['status'] ?? '';
$allowedStatuses = ['new', 'contacted', 'confirmed', 'cancelled'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}
$search = trim($_GET['q'] ?? '');

$pdo = get_db_connection();

// ---------- stats ----------
$stats = ['new' => 0, 'contacted' => 0, 'confirmed' => 0, 'cancelled' => 0, 'total' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) AS c FROM bookings GROUP BY status') as $row) {
    $stats[$row['status']] = (int) $row['c'];
    $stats['total'] += (int) $row['c'];
}

// ---------- fetch bookings ----------
$where = [];
$params = [];
if ($statusFilter !== '') {
    $where[] = 'status = :status';
    $params[':status'] = $statusFilter;
}
if ($search !== '') {
    $where[] = '(full_name LIKE :q OR email LIKE :q OR phone LIKE :q OR program LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}
$sql = 'SELECT * FROM bookings';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

function h($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bookings — Saraṇa Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Manrope:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="admin.css">
<style>
/* ---- edit modal overlay ---- */
.modal-overlay{
  display:none; position:fixed; inset:0; z-index:1000;
  background:rgba(30,18,8,.55); backdrop-filter:blur(4px);
  align-items:center; justify-content:center; padding:20px;
}
.modal-overlay.is-open{ display:flex; }
.modal-box{
  background:var(--bg); border-radius:var(--radius-lg);
  box-shadow:0 24px 60px rgba(0,0,0,.22);
  width:100%; max-width:580px; max-height:90vh;
  display:flex; flex-direction:column;
  animation:modalIn .22s var(--ease);
}
@keyframes modalIn{
  from{ opacity:0; transform:translateY(18px) scale(.97); }
  to  { opacity:1; transform:none; }
}
.modal-header{
  display:flex; align-items:center; justify-content:space-between;
  padding:20px 24px 16px;
  border-bottom:1px solid var(--line);
}
.modal-header h2{
  font-family:var(--font-display); font-size:1.1rem; margin:0;
  color:var(--ink);
}
.modal-close{
  background:none; border:none; cursor:pointer;
  color:var(--ink-soft); padding:4px; line-height:0;
  border-radius:var(--radius-sm); transition:background .2s;
}
.modal-close:hover{ background:var(--bg-alt); color:var(--ink); }
.modal-body{
  flex:1; overflow-y:auto;
  padding:20px 24px;
  display:grid; gap:14px;
}
.modal-row{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.mfield{ display:flex; flex-direction:column; gap:5px; }
.mfield label{ font-size:.8rem; font-weight:700; color:var(--ink-soft); text-transform:uppercase; letter-spacing:.04em; }
.mfield input, .mfield select, .mfield textarea{
  padding:10px 12px;
  border:1px solid var(--line); border-radius:var(--radius-sm);
  background:var(--white); color:var(--ink);
  font-family:var(--font-body); font-size:.9rem;
  transition:border-color .2s;
}
.mfield input:focus, .mfield select:focus, .mfield textarea:focus{
  outline:none; border-color:var(--gold);
}
.mfield textarea{ resize:vertical; min-height:80px; }
.modal-footer{
  padding:16px 24px;
  border-top:1px solid var(--line);
  display:flex; gap:10px; justify-content:flex-end;
}
.modal-footer .admin-btn{ width:auto; padding:10px 22px; font-size:.88rem; }
.modal-msg{ font-size:.82rem; margin-right:auto; align-self:center; }
.modal-msg.is-success{ color:#4a6b34; }
.modal-msg.is-error{ color:#8f2f20; }

/* ---- action button pills ---- */
.row-actions{ display:flex; gap:6px; align-items:center; flex-wrap:wrap; }
.act-btn{
  display:inline-flex; align-items:center; gap:5px;
  border:none; border-radius:100px;
  padding:5px 11px; font-size:.76rem; font-weight:700;
  font-family:var(--font-body); cursor:pointer;
  white-space:nowrap; transition:opacity .2s, transform .15s;
}
.act-btn:hover{ opacity:.85; transform:translateY(-1px); }
.act-btn:active{ transform:none; }
.act-btn svg{ width:13px; height:13px; flex-shrink:0; }
.act-btn.edit   { background:rgba(138,90,52,.13); color:var(--gold-deep); }
.act-btn.resend { background:rgba(122,158,96,.15); color:#3d6028; }
.act-btn.delete { background:rgba(179,64,46,.12);  color:#8f2f20; }
.act-btn:disabled{ opacity:.45; cursor:not-allowed; transform:none; }

/* ---- status badge colors (used inside table) ---- */
.status-select{
  padding:6px 8px; border-radius:var(--radius-sm);
  border:1px solid var(--line); background:var(--white);
  font-size:.8rem; font-family:var(--font-body);
}

/* ---- toast notification ---- */
.toast{
  position:fixed; bottom:28px; right:28px; z-index:2000;
  padding:14px 22px; border-radius:var(--radius-md);
  font-family:var(--font-body); font-size:.9rem; font-weight:600;
  box-shadow:0 8px 32px rgba(0,0,0,.18);
  transform:translateY(80px); opacity:0;
  transition:transform .3s var(--ease), opacity .3s;
}
.toast.show{ transform:none; opacity:1; }
.toast.is-success{ background:#2e5e1a; color:#fff; }
.toast.is-error  { background:#8f2f20; color:#fff; }
</style>
</head>
<body class="admin">

  <div class="admin-topbar">
    <h1>Saraṇa Admin</h1>
    <nav class="admin-nav">
      <a href="index.php" class="active">Bookings</a>
      <a href="content.php">Wording</a>
      <a href="images.php">Images</a>
    </nav>
    <div class="admin-topbar-right">
      <span class="who">Signed in as <strong><?= h($_SESSION['admin_username'] ?? 'admin') ?></strong></span>
      <a href="logout.php" class="admin-btn ghost" style="width:auto;padding:8px 16px;font-size:.85rem;">Log out</a>
    </div>
  </div>

  <div class="admin-main">

    <div class="admin-stats">
      <div class="admin-stat"><div class="num"><?= $stats['total'] ?></div><div class="label">Total</div></div>
      <div class="admin-stat"><div class="num"><?= $stats['new'] ?></div><div class="label">New</div></div>
      <div class="admin-stat"><div class="num"><?= $stats['contacted'] ?></div><div class="label">Contacted</div></div>
      <div class="admin-stat"><div class="num"><?= $stats['confirmed'] ?></div><div class="label">Confirmed</div></div>
      <div class="admin-stat"><div class="num"><?= $stats['cancelled'] ?></div><div class="label">Cancelled</div></div>
    </div>

    <div class="admin-toolbar">
      <form method="get" action="index.php">
        <input type="text" name="q" placeholder="Search name, email, phone, program…" value="<?= h($search) ?>">
        <select name="status">
          <option value="">All statuses</option>
          <?php foreach ($allowedStatuses as $s): ?>
            <option value="<?= h($s) ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="admin-btn">Filter</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
          <a href="index.php" class="admin-btn ghost">Clear</a>
        <?php endif; ?>
      </form>
      <a href="export-csv.php?status=<?= urlencode($statusFilter) ?>&q=<?= urlencode($search) ?>" class="admin-btn ghost">Export CSV</a>
    </div>

    <div class="admin-table-wrap">
      <?php if (!$bookings): ?>
        <div class="admin-empty">No bookings found.</div>
      <?php else: ?>
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Received</th>
            <th>Name</th>
            <th>Contact</th>
            <th>Program</th>
            <th>Preferred dates</th>
            <th>Message</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr data-row-id="<?= (int) $b['id'] ?>">
            <td class="nowrap">#<?= (int) $b['id'] ?></td>
            <td class="nowrap"><?= h(date('d M Y, g:i A', strtotime($b['created_at']))) ?></td>
            <td data-field="full_name"><?= h($b['full_name']) ?></td>
            <td class="nowrap" data-field="contact">
              <div><a href="mailto:<?= h($b['email']) ?>"><?= h($b['email']) ?></a></div>
              <?php if ($b['phone']): ?><div><?= h($b['phone']) ?></div><?php endif; ?>
            </td>
            <td data-field="program"><?= h($b['program']) ?></td>
            <td data-field="preferred_dates"><?= h($b['preferred_dates'] ?: '—') ?></td>
            <td class="msg-cell" data-field="message"><?= h($b['message'] ?: '—') ?></td>
            <td>
              <select class="status-select" data-id="<?= (int) $b['id'] ?>">
                <?php foreach ($allowedStatuses as $s): ?>
                  <option value="<?= h($s) ?>" <?= $b['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td>
              <div class="row-actions">
                <!-- Edit -->
                <button type="button" class="act-btn edit" title="Edit booking"
                  data-edit-id="<?= (int) $b['id'] ?>"
                  data-edit-name="<?= h($b['full_name']) ?>"
                  data-edit-email="<?= h($b['email']) ?>"
                  data-edit-phone="<?= h($b['phone'] ?? '') ?>"
                  data-edit-program="<?= h($b['program']) ?>"
                  data-edit-dates="<?= h($b['preferred_dates'] ?? '') ?>"
                  data-edit-message="<?= h($b['message'] ?? '') ?>"
                  data-edit-status="<?= h($b['status']) ?>">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  Edit
                </button>
                <!-- Resend -->
                <button type="button" class="act-btn resend" title="Resend confirmation email" data-resend-id="<?= (int) $b['id'] ?>">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
                  Resend
                </button>
                <!-- Delete -->
                <button type="button" class="act-btn delete" title="Delete booking permanently" data-delete-id="<?= (int) $b['id'] ?>">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                  Delete
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <p class="admin-footer-note">Showing <?= count($bookings) ?> of <?= $stats['total'] ?> total bookings.</p>
  </div>

  <!-- ============ EDIT MODAL ============ -->
  <div class="modal-overlay" id="editModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal-box">
      <div class="modal-header">
        <h2 id="modalTitle">Edit Booking</h2>
        <button class="modal-close" id="modalCloseBtn" aria-label="Close">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
      <form id="editForm" class="modal-body">
        <input type="hidden" id="edit-id" name="id">
        <div class="modal-row">
          <div class="mfield">
            <label for="edit-name">Full Name</label>
            <input type="text" id="edit-name" name="full_name" required>
          </div>
          <div class="mfield">
            <label for="edit-email">Email</label>
            <input type="email" id="edit-email" name="email" required>
          </div>
        </div>
        <div class="modal-row">
          <div class="mfield">
            <label for="edit-phone">Phone</label>
            <input type="text" id="edit-phone" name="phone" placeholder="Optional">
          </div>
          <div class="mfield">
            <label for="edit-status">Status</label>
            <select id="edit-status" name="status">
              <option value="new">New</option>
              <option value="contacted">Contacted</option>
              <option value="confirmed">Confirmed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div class="modal-row">
          <div class="mfield">
            <label for="edit-program">Program</label>
            <select id="edit-program" name="program">
              <option>A single daily session</option>
              <option>Reset Weekend retreat</option>
              <option>Deep Stillness Retreat (5 days)</option>
              <option>Long Silent Retreat (10 days)</option>
              <option>Corporate mindfulness</option>
            </select>
          </div>
          <div class="mfield">
            <label for="edit-dates">Preferred Dates</label>
            <input type="text" id="edit-dates" name="preferred_dates" placeholder="e.g. 12–14 August">
          </div>
        </div>
        <div class="mfield">
          <label for="edit-message">Message</label>
          <textarea id="edit-message" name="message"></textarea>
        </div>
      </form>
      <div class="modal-footer">
        <span class="modal-msg" id="modalMsg"></span>
        <button type="button" class="admin-btn ghost" id="modalCancelBtn">Cancel</button>
        <button type="button" class="admin-btn" id="modalSaveBtn">Save Changes</button>
      </div>
    </div>
  </div>

  <!-- ============ TOAST ============ -->
  <div class="toast" id="toast" role="alert" aria-live="assertive"></div>

  <script>
    const CSRF = <?= json_encode($csrf) ?>;

    // ---- Toast helper ----
    const toastEl = document.getElementById('toast');
    let toastTimer;
    function showToast(msg, type = 'is-success') {
      clearTimeout(toastTimer);
      toastEl.textContent  = msg;
      toastEl.className    = `toast show ${type}`;
      toastTimer = setTimeout(() => { toastEl.classList.remove('show'); }, 3200);
    }

    // ---- Status select ----
    document.querySelectorAll('.status-select').forEach(sel => {
      sel.addEventListener('change', async () => {
        const id = sel.dataset.id;
        const status = sel.value;
        sel.disabled = true;
        try {
          const res  = await fetch('update-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, status, csrf: CSRF })
          });
          const data = await res.json();
          if (data.success) {
            showToast('Status updated ✓');
          } else {
            showToast(data.message || 'Could not update status.', 'is-error');
          }
        } catch (e) {
          showToast('Network error — please try again.', 'is-error');
        } finally {
          sel.disabled = false;
        }
      });
    });

    // ---- Resend confirmation ----
    document.querySelectorAll('[data-resend-id]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id       = btn.dataset.resendId;
        const orig     = btn.innerHTML;
        btn.disabled   = true;
        btn.textContent = 'Sending…';
        try {
          const res  = await fetch('resend-confirmation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, csrf: CSRF })
          });
          const data = await res.json();
          if (data.success) {
            showToast('Confirmation email sent ✓');
            btn.textContent = 'Sent ✓';
            setTimeout(() => { btn.innerHTML = orig; btn.disabled = false; }, 2500);
          } else {
            showToast(data.message || 'Could not send the confirmation email.', 'is-error');
            btn.innerHTML = orig;
            btn.disabled  = false;
          }
        } catch (e) {
          showToast('Network error — please try again.', 'is-error');
          btn.innerHTML = orig;
          btn.disabled  = false;
        }
      });
    });

    // ---- Delete ----
    document.querySelectorAll('[data-delete-id]').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.deleteId;
        if (!confirm('Delete this booking permanently? This cannot be undone.')) return;
        btn.disabled = true;
        try {
          const res  = await fetch('delete-booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ id, csrf: CSRF })
          });
          const data = await res.json();
          if (data.success) {
            const row = document.querySelector(`tr[data-row-id="${id}"]`);
            if (row) {
              row.style.transition = 'opacity .3s';
              row.style.opacity    = '0';
              setTimeout(() => row.remove(), 300);
            }
            showToast('Booking deleted.');
          } else {
            showToast(data.message || 'Could not delete booking.', 'is-error');
            btn.disabled = false;
          }
        } catch (e) {
          showToast('Network error — please try again.', 'is-error');
          btn.disabled = false;
        }
      });
    });

    // ---- Edit modal ----
    const modal      = document.getElementById('editModal');
    const modalMsg   = document.getElementById('modalMsg');
    const modalSave  = document.getElementById('modalSaveBtn');
    const editForm   = document.getElementById('editForm');

    function openModal(btn) {
      const d = btn.dataset;
      document.getElementById('edit-id').value       = d.editId;
      document.getElementById('edit-name').value     = d.editName;
      document.getElementById('edit-email').value    = d.editEmail;
      document.getElementById('edit-phone').value    = d.editPhone;
      document.getElementById('edit-dates').value    = d.editDates;
      document.getElementById('edit-message').value  = d.editMessage;

      // Program select
      const progSel = document.getElementById('edit-program');
      [...progSel.options].forEach(o => {
        o.selected = o.value === d.editProgram || o.textContent === d.editProgram;
      });
      // Status select
      const statSel = document.getElementById('edit-status');
      [...statSel.options].forEach(o => { o.selected = o.value === d.editStatus; });

      modalMsg.textContent = '';
      modalMsg.className   = 'modal-msg';
      modal.classList.add('is-open');
      document.getElementById('edit-name').focus();
    }

    function closeModal() {
      modal.classList.remove('is-open');
    }

    document.querySelectorAll('[data-edit-id]').forEach(btn => btn.addEventListener('click', () => openModal(btn)));
    document.getElementById('modalCloseBtn').addEventListener('click', closeModal);
    document.getElementById('modalCancelBtn').addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal(); });

    modalSave.addEventListener('click', async () => {
      modalMsg.textContent = '';
      const id     = document.getElementById('edit-id').value;
      const params = new URLSearchParams({
        id,
        csrf:            CSRF,
        full_name:       document.getElementById('edit-name').value.trim(),
        email:           document.getElementById('edit-email').value.trim(),
        phone:           document.getElementById('edit-phone').value.trim(),
        program:         document.getElementById('edit-program').value,
        preferred_dates: document.getElementById('edit-dates').value.trim(),
        message:         document.getElementById('edit-message').value.trim(),
        status:          document.getElementById('edit-status').value,
      });

      modalSave.disabled    = true;
      modalSave.textContent = 'Saving…';

      try {
        const res  = await fetch('edit-booking.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
        const data = await res.json();

        if (data.success) {
          // Update visible row cells
          const row = document.querySelector(`tr[data-row-id="${id}"]`);
          if (row) {
            row.querySelector('[data-field="full_name"]').textContent = params.get('full_name');
            row.querySelector('[data-field="program"]').textContent   = params.get('program');
            const datesCell = row.querySelector('[data-field="preferred_dates"]');
            if (datesCell) datesCell.textContent = params.get('preferred_dates') || '—';
            const msgCell = row.querySelector('[data-field="message"]');
            if (msgCell) msgCell.textContent = params.get('message') || '—';
            // Update status select
            const statusSel = row.querySelector('.status-select');
            if (statusSel) statusSel.value = params.get('status');
            // Update edit button data attrs
            const editBtn = row.querySelector('[data-edit-id]');
            if (editBtn) {
              editBtn.dataset.editName    = params.get('full_name');
              editBtn.dataset.editEmail   = params.get('email');
              editBtn.dataset.editPhone   = params.get('phone');
              editBtn.dataset.editProgram = params.get('program');
              editBtn.dataset.editDates   = params.get('preferred_dates');
              editBtn.dataset.editMessage = params.get('message');
              editBtn.dataset.editStatus  = params.get('status');
            }
            // Highlight row briefly
            row.style.transition = 'background .4s';
            row.style.background = 'rgba(122,158,96,.12)';
            setTimeout(() => { row.style.background = ''; }, 900);
          }
          showToast('Booking saved ✓');
          closeModal();
        } else {
          modalMsg.textContent = data.message || 'Could not save changes.';
          modalMsg.className   = 'modal-msg is-error';
        }
      } catch (e) {
        modalMsg.textContent = 'Network error — please try again.';
        modalMsg.className   = 'modal-msg is-error';
      } finally {
        modalSave.disabled    = false;
        modalSave.textContent = 'Save Changes';
      }
    });
  </script>

</body>
</html>
