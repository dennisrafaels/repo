<?php
// 1. Connect to the private database
$db = new PDO("sqlite:" . __DIR__ . "/../private/members.db");

// 2. Check if an ID is requested
$id = $_GET['id'] ?? null;

if (!$id) {
    // Show the list of all members (The Index)
    $stmt = $db->query("SELECT id, name FROM members");
    while ($row = $stmt->fetch()) {
        echo "<a href='member.php?id={$row['id']}'>{$row['name']}</a><br>";
    }
} else {
    // Show the specific member details
    $stmt = $db->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    echo "<h1>" . $user['name'] . "</h1>";
    echo "<p>" . $user['bio'] . "</p>";
}
