<?php
// Use the absolute path so it finds the DB you already created
$dbPath = __DIR__ . '/members.db';
$db = new PDO("sqlite:" . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Create the table if you haven't (just in case)
$db->exec("CREATE TABLE IF NOT EXISTS members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    student_id INTEGER UNIQUE NOT NULL,
    email TEXT,
    bio TEXT,
    image_url TEXT
)");

// 2. Clear old data to avoid "Unique Constraint" errors if you run this twice
$db->exec("DELETE FROM members");

// 3. Prepare your group data
$groupMembers = [
    ['name' => 'Matthew', 'student_id' => '413856047', 'email' => '413856047@o365.tku.edu.tw', 'bio' => '', 'image_url' => ''],
 ['name' => 'Dennis', 'student_id' => '413855742', 'email' => '413855742@o365.tku.edu.tw', 'bio' => '', 'image_url' => ''],
 ['name' => 'Kevin', 'student_id' => '413854679', 'email' => '413854679@o365.tku.edu.tw', 'bio' => '', 'image_url' => ''],
 ['name' => 'Jin', 'student_id' => '413855221', 'email' => '413855221@o365.tku.edu.tw', 'bio' => '', 'image_url' => ''],
 ['name' => 'Eugene', 'student_id' => '413856062', 'email' => '413856062@o365.tku.edu.tw', 'bio' => '', 'image_url' => ''],
 ['name' => 'Sky', 'student_id' => '413856187', 'email' => '413856187@o365.tku.edu.tw', 'bio' => '', 'image_url' => ''],


];

// 4. Insert the data using PDO Prepared Statements
$sql = "INSERT INTO members (name, student_id, email, bio, image_url) VALUES (:name, :student_id, :email, :bio, :image_url)";
$stmt = $db->prepare($sql);

foreach ($groupMembers as $member) {
    $stmt->execute($member);
    echo "Added: " . $member['name'] . "\n";
}

