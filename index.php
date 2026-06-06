<?php
// index.php — Server-side rendered Activity Calendar
require_once 'php/config.php';

$db = getDB();

// ── GET MONTH/YEAR FROM URL OR DEFAULT TO NOW ──
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// Keep month/year in valid range
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

// ── HANDLE FORM SUBMISSIONS (POST) ──
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE ACTIVITY
    if ($action === 'create_activity') {
        $title       = htmlspecialchars(trim($_POST['title'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $location    = htmlspecialchars(trim($_POST['location'] ?? ''));
        $start       = $_POST['start_datetime'] ?? '';
        $end         = $_POST['end_datetime'] ?? '';
        $max         = !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null;
        $created_by  = htmlspecialchars(trim($_POST['created_by'] ?? ''));

        if (!$title || !$start || !$end || !$created_by) {
            $message = 'Please fill in all required fields.';
            $message_type = 'error';
        } elseif ($start >= $end) {
            $message = 'End time must be after start time.';
            $message_type = 'error';
        } else {
            $stmt = $db->prepare("INSERT INTO activities (title, description, category_id, location, start_datetime, end_datetime, max_participants, created_by) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->execute([$title, $description, $category_id, $location, $start, $end, $max, $created_by]);
            $message = 'Activity created successfully!';
            $message_type = 'success';
        }
    }

    // UPDATE ACTIVITY
    if ($action === 'update_activity') {
        $id          = (int)$_POST['id'];
        $title       = htmlspecialchars(trim($_POST['title'] ?? ''));
        $description = htmlspecialchars(trim($_POST['description'] ?? ''));
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $location    = htmlspecialchars(trim($_POST['location'] ?? ''));
        $start       = $_POST['start_datetime'] ?? '';
        $end         = $_POST['end_datetime'] ?? '';
        $max         = !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null;

        $stmt = $db->prepare("UPDATE activities SET title=?, description=?, category_id=?, location=?, start_datetime=?, end_datetime=?, max_participants=? WHERE id=?");
        $stmt->execute([$title, $description, $category_id, $location, $start, $end, $max, $id]);
        $message = 'Activity updated!';
        $message_type = 'success';
    }

    // DELETE ACTIVITY
    if ($action === 'delete_activity') {
        $id = (int)$_POST['id'];
        $stmt = $db->prepare("DELETE FROM activities WHERE id=?");
        $stmt->execute([$id]);
        $message = 'Activity deleted.';
        $message_type = 'success';
    }

    // RSVP
    if ($action === 'rsvp') {
        $activity_id = (int)$_POST['activity_id'];
        $name        = htmlspecialchars(trim($_POST['participant_name'] ?? ''));

        if (!$name) {
            $message = 'Please enter your name.';
            $message_type = 'error';
        } else {
            // Check capacity
            $stmt = $db->prepare("SELECT max_participants, COUNT(r.id) AS rsvp_count FROM activities a LEFT JOIN rsvps r ON a.id = r.activity_id WHERE a.id = ? GROUP BY a.id");
            $stmt->execute([$activity_id]);
            $info = $stmt->fetch();

            if ($info && $info['max_participants'] && $info['rsvp_count'] >= $info['max_participants']) {
                $message = 'This activity is full!';
                $message_type = 'error';
            } else {
                $stmt = $db->prepare("INSERT IGNORE INTO rsvps (activity_id, participant_name) VALUES (?,?)");
                $stmt->execute([$activity_id, $name]);
                if ($db->lastInsertId()) {
                    $message = "You're in, $name!";
                    $message_type = 'success';
                } else {
                    $message = 'You already joined this activity!';
                    $message_type = 'error';
                }
            }
        }
    }

    // Redirect to avoid form resubmission
    $redirect = "index.php?month=$month&year=$year";
    if (isset($_POST['activity_id'])) $redirect .= "&detail=" . (int)$_POST['activity_id'];
    if (isset($_POST['id']) && $action !== 'delete_activity') $redirect .= "&detail=" . (int)$_POST['id'];
    header("Location: $redirect&msg=" . urlencode($message) . "&msg_type=$message_type");
    exit;
}

// ── SHOW MESSAGE FROM REDIRECT ──
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $message_type = $_GET['msg_type'] ?? 'success';
}

// ── FETCH DATA ──
// Fetch activities for this month
$stmt = $db->prepare("
    SELECT a.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
           COUNT(r.id) AS rsvp_count
    FROM activities a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN rsvps r ON a.id = r.activity_id
    WHERE MONTH(a.start_datetime) = ? AND YEAR(a.start_datetime) = ?
    GROUP BY a.id
    ORDER BY a.start_datetime ASC
");
$stmt->execute([$month, $year]);
$activities = $stmt->fetchAll();

// Fetch categories
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Fetch detail if requested
$detail = null;
if (isset($_GET['detail'])) {
    $did = (int)$_GET['detail'];
    $stmt = $db->prepare("
        SELECT a.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
               COUNT(r.id) AS rsvp_count
        FROM activities a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN rsvps r ON a.id = r.activity_id
        WHERE a.id = ?
        GROUP BY a.id
    ");
    $stmt->execute([$did]);
    $detail = $stmt->fetch();

    if ($detail) {
        $stmt2 = $db->prepare("SELECT participant_name, joined_at FROM rsvps WHERE activity_id = ? ORDER BY joined_at ASC");
        $stmt2->execute([$did]);
        $detail['rsvps'] = $stmt2->fetchAll();
    }
}

// ── CALENDAR HELPERS ──
$months_list = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$month_name  = $months_list[$month];
$first_day   = (int)date('w', mktime(0,0,0,$month,1,$year)); // 0=Sun
$days_in_month = (int)date('t', mktime(0,0,0,$month,1,$year));
$today_day   = (date('m') == $month && date('Y') == $year) ? (int)date('d') : 0;

// Group activities by day
$by_day = [];
foreach ($activities as $a) {
    $d = (int)date('j', strtotime($a['start_datetime']));
    $by_day[$d][] = $a;
}

// Prev/next month links
$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ActivityHub — Campus Calendar</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0f0f13; --surface: #1a1a22; --surface2: #22222e; --border: #2e2e3e;
    --accent: #c8f135; --accent2: #7c6af7; --text: #f0f0f5; --text-muted: #888899;
    --danger: #ff4d6d; --radius: 14px;
    --font-head: 'Syne', sans-serif; --font-body: 'DM Sans', sans-serif;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: var(--font-body); min-height: 100vh; }

  /* HEADER */
  header { display:flex; align-items:center; justify-content:space-between; padding:20px 36px; border-bottom:1px solid var(--border); background:rgba(15,15,19,0.95); position:sticky; top:0; z-index:100; }
  .logo { font-family:var(--font-head); font-size:1.5rem; font-weight:800; letter-spacing:-0.03em; }
  .logo span { color:var(--accent); }
  .header-nav a { color:var(--text-muted); text-decoration:none; margin-left:20px; font-size:0.875rem; }
  .header-nav a:hover { color:var(--text); }

  /* BUTTONS */
  .btn { font-family:var(--font-body); font-weight:500; font-size:0.875rem; border:none; border-radius:8px; cursor:pointer; padding:9px 18px; transition:all 0.18s; text-decoration:none; display:inline-block; }
  .btn-primary { background:var(--accent); color:#0f0f13; }
  .btn-primary:hover { background:#d4f545; }
  .btn-secondary { background:var(--surface2); color:var(--text); border:1px solid var(--border); }
  .btn-secondary:hover { background:var(--border); }
  .btn-danger { background:var(--danger); color:white; }
  .btn-ghost { background:transparent; color:var(--text-muted); border:1px solid var(--border); }
  .btn-ghost:hover { color:var(--text); }

  /* LAYOUT */
  .app { display:grid; grid-template-columns:280px 1fr; min-height:calc(100vh - 65px); }

  /* SIDEBAR */
  .sidebar { border-right:1px solid var(--border); padding:28px 20px; display:flex; flex-direction:column; gap:24px; }
  .section-title { font-family:var(--font-head); font-size:0.75rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--text-muted); margin-bottom:10px; }

  /* MINI CALENDAR */
  .mini-cal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
  .mini-cal-header h3 { font-family:var(--font-head); font-size:0.95rem; font-weight:700; }
  .mini-cal-header a { color:var(--text-muted); text-decoration:none; padding:4px 8px; border-radius:6px; transition:0.15s; }
  .mini-cal-header a:hover { background:var(--surface2); color:var(--text); }
  .mini-cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; text-align:center; }
  .mini-cal-grid .day-label { font-size:0.7rem; color:var(--text-muted); padding:4px 0; font-weight:500; }
  .mini-cal-grid .day { font-size:0.8rem; padding:5px 3px; border-radius:6px; color:var(--text-muted); }
  .mini-cal-grid .day.today { background:var(--accent2); color:white; font-weight:600; }
  .mini-cal-grid .day.has-event { color:var(--accent); font-weight:600; }
  .mini-cal-grid .day.empty { cursor:default; }

  /* CATEGORY FILTERS */
  .category-filters { display:flex; flex-direction:column; gap:6px; }
  .cat-chip { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; text-decoration:none; color:var(--text); border:1px solid transparent; font-size:0.875rem; transition:0.15s; }
  .cat-chip:hover { background:var(--surface2); }
  .cat-chip.active { background:var(--surface2); border-color:var(--border); }
  .cat-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

  /* MAIN */
  .main { padding:28px 32px; }
  .view-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
  .view-title { font-family:var(--font-head); font-size:1.8rem; font-weight:800; }
  .view-subtitle { font-size:0.875rem; color:var(--text-muted); margin-top:2px; }
  .nav-btns { display:flex; gap:8px; }

  /* CALENDAR GRID */
  .calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:8px; margin-bottom:32px; }
  .cal-day-label { text-align:center; font-size:0.75rem; font-weight:600; color:var(--text-muted); letter-spacing:0.05em; padding-bottom:8px; text-transform:uppercase; }
  .cal-cell { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); min-height:110px; padding:10px; transition:0.15s; }
  .cal-cell.empty { background:transparent; border-color:transparent; }
  .cal-cell.today { border-color:var(--accent); }
  .cal-date { font-family:var(--font-head); font-size:0.95rem; font-weight:700; margin-bottom:6px; }
  .cal-cell.today .cal-date { background:var(--accent); color:#0f0f13; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.8rem; }
  .cal-event-pill { display:block; font-size:0.68rem; padding:2px 6px; border-radius:4px; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#0f0f13; font-weight:500; text-decoration:none; transition:0.1s; }
  .cal-event-pill:hover { opacity:0.85; }

  /* ACTIVITY LIST */
  .activities-list { display:flex; flex-direction:column; gap:12px; }
  .activity-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:18px 20px; display:flex; gap:16px; align-items:flex-start; transition:0.18s; text-decoration:none; color:var(--text); }
  .activity-card:hover { border-color:var(--accent2); transform:translateY(-1px); }
  .act-color-bar { width:4px; border-radius:4px; align-self:stretch; flex-shrink:0; }
  .act-info { flex:1; min-width:0; }
  .act-title { font-family:var(--font-head); font-size:1rem; font-weight:700; margin-bottom:4px; }
  .act-meta { display:flex; gap:14px; flex-wrap:wrap; font-size:0.8rem; color:var(--text-muted); margin-bottom:8px; }
  .act-badges { display:flex; gap:6px; flex-wrap:wrap; }
  .badge { font-size:0.7rem; padding:3px 8px; border-radius:20px; font-weight:500; }
  .badge-full { background:rgba(255,77,109,0.2); color:var(--danger); }
  .badge-open { background:rgba(200,241,53,0.15); color:var(--accent); }
  .act-rsvp-count { font-family:var(--font-head); font-size:1.4rem; font-weight:800; color:var(--text-muted); text-align:right; flex-shrink:0; }
  .act-rsvp-count small { display:block; font-size:0.65rem; font-weight:400; font-family:var(--font-body); }

  /* DETAIL PANEL */
  .detail-panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:28px; margin-bottom:28px; }
  .detail-strip { height:6px; border-radius:4px; margin-bottom:20px; }
  .detail-title { font-family:var(--font-head); font-size:1.6rem; font-weight:800; margin-bottom:8px; }
  .detail-meta { display:flex; flex-direction:column; gap:8px; margin-bottom:20px; }
  .detail-meta-row { display:flex; align-items:center; gap:10px; font-size:0.875rem; color:var(--text-muted); }
  .detail-desc { font-size:0.9rem; line-height:1.6; color:var(--text-muted); margin-bottom:20px; }
  .rsvp-section { border-top:1px solid var(--border); padding-top:20px; margin-top:20px; }
  .rsvp-title { font-family:var(--font-head); font-weight:700; margin-bottom:12px; font-size:0.95rem; }
  .rsvp-list { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
  .rsvp-chip { background:var(--surface2); border:1px solid var(--border); border-radius:20px; font-size:0.8rem; padding:4px 12px; color:var(--text-muted); }
  .rsvp-form { display:flex; gap:8px; }
  .detail-actions { display:flex; gap:8px; margin-top:20px; border-top:1px solid var(--border); padding-top:20px; flex-wrap:wrap; }

  /* FORMS */
  .form-panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:28px; margin-bottom:28px; }
  .form-panel h2 { font-family:var(--font-head); font-size:1.3rem; font-weight:800; margin-bottom:20px; }
  .form-group { margin-bottom:16px; }
  .form-label { display:block; font-size:0.8rem; font-weight:500; color:var(--text-muted); margin-bottom:6px; letter-spacing:0.04em; text-transform:uppercase; }
  .form-input, .form-select, .form-textarea { width:100%; background:var(--surface2); border:1px solid var(--border); border-radius:8px; color:var(--text); font-family:var(--font-body); font-size:0.9rem; padding:10px 14px; outline:none; transition:0.15s; }
  .form-input:focus, .form-select:focus, .form-textarea:focus { border-color:var(--accent); }
  .form-textarea { resize:vertical; min-height:80px; }
  .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .form-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:24px; }

  /* MESSAGE */
  .message { padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:0.875rem; }
  .message.success { background:rgba(200,241,53,0.1); border:1px solid var(--accent); color:var(--accent); }
  .message.error { background:rgba(255,77,109,0.1); border:1px solid var(--danger); color:var(--danger); }

  /* EMPTY */
  .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
  .empty-state .emoji { font-size:3rem; margin-bottom:12px; }
  .empty-state h3 { font-family:var(--font-head); font-size:1.1rem; font-weight:700; color:var(--text); margin-bottom:6px; }

  @media(max-width:900px) { .app { grid-template-columns:1fr; } .sidebar { display:none; } .form-row { grid-template-columns:1fr; } }
</style>
</head>
<body>

<header>
  <div class="logo">Activity<span>Hub</span></div>
  <div class="header-nav">
    <a href="index.php">Calendar</a>
    <a href="team.php">Our Team</a>
    <a href="index.php?action=new&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-primary" style="margin-left:16px">+ New Activity</a>
  </div>
</header>

<div class="app">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="mini-calendar">
      <div class="mini-cal-header">
        <a href="index.php?month=<?= $prev_month ?>&year=<?= $prev_year ?>">&#8592;</a>
        <h3><?= $month_name ?> <?= $year ?></h3>
        <a href="index.php?month=<?= $next_month ?>&year=<?= $next_year ?>">&#8594;</a>
      </div>
      <div class="mini-cal-grid">
        <?php foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $dl): ?>
          <div class="day-label"><?= $dl ?></div>
        <?php endforeach; ?>
        <?php for($i=0;$i<$first_day;$i++): ?>
          <div class="day empty"></div>
        <?php endfor; ?>
        <?php for($d=1;$d<=$days_in_month;$d++): ?>
          <?php $cls = ($d===$today_day)?'today':''; $cls .= isset($by_day[$d])?' has-event':''; ?>
          <div class="day <?= $cls ?>"><?= $d ?></div>
        <?php endfor; ?>
      </div>
    </div>

    <div>
      <p class="section-title">Filter by Category</p>
      <div class="category-filters">
        <?php $active_cat = $_GET['cat'] ?? 'all'; ?>
        <a href="index.php?month=<?= $month ?>&year=<?= $year ?>" class="cat-chip <?= $active_cat==='all'?'active':'' ?>">
          <span class="cat-dot" style="background:#888899"></span>
          <span>All Categories</span>
        </a>
        <?php foreach($categories as $c): ?>
          <a href="index.php?month=<?= $month ?>&year=<?= $year ?>&cat=<?= $c['id'] ?>" class="cat-chip <?= $active_cat==$c['id']?'active':'' ?>">
            <span class="cat-dot" style="background:<?= $c['color'] ?>"></span>
            <span><?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">
    <?php if($message): ?>
      <div class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php
    // Filter activities by category if selected
    $filtered = $activities;
    if ($active_cat !== 'all') {
        $filtered = array_filter($activities, fn($a) => $a['category_id'] == $active_cat);
    }
    ?>

    <!-- NEW / EDIT FORM -->
    <?php if(isset($_GET['action']) && $_GET['action']==='new'): ?>
    <div class="form-panel">
      <h2>➕ New Activity</h2>
      <form method="POST" action="index.php?month=<?= $month ?>&year=<?= $year ?>">
        <input type="hidden" name="action" value="create_activity">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-input" placeholder="e.g. Basketball Pickup Game" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-textarea" placeholder="What's this activity about?"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="">No Category</option>
              <?php foreach($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-input" placeholder="e.g. Room 204">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date & Time *</label>
            <input type="datetime-local" name="start_datetime" class="form-input" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date & Time *</label>
            <input type="datetime-local" name="end_datetime" class="form-input" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Max Participants</label>
            <input type="number" name="max_participants" class="form-input" placeholder="Leave blank = unlimited" min="1">
          </div>
          <div class="form-group">
            <label class="form-label">Your Name *</label>
            <input type="text" name="created_by" class="form-input" placeholder="Created by" required>
          </div>
        </div>
        <div class="form-actions">
          <a href="index.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Create Activity</button>
        </div>
      </form>
    </div>

    <?php elseif(isset($_GET['action']) && $_GET['action']==='edit' && isset($_GET['id'])): ?>
    <?php
      $eid = (int)$_GET['id'];
      $stmt = $db->prepare("SELECT * FROM activities WHERE id=?");
      $stmt->execute([$eid]);
      $ea = $stmt->fetch();
    ?>
    <?php if($ea): ?>
    <div class="form-panel">
      <h2>✏️ Edit Activity</h2>
      <form method="POST" action="index.php?month=<?= $month ?>&year=<?= $year ?>">
        <input type="hidden" name="action" value="update_activity">
        <input type="hidden" name="id" value="<?= $ea['id'] ?>">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-input" value="<?= htmlspecialchars($ea['title']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-textarea"><?= htmlspecialchars($ea['description']) ?></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
              <option value="">No Category</option>
              <?php foreach($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id']==$ea['category_id']?'selected':'' ?>><?= $c['icon'] ?> <?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-input" value="<?= htmlspecialchars($ea['location']) ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date & Time *</label>
            <input type="datetime-local" name="start_datetime" class="form-input" value="<?= substr($ea['start_datetime'],0,16) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date & Time *</label>
            <input type="datetime-local" name="end_datetime" class="form-input" value="<?= substr($ea['end_datetime'],0,16) ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Max Participants</label>
            <input type="number" name="max_participants" class="form-input" value="<?= $ea['max_participants'] ?>" min="1">
          </div>
        </div>
        <div class="form-actions">
          <a href="index.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-ghost">Cancel</a>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <!-- CALENDAR VIEW -->
    <div class="view-header">
      <div>
        <div class="view-title"><?= $month_name ?> <?= $year ?></div>
        <div class="view-subtitle"><?= count($filtered) ?> activities this month</div>
      </div>
      <div class="nav-btns">
        <a href="index.php?month=<?= $prev_month ?>&year=<?= $prev_year ?>" class="btn btn-ghost">&#8592; Prev</a>
        <a href="index.php" class="btn btn-ghost">Today</a>
        <a href="index.php?month=<?= $next_month ?>&year=<?= $next_year ?>" class="btn btn-ghost">Next &#8594;</a>
      </div>
    </div>

    <!-- CALENDAR GRID -->
    <div class="calendar-grid">
      <?php foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dl): ?>
        <div class="cal-day-label"><?= $dl ?></div>
      <?php endforeach; ?>
      <?php for($i=0;$i<$first_day;$i++): ?>
        <div class="cal-cell empty"></div>
      <?php endfor; ?>
      <?php for($d=1;$d<=$days_in_month;$d++): ?>
        <div class="cal-cell <?= ($d===$today_day)?'today':'' ?>">
          <div class="cal-date"><?= $d ?></div>
          <?php if(isset($by_day[$d])): ?>
            <?php foreach(array_slice($by_day[$d],0,3) as $a): ?>
              <a href="index.php?month=<?= $month ?>&year=<?= $year ?>&detail=<?= $a['id'] ?>" class="cal-event-pill" style="background:<?= $a['category_color']??'#7c6af7' ?>">
                <?= $a['category_icon']??'📌' ?> <?= htmlspecialchars($a['title']) ?>
              </a>
            <?php endforeach; ?>
            <?php if(count($by_day[$d])>3): ?>
              <span style="font-size:0.65rem;color:var(--text-muted)">+<?= count($by_day[$d])-3 ?> more</span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>

    <!-- DETAIL PANEL -->
    <?php if($detail): ?>
    <div class="detail-panel">
      <div class="detail-strip" style="background:<?= $detail['category_color']??'#7c6af7' ?>"></div>
      <div class="detail-title"><?= htmlspecialchars($detail['title']) ?></div>
      <div class="detail-meta">
        <div class="detail-meta-row"><span>📅</span><?= date('l, F j, Y', strtotime($detail['start_datetime'])) ?></div>
        <div class="detail-meta-row"><span>🕐</span><?= date('g:i A', strtotime($detail['start_datetime'])) ?> – <?= date('g:i A', strtotime($detail['end_datetime'])) ?></div>
        <?php if($detail['location']): ?>
          <div class="detail-meta-row"><span>📍</span><?= htmlspecialchars($detail['location']) ?></div>
        <?php endif; ?>
        <div class="detail-meta-row"><span>👤</span>Organized by <?= htmlspecialchars($detail['created_by']) ?></div>
        <div class="detail-meta-row"><span>🏷️</span><?= $detail['category_icon']??'📌' ?> <?= htmlspecialchars($detail['category_name']??'General') ?></div>
        <div class="detail-meta-row"><span>👥</span>
          <?= $detail['rsvp_count'] ?><?= $detail['max_participants']?'/'.$detail['max_participants']:'' ?> joined
          <?php if($detail['max_participants'] && $detail['rsvp_count']>=$detail['max_participants']): ?>
            <span style="color:var(--danger);font-size:0.8rem">(Full)</span>
          <?php endif; ?>
        </div>
      </div>
      <?php if($detail['description']): ?>
        <div class="detail-desc"><?= htmlspecialchars($detail['description']) ?></div>
      <?php endif; ?>

      <!-- RSVP SECTION -->
      <div class="rsvp-section">
        <div class="rsvp-title">👋 Who's joining</div>
        <div class="rsvp-list">
          <?php if($detail['rsvps']): ?>
            <?php foreach($detail['rsvps'] as $r): ?>
              <span class="rsvp-chip"><?= htmlspecialchars($r['participant_name']) ?></span>
            <?php endforeach; ?>
          <?php else: ?>
            <span style="color:var(--text-muted);font-size:0.85rem">No one yet — be the first!</span>
          <?php endif; ?>
        </div>
        <?php $is_full = $detail['max_participants'] && $detail['rsvp_count'] >= $detail['max_participants']; ?>
        <?php if(!$is_full): ?>
          <form method="POST" action="index.php?month=<?= $month ?>&year=<?= $year ?>&detail=<?= $detail['id'] ?>">
            <input type="hidden" name="action" value="rsvp">
            <input type="hidden" name="activity_id" value="<?= $detail['id'] ?>">
            <div class="rsvp-form">
              <input type="text" name="participant_name" class="form-input" placeholder="Your name to join..." required>
              <button type="submit" class="btn btn-primary">Join!</button>
            </div>
          </form>
        <?php else: ?>
          <p style="color:var(--danger);font-size:0.85rem">This activity is full.</p>
        <?php endif; ?>
      </div>

      <div class="detail-actions">
        <a href="index.php?action=edit&id=<?= $detail['id'] ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-secondary">✏️ Edit</a>
        <form method="POST" action="index.php?month=<?= $month ?>&year=<?= $year ?>" style="display:inline" onsubmit="return confirm('Delete this activity?')">
          <input type="hidden" name="action" value="delete_activity">
          <input type="hidden" name="id" value="<?= $detail['id'] ?>">
          <button type="submit" class="btn btn-danger">🗑 Delete</button>
        </form>
        <a href="index.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-ghost">✕ Close</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- ACTIVITIES LIST -->
    <p class="section-title"><?= $active_cat==='all'?'All':'Filtered' ?> Activities (<?= count($filtered) ?>)</p>
    <div class="activities-list">
      <?php if(empty($filtered)): ?>
        <div class="empty-state">
          <div class="emoji">📭</div>
          <h3>No activities yet</h3>
          <p>Be the first to create one!</p>
        </div>
      <?php else: ?>
        <?php foreach($filtered as $a): ?>
          <?php
            $is_full = $a['max_participants'] && $a['rsvp_count'] >= $a['max_participants'];
            $start = strtotime($a['start_datetime']);
          ?>
          <a href="index.php?month=<?= $month ?>&year=<?= $year ?>&detail=<?= $a['id'] ?>" class="activity-card">
            <div class="act-color-bar" style="background:<?= $a['category_color']??'#7c6af7' ?>"></div>
            <div class="act-info">
              <div class="act-title"><?= $a['category_icon']??'📌' ?> <?= htmlspecialchars($a['title']) ?></div>
              <div class="act-meta">
                <span>📅 <?= date('D, M j', $start) ?></span>
                <span>🕐 <?= date('g:i A', $start) ?></span>
                <?php if($a['location']): ?><span>📍 <?= htmlspecialchars($a['location']) ?></span><?php endif; ?>
                <span>👤 <?= htmlspecialchars($a['created_by']) ?></span>
              </div>
              <div class="act-badges">
                <span class="badge" style="background:<?= $a['category_color']??'#7c6af7' ?>22;color:<?= $a['category_color']??'#7c6af7' ?>"><?= htmlspecialchars($a['category_name']??'General') ?></span>
                <?= $is_full ? '<span class="badge badge-full">Full</span>' : '<span class="badge badge-open">Open</span>' ?>
              </div>
            </div>
            <div class="act-rsvp-count">
              <?= $a['rsvp_count'] ?><?= $a['max_participants']?'/'.$a['max_participants']:'' ?>
              <small>joined</small>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>

</body>
</html>
