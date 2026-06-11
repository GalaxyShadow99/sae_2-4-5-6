//liens vers le reste des pages

if (!isset($_SESSION['user_id']) || !isUserAdmin($conn, $_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}
