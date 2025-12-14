<?php
session_start();
include('header.php');
?>

<main>
  <section class="content-section">
    <div class="about-box">
      <!-- Left side: logo -->
      <div class="about-left">
        <img src="Images/CampusTradeLogo.png" alt="CampusTrade Logo" class="about-logo">
      </div>

      <!-- Right side: text content -->
      <div class="about-right">
        <h1 class="hero-title">About CampusTrade</h1>

        <h2>Our Story</h2>
        <p>
          CampusTrade was created by students for students across the Minnesota State system.
          It started with a simple idea to make it easier for college students to buy and sell textbooks
          directly with one another without overpaying at bookstores or waiting on shipping.
        </p>
        <p>
          What began as a class project quickly became a community-driven marketplace that helps
          thousands of students save money and connect across campuses. Whether you’re looking to sell
          last semester’s books or find affordable study materials, CampusTrade keeps things simple,
          local, and trustworthy.
        </p>

        <h2>Marketplace Policy</h2>
        <p>
          CampusTrade provides a safe platform for students to connect similar to Facebook Marketplace.
          <strong>All transactions happen directly between the buyer and seller.</strong>
          CampusTrade does not handle payments or disputes.
        </p>

        <ul class="bullet-list">
          <li>✔ Meet in person on campus or in public areas.</li>
          <li>✔ Verify the book’s condition and edition before payment.</li>
          <li>✔ Use trusted payment methods such as Venmo, Cash App, or PayPal.</li>
          <li>✔ Keep your personal information private and avoid sharing sensitive data.</li>
        </ul>

        <p>
          By using CampusTrade, you agree to act respectfully and responsibly when buying or selling textbooks.
        </p>
      </div>
    </div>
  </section>
</main>

<?php include('footer.php'); ?>

