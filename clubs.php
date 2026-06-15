<?php
session_start();

$clubs_json = file_get_contents('clubs.json');
$clubs = json_decode($clubs_json, true);

$is_clubadmin = false;
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'manager') {
    $is_clubadmin = true;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolei</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .add-club-btn-container {
            text-align: center;
            margin: 20px 0;
        }
        .add-club-btn {
            display: inline-block;
            background-color: #ff6600;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .add-club-btn:hover {
            background-color: #e65c00;
        }
    </style>
</head>
<body>
    <a href="index.html" class="logo">
        <img src="assets/images/mpalabolei.png" alt="Home">
        <span>Bolei</span> 
    </a>

    <div class="container-clubs">
        <?php if ($is_clubadmin): ?>
        <div style="text-align: center; margin-bottom: 20px;">
            <form action="add_club.php" method="GET">
                <button type="submit" class="submit-btn">Επεξεργασία</button>
            </form>
        </div>
        <?php endif; ?>
        <?php foreach ($clubs as $club): ?>
        <div class="club-node">
            <div class="club-header">
                <img src="<?= htmlspecialchars($club['logo']) ?>" alt="<?= htmlspecialchars($club['name']) ?>" class="club-logo">
                <span class="club-name"><?= htmlspecialchars($club['name']) ?></span>
                <button class="info">info</button>
            </div>
            
            <div class="hidden">
                <div class="media-side">
                    <img src="<?= htmlspecialchars($club['team_photo']) ?>" alt="team photo">
                    <iframe height="200" src="<?= htmlspecialchars($club['youtube']) ?>" frameborder="0" allowfullscreen></iframe>
                    <a href="<?= htmlspecialchars($club['site']) ?>">Ιστότοπος ομάδας</a>
                </div>
                
                <div class="roster-side">
                    <div class="coach-info">Προπονητής: <?= htmlspecialchars($club['coach']) ?></div>
                    <table class="roster-table">
                        <thead>
                            <tr>
                                <th class="col-num">#</th>
                                <th class="col-name">ΟΝΟΜΑΤΕΠΩΝΥΜΟ</th>
                                <th class="col-pos">ΘΕΣΗ</th>
                                <th class="col-bday">ΗΜ. ΓΕΝΝΗΣΗΣ</th>
                                <th class="col-height">ΥΨΟΣ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($club['players'] as $player): ?>
                            <tr>
                                <td class="col-num"><?= htmlspecialchars($player['number']) ?></td>
                                <td class="col-name"><?= htmlspecialchars($player['name']) ?></td>
                                <td class="col-pos"><?= htmlspecialchars($player['position']) ?></td>
                                <td class="col-bday"><?= htmlspecialchars($player['birthdate']) ?></td>
                                <td class="col-height"><?= htmlspecialchars($player['height']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

  

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const infoButtons = document.querySelectorAll('.info');
            infoButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const clubNode = this.closest('.club-node');
                    if (clubNode) {
                        clubNode.classList.toggle('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
