/* ==========================================================================
   AI PACKAGE INSPECTOR - PROFILE MANAGEMENT ENGINE (js/profile.js)
   ========================================================================== */

(function () {
  document.addEventListener("DOMContentLoaded", function () {
    const user = window.requireAuth();
    if (!user) return;

    const profileForm = document.getElementById("profile-form");
    if (!profileForm) return;

    // Populate Fields
    const nameInp = document.getElementById("prof-name");
    const empIdInp = document.getElementById("prof-empid");
    const emailInp = document.getElementById("prof-email");
    const phoneInp = document.getElementById("prof-phone");
    const deptInp = document.getElementById("prof-dept");
    const desigInp = document.getElementById("prof-designation");
    const avatarImg = document.getElementById("prof-avatar-img");
    const roleTag = document.getElementById("prof-role-tag");

    if (nameInp) nameInp.value = user.name || "";
    if (empIdInp) empIdInp.value = user.employeeId || "";
    if (emailInp) emailInp.value = user.email || "";
    if (phoneInp) phoneInp.value = user.phone || "";
    if (deptInp) deptInp.value = user.department || "";
    if (desigInp) desigInp.value = user.designation || "";
    if (avatarImg && user.avatar) avatarImg.src = user.avatar;
    if (roleTag) roleTag.textContent = user.role.toUpperCase();

    // Profile Form Save
    profileForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const newName = nameInp.value.trim();
      const newPhone = phoneInp.value.trim();
      const newDept = deptInp.value.trim();
      const newDesig = desigInp.value.trim();

      if (!newName || !newPhone) {
        window.showToast("Full Name and Phone Number are required.", "warning");
        return;
      }

      // Update Current User State
      user.name = newName;
      user.phone = newPhone;
      user.department = newDept;
      user.designation = newDesig;

      window.setCurrentUser(user);

      // Synchronize back to all users array
      const users = JSON.parse(localStorage.getItem("users") || "[]");
      const idx = users.findIndex((u) => u.id === user.id);
      if (idx !== -1) {
        users[idx] = { ...users[idx], ...user };
        localStorage.setItem("users", JSON.stringify(users));
      }

      window.showToast("Profile information updated successfully.", "success");
    });

    // Password Update Form
    const pwdForm = document.getElementById("change-password-form");
    if (pwdForm) {
      pwdForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const currentPwd = document.getElementById("pwd-current").value;
        const newPwd = document.getElementById("pwd-new").value;
        const confirmPwd = document.getElementById("pwd-confirm").value;

        if (currentPwd !== user.password) {
          window.showToast("Current password is incorrect.", "danger");
          return;
        }

        if (newPwd.length < 6) {
          window.showToast("New password must be at least 6 characters.", "warning");
          return;
        }

        if (newPwd !== confirmPwd) {
          window.showToast("New passwords do not match.", "warning");
          return;
        }

        user.password = newPwd;
        window.setCurrentUser(user);

        const users = JSON.parse(localStorage.getItem("users") || "[]");
        const idx = users.findIndex((u) => u.id === user.id);
        if (idx !== -1) {
          users[idx].password = newPwd;
          localStorage.setItem("users", JSON.stringify(users));
        }

        pwdForm.reset();
        window.showToast("Security password changed successfully.", "success");
      });
    }
  });
})();
