<?php
// setup.php — Creates and seeds the members SQLite database
// Run once: php /var/www/html/private/setup.php

$dbPath = __DIR__ . '/members.db';
$db = new PDO("sqlite:" . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create table
$db->exec("CREATE TABLE IF NOT EXISTS members (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id  TEXT    UNIQUE NOT NULL,
    name        TEXT    NOT NULL,
    role        TEXT    NOT NULL,
    bio         TEXT,
    skills      TEXT,
    color       TEXT    DEFAULT '#c8f135',
    avatar      TEXT    DEFAULT '👤'
)");

// Clear old data
$db->exec("DELETE FROM members");

// Seed members
$members = [
    ['413856047', '劉建宏',  'Full Stack Developer',  'Responsible for integrating frontend and backend systems, ensuring smooth communication between the PHP server and MariaDB database.', 'PHP,MariaDB,HTML,CSS',          '#c8f135', '👨‍💻'],
    ['413855221', '原仁一郎', 'Backend Developer',     'Developed the server-side PHP logic, API endpoints, and handled database queries and CRUD operations.',                             'PHP,SQL,REST API,Linux',        '#7c6af7', '👨‍🔧'],
    ['413856062', '姚昭揚',  'Frontend Developer',    'Designed and built the user interface and calendar views. Focused on creating a clean and responsive user experience.',              'HTML,CSS,JavaScript,UI Design', '#ff9800', '👨‍🎨'],
    ['413854679', '歐陽宏星', 'Database Administrator','Managed the database schema design process from ERD to 3NF, and handled MariaDB setup and optimization on the Raspberry Pi.',      'MariaDB,SQL,ERD,RPi Setup',     '#ff5722', '👨‍🔬'],
    ['413856187', '蕭嘉翔',  'Project Manager',       'Coordinated team workflow, managed the GitHub repository, and wrote project documentation including README and installation guides.','Git,GitHub,Documentation,Testing','#00bcd4','👨‍📋'],
    ['413855742', '陳永強',  'System Administrator',  'Handled server configuration and deployment on the Raspberry Pi Zero 2W, including Apache, PHP, and network setup.',               'Linux,Apache,RPi,Networking',   '#e91e63', '👨‍💼'],
];

$stmt = $db->prepare("INSERT INTO members (student_id, name, role, bio, skills, color, avatar) VALUES (?,?,?,?,?,?,?)");
foreach ($members as $m) {
    $stmt->execute($m);
    echo "Added: " . $m[1] . "\n";
}

echo "\nDone! members.db created at: $dbPath\n";
?>
