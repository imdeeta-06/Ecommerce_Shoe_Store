<?php
// Simple redirect to checkout page since checkout acts as a full cart view
header("Location: " . BASE_URL . "checkout");
exit();
?>