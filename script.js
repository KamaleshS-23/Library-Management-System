document.addEventListener('DOMContentLoaded', function() {
    // Tab and form switching references
    const tabs = document.querySelectorAll('.tab');
    const loginForms = document.querySelectorAll('.login-form');
    const registerSections = document.querySelectorAll('.register-section');

    // === TAB SWITCHING LOGIC ===
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            tabs.forEach(t => t.classList.remove('active'));
            loginForms.forEach(form => form.classList.remove('active'));
            registerSections.forEach(section => section.classList.remove('active'));
            this.classList.add('active');
            const tabName = this.getAttribute('data-tab');
            document.getElementById(`${tabName}LoginForm`).classList.add('active');
        });
    });

    // === FORM SWITCHING LOGIC ===
    document.getElementById('showUserRegister').addEventListener('click', function(e) {
        e.preventDefault();
        loginForms.forEach(form => form.classList.remove('active'));
        registerSections.forEach(section => section.classList.remove('active'));
        document.getElementById('userRegisterSection').classList.add('active');
    });

    document.getElementById('showAdminRegister').addEventListener('click', function(e) {
        e.preventDefault();
        loginForms.forEach(form => form.classList.remove('active'));
        registerSections.forEach(section => section.classList.remove('active'));
        document.getElementById('adminRegisterSection').classList.add('active');
    });

    document.querySelectorAll('.show-login').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab');
            loginForms.forEach(form => form.classList.remove('active'));
            registerSections.forEach(section => section.classList.remove('active'));
            document.getElementById(`${tabName}LoginForm`).classList.add('active');
            document.querySelector(`.tab[data-tab="${tabName}"]`).classList.add('active');
        });
    });

    // === USER REGISTRATION ===
    const userRegisterForm = document.getElementById('userRegisterForm');
    userRegisterForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = {
            full_name: document.getElementById('userRegName').value,
            email: document.getElementById('userRegEmail').value,
            username: document.getElementById('userRegUsername').value,
            password: document.getElementById('userRegPassword').value,
            confirm_password: document.getElementById('userRegConfirmPassword').value
        };

        // Basic validation
        if (!formData.full_name || !formData.email || !formData.username || !formData.password || !formData.confirm_password) {
            alert('All fields are required');
            return;
        }

        if (formData.password !== formData.confirm_password) {
            alert('Passwords do not match');
            return;
        }

        if (formData.password.length < 6) {
            alert('Password must be at least 6 characters');
            return;
        }

        // Show loading state
        const registerBtn = document.querySelector('#userRegisterForm button[type="submit"]');
        registerBtn.disabled = true;
        registerBtn.textContent = 'Registering...';

        fetch('register_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Registration successful! You can now login.');
                userRegisterForm.reset();
                registerSections.forEach(section => section.classList.remove('active'));
                document.getElementById('userLoginForm').classList.add('active');
                document.querySelector('.tab-user').classList.add('active');
            } else {
                alert(data.message || 'Registration failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            registerBtn.disabled = false;
            registerBtn.textContent = 'Register as User';
        });
    });

    // === USER LOGIN ===
    const userLoginForm = document.getElementById('userLoginForm');
    userLoginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('userUsername').value;
        const password = document.getElementById('userPassword').value;
        
        if (!username || !password) {
            alert('Please enter both username and password');
            return;
        }

        // Show loading state
        const loginBtn = document.getElementById('userLoginButton');
        loginBtn.disabled = true;
        loginBtn.textContent = 'Logging in...';

        fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                password: password,
                userType: 'user'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = 'user_dashboard.html';
            } else {
                alert(data.message || 'Login failed. Please try again.');
                document.getElementById('userPassword').value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during login. Please try again.');
        })
        .finally(() => {
            loginBtn.disabled = false;
            loginBtn.textContent = 'Login as User';
        });
    });

    // === ADMIN LOGIN ===
    const adminLoginForm = document.getElementById('adminLoginForm');
    adminLoginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('adminUsername').value;
        const password = document.getElementById('adminPassword').value;
        
        if (!username || !password) {
            alert('Please enter both username and password');
            return;
        }

        // Show loading state
        const loginBtn = document.getElementById('adminLoginButton');
        loginBtn.disabled = true;
        loginBtn.textContent = 'Logging in...';

        fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: username,
                password: password,
                userType: 'admin'
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                window.location.href = 'admin_dashboard.php';
            } else {
                alert(data.message || 'Login failed. Please try again.');
                document.getElementById('adminPassword').value = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during login. Please try again.');
        })
        .finally(() => {
            loginBtn.disabled = false;
            loginBtn.textContent = 'Login as Admin';
        });
    });

    // === ADMIN REGISTRATION ===
    const adminRegisterForm = document.getElementById('adminRegisterForm');
    adminRegisterForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = {
            full_name: document.getElementById('adminRegName').value,
            email: document.getElementById('adminRegEmail').value,
            position: document.getElementById('adminRegPosition').value,
            reason: document.getElementById('adminRegReason').value
        };

        // Basic validation
        if (!formData.full_name || !formData.email || !formData.position || !formData.reason) {
            alert('All fields are required');
            return;
        }

        // Show loading state
        const submitBtn = document.querySelector('#adminRegisterForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        fetch('request_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert(data.message || 'Request submitted successfully!');
                adminRegisterForm.reset();
                loginForms.forEach(form => form.classList.remove('active'));
                registerSections.forEach(section => section.classList.remove('active'));
                document.getElementById('adminLoginForm').classList.add('active');
                document.querySelector('.tab-admin').classList.add('active');
            } else {
                alert(data.message || 'Request failed. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Request Admin Access';
        });
    });

    // === FIELD VALIDATION ===
    function setupValidation(inputElement, errorElement, minLength) {
        inputElement.addEventListener('input', function() {
            if (inputElement.value.length < minLength) {
                errorElement.style.display = 'block';
                inputElement.style.borderColor = 'var(--error-color)';
            } else {
                errorElement.style.display = 'none';
                inputElement.style.borderColor = '#ddd';
            }
        });
    }

    // Setup validation for login fields
    setupValidation(document.getElementById('userUsername'), document.getElementById('userUsernameError'), 3);
    setupValidation(document.getElementById('userPassword'), document.getElementById('userPasswordError'), 6);
    setupValidation(document.getElementById('adminUsername'), document.getElementById('adminUsernameError'), 3);
    setupValidation(document.getElementById('adminPassword'), document.getElementById('adminPasswordError'), 6);
});