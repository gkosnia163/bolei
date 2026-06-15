<?php
// api/clubs.php
header('Content-Type: application/json; charset=utf-8');
require_once 'api/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if ($id) {
            //i) GET club details & players
            $stmt = $con->prepare("SELECT * FROM clubs WHERE club_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $club = $res->fetch_assoc();
            
            if ($club) {
                $stmt_p = $con->prepare("SELECT * FROM players WHERE club_id = ?");
                $stmt_p->bind_param("i", $id);
                $stmt_p->execute();
                $res_p = $stmt_p->get_result();
                $players = [];
                while ($p = $res_p->fetch_assoc()) {
                    $players[] = $p;
                }
                $club['players'] = $players;
                echo json_encode($club, JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Club not found']);
            }
        } else {
            // ii) GET all clubs
            $res = $con->query("SELECT * FROM clubs");
            $clubs = [];
            while ($row = $res->fetch_assoc()) {
                $clubs[] = $row;
            }
            echo json_encode($clubs, JSON_UNESCAPED_UNICODE);
        }
        break;

    case 'POST':
        //iv) POST add a new club
        if (!$input || !isset($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields (name)']);
            break;
        }
        $name = $input['name'];
        $website = $input['website'] ?? null;
        $coach = $input['coach_name'] ?? null;
        
        $stmt = $con->prepare("INSERT INTO clubs (name, website, coach_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $website, $coach);
        
        if ($stmt->execute()) {
            $new_id = $stmt->insert_id;
            echo json_encode(['message' => 'Club created successfully', 'club_id' => $new_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create club']);
        }
        break;

    case 'PUT':
    case 'PATCH':
        //iii) PUT/PATCH update a club
        if (!$id || !$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing club ID or JSON data']);
            break;
        }
        
        $name = $input['name'] ?? null;
        $website = $input['website'] ?? null;
        $coach = $input['coach_name'] ?? null;
        
        $stmt = $con->prepare("UPDATE clubs SET name = COALESCE(?, name), website = COALESCE(?, website), coach_name = COALESCE(?, coach_name) WHERE club_id = ?");
        $stmt->bind_param("sssi", $name, $website, $coach, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Club updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update club']);
        }
        break;

    case 'DELETE':
        //v) DELETE a club
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing club ID']);
            break;
        }
        
        mysqli_query($con, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($con, "DELETE FROM players WHERE club_id = $id");
        mysqli_query($con, "DELETE FROM matches WHERE home_team_id = $id OR away_team_id = $id");
        
        $stmt = $con->prepare("DELETE FROM clubs WHERE club_id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Club deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete club']);
        }
        mysqli_query($con, "SET FOREIGN_KEY_CHECKS = 1");
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>
