<?php
// 1. Connect to your database
$db = new SQLite3('../private/members.db');

// 2. Check if an ID is requested
if (!isset($_GET['id'])) {
    // === SHOW THE INDEX LIST ===
    // Fetch all members from the DB
    $results = $db->query("SELECT id, name FROM members");
    
    // Start your HTML layout for the index page
    echo "<h1>Group Members</h1>";
    echo "<ul>";
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        // Echo out the dynamic HTML links
        echo "<li><a href='member.php?id=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
    }
    echo "</ul>";

} else {
    // === SHOW A SPECIFIC MEMBER ===
    $id = (int)$_GET['id'];
    
    // Fetch the specific member's data
    $stmt = $db->prepare("SELECT * FROM members WHERE id = :id");
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result) {
        // Drop out of PHP mode to write clean, formatted HTML 
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title><?php echo $result['name']; ?>'s Profile</title>
            <link rel="stylesheet" href="style.css"> </head>
        <body>
            <div class="profile-card">
                <h1><?php echo $result['name']; ?></h1>
                <p><strong>Student ID:</strong> <?php echo $result['student_id']; ?></p>
                <p><strong>Email:</strong> <?php echo $result['email']; ?></p>
                <p class="bio"><?php echo $result['bio']; ?></p>
                
                <a href="member.php" class="back-btn">Back to List</a>
            </div>
        </body>
        </html>
        <?php
    } else {
        echo "<h1>Member not found!</h1>";
    }
}
?>
