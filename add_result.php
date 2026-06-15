<?php
session_start();
require 'api/db.php';

$message = "";

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['referee', 'admin'])) {
    die("Πρόσβαση επιτρέπεται μόνο σε διαιτητές και διαχειριστές.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = isset($_POST['match_id']) ? intval($_POST['match_id']) : 0;//if 
    
    $set1_home = $_POST['set1_home'];
    $set1_away = $_POST['set1_away'];
    $set2_home = $_POST['set2_home'];
    $set2_away = $_POST['set2_away'];
    $set3_home = $_POST['set3_home'];
    $set3_away = $_POST['set3_away'];
    $set4_home = !empty($_POST['set4_home']) ? $_POST['set4_home'] : null;
    $set4_away = !empty($_POST['set4_away']) ? $_POST['set4_away'] : null;
    $set5_home = !empty($_POST['set5_home']) ? $_POST['set5_home'] : null;
    $set5_away = !empty($_POST['set5_away']) ? $_POST['set5_away'] : null;

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $match_sheet_path = '';
    if (isset($_FILES['match_sheet']) && $_FILES['match_sheet']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['match_sheet']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['match_sheet']['name']);
        $match_sheet_path = $upload_dir . $file_name;
        move_uploaded_file($file_tmp, $match_sheet_path);
    } else {
        die("Σφάλμα στο ανέβασμα του φύλλου αγώνα.");
    }

    //db syndesh
    $query = "INSERT INTO match_results (match_id, set1_home, set1_away, set2_home, set2_away, set3_home, set3_away, set4_home, set4_away, set5_home, set5_away, match_sheet, status) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
              
    try { //bgalto ayto
        $stmt = mysqli_prepare($con, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iiiiiiiiiiis", $match_id, $set1_home, $set1_away, $set2_home, $set2_away, $set3_home, $set3_away, $set4_home, $set4_away, $set5_home, $set5_away, $match_sheet_path);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Το αποτέλεσμα υποβλήθηκε επιτυχώς! Βρίσκεται σε κατάσταση 'pending' και αναμένει επικύρωση από τον διαχειριστή.";
            } else {
                $message = "Σφάλμα κατά την υποβολή (execute): " . mysqli_error($con);
            }
        } else {
            $message = "Σφάλμα στη βάση δεδομένων (prepare): " . mysqli_error($con);
        }
    } catch (Exception $e) {
        $message = "Σφάλμα βάσης δεδομένων (Exception): " . $e->getMessage();
    }
}

// Preserve match_id whether it comes from GET (initial load) or POST (after submission)
$match_id_get = 0;
if (isset($_GET['match_id'])) {
    $match_id_get = intval($_GET['match_id']);
} elseif (isset($_POST['match_id'])) {
    $match_id_get = intval($_POST['match_id']);
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolei - Καταχώρηση Αποτελέσματος</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .message-box { padding: 15px; margin-bottom: 20px; background-color: #d4edda; color: #155724; border-radius: 5px; text-align: center; }
    </style>
</head>
<body>
    <a href="index.html" class="logo">
        <img src="assets/images/mpalabolei.png" alt="Home">
        <span>Bolei</span>
    </a>

    <div class="form-container">
        <div class="form-card">
            <div class="form-header">ΚΑΤΑΧΩΡΗΣΗ ΑΠΟΤΕΛΕΣΜΑΤΟΣ</div>
            <?php if ($message): ?>
                <div class="message-box"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <form id="result-form" class="result-form" method="POST" action="add_result.php" enctype="multipart/form-data">
                <input type="hidden" name="match_id" value="<?php echo htmlspecialchars($match_id_get); ?>">
                
                <div class="set-inputs-header">
                    <span>Set</span>
                    <span>Home</span>
                    <span>Away</span>
                </div>
                
                <div class="set-row">
                    <label>Set 1</label>
                    <input type="number" name="set1_home" min="0" placeholder="0" required>
                    <input type="number" name="set1_away" min="0" placeholder="0" required>
                </div>
                <div class="set-row">
                    <label>Set 2</label>
                    <input type="number" name="set2_home" min="0" placeholder="0" required>
                    <input type="number" name="set2_away" min="0" placeholder="0" required>
                </div>
                <div class="set-row">
                    <label>Set 3</label>
                    <input type="number" name="set3_home" min="0" placeholder="0" required>
                    <input type="number" name="set3_away" min="0" placeholder="0" required>
                </div>
                <div class="set-row">
                    <label>Set 4</label>
                    <input type="number" name="set4_home" min="0" placeholder="0">
                    <input type="number" name="set4_away" min="0" placeholder="0">
                </div>
                <div class="set-row">
                    <label>Set 5</label>
                    <input type="number" name="set5_home" min="0" placeholder="0">
                    <input type="number" name="set5_away" min="0" placeholder="0">
                </div>

                <div class="file-upload-section">
                    <label for="match-sheet">Φύλλο Αγώνα:</label>
                    <input type="file" id="match-sheet" name="match_sheet" accept=".pdf,image/*" required>
                </div>

                <button type="submit" class="submit-btn">Υποβολη Αποτελεσματος</button>
            </form>
        </div>
    </div>
</body>
</html>
