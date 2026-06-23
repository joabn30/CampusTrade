<?php
session_start();

// Checks if user is already logged in before any page output is sent.
if (isset($_SESSION['user_id'])) {
    header("Location: buyerpage.php");
    exit;
}

$errors = $_SESSION['flash_errors'] ?? [];
$success = $_SESSION['flash_success'] ?? null;
$old = $_SESSION['old'] ?? [];
unset($_SESSION['flash_errors'], $_SESSION['flash_success'], $_SESSION['old']);

$value = static fn (string $key): string => htmlspecialchars((string)($old[$key] ?? ''), ENT_QUOTES, 'UTF-8');
$selected = static fn (string $key, string $value): string => (($old[$key] ?? '') === $value) ? ' selected' : '';

include("header.php");

?>

<main class="signup-page">
  <div class="signup-wrapper">
    <div class="logo-side signup-logo-side">
      <img src="Images/CampusTradeLogo.png" alt="CampusTrade Logo">
    </div>

    <div class="signup-container">
      <h2>Create an account</h2>

      <?php if (!empty($success)): ?>
        <div class="alert success" role="status" aria-live="polite">
          <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert error" role="alert" aria-live="polite">
          <ul class="alert-list">
            <?php foreach ($errors as $msg): ?>
              <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="signup-form" action="SignUP_Controller.php" method="POST">
        <div class="signup-inputs">
          <div class="form-field">
            <label for="firstName">First Name</label>
            <input id="firstName" name="firstName" type="text" value="<?= $value('firstName') ?>" autocomplete="given-name" required>
          </div>

          <div class="form-field">
            <label for="lastName">Last Name</label>
            <input id="lastName" name="lastName" type="text" value="<?= $value('lastName') ?>" autocomplete="family-name" required>
          </div>

          <div class="form-field form-field--wide">
            <label for="email">MinnState Email</label>
            <input id="email" name="email" type="email" placeholder="StartID@go.minnstate.edu" value="<?= $value('email') ?>" autocomplete="email" required>
          </div>

          <div class="form-field">
            <label for="school">School Name</label>
            <input id="school" name="school" type="text" value="<?= $value('school') ?>" required>
          </div>

          <div class="form-field">
            <label for="major">Major</label>
            <input id="major" name="major" type="text" value="<?= $value('major') ?>" required>
          </div>

          <div class="form-field">
            <label for="location">State/City</label>
            <input id="location" name="location" type="text" placeholder="State/City" value="<?= $value('location') ?>" required>
          </div>

          <div class="form-field">
            <label for="role">Role</label>
            <select id="role" name="role" required>
              <option value="" disabled<?= empty($old['role']) ? ' selected' : '' ?>>Student or Alumni</option>
              <option value="Student"<?= $selected('role', 'Student') ?>>Student</option>
              <option value="Alumni"<?= $selected('role', 'Alumni') ?>>Alumni</option>
            </select>
          </div>

          <div class="form-field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="6 characters minimum" autocomplete="new-password" required>
          </div>

          <div class="form-field">
            <label for="confirmPassword">Confirm Password</label>
            <input id="confirmPassword" name="confirmPassword" type="password" autocomplete="new-password" required>
          </div>
        </div>

        <button type="submit" name="submit">Sign Up</button>
      </form>

      <div class="login-redirect">
        Already have an account? <a href="LoginPage.php">Log in here</a>
      </div>

      <div class="note orange">Only for MinnState schools.</div>
    </div>
  </div>
</main>

<?php
include("footer.php");
?>
