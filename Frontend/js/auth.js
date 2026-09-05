/* ==========================================================================
   AI PACKAGE INSPECTOR - AUTHENTICATION & SEED ENGINE (js/auth.js)
   ========================================================================== */

(function () {
  // Default Mock Data Seeders
  const DEFAULT_USERS = [
    {
      id: "U001",
      name: "Admin Officer",
      employeeId: "ADM-1001",
      email: "admin@example.com",
      phone: "+91 98765 43210",
      department: "Directorate of Legal Metrology",
      designation: "Chief Metrology Admin",
      role: "admin",
      password: "admin123",
      status: "Active",
      joiningDate: "2024-01-15",
      avatar: "https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80"
    },
    {
      id: "U002",
      name: "Rahul Sharma",
      employeeId: "INS-102",
      email: "inspector@example.com",
      phone: "+91 98123 45678",
      department: "Legal Metrology - North Zone",
      designation: "Senior Metrology Inspector",
      role: "inspector",
      password: "123456",
      status: "Active",
      joiningDate: "2024-03-10",
      avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80"
    },
    {
      id: "U003",
      name: "Priya Verma",
      employeeId: "INS-105",
      email: "priya.verma@example.com",
      phone: "+91 97234 56789",
      department: "Legal Metrology - South Zone",
      designation: "Legal Inspector",
      role: "inspector",
      password: "123456",
      status: "Active",
      joiningDate: "2024-05-22",
      avatar: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80"
    },
    {
      id: "U004",
      name: "Amit Patel",
      employeeId: "INS-108",
      email: "amit.patel@example.com",
      phone: "+91 96345 67890",
      department: "Packaged Goods Standards Division",
      designation: "Field Inspection Officer",
      role: "inspector",
      password: "123456",
      status: "Active",
      joiningDate: "2024-08-11",
      avatar: "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80"
    },
    {
      id: "U005",
      name: "Sunita Rao",
      employeeId: "INS-112",
      email: "sunita.rao@example.com",
      phone: "+91 95456 78901",
      department: "Legal Metrology - Central Zone",
      designation: "Metrology Inspector",
      role: "inspector",
      password: "123456",
      status: "Active",
      joiningDate: "2025-01-09",
      avatar: "https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80"
    },
    {
      id: "U006",
      name: "Vikram Singh",
      employeeId: "INS-115",
      email: "vikram.singh@example.com",
      phone: "+91 94567 89012",
      department: "Import Compliance Cell",
      designation: "Customs Metrology Inspector",
      role: "inspector",
      password: "123456",
      status: "Inactive",
      joiningDate: "2025-04-18",
      avatar: "https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&auto=format&fit=crop&q=80"
    }
  ];

  // Initialize LocalStorage Data if Empty
  window.initAppData = function () {
    if (!localStorage.getItem("users")) {
      localStorage.setItem("users", JSON.stringify(DEFAULT_USERS));
    }
  };

  // Run seed initialization
  window.initAppData();

  // Auth Helpers
  window.getCurrentUser = function () {
    const userStr = localStorage.getItem("currentUser");
    return userStr ? JSON.parse(userStr) : null;
  };

  window.setCurrentUser = function (user) {
    localStorage.setItem("currentUser", JSON.stringify(user));
  };

  window.logoutUser = function () {
    localStorage.removeItem("currentUser");
    const path = window.location.pathname;
    if (path.includes("/admin/")) {
      window.location.href = "../login.html";
    } else if (path.includes("/pages/")) {
      window.location.href = "login.html";
    } else {
      window.location.href = "pages/login.html";
    }
  };

  // Route Guards
  window.requireAuth = function () {
    const user = window.getCurrentUser();
    if (!user) {
      const path = window.location.pathname;
      if (path.includes("/admin/")) {
        window.location.href = "../login.html";
      } else if (path.includes("/pages/")) {
        window.location.href = "login.html";
      } else {
        window.location.href = "pages/login.html";
      }
      return null;
    }
    return user;
  };

  window.requireAdmin = function () {
    const user = window.requireAuth();
    if (user && user.role !== "admin") {
      window.location.href = "../dashboard.html";
      return null;
    }
    return user;
  };

  // Login Form Binding
  document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("login-form");
    if (loginForm) {
      loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const emailInput = document.getElementById("email") || document.getElementById("employee-id");
        const passwordInput = document.getElementById("password");
        const loginBtn = document.getElementById("btn-login-submit");
        const errorAlert = document.getElementById("auth-error-alert");

        if (!emailInput || !passwordInput) return;

        let identifier = emailInput.value.trim().toLowerCase();
        // Allow officer@example.com alias
        if (identifier === "officer@example.com") identifier = "inspector@example.com";

        const password = passwordInput.value.trim();

        if (loginBtn) {
          loginBtn.classList.add("loading");
          loginBtn.innerHTML = '<span class="spinner-icon"></span> Authenticating...';
        }

        setTimeout(() => {
          const users = JSON.parse(localStorage.getItem("users") || "[]");
          const foundUser = users.find(
            (u) =>
              (u.email.toLowerCase() === identifier || u.employeeId.toLowerCase() === identifier) &&
              u.password === password
          );

          if (foundUser) {
            if (foundUser.status === "Inactive") {
              if (loginBtn) {
                loginBtn.classList.remove("loading");
                loginBtn.innerHTML = "Login";
              }
              if (errorAlert) {
                errorAlert.textContent = "Account deactivated. Please contact administrator.";
                errorAlert.style.display = "block";
              }
              return;
            }

            window.setCurrentUser(foundUser);

            if (foundUser.role === "admin") {
              window.location.href = "admin/dashboard.html";
            } else {
              window.location.href = "dashboard.html";
            }
          } else {
            if (loginBtn) {
              loginBtn.classList.remove("loading");
              loginBtn.innerHTML = "Login";
            }
            if (errorAlert) {
              errorAlert.textContent = "Invalid Email/Employee ID or Password.";
              errorAlert.style.display = "block";
            }
          }
        }, 600);
      });
    }

    // Password Toggle Button
    const togglePasswordBtn = document.getElementById("toggle-password-btn");
    if (togglePasswordBtn) {
      togglePasswordBtn.addEventListener("click", function () {
        const pwdInput = document.getElementById("password");
        if (pwdInput) {
          const isPassword = pwdInput.type === "password";
          pwdInput.type = isPassword ? "text" : "password";
          togglePasswordBtn.innerHTML = isPassword
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        }
      });
    }

    // Register Form Binding
    const registerForm = document.getElementById("register-form");
    if (registerForm) {
      registerForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const fullName = document.getElementById("reg-name").value.trim();
        const empId = document.getElementById("reg-empid").value.trim();
        const email = document.getElementById("reg-email").value.trim();
        const phone = document.getElementById("reg-phone").value.trim();
        const dept = document.getElementById("reg-dept").value;
        const password = document.getElementById("reg-password").value;
        const confirmPassword = document.getElementById("reg-confirm-password").value;
        const regBtn = document.getElementById("btn-register-submit");
        const successMsg = document.getElementById("register-success-alert");
        const errorAlert = document.getElementById("register-error-alert");

        if (errorAlert) errorAlert.style.display = "none";

        if (password !== confirmPassword) {
          if (errorAlert) {
            errorAlert.textContent = "Passwords do not match.";
            errorAlert.style.display = "block";
          }
          return;
        }

        if (password.length < 6) {
          if (errorAlert) {
            errorAlert.textContent = "Password must be at least 6 characters.";
            errorAlert.style.display = "block";
          }
          return;
        }

        if (regBtn) {
          regBtn.classList.add("loading");
          regBtn.innerHTML = '<span class="spinner-icon"></span> Registering...';
        }

        setTimeout(() => {
          const users = JSON.parse(localStorage.getItem("users") || "[]");
          const existing = users.find((u) => u.email === email || u.employeeId === empId);

          if (existing) {
            if (regBtn) {
              regBtn.classList.remove("loading");
              regBtn.innerHTML = "Create Officer Account";
            }
            if (errorAlert) {
              errorAlert.textContent = "An officer with this Email or Employee ID already exists.";
              errorAlert.style.display = "block";
            }
            return;
          }

          const newInspector = {
            id: "U" + String(users.length + 1).padStart(3, "0"),
            name: fullName,
            employeeId: empId,
            email: email,
            phone: phone,
            department: dept,
            designation: "Legal Metrology Officer",
            role: "inspector",
            password: password,
            status: "Active",
            joiningDate: new Date().toISOString().split("T")[0],
            avatar: "https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150&auto=format&fit=crop&q=80"
          };

          users.push(newInspector);
          localStorage.setItem("users", JSON.stringify(users));

          if (regBtn) {
            regBtn.classList.remove("loading");
            regBtn.innerHTML = "Create Officer Account";
          }

          if (successMsg) {
            successMsg.style.display = "block";
            registerForm.reset();
            setTimeout(() => {
              window.location.href = "login.html";
            }, 1800);
          }
        }, 700);
      });
    }
  });
})();
