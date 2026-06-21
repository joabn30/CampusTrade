<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header('Location: LoginPage.php');
    exit;
}

// Connect to DB (mysqli)
$db = require __DIR__ . '/Database.php';

// 1. Get book id from the URL: BuyButtonPage.php?id=123
$bookId = $_GET['id'] ?? null;

if (!$bookId) {
    // No id in the URL → go back to buyer page
    header('Location: buyerpage.php');
    exit;
}

$bookId = (int)$bookId;

// 2. Load book + seller info from DB
//    booklistings  -> book data
//    accounts      -> seller account info
//    userprofile   -> avatar (optional)
$sql = "
    SELECT 
        b.*,
        a.email,
        a.first_name,
        a.last_name,
        a.school_name,
        a.major,
        a.acad_role,
        a.city_state,
        u.preferred_pay,
        u.profile_image
    FROM booklistings b
    JOIN accounts a ON b.seller_id = a.id
    LEFT JOIN userprofile u ON u.user_id = a.id
    WHERE b.id = ?
";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $bookId);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

// defaults
$sellerName = '';
$avatarSrc  = 'Images/ProfileIcon.png';
$coverSrc   = null;

if ($book) {
    $sellerName = trim(($book['first_name'] ?? '') . ' ' . ($book['last_name'] ?? ''));
    if (!empty($book['profile_image'])) {
        $avatarSrc = $book['profile_image'];
    }
    if (!empty($book['image_path'])) {
        $coverSrc = $book['image_path'];
    }
}

include('header.php');
?>

<main class="buy-page-main">
  <section class="buy-page-section">
    <div class="buy-box">

      <?php if (!$book): ?>

        <!-- If no matching book found -->
        <div style="grid-column:1 / -1; text-align:center; padding:40px 10px;">
          <h1>Book Not Found</h1>
          <p>The book you are trying to view does not exist or has been removed.</p>
          <a href="buyerpage.php" class="button switch" style="margin-top:15px; display:inline-block;">
            Back to Book List
          </a>
        </div>

      <?php else: ?>
      <div class="parent-buy">
        <!-- LEFT: Seller info -->
        <div class="buy-left">
          <h1>Seller Information</h1>

          <div class="avatar-uploader">
            <label class="avatar" aria-label="Seller profile picture">
              <img
                src="<?php echo htmlspecialchars($avatarSrc); ?>"
                alt="Seller profile picture"
                onerror="this.src='Images/ProfileIcon.png'">
            </label>
          </div>

          <p><strong>Name:</strong>
            <?php echo htmlspecialchars($sellerName); ?>
          </p>
          <p><strong>Status:</strong>
            <?php echo htmlspecialchars($book['acad_role']); ?>
          </p>
          <p><strong>School:</strong>
            <?php echo htmlspecialchars($book['school_name']); ?>
          </p>
          <p><strong>Major:</strong>
            <?php echo htmlspecialchars($book['major']); ?>
          </p>
          <p><strong>Location:</strong>
            <?php echo htmlspecialchars($book['city_state']); ?>
          </p>
          <p><strong>Email:</strong>
            <?php if (!empty($book['email'])): ?>
              <a href="mailto:<?php echo htmlspecialchars($book['email']); ?>?subject=Interested%20in%20your%20book:%20<?php echo rawurlencode($book['title']); ?>">
                <?php echo htmlspecialchars($book['email']); ?>
              </a>
            <?php else: ?>
              Not provided
            <?php endif; ?>
          </p>
        </div>

        <!-- RIGHT: Book details -->
        <div class="buy-right">
          <h1>Book Details</h1>

          <div class="book-upload">
            <?php if (!empty($coverSrc)): ?>
              <div class="book-cover-view">
                <img
                  src="<?php echo htmlspecialchars($coverSrc); ?>"
                  alt="Book cover for <?php echo htmlspecialchars($book['title']); ?>">
              </div>
            <?php else: ?>
              <label class="book-circle" aria-label="Book image">
                <span class="book-plus">+</span>
                <span class="book-hint">No Image</span>
              </label>
            <?php endif; ?>
          </div>

          <p><strong>Title:</strong>
            <?php echo htmlspecialchars($book['title']); ?>
          </p>
          <p><strong>Price:</strong>
            $<?php echo htmlspecialchars($book['price']); ?>
          </p>
          <p><strong>Course:</strong>
            <?php echo htmlspecialchars($book['course_id'] ?? ''); ?>
          </p>
          <p><strong>Condition:</strong>
            <?php echo htmlspecialchars($book['book_state']); ?>
          </p>
          <p><strong>ISBN:</strong>
            <?php echo htmlspecialchars($book['isbn'] ?? ''); ?>
          </p>
          <p><strong>Status:</strong>
            <?php echo htmlspecialchars($book['status']); ?>
          </p>
        </div>
      </div>

      <!-- NOTE + MESSAGE BUTTON: full-width row under all columns -->
      <div class="buy-note">
        <div class="note">
          Please contact the seller directly to discuss how payment will be made and how you will receive the book.
        </div>

        <?php if (!empty($book['email'])): ?>
          <a class="message-button"
             href="mailto:<?php echo htmlspecialchars($book['email']); ?>?subject=Interested%20in%20your%20book:%20<?php echo rawurlencode($book['title']); ?>">
            Message Seller
          </a>
        <?php endif; ?>
      </div>

      <?php endif; // end if $book ?>

    </div>
  </section>
</main>

<?php include('footer.php'); ?>
