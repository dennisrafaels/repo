<?php
// api.php — Main API endpoint for Activity Calendar
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── GET ALL ACTIVITIES ──────────────────────────────────────────
    case 'get_activities':
        $db = getDB();
        $month = $_GET['month'] ?? date('m');
        $year  = $_GET['year']  ?? date('Y');

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
        echo json_encode($stmt->fetchAll());
        break;

    // ── GET SINGLE ACTIVITY ─────────────────────────────────────────
    case 'get_activity':
        $db = getDB();
        $id = intval($_GET['id'] ?? 0);

        $stmt = $db->prepare("
            SELECT a.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                   COUNT(r.id) AS rsvp_count
            FROM activities a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN rsvps r ON a.id = r.activity_id
            WHERE a.id = ?
            GROUP BY a.id
        ");
        $stmt->execute([$id]);
        $activity = $stmt->fetch();

        if (!$activity) {
            echo json_encode(['error' => 'Activity not found']); break;
        }

        // Get RSVPs
        $stmt2 = $db->prepare("SELECT participant_name, joined_at FROM rsvps WHERE activity_id = ? ORDER BY joined_at ASC");
        $stmt2->execute([$id]);
        $activity['rsvps'] = $stmt2->fetchAll();

        echo json_encode($activity);
        break;

    // ── CREATE ACTIVITY ─────────────────────────────────────────────
    case 'create_activity':
        $db   = getDB();
        $data = json_decode(file_get_contents('php://input'), true);

        // Validation
        if (empty($data['title']) || empty($data['start_datetime']) || empty($data['end_datetime']) || empty($data['created_by'])) {
            echo json_encode(['error' => 'Missing required fields']); break;
        }
        if ($data['start_datetime'] >= $data['end_datetime']) {
            echo json_encode(['error' => 'End time must be after start time']); break;
        }

        $stmt = $db->prepare("
            INSERT INTO activities (title, description, category_id, location, start_datetime, end_datetime, max_participants, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            htmlspecialchars($data['title']),
            htmlspecialchars($data['description'] ?? ''),
            $data['category_id'] ?? null,
            htmlspecialchars($data['location'] ?? ''),
            $data['start_datetime'],
            $data['end_datetime'],
            $data['max_participants'] ?? null,
            htmlspecialchars($data['created_by'])
        ]);

        echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Activity created!']);
        break;

    // ── UPDATE ACTIVITY ─────────────────────────────────────────────
    case 'update_activity':
        $db   = getDB();
        $data = json_decode(file_get_contents('php://input'), true);
        $id   = intval($data['id'] ?? 0);

        if (!$id) { echo json_encode(['error' => 'Invalid ID']); break; }

        $stmt = $db->prepare("
            UPDATE activities SET title=?, description=?, category_id=?, location=?, start_datetime=?, end_datetime=?, max_participants=?
            WHERE id=?
        ");
        $stmt->execute([
            htmlspecialchars($data['title']),
            htmlspecialchars($data['description'] ?? ''),
            $data['category_id'] ?? null,
            htmlspecialchars($data['location'] ?? ''),
            $data['start_datetime'],
            $data['end_datetime'],
            $data['max_participants'] ?? null,
            $id
        ]);

        echo json_encode(['success' => true, 'message' => 'Activity updated!']);
        break;

    // ── DELETE ACTIVITY ─────────────────────────────────────────────
    case 'delete_activity':
        $db = getDB();
        $id = intval($_GET['id'] ?? 0);

        if (!$id) { echo json_encode(['error' => 'Invalid ID']); break; }

        $stmt = $db->prepare("DELETE FROM activities WHERE id=?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Activity deleted!']);
        break;

    // ── RSVP ────────────────────────────────────────────────────────
    case 'rsvp':
        $db   = getDB();
        $data = json_decode(file_get_contents('php://input'), true);

        $activity_id = intval($data['activity_id'] ?? 0);
        $name        = htmlspecialchars(trim($data['participant_name'] ?? ''));

        if (!$activity_id || empty($name)) {
            echo json_encode(['error' => 'Activity ID and name are required']); break;
        }

        // Check capacity
        $stmt = $db->prepare("SELECT max_participants, COUNT(r.id) AS rsvp_count FROM activities a LEFT JOIN rsvps r ON a.id = r.activity_id WHERE a.id = ? GROUP BY a.id");
        $stmt->execute([$activity_id]);
        $info = $stmt->fetch();

        if ($info && $info['max_participants'] && $info['rsvp_count'] >= $info['max_participants']) {
            echo json_encode(['error' => 'Activity is full!']); break;
        }

        // Insert RSVP (ignore duplicate)
        $stmt = $db->prepare("INSERT IGNORE INTO rsvps (activity_id, participant_name) VALUES (?, ?)");
        $stmt->execute([$activity_id, $name]);

        if ($db->rowCount() === 0) {
            echo json_encode(['error' => 'You already joined this activity!']); break;
        }

        echo json_encode(['success' => true, 'message' => "You're in, $name!"]);
        break;

    // ── GET CATEGORIES ───────────────────────────────────────────────
    case 'get_categories':
        $db = getDB();
        $stmt = $db->query("SELECT * FROM categories ORDER BY name");
        echo json_encode($stmt->fetchAll());
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
