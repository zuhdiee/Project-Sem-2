<?php
// Jembatan redirect: semua halaman yang redirect ke login.php
// akan diteruskan ke index.php
header("Location: index.php");
exit();
?>