<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'register') {
        $firstname = $_POST['firstname'];
        $lastname  = $_POST['lastname'];
        $phone     = $_POST['phone'];
        $email     = $_POST['email'];
        $username  = $_POST['username'];
        $password  = $_POST['password'];
        $role      = isset($_POST['role']) && !empty($_POST['role']) ? $_POST['role'] : 'visitor'; 

        $result = mysqli_query($con, "SELECT username FROM users WHERE username = '$username'");
        if (mysqli_fetch_assoc($result)) {
            echo "Το username υπάρχει ήδη!";
            exit;
        }

        $status = ($role === 'visitor') ? 'active' : 'pending'; 
        $query = "INSERT INTO users (firstname, lastname, phone, email, username, password, role, status) 
                  VALUES ('$firstname', '$lastname', '$phone', '$email', '$username', '$password', '$role', '$status')";
        
        if (mysqli_query($con, $query)) {
            echo "success";
        } else {
            echo "Πρόβλημα κατά την εγγραφή: " . mysqli_error($con);
        }
        exit;
    }

    if ($action === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $result = mysqli_query($con, "SELECT * FROM users WHERE username = '$username' AND password = '$password'");
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            if ($user['status'] !== 'active') {
                echo "Ο λογαριασμός σας δεν είναι ενεργός.";
                exit;
            }
            $_SESSION['user'] = $user;
            echo "success";
        } else {
            echo "Λάθος username ή password.";
        }
        exit;
    }

    if ($action === 'update') {
        if (!isset($_SESSION['user'])) {
            echo "Δεν είστε συνδεδεμένοι.";
            exit;
        }

        $currentUsername = $_SESSION['user']['username'];
        $firstname = $_POST['firstname'];
        $lastname  = $_POST['lastname'];
        $phone     = $_POST['phone'];
        $email     = $_POST['email'];
        $password  = $_POST['password'];

        $query = "UPDATE users SET 
                  firstname = '$firstname', 
                  lastname = '$lastname', 
                  phone = '$phone', 
                  email = '$email', 
                  password = '$password' 
                  WHERE username = '$currentUsername'";

        if (mysqli_query($con, $query)) { //update to session
            $_SESSION['user']['firstname'] = $firstname;
            $_SESSION['user']['lastname'] = $lastname;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['password'] = $password;
            
            echo "success";
        } else {
            echo "Πρόβλημα κατά την ενημέρωση: " . mysqli_error($con);
        }
        exit;
    }

    if ($action === 'get_all_users') {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $role_filter = isset($_POST['role_filter']) ? $_POST['role_filter'] : '';
        $status_filter = isset($_POST['status_filter']) ? $_POST['status_filter'] : '';

        $query = "SELECT id, firstname, lastname, email, username, phone, role, status FROM users WHERE 1=1";
        
        if ($role_filter) {
            $query .= " AND role = '" . mysqli_real_escape_string($con, $role_filter) . "'";
        }
        if ($status_filter) {
            $query .= " AND status = '" . mysqli_real_escape_string($con, $status_filter) . "'";
        }

        $result = mysqli_query($con, $query);
        $users = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
        }
        echo json_encode($users);
        exit;
    }

    if ($action === 'update_user_status') {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            echo "Unauthorized";
            exit;
        }

        $user_id = intval($_POST['user_id']);
        $new_status = $_POST['new_status']; 

        if ($new_status === 'delete') {
            $query = "DELETE FROM users WHERE id = $user_id";
        } else {
            $query = "UPDATE users SET status = '" . mysqli_real_escape_string($con, $new_status) . "' WHERE id = $user_id";
        }

        if (mysqli_query($con, $query)) {
            echo "success";
        } else {
            echo "Σφάλμα: " . mysqli_error($con);
        }
        exit;
    }

    if ($action === 'logout') {
        session_destroy(); 
        echo "success";
        exit;
    }

    if ($action === 'get_current_user') {
        header('Content-Type: application/json');
        if (isset($_SESSION['user'])) {
            echo json_encode($_SESSION['user']);
        } else {
            echo json_encode(null);
        }
        exit;
    }
}
?>
