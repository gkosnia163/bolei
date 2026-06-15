<?php
session_start();
require_once 'api/db.php';

// Φόρτωση δεδομένων από το json όπως στο matches.php
$json_data = file_get_contents('matches.json');
$matches_array = json_decode($json_data, true);

// Φόρτωση των valid αποτελεσμάτων από τη βάση
$query = "SELECT * FROM match_results WHERE status = 'valid'";
$result = mysqli_query($con, $query);

$db_results = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $db_results[$row['match_id']] = $row;
    }
}

// Αρχικοποίηση πίνακα ομάδων
$teams = [];

if ($matches_array) {
    foreach ($matches_array as $match) {
        $homeName = $match['home']['name'];
        $homeLogo = $match['home']['logo'];
        $awayName = $match['away']['name'];
        $awayLogo = $match['away']['logo'];
        
        if (!isset($teams[$homeName])) {
            $teams[$homeName] = ['name' => $homeName, 'logo' => $homeLogo, 'matches' => 0, 'points' => 0, 'wins' => 0, 'losses' => 0, 'sets_won' => 0, 'sets_lost' => 0];
        }
        if (!isset($teams[$awayName])) {
            $teams[$awayName] = ['name' => $awayName, 'logo' => $awayLogo, 'matches' => 0, 'points' => 0, 'wins' => 0, 'losses' => 0, 'sets_won' => 0, 'sets_lost' => 0];
        }
    }

    // Υπολογισμός βαθμολογίας
    foreach ($matches_array as $match) {
        $match_id = $match['id'];
        $homeName = $match['home']['name'];
        $awayName = $match['away']['name'];
        
        $home_sets = -1;
        $away_sets = -1;
        
        // Αν υπάρχει επικυρωμένο (valid) αποτέλεσμα στη βάση
        if (isset($db_results[$match_id])) {
            $db_res = $db_results[$match_id];
            $home_sets = 0; $away_sets = 0;
            for ($i = 1; $i <= 5; $i++) {
                $h = $db_res["set{$i}_home"];
                $a = $db_res["set{$i}_away"];
                if ($h !== null && $a !== null) {
                    if ($h > $a) $home_sets++;
                    elseif ($a > $h) $away_sets++;
                }
            }
        } 
        // Αλλιώς αν υπάρχει αρχικό σκορ στο json
        elseif ($match['score'] !== null) {
            $parts = explode('-', $match['score']);
            if (count($parts) == 2) {
                $home_sets = (int)trim($parts[0]);
                $away_sets = (int)trim($parts[1]);
            }
        }
        
        //an exei oloklhrwthei
        if ($home_sets >= 0 && $away_sets >= 0) {
            $teams[$homeName]['matches']++;
            $teams[$awayName]['matches']++;
            $teams[$homeName]['sets_won'] += $home_sets;
            $teams[$homeName]['sets_lost'] += $away_sets;
            $teams[$awayName]['sets_won'] += $away_sets;
            $teams[$awayName]['sets_lost'] += $home_sets;
            
            if ($home_sets > $away_sets) {
                $teams[$homeName]['wins']++;
                $teams[$awayName]['losses']++;
                if ($home_sets == 3 && ($away_sets == 0 || $away_sets == 1)) {
                    $teams[$homeName]['points'] += 3;
                } elseif ($home_sets == 3 && $away_sets == 2) {
                    $teams[$homeName]['points'] += 2;
                    $teams[$awayName]['points'] += 1;
                }
            } elseif ($away_sets > $home_sets) {
                $teams[$awayName]['wins']++;
                $teams[$homeName]['losses']++;
                if ($away_sets == 3 && ($home_sets == 0 || $home_sets == 1)) {
                    $teams[$awayName]['points'] += 3;
                } elseif ($away_sets == 3 && $home_sets == 2) {
                    $teams[$awayName]['points'] += 2;
                    $teams[$homeName]['points'] += 1;
                }
            }
        }
    }
}

//taksinomish
usort($teams, function($a, $b) {
    if ($a['points'] != $b['points']) {
        return $b['points'] - $a['points'];
    }
    $setDiffA = $a['sets_won'] - $a['sets_lost'];
    $setDiffB = $b['sets_won'] - $b['sets_lost'];
    if ($setDiffA != $setDiffB) {
        return $setDiffB - $setDiffA;
    }
    return 0; // Σε ισοβαθμία παραμένουν ως έχουν
});
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolei - Πρόγραμμα Αγώνων</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <a href="index.html" class="logo">
        <img src="assets/images/mpalabolei.png" alt="Home">
        <span>Bolei</span>
    </a>
    <div class="ranking-container">
        <div class="ranking-card">
            <div class="ranking-header">Βαθμολογία</div>
            <div class="ranking-menu">
                <span>NO.</span>
                <span>Ομάδα</span>
                <span>Αγώνες</span>
                <span>Βαθμοί</span>
                <span>Νίκες</span>
                <span>Ήττες</span>
                <span>Σετ +</span>
                <span>Σετ -</span>
            </div>
            <?php 
            $rank = 1;
            foreach ($teams as $team): 
            ?>
            <div class="ranking-item">
                <span><?php echo $rank++; ?></span>
                <div class="team-info-ranking">
                    <img src="<?php echo htmlspecialchars($team['logo']); ?>" class="team-logo-match">
                    <span class="team-name-match"><?php echo htmlspecialchars($team['name']); ?></span>
                </div>
                <span><?php echo $team['matches']; ?></span>
                <span><?php echo $team['points']; ?></span>
                <span><?php echo $team['wins']; ?></span>
                <span><?php echo $team['losses']; ?></span>
                <span><?php echo $team['sets_won']; ?></span>
                <span><?php echo $team['sets_lost']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
