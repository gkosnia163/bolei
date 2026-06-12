const usersSum = 'bolei_users';
const usersCurrent = 'bolei_current_user';

function initializeUsers() {
    if (!localStorage.getItem(usersSum)) {
        localStorage.setItem(usersSum, JSON.stringify([]));
    }
}

function getUsers() { //getter olwn twn xrhstwn
    initializeUsers();
    return JSON.parse(localStorage.getItem(usersSum)); 
}

function registerUser(user) {
    const users = getUsers();
    
    // Έλεγχος αν υπάρχει ήδη το username
    const exists = users.find(u => u.username === user.username);
    if (exists) {
        return { success: false, message: 'Το username υπάρχει ήδη!' };
    }

    user.status = 'active';
    users.push(user);
    localStorage.setItem(usersSum, JSON.stringify(users));
    
    return { success: true };
}

function loginUser(username, password) {
    const users = getUsers();
    const user = users.find(u => u.username === username && u.password === password);
    
    if (user) {
        if (user.status !== 'active') {
            return { success: false, message: 'Ο λογαριασμός σας δεν είναι ενεργός.' };
        }
        localStorage.setItem(usersCurrent, JSON.stringify(user));
        return { success: true, user };
    }
    return { success: false, message: 'Λάθος username ή password.' };
}

function logoutUser() {
    localStorage.removeItem(usersCurrent);
}

function getCurrentUser() {
    const userStr = localStorage.getItem(usersCurrent);
    return userStr ? JSON.parse(userStr) : null;
}

function updateUserProfile(updatedData) {
    const users = getUsers();
    const currentUser = getCurrentUser();
    
    if (!currentUser) return false;

    const userIndex = users.findIndex(u => u.username === currentUser.username);
    if (userIndex !== -1) {
        // Διατηρούμε το username, role, status και ενημερώνουμε τα υπόλοιπα
        const updatedUser = {
            ...users[userIndex],
            firstname: updatedData.firstname,
            lastname: updatedData.lastname,
            phone: updatedData.phone,
            email: updatedData.email,
            password: updatedData.password
        };
        
        users[userIndex] = updatedUser;
        localStorage.setItem(usersSum, JSON.stringify(users));
        localStorage.setItem(usersCurrent, JSON.stringify(updatedUser)); //update session
        return true;
    }
    return false;
}
