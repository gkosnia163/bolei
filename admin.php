<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('sneakysneaky');
    exit;
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .admin-controls { margin-bottom: 20px; display: flex; gap: 15px; align-items: center; justify-content: center; flex-wrap: wrap; }
        .user-list { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .user-list th, .user-list td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        .user-list th { background-color: #f4f4f4; color: #333; }
        .action-btn { padding: 6px 12px; cursor: pointer; border: none; border-radius: 4px; color: white; font-size: 0.9em; margin-right: 5px;}
        .btn-approve { background-color: #28a745; }
        .btn-approve:hover { background-color: #218838; }
        .btn-reject { background-color: #dc3545; }
        .btn-reject:hover { background-color: #c82333; }
        .btn-view { background-color: #007bff; }
        
        .profile-node { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); }
        .profile-node-content { background-color: #fff; margin: 8% auto; padding: 25px; border-radius: 8px; width: 90%; max-width: 500px; color: #333; position: relative;}
        .close-btn { color: #aaa; position: absolute; top: 15px; right: 20px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover { color: black; }
        .profile-detail { margin-bottom: 10px; font-size: 1.1em; border-bottom: 1px solid #eee; padding-bottom: 8px;}
        .profile-detail strong { display: inline-block; width: 120px; color: #555; }
    </style>
</head>
<body>
    <a href="index.html" class="logo">
        <img src="assets/images/mpalabolei.png" alt="Home">
        <span>Bolei</span>
    </a>
    
    <div class="form-container" style="max-width: 1000px; align-self: flex-start;">
        <div class="form-card">
            <div class="form-header" style="display:flex; justify-content:space-between; align-items:center;">Admin Dashboard</div>

            <div class="tabs" style="display:flex; gap:10px; margin: 20px 0; justify-content:center;">
                <button class="submit-btn" id="tabUsers" style="width:auto; margin:0;" onclick="switchTab('users')">Διαχείριση Χρηστών</button>
                <button class="submit-btn" id="tabMatches" style="width:auto; margin:0; opacity:0.6;" onclick="switchTab('matches')">Διαχείριση Αγώνων</button>
            </div>

            <div id="usersSection">
                <div class="admin-controls">
                    <select id="roleFilter" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="">Όλοι οι ρόλοι</option>
                        <option value="visitor">Visitors</option>
                        <option value="manager">Club administrators</option>
                        <option value="referee">Referees</option>
                    </select>
                    
                    <select id="statusFilter" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="">Όλες οι καταστάσεις</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                    </select>

                    <button class="submit-btn" style="width:auto; padding:8px 20px; margin:0;" onclick="loadUsers()">Αναζήτηση</button>
                </div>

                <div id="tableContainer" style="overflow-x:auto; display: none;">
                    <table class="user-list">
                        <thead>
                            <tr>
                                <th>username</th>
                                <th>fullname</th>
                                <th>role</th>
                                <th>state</th>
                                <th>action</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="matchesSection" style="display:none;">
                <div class="admin-controls">
                    <select id="matchStatusFilter" style="padding: 8px; border-radius: 4px; border: 1px solid #ccc;">
                        <option value="">Όλοι οι αγώνες</option>
                        <option value="valid">valid</option>
                        <option value="pending">pending</option>
                        <option value="unplayed">unplayed</option>
                    </select>

                    <button class="submit-btn" style="width:auto; padding:8px 20px; margin:0;" onclick="loadMatches()">Αναζήτηση</button>
                    <button class="submit-btn" style="width:auto; padding:8px 20px; margin:0; background-color: #fff;" onclick="generateDraw()">Αυτόματη Κλήρωση</button>
                </div>

                <div id="matchesTableContainer" style="overflow-x:auto; display: none;">
                    <table class="user-list">
                        <thead>
                            <tr>
                                <th>match</th>
                                <th style="text-align:center;">score</th>
                                <th>set</th>
                                <th>state</th>
                                <th>actions</th>
                            </tr>
                        </thead>
                        <tbody id="matchesTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

        <div id="profileNode" class="profile-node">
        <div class="profile-node-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2 style="margin-bottom: 25px; color:#0056b3;">Προφίλ Χρήστη</h2>
            
            <div class="profile-detail"><strong>firstname:</strong> <span id="p_fname"></span></div>
            <div class="profile-detail"><strong>lastname:</strong> <span id="p_lname"></span></div>
            <div class="profile-detail"><strong>username:</strong> <span id="p_uname"></span></div>
            <div class="profile-detail"><strong>mail:</strong> <span id="p_email"></span></div>
            <div class="profile-detail"><strong>phone:</strong> <span id="p_phone"></span></div>
            <div class="profile-detail"><strong>role:</strong> <span id="p_role" style="text-transform: capitalize;"></span></div>
            
            <div id="nodeActions" style="margin-top: 30px; text-align: right;">
            </div>
        </div>
    </div>

    <script>
        let allUsers = [];

        function switchTab(tab) {
            if (tab === 'users') {
                document.getElementById('usersSection').style.display = 'block';
                document.getElementById('matchesSection').style.display = 'none';
                document.getElementById('tabUsers').style.opacity = '1';
                document.getElementById('tabMatches').style.opacity = '0.6';
            } else {
                document.getElementById('usersSection').style.display = 'none';
                document.getElementById('matchesSection').style.display = 'block';
                document.getElementById('tabUsers').style.opacity = '0.6';
                document.getElementById('tabMatches').style.opacity = '1';
                loadMatches();
            }
        }

        function loadMatches() {
            document.getElementById('matchesTableContainer').style.display = 'block';
            const status = document.getElementById('matchStatusFilter').value;
            
            const formData = new FormData();
            formData.append('action', 'get_matches');

            fetch('api/admin_properties.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(matches => {
                if(matches.error) {
                    alert(matches.error);
                    return;
                }
                const tbody = document.getElementById('matchesTableBody');
                tbody.innerHTML = '';
                
                const filtered = matches.filter(m => {
                    if (!status) return true;
                    return m.status === status;
                });

                if (filtered.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">Δεν βρέθηκαν αγώνες σε αυτή την κατάσταση</td></tr>';
                    return;
                }

                filtered.forEach(m => {
                    let statusHtml = '';
                    let actionsHtml = '';
                    
                    if (m.status === 'valid') {
                        statusHtml = '<span style="color:#155724; font-weight:bold; background:#d4edda; padding:3px 8px; border-radius:3px;">valid</span>';
                    } else if (m.status === 'pending') {
                        statusHtml = '<span style="color:#d39e00; font-weight:bold; background:#fff3cd; padding:3px 8px; border-radius:3px;">pending</span>';
                        actionsHtml = `<button class="action-btn btn-approve" onclick="validateMatch(${m.id})">Επικύρωση</button>`;
                    } else {
                        statusHtml = '<span style="color:#6c757d; font-weight:bold; background:#e2e3e5; padding:3px 8px; border-radius:3px;">unplayed</span>';
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><strong>${m.home}</strong> - ${m.away}</td>
                        <td style="text-align:center; font-weight:bold;">${m.score}</td>
                        <td><small>${m.sets}</small></td>
                        <td>${statusHtml}</td>
                        <td>${actionsHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(err => console.error(err));
        }

        function validateMatch(id) {
            if (!confirm('Είστε σίγουροι ότι θέλετε να επικυρώσετε αυτό το αποτέλεσμα;')) return;

            const formData = new FormData();
            formData.append('action', 'validate_match');
            formData.append('match_id', id);

            fetch('api/admin_properties.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                if (text.trim() === 'success') {
                    loadMatches(); //refresh
                } else {
                    alert('Σφάλμα κατά την επικύρωση.');
                }
            })
            .catch(err => console.error(err));
        }

        function generateDraw() {
            if (!confirm('Είστε σίγουροι; Αυτό θα διαγράψει το τρέχον πρόγραμμα και τα αποτελέσματα αγώνων!')) return;

            const formData = new FormData();
            formData.append('action', 'generate_draw');

            fetch('api/admin_properties.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                if (text.trim() === 'success') {
                    loadMatches(); //refresh
                } else {
                    alert('Σφάλμα: ' + text);
                }
            })
            .catch(err => console.error(err));
        }

        function loadUsers() {
            document.getElementById('tableContainer').style.display = 'block';
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;
            
            const formData = new FormData();
            formData.append('action', 'get_all_users');
            if (role) formData.append('role_filter', role);
            if (status) formData.append('status_filter', status);

            fetch('api/profiles.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(users => {
                if (users.error) {
                    alert('Σφάλμα Πρόσβασης: ' + users.error);
                    return;
                }
                allUsers = users;
                const tbody = document.getElementById('usersTableBody');
                tbody.innerHTML = '';
                
                if (users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">Δεν βρέθηκαν χρήστες</td></tr>';
                    return;
                }

                users.forEach(user => {
                    let statusHtml = user.status === 'pending' 
                        ? '<span style="color:#d39e00; font-weight:bold; background:#fff3cd; padding:3px 8px; border-radius:3px;">Pending</span>' 
                        : '<span style="color:#155724; font-weight:bold; background:#d4edda; padding:3px 8px; border-radius:3px;">Active</span>';

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${user.username}</td>
                        <td>${user.firstname} ${user.lastname}</td>
                        <td style="text-transform: capitalize;">${user.role}</td>
                        <td>${statusHtml}</td>
                        <td>
                            <button class="action-btn btn-view" onclick="viewProfile(${user.id})">Επίσκεψη Προφίλ</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(err => console.error(err));
        }

        function viewProfile(id) {
            const user = allUsers.find(u => u.id == id);
            if (!user) return;

            document.getElementById('p_fname').textContent = user.firstname;
            document.getElementById('p_lname').textContent = user.lastname;
            document.getElementById('p_uname').textContent = user.username;
            document.getElementById('p_email').textContent = user.email;
            document.getElementById('p_phone').textContent = user.phone;
            document.getElementById('p_role').textContent = user.role;

            const actionsDiv = document.getElementById('nodeActions');
            if (user.status === 'pending') {
                actionsDiv.innerHTML = `
                    <button class="action-btn btn-reject" onclick="updateStatus(${user.id}, 'delete')">Απόρριψη</button>
                    <button class="action-btn btn-approve" onclick="updateStatus(${user.id}, 'active')">Ενεργοποίηση</button>
                `;
            } else {
                actionsDiv.innerHTML = `<p style="color: #155724; font-weight: bold;">✔ Ο χρήστης είναι ενεργός.</p>`;
            }

            document.getElementById('profileNode').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('profileNode').style.display = 'none';
        }

        function updateStatus(userId, newStatus) {
            if (!confirm(newStatus === 'delete' ? 'Είστε σίγουροι ότι θέλετε να απορρίψετε (διαγράψετε) αυτόν τον χρήστη;' : 'Είστε σίγουροι για την ενεργοποίηση;')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'update_user_status');
            formData.append('user_id', userId);
            formData.append('new_status', newStatus);

            fetch('api/profiles.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                if (text.trim() === 'success') {
                    closeModal();
                    loadUsers(); //refresh
                } else {
                    alert('Σφάλμα: ' + text);
                }
            })
            .catch(err => console.error(err));
        }

        window.onclick = function(event) {
            const node = document.getElementById('profileNode');
            if (event.target == node) {
                closeModal();
            }
        }
    </script>
</body>
</html>