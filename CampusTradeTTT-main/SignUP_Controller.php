<?php

session_start();

$db = require __DIR__ . '/Database.php';  

//Load the model class definition
require __DIR__ . '/UserModel.php';

//mysqli throws exceptions for try and catch handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


//Create a new instance of the  UserModel class, and Pass in the pdo connection as a parameter, you can perfom sql queries
$userModel = new UserModel($db); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

$email = $_POST['email'] ?? '';
$password =$_POST['password'] ?? '';
$first_name = $_POST['firstName'] ?? '';
$last_name = $_POST['lastName'] ?? '';
$school_name =$_POST['school'] ?? '';
$major = $_POST['major'] ?? '';
$acad_role = $_POST['role'] ?? 'Student';
$city = $_POST['location'] ?? '';


$confirm_pass = $_POST['confirmPassword'];

//Verify that the password field  matches the confirm password field.
if($password !== $confirm_pass){
    $msg = 'Password field and Confirm Password field must match';
    $_SESSION['flash_errors'] = [$msg];
    $_SESSION['old'] = $_POST;
    header('Location: SignUpPage.php');
    exit;
}

//Verify that the password field and confirm password fields aren't empty
if(empty($password) || empty($confirm_pass)){
    $msg = 'Password fields cannot be empty';
    $_SESSION['flash_errors'] = [$msg];
    $_SESSION['old'] = $_POST;
    header('Location: SignUpPage.php');
    exit;
}

//Store Signup page data into an associative array
$User_data = [
  'email'       => $email,
  'password'    => $password,
  'first_name'  => $first_name,
  'last_name'   => $last_name,
  'school_name' => $school_name,
  'major'       => $major,
  'acad_role'   => $acad_role,
  'city' => $city,
];

try{
  $id = $userModel->CreateAccount($User_data);

header('Location: LoginPage.php');
exit;

//Catches the bad email exception
}catch(InvalidArgumentException $e){
    $_SESSION['flash_errors'] = [$e ->getMessage()];
    $_SESSION['old'] = $_POST;
    header('Location: SignUpPage.php');
    exit;
}catch(mysqli_sql_exception $e){
     // Covers unexpected DB issues (like duplicate email race or DB offline)
    $msg = ((int)$e->getCode() === 1062)
        ? 'Email already exists. Please log in or use another email.'
        : 'Something went wrong. Please try again later.';
    $_SESSION['flash_errors'] = [$msg];
    $_SESSION['old'] = $_POST;
    header('Location: SignUpPage.php');
    exit;
}
}
?>
