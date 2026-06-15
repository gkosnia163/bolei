<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'get_matches') {//ai
    $q = "SELECT m.id, m.round, ch.name as hname, ch.logo_path as hlogo, ca.name as aname, ca.logo_path as alogo 
          FROM matches m 
          JOIN clubs ch ON m.home_team_id = ch.club_id 
          JOIN clubs ca ON m.away_team_id = ca.club_id
          ORDER BY m.round, m.id";
    $res_matches = mysqli_query($con, $q);
    $matches_array = [];
    if ($res_matches) {
        while ($r = mysqli_fetch_assoc($res_matches)) {
            $matches_array[] = [
                'id' => $r['id'],
                'round' => $r['round'],
                'home' => ['name' => $r['hname'], 'logo' => $r['hlogo']],
                'away' => ['name' => $r['aname'], 'logo' => $r['alogo']],
                'score' => null,
                'sets' => null
            ];
        }
    }

    $query = "SELECT * FROM match_results";
    $result = mysqli_query($con, $query);
    $db_results = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $db_results[$row['match_id']] = $row;
        }
    }

    $response = [];
    foreach ($matches_array as $m) {
        $id = $m['id'];
        $db_match = isset($db_results[$id]) ? $db_results[$id] : null;
        
        $match_status = 'unplayed';
        if ($db_match) {
            $match_status = $db_match['status']; // 'pending' or 'valid'
        } elseif ($m['score'] !== null) {
            $match_status = 'valid';
        }

        $score_str = $m['score'] ? $m['score'] : '-';
        $sets_str = $m['sets'] ? $m['sets'] : '-';
        
        if ($db_match) {
            $h_sets = 0; $a_sets = 0;
            $sets_details = [];
            for ($i = 1; $i <= 5; $i++) {
                $h = $db_match["set{$i}_home"];
                $a = $db_match["set{$i}_away"];
                if ($h !== null && $a !== null) {
                    if ($h > $a) $h_sets++;
                    elseif ($a > $h) $a_sets++;
                    $sets_details[] = "$h-$a";
                }
            }
            $score_str = "$h_sets - $a_sets";
            $sets_str = "(" . implode(", ", $sets_details) . ")";
        }

        $response[] = [
            'id' => $id,
            'round' => $m['round'],
            'home' => $m['home']['name'],
            'away' => $m['away']['name'],
            'status' => $match_status,
            'score' => $score_str,
            'sets' => $sets_str,
            'db_match' => $db_match
        ];
    }
    echo json_encode($response);
    exit;
}

if ($action === 'generate_draw') {
    //free paliou table
    mysqli_query($con, "SET FOREIGN_KEY_CHECKS = 0");
    mysqli_query($con, "TRUNCATE TABLE match_results");
    mysqli_query($con, "TRUNCATE TABLE matches");
    mysqli_query($con, "TRUNCATE TABLE matchdays");
    mysqli_query($con, "SET FOREIGN_KEY_CHECKS = 1");

    //fetch omadon apo bash
    $res = mysqli_query($con, "SELECT club_id FROM clubs");
    $teams = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $teams[] = $row['club_id'];
    }

    $n = count($teams);
    if ($n < 2) {
        echo "Not enough clubs";
        exit;
    }

    //1os gyros
    $rounds = $n - 1;
    for ($r = 0; $r < $rounds; $r++) {
        $round_num = $r + 1;
        mysqli_query($con, "INSERT INTO matchdays (matchday_number) VALUES ($round_num)");

        for ($i = 0; $i < $n / 2; $i++) {
            $home = $teams[$i];
            $away = $teams[$n - 1 - $i];

            if ($i === 0 && $r % 2 === 1) {
                $tmp = $home; $home = $away; $away = $tmp;
            }
            
            mysqli_query($con, "INSERT INTO matches (round, home_team_id, away_team_id) VALUES ($round_num, $home, $away)");
        }
        $last = array_pop($teams);
        array_splice($teams, 1, 0, [$last]);
    }

    //2os gyros
    for ($r = $rounds + 1; $r <= $rounds * 2; $r++) {
        mysqli_query($con, "INSERT INTO matchdays (matchday_number) VALUES ($r)");
    }

    $res = mysqli_query($con, "SELECT * FROM matches WHERE round <= $rounds");
    while ($row = mysqli_fetch_assoc($res)) {
        $r2 = $row['round'] + $rounds;
        $h_id = $row['away_team_id'];
        $a_id = $row['home_team_id'];
        mysqli_query($con, "INSERT INTO matches (round, home_team_id, away_team_id) VALUES ($r2, $h_id, $a_id)");
    }

    echo "success";
    exit;
}

if ($action === 'validate_match') {
    $match_id = (int)$_POST['match_id'];
    $query = "UPDATE match_results SET status='valid' WHERE match_id=$match_id";
    if (mysqli_query($con, $query)) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
}
?>