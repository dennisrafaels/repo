<?php
$members = [
    ['id' => '413856047', 'name' => '劉建宏', 'role' => 'Full Stack Developer', 'avatar' => '👨‍💻', 'color' => '#c8f135',
     'bio' => 'Responsible for integrating frontend and backend systems, ensuring smooth communication between the PHP server and MariaDB database.',
     'skills' => ['PHP', 'MariaDB', 'HTML', 'CSS']],
    ['id' => '413855221', 'name' => '原仁一郎', 'role' => 'Backend Developer', 'avatar' => '👨‍🔧', 'color' => '#7c6af7',
     'bio' => 'Developed the server-side PHP logic, API endpoints, and handled database queries and CRUD operations.',
     'skills' => ['PHP', 'SQL', 'REST API', 'Linux']],
    ['id' => '413856062', 'name' => '姚昭揚', 'role' => 'Frontend Developer', 'avatar' => '👨‍🎨', 'color' => '#ff9800',
     'bio' => 'Designed and built the user interface and calendar views. Focused on creating a clean and responsive user experience.',
     'skills' => ['HTML', 'CSS', 'JavaScript', 'UI Design']],
    ['id' => '413854679', 'name' => '歐陽宏星', 'role' => 'Database Administrator', 'avatar' => '👨‍🔬', 'color' => '#ff5722',
     'bio' => 'Managed the database schema design process from ERD to 3NF, and handled MariaDB setup and optimization on the Raspberry Pi.',
     'skills' => ['MariaDB', 'SQL', 'ERD', 'RPi Setup']],
    ['id' => '413856187', 'name' => '蕭嘉翔', 'role' => 'Project Manager', 'avatar' => '👨‍📋', 'color' => '#00bcd4',
     'bio' => 'Coordinated team workflow, managed the GitHub repository, and wrote project documentation including README and installation guides.',
     'skills' => ['Git', 'GitHub', 'Documentation', 'Testing']],
    ['id' => '413855742', 'name' => '陳永強', 'role' => 'System Administrator', 'avatar' => '👨‍💼', 'color' => '#e91e63',
     'bio' => 'Handled server configuration and deployment on the Raspberry Pi Zero 2W, including Apache, PHP, and network setup.',
     'skills' => ['Linux', 'Apache', 'RPi', 'Networking']],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Team — ActivityHub</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0f0f13; --surface: #1a1a22; --surface2: #22222e;
    --border: #2e2e3e; --accent: #c8f135; --accent2: #7c6af7;
    --text: #f0f0f5; --text-muted: #888899;
    --font-head: 'Syne', sans-serif; --font-body: 'DM Sans', sans-serif;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: var(--bg); color: var(--text); font-family: var(--font-body); min-height: 100vh; }

  header { display:flex; align-items:center; justify-content:space-between; padding:20px 36px; border-bottom:1px solid var(--border); }
  .logo { font-family:var(--font-head); font-size:1.5rem; font-weight:800; }
  .logo span { color:var(--accent); }
  nav a { color:var(--text-muted); text-decoration:none; font-size:0.875rem; margin-left:20px; transition:0.15s; }
  nav a:hover, nav a.active { color:var(--text); }

  .hero { text-align:center; padding:80px 20px 60px; border-bottom:1px solid var(--border); }
  .hero-tag { display:inline-block; background:rgba(200,241,53,0.1); color:var(--accent); border:1px solid rgba(200,241,53,0.3); border-radius:20px; font-size:0.75rem; font-weight:600; letter-spacing:0.1em; text-transform:uppercase; padding:5px 14px; margin-bottom:20px; }
  .hero h1 { font-family:var(--font-head); font-size:clamp(2rem,5vw,3.5rem); font-weight:800; line-height:1.1; margin-bottom:16px; }
  .hero h1 em { color:var(--accent); font-style:normal; }
  .hero p { color:var(--text-muted); font-size:1rem; max-width:500px; margin:0 auto; line-height:1.6; }

  .team-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:20px; max-width:1100px; margin:60px auto; padding:0 36px; }

  .member-card { background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:28px; transition:0.2s; position:relative; overflow:hidden; }
  .member-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--accent-color, var(--accent)); }
  .member-card:hover { transform:translateY(-4px); border-color:var(--accent-color, var(--accent)); }
  .member-avatar { width:72px; height:72px; border-radius:50%; background:var(--surface2); display:flex; align-items:center; justify-content:center; font-size:2rem; margin-bottom:16px; border:2px solid var(--border); }
  .member-name { font-family:var(--font-head); font-size:1.2rem; font-weight:700; margin-bottom:4px; }
  .member-role { font-size:0.8rem; font-weight:600; letter-spacing:0.08em; text-transform:uppercase; color:var(--accent); margin-bottom:8px; }
  .member-id { font-size:0.78rem; color:var(--text-muted); margin-bottom:14px; }
  .member-bio { font-size:0.875rem; line-height:1.6; color:var(--text-muted); margin-bottom:16px; }
  .member-skills { display:flex; flex-wrap:wrap; gap:6px; }
  .skill-tag { background:var(--surface2); border:1px solid var(--border); border-radius:20px; font-size:0.7rem; padding:3px 10px; color:var(--text-muted); }

  .project-section { max-width:1100px; margin:0 auto 80px; padding:0 36px; }
  .section-heading { font-family:var(--font-head); font-size:1.6rem; font-weight:800; margin-bottom:20px; }
  .project-card { background:var(--surface); border:1px solid var(--border); border-radius:18px; padding:32px; display:grid; grid-template-columns:1fr 1fr; gap:32px; }
  .project-card h3 { font-family:var(--font-head); font-size:1.1rem; font-weight:700; margin-bottom:10px; }
  .project-card p { font-size:0.875rem; color:var(--text-muted); line-height:1.7; }
  .tech-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
  .tech-badge { background:rgba(124,106,247,0.15); color:var(--accent2); border:1px solid rgba(124,106,247,0.3); border-radius:6px; font-size:0.75rem; padding:4px 10px; font-weight:500; }

  .stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; max-width:1100px; margin:0 auto 60px; padding:0 36px; }
  .stat-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:24px; text-align:center; }
  .stat-number { font-family:var(--font-head); font-size:2.5rem; font-weight:800; color:var(--accent); }
  .stat-label { font-size:0.8rem; color:var(--text-muted); margin-top:4px; }

  @media(max-width:700px) { .team-grid { padding:0 20px; } .project-card { grid-template-columns:1fr; gap:20px; } header { padding:16px 20px; } .stats { grid-template-columns:1fr; padding:0 20px; } }
</style>
</head>
<body>

<header>
  <div class="logo">Activity<span>Hub</span></div>
  <nav>
    <a href="index.php">Calendar</a>
    <a href="team.php" class="active">Team</a>
  </nav>
</header>

<section class="hero">
  <div class="hero-tag">Meet the Team</div>
  <h1>JUJU</h1>
  <p>We created ActivityHub to help campus communities discover, share, and organize activities — all in one place.</p>
</section>

<!-- STATS -->
<div class="stats">
  <div class="stat-card">
    <div class="stat-number"><?= count($members) ?></div>
    <div class="stat-label">Team Members</div>
  </div>
  <div class="stat-card">
    <div class="stat-number">1</div>
    <div class="stat-label">Database Tables</div>
  </div>
  <div class="stat-card">
    <div class="stat-number">RPi</div>
    <div class="stat-label">Zero 2W Powered</div>
  </div>
</div>

<!-- TEAM MEMBERS (rendered by PHP) -->
<div class="team-grid">
  <?php foreach($members as $m): ?>
  <div class="member-card" style="--accent-color:<?= $m['color'] ?>">
    <div class="member-avatar"><?= $m['avatar'] ?></div>
    <div class="member-name"><?= htmlspecialchars($m['name']) ?></div>
    <div class="member-role"><?= htmlspecialchars($m['role']) ?></div>
    <div class="member-id">Student ID: <?= htmlspecialchars($m['id']) ?></div>
    <p class="member-bio"><?= htmlspecialchars($m['bio']) ?></p>
    <div class="member-skills">
      <?php foreach($m['skills'] as $skill): ?>
        <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- PROJECT INFO -->
<div class="project-section">
  <div class="section-heading">About the Project</div>
  <div class="project-card">
    <div>
      <h3>🎯 What We Built</h3>
      <p>ActivityHub is a campus activity calendar that lets students create, discover, and join activities. From study groups to sports games, everything is in one place with real-time RSVP tracking and category filters.</p>
    </div>
    <div>
      <h3>⚙️ Tech Stack</h3>
      <p>Built with vanilla web technologies as required — no frameworks used. Runs on a Raspberry Pi Zero 2W with Apache, PHP, and MariaDB.</p>
      <div class="tech-list">
        <span class="tech-badge">HTML5</span>
        <span class="tech-badge">CSS3</span>
        <span class="tech-badge">PHP</span>
        <span class="tech-badge">MariaDB</span>
        <span class="tech-badge">Apache</span>
        <span class="tech-badge">RPi Zero 2W</span>
      </div>
    </div>
  </div>
</div>

</body>
</html>
