<?php
session_start();
require_once 'api/db.php';

$is_club_admin = false;
$username = '';
if (isset($_SESSION['user']) && in_array($_SESSION['user']['role'], ['manager', 'admin'])) {
    $is_club_admin = true;
    $username = $_SESSION['user']['username'];
}

if (!$is_club_admin) {
    header("Location: index.html");
    exit;
}

$clubs_file = 'clubs.json';
$clubs = file_exists($clubs_file) ? json_decode(file_get_contents($clubs_file), true) : [];
if (!is_array($clubs)) $clubs = [];

$my_club_index = -1;
$my_club = null;
foreach ($clubs as $index => $club) {
    if (isset($club['owner']) && $club['owner'] === $username) {
        $my_club_index = $index;
        $my_club = $club;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete' && $my_club_index !== -1) {
        array_splice($clubs, $my_club_index, 1);
        file_put_contents($clubs_file, json_encode($clubs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        header("Location: clubs.php");
        exit;
    }

    if ($action === 'save') {
        $team_name = $_POST['team_name'] ?? '';
        $team_website = $_POST['team_website'] ?? '';
        $coach_name = $_POST['coach_name'] ?? '';
        
        $youtube = $_POST['video_url'] ?? '';
        if (empty($youtube) && $my_club) {
            $youtube = $my_club['youtube'];
        }

        $logo_path = $my_club ? $my_club['logo'] : '';
        if (!empty($_FILES['team_logo']['name'])) {
            // Clean filename to avoid issues with greek characters/spaces
            $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['team_logo']['name']));
            $logo_path = 'assets/images-club/' . $filename;
            move_uploaded_file($_FILES['team_logo']['tmp_name'], $logo_path);
        }

        $photo_path = $my_club ? $my_club['team_photo'] : '';
        if (!empty($_FILES['team_photo']['name'])) {
            $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($_FILES['team_photo']['name']));
            $photo_path = 'assets/images-club/' . $filename;
            move_uploaded_file($_FILES['team_photo']['tmp_name'], $photo_path);
        }

        $players = [];
        for ($i = 1; $i <= 12; $i++) {
            if (!empty($_POST["player_name_$i"])) {
                $raw_node = $_POST["player_dob_$i"]; //date of birth
                $formatted_node = $raw_node;
                if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw_node, $matches)) { //
                    $formatted_node = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
                }

                $players[] = [
                    "number" => $_POST["player_num_$i"],
                    "name" => $_POST["player_name_$i"],
                    "position" => $_POST["player_pos_$i"],
                    "birthdate" => $formatted_node,
                    "height" => $_POST["player_height_$i"]
                ];
            }
        }

        $new_club = [
            "name" => $team_name,
            "logo" => $logo_path,
            "team_photo" => $photo_path,
            "youtube" => $youtube,
            "site" => $team_website,
            "coach" => $coach_name,
            "players" => $players,
            "owner" => $username
        ];

        if ($my_club_index !== -1) {
            $clubs[$my_club_index] = $new_club;
        } else {
            $clubs[] = $new_club;
        }

        file_put_contents($clubs_file, json_encode($clubs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // Sync with DB so matches.php sees the new logo
        $db_team_name = mysqli_real_escape_string($con, $team_name);
        $db_logo_path = mysqli_real_escape_string($con, $logo_path);
        mysqli_query($con, "UPDATE clubs SET logo_path='$db_logo_path' WHERE name='$db_team_name'");

        header("Location: clubs.php");
        exit;
    }
}

//formating gia html 
$pre_name = $my_club ? htmlspecialchars($my_club['name']) : ''; //ifs
$pre_site = $my_club ? htmlspecialchars($my_club['site']) : '';
$pre_coach = $my_club ? htmlspecialchars($my_club['coach']) : '';
$pre_youtube = $my_club ? htmlspecialchars($my_club['youtube']) : '';
$is_editing = $my_club !== null;
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Προσθήκη/Επεξεργασία Ομάδας</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .delete-btn {
            background-color: #cc0000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 10px;
        }
        .delete-btn:hover {
            background-color: #990000;
        }
    </style>
</head>
<body>
    <a href="index.html" class="logo">
        <img src="assets/images/mpalabolei.png" alt="Home">
        <span>Bolei</span>
    </a>
    
    <div class="form-club-container">
        <div class="form-card">
            <div class="form-header"><?= $is_editing ? 'ΕΠΕΞΕΡΓΑΣΙΑ ΟΜΑΔΑΣ' : 'ΠΡΟΣΘΗΚΗ ΟΜΑΔΑΣ' ?></div>
            <form id="add-club-form" class="club-form" method="POST" enctype="multipart/form-data"> 
                <input type="hidden" name="action" id="form-action" value="save">
                
                <div class="grid-2-col">
                    <div class="form-section">
                        <label for="team-name">Όνομα ομάδας*</label>
                        <input type="text" id="team-name" name="team_name" value="<?= $pre_name ?>" required>
                    </div>
                    <div class="form-section">
                        <label for="team-website">Σύνδεσμος site ομάδας*</label>
                        <input type="url" id="team-website" name="team_website" placeholder="https://..." value="<?= $pre_site ?>" required>
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="form-section">
                        <label for="team-logo">Λογότυπο ομάδας <?= $is_editing ? '(αφήστε κενό για διατήρηση)' : '*' ?></label>
                        <input type="file" id="team-logo" name="team_logo" accept="image/*" <?= $is_editing ? '' : 'required' ?>>
                    </div>
                    <div class="form-section">
                        <label for="team-photo">Φωτογραφία ομάδας <?= $is_editing ? '(αφήστε κενό για διατήρηση)' : '*' ?></label>
                        <input type="file" id="team-photo" name="team_photo" accept="image/*" <?= $is_editing ? '' : 'required' ?>>
                    </div>
                </div>

                <div class="players-table-container">
                    <label>Παίκτες/Παίκτριες (Τουλάχιστον 12)*</label>
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th>#*</th>
                                <th>Ονοματεπώνυμο*</th>
                                <th>Θέση *</th>
                                <th>Ύψος (m)*</th>
                                <th>Ημ. Γέννησης *</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for ($i = 1; $i <= 12; $i++): 
                                $p = $my_club && isset($my_club['players'][$i-1]) ? $my_club['players'][$i-1] : null;
                                $p_num = $p ? htmlspecialchars($p['number']) : '';
                                $p_name = $p ? htmlspecialchars($p['name']) : '';
                                $p_pos = $p ? htmlspecialchars($p['position']) : '';
                                $p_h = $p ? htmlspecialchars($p['height']) : '';
                                
                                $p_dob = '';
                                if ($p && !empty($p['birthdate'])) {
                                    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $p['birthdate'], $m)) {
                                        $p_dob = $m[3] . '-' . $m[2] . '-' . $m[1];
                                    } else {
                                        $p_dob = $p['birthdate'];
                                    }
                                }
                            ?>
                            <tr>
                                <td><input type="number" name="player_num_<?= $i ?>" min="0" max="99" step="1" value="<?= $p_num ?>" required></td>
                                <td><input type="text" name="player_name_<?= $i ?>" value="<?= $p_name ?>" required></td>
                                <td>
                                    <select name="player_pos_<?= $i ?>" required>
                                        <option value="">Επιλέξτε...</option>
                                        <option value="Outside Hitter" <?= $p_pos === 'Outside Hitter' ? 'selected' : '' ?>>Outside Hitter</option>
                                        <option value="Libero" <?= $p_pos === 'Libero' ? 'selected' : '' ?>>Libero</option>
                                        <option value="Opposite" <?= $p_pos === 'Opposite' || $p_pos === 'Opposite, Outside Hitter' ? 'selected' : '' ?>>Opposite</option>
                                        <option value="Setter" <?= $p_pos === 'Setter' ? 'selected' : '' ?>>Setter</option>
                                        <option value="Middle Blocker" <?= $p_pos === 'Middle Blocker' ? 'selected' : '' ?>>Middle Blocker</option>
                                    </select>
                                </td>
                                <td><input type="number" name="player_height_<?= $i ?>" min="1.00" max="2.30" step="0.01" value="<?= $p_h ?>" required></td>
                                <td><input type="date" name="player_dob_<?= $i ?>" value="<?= $p_dob ?>" required></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>

                <div class="grid-2-col">
                    <div class="form-section">
                        <label for="coach-name">Όν/μο Προπονητή*</label>
                        <input type="text" id="coach-name" name="coach_name" value="<?= $pre_coach ?>" required>
                    </div>
                    <div class="form-section">
                        <label for="trainer-name">Όν/μο Γυμναστή</label>
                        <input type="text" id="trainer-name" name="trainer_name">
                    </div>
                </div>

                <div class="grid-2-col">
                    <div class="form-section">
                        <label for="physio-name">Όν/μο Φυσικοθεραπευτή</label>
                        <input type="text" id="physio-name" name="physio_name">
                    </div>
                    <div class="form-section">
                        <label for="manager-name">Όν/μο Έφορου</label>
                        <input type="text" id="manager-name" name="manager_name">
                    </div>
                </div>

                <div class="form-section">
                    <label for="stats-name">Όν/μο Στατιστικολόγου</label>
                    <input type="text" id="stats-name" name="stats_name" style="width: 48.5%;">
                </div>

                <div class="form-section">
                    <label>Video (Αρχείο ή URL) <?= $is_editing ? '(αφήστε κενό για διατήρηση)' : '*' ?></label>
                    <div style="display: flex; gap: 20px;">
                        <input type="file" name="video_file" accept="video/*" style="flex: 1;">
                        <span style="align-self: center;">Ή</span>
                        <input type="url" name="video_url" placeholder="URL Video (π.χ. YouTube)" value="<?= $is_editing ? '' : $pre_youtube ?>" style="flex: 1;">
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="submit-btn" onclick="document.getElementById('form-action').value='save';"><?= $is_editing ? 'Ενημέρωση Ομάδας' : 'Υποβολή Ομάδας' ?></button>
                    <?php if ($is_editing): ?>
                    <button type="button" class="delete-btn" onclick="if(confirm('Είστε σίγουροι;')) { document.getElementById('form-action').value='delete'; document.getElementById('add-club-form').submit(); }">Διαγραφή Ομάδας</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('add-club-form').addEventListener('submit', function(e) {
            const action = document.getElementById('form-action').value;
            if (action === 'save' && !<?= $is_editing ? 'true' : 'false' ?>) {
                const videoFile = this.elements['video_file'].value;
                const videoUrl = this.elements['video_url'].value;
                
                if (!videoFile && !videoUrl) {
                    e.preventDefault();
                    alert('Aνεβάστε ένα video ή προσθέστε ένα URL.');
                }
            }
        });
    </script>
</body>
</html>
