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

    if ($action === 'logout') {
        session_destroy(); 
        echo "success";
        exit;
    }
}
?>
