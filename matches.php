<?php
session_start();
require_once 'api/db.php';

$canAddScore = false;
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    if (($user['role'] === 'referee' || $user['role'] === 'manager' || $user['role'] === 'admin') && $user['status'] === 'active') {
        $canAddScore = true;
    }
}

$q_matches = "SELECT m.id, m.round, ch.name as hname, ch.logo_path as hlogo, ca.name as aname, ca.logo_path as alogo 
              FROM matches m 
              JOIN clubs ch ON m.home_team_id = ch.club_id 
              JOIN clubs ca ON m.away_team_id = ca.club_id
              ORDER BY m.round, m.id";
$res_matches = mysqli_query($con, $q_matches);
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

$rounds = [];
if ($matches_array) {
    foreach ($matches_array as $match) {
        $match_id = $match['id'];
        $match['can_add_score'] = ($match['score'] === null);

        if (isset($db_results[$match_id])) {
            $db_res = $db_results[$match_id];//an exei result
            $match['can_add_score'] = false; 

            if ($db_res['status'] === 'valid') {
                $home_sets = 0; $away_sets = 0;
                $set_scores = [];
                for ($i = 1; $i <= 5; $i++) {
                    $h = $db_res["set{$i}_home"];
                    $a = $db_res["set{$i}_away"];
                    if ($h !== null && $a !== null) {
                        $set_scores[] = "{$h}-{$a}";
                        if ($h > $a) $home_sets++;
                        elseif ($a > $h) $away_sets++;
                    }
                }
                $match['score'] = "{$home_sets} - {$away_sets}";
                $match['sets'] = "(" . implode(", ", $set_scores) . ")";
            } elseif ($db_res['status'] === 'pending') {
                $match['score'] = 'Pending';
                $match['sets'] = 'Αναμονή έγκρισης';
            }
        }
        
        $rounds[$match['round']][] = $match;
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolei - Πρόγραμμα Αγώνων</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .pending-score { color: #856404; font-weight: bold; }
    </style>
</head>
<body>
    <a href="index.html" class="logo">
        <img src="assets/images/mpalabolei.png" alt="Home">
        <span>Bolei</span>
    </a>
    
    <div class="matches-container">
        <?php foreach ($rounds as $roundNum => $roundMatches): ?>
            <div class="matches-card">
                <div class="matches-header"><?php echo htmlspecialchars($roundNum); ?>η Αγωνιστική</div>
                <div class="matches-content">
                    <?php foreach ($roundMatches as $match): ?>
                        <div class="match-item">
                            <div class="team-info home">
                                <span class="team-name-match"><?php echo htmlspecialchars($match['home']['name']); ?></span>
                                <img src="<?php echo htmlspecialchars($match['home']['logo']); ?>" class="team-logo-match">
                            </div>
                            <div class="score-section">
                                <?php if ($match['score']): ?>
                                    <?php if ($match['score'] === 'Pending'): ?>
                                        <div class="main-score pending-score">Pending</div>
                                        <div class="set-scores">Αναμονή έγκρισης</div>
                                    <?php else: ?>
                                        <div class="main-score"><?php echo htmlspecialchars($match['score']); ?></div>
                                        <div class="set-scores"><?php echo htmlspecialchars($match['sets']); ?></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($canAddScore && $match['can_add_score']): ?>
                                    <a href="add_result.php?match_id=<?php echo $match['id']; ?>" class="add-result-link">
                                        <div class="add-result-btn">add score</div>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="team-info away">
                                <img src="<?php echo htmlspecialchars($match['away']['logo']); ?>" class="team-logo-match">
                                <span class="team-name-match"><?php echo htmlspecialchars($match['away']['name']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
