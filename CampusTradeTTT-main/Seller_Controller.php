<?php

// Seller_Controller.php
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$db = require __DIR__ . '/Database.php';
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/UserModel.php';

$userModel = new UserModel($db);
$s3Service = new \App\Services\S3Service();

// =========================
// Require login
// =========================
if (empty($_SESSION['user_id'])) {
    header("Location: LoginPage.php");
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$sellerId = $userId; // same thing in your app

// =========================
// POST HANDLERS
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =======================================
       1) PROFILE IMAGE UPLOAD (auto circle)
       ======================================= */
    if (!empty($_FILES['profileImage']['name'])) {

        $file  = $_FILES['profileImage'];
        $error = $file['error'];

        if ($error === UPLOAD_ERR_OK && $file['size'] > 0) {
            $imagePath = null;

            try {
                $profile = $userModel->ProfileExtraction();
                $oldImagePath = $profile['profile_image'] ?? null;
                $imagePath = $s3Service->uploadProfileImage($file);

                if (!$userModel->UpdateProfileImage($imagePath, $userId)) {
                    throw new RuntimeException('Profile image was uploaded, but the database was not updated.');
                }

                try {
                    $s3Service->deleteImage($oldImagePath);
                } catch (RuntimeException $cleanupError) {
                    $_SESSION['error'] = $cleanupError->getMessage();
                }

                header("Location: Seller_Controller.php?profile=img_updated");
                exit;
            } catch (InvalidArgumentException | RuntimeException $e) {
                if ($imagePath !== null) {
                    try {
                        $s3Service->deleteImage($imagePath);
                    } catch (RuntimeException $cleanupError) {
                        // Keep the original upload/profile error visible to the user.
                    }
                }

                $_SESSION['error'] = $e->getMessage();
                header("Location: Seller_Controller.php?profile=img_failed");
                exit;
            }
        }

        $_SESSION['error'] = "Upload error code: " . $error;
        header("Location: Seller_Controller.php?profile=img_failed");
        exit;
    }

    /* =======================================
       2) SAVE PROFILE FIELDS (Profile_Update.php)
       ======================================= */
    if (isset($_POST['edit_profile']) && isset($_POST['save_fields'])) {

        $Profile_data = [
            'first'  => trim($_POST['first_name'] ?? ''),
            'last'   => trim($_POST['last_name'] ?? ''),
            'acad'   => trim($_POST['acad_role'] ?? 'Student'),
            'school' => trim($_POST['school_name'] ?? ''),
            'major'  => trim($_POST['major'] ?? ''),
            'citySt' => trim($_POST['city_state'] ?? ''),
        ];

        try {
            $ok = $userModel->UpdateProfile($Profile_data, $userId);

            header("Location: Seller_Controller.php?profile=" . ($ok ? "updated" : "nochange"));
            exit;

        } catch (RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: Seller_Controller.php?view=profile_update&profile=failed");
            exit;
        }
    }

    /* =======================================
       3) POST A BOOK
       ======================================= */
    if (isset($_POST['post_book'])) {

        $titleAuthor = trim($_POST['titleAuthor'] ?? '');
        $isbn        = trim($_POST['isbn'] ?? '');
        $priceInput  = trim($_POST['price'] ?? '0');
        $condition   = $_POST['condition'] ?? 'New';
        $courseDept  = trim($_POST['courseDept'] ?? '');
        $contact     = trim($_POST['contact'] ?? '');

        if ($titleAuthor === '') {
            header('Location: Seller_Controller.php?error=missing_title');
            exit;
        }

        $price = (int) round((float)$priceInput);
        if ($price < 0) $price = 0;

        $bookState = ($condition === 'Used') ? 'Used' : 'New';
        $status    = 'Active';

        // book image upload
        $imagePath = null;
        if (!empty($_FILES['bookImage']['name'])) {
            $file  = $_FILES['bookImage'];
            $error = $file['error'];

            if ($error === UPLOAD_ERR_OK && $file['size'] > 0) {
                try {
                    $imagePath = $s3Service->uploadBookImage($file);
                } catch (InvalidArgumentException | RuntimeException $e) {
                    $_SESSION['error'] = $e->getMessage();
                    header('Location: Seller_Controller.php?error=image_upload_failed');
                    exit;
                }
            } elseif ($error !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['error'] = 'Upload error code: ' . $error;
                header('Location: Seller_Controller.php?error=image_upload_failed');
                exit;
            }
        }

        $sql = "
            INSERT INTO booklistings
            (seller_id, title, isbn, image_path, price, book_state, status, course_id, contact_info)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->bind_param(
                "isssissss",
                $sellerId,
                $titleAuthor,
                $isbn,
                $imagePath,
                $price,
                $bookState,
                $status,
                $courseDept,
                $contact
            );
            $stmt->execute();
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if ($imagePath !== null) {
                try {
                    $s3Service->deleteImage($imagePath);
                } catch (RuntimeException $cleanupError) {
                    // Keep the database error visible to the user.
                }
            }

            $_SESSION['error'] = 'Book was not saved: ' . $e->getMessage();
            header('Location: Seller_Controller.php?error=db_insert_failed');
            exit;
        }

        header('Location: Seller_Controller.php?posted=1');
        exit;
    }

   /* ---- B) PROFILE IMAGE FORM WITHOUT A FILE ---- */
    if (isset($_POST['edit_profile'])) {
        header('Location: Seller_Controller.php?profile=updated');
        exit;
    }


    /* ---- C) DELETE BOOK ---- */
    if (isset($_POST['delete_book'])) {
        $bookIdToDelete = isset($_POST['postedBook']) ? (int) $_POST['postedBook'] : 0;

        if ($bookIdToDelete > 0) {
            $bookToDelete = $userModel->GetBookId($bookIdToDelete, $sellerId);

            $sql = "DELETE FROM booklistings WHERE id = ? AND seller_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $bookIdToDelete, $sellerId);
            $stmt->execute();
            $deletedRows = $stmt->affected_rows;
            $stmt->close();

            if ($deletedRows > 0 && !empty($bookToDelete['image_path'])) {
                try {
                    $s3Service->deleteImage($bookToDelete['image_path']);
                } catch (RuntimeException $e) {
                    $_SESSION['error'] = $e->getMessage();
                }
            }
        }

        header('Location: Seller_Controller.php?deleted=1');
        exit;
    }

    /* =======================================
       5) EDIT BOOK (open Edit_book.php)
       ======================================= */
    if (isset($_POST['edit_book'])) {
        $booktoEdit = isset($_POST['postedBook']) ? (int) $_POST['postedBook'] : 0;

        if ($booktoEdit <= 0) {
            $_SESSION['error'] = "Please select a book to edit.";
            header("Location: Seller_Controller.php");
            exit;
        }

        $book = $userModel->GetBookId($booktoEdit, $sellerId);

        if (!$book) {
            $_SESSION['error'] = "Book not found.";
            header("Location: Seller_Controller.php");
            exit;
        }

        include 'Edit_book.php';
        exit;
    }

    /* =======================================
       6) UPDATE BOOK
       ======================================= */
    if (isset($_POST['update_book'])) {

        $Book_info = [
            'id'          => (int)($_POST['book_id'] ?? 0),
            'titleAuthor' => trim($_POST['titleAuthor'] ?? ''),
            'isbn'        => trim($_POST['isbn'] ?? ''),
            'price'       => trim($_POST['price'] ?? '0'),
            'condition'   => $_POST['condition'] ?? 'New',
            'courseDept'  => trim($_POST['courseDept'] ?? ''),
            'contact'     => trim($_POST['contact'] ?? ''),
        ];

        if ($Book_info['id'] <= 0) {
            $_SESSION['error'] = "Invalid book.";
            header("Location: Seller_Controller.php");
            exit;
        }

        $existingImage = trim($_POST['existing_image'] ?? '');
        $newImagePath  = $existingImage;

        if (!empty($_FILES['bookImage']['name'])) {
            $file  = $_FILES['bookImage'];
            $error = $file['error'];

            if ($error === UPLOAD_ERR_OK && $file['size'] > 0) {
                try {
                    $newImagePath = $s3Service->uploadBookImage($file);
                } catch (InvalidArgumentException | RuntimeException $e) {
                    $_SESSION['error'] = $e->getMessage();
                    header("Location: Seller_Controller.php");
                    exit;
                }
            } elseif ($error !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['error'] = 'Upload error code: ' . $error;
                header("Location: Seller_Controller.php");
                exit;
            }
        }

        $Book_info['image_path'] = $newImagePath;

        try {
            $userModel->UpdateBook($Book_info, $sellerId);

            if ($newImagePath !== $existingImage) {
                $s3Service->deleteImage($existingImage);
            }

            header("Location: Seller_Controller.php");
            exit;

        } catch (Exception $e) {
            if ($newImagePath !== $existingImage) {
                try {
                    $s3Service->deleteImage($newImagePath);
                } catch (RuntimeException $cleanupError) {
                    // Keep the original database error visible to the user.
                }
            }

            $_SESSION['error'] = "Error: " . $e->getMessage();
            header("Location: Seller_Controller.php");
            exit;
        }
    }
}

// =========================
// GET VIEW: Profile Update page
// =========================
if (isset($_GET['view']) && $_GET['view'] === "profile_update") {

    $profile = $userModel->ProfileExtraction();

    $vFirst     = $profile['first_name'] ?? '';
    $vLast      = $profile['last_name'] ?? '';
    $vAcad      = $profile['acad_role'] ?? '';
    $vSchool    = $profile['school_name'] ?? '';
    $vMajor     = $profile['major'] ?? '';
    $vCityState = $profile['city_state'] ?? '';
    $vEmail     = $profile['email'] ?? '';
    $vImgSrc    = $profile['profile_image'] ?: "Images/ProfileIcon.png";

    require "Profile_Update.php";
    exit;
}

// =========================
// DEFAULT LOAD: SellerPage
// =========================
$profile = $userModel->ProfileExtraction();

$vFirst     = $profile['first_name'] ?? '';
$vLast      = $profile['last_name'] ?? '';
$vAcad      = $profile['acad_role'] ?? '';
$vSchool    = $profile['school_name'] ?? '';
$vMajor     = $profile['major'] ?? '';
$vCityState = $profile['city_state'] ?? '';
$vEmail     = $profile['email'] ?? '';
$vPay       = $profile['preferred_pay'] ?? 'Cash';
$vImgSrc    = $profile['profile_image'] ?: "Images/ProfileIcon.png";

// books for dropdown
$sql = "SELECT id, title FROM booklistings WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$postedBooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require "SellerPage.php";
exit;



