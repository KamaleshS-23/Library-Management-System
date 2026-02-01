
function sendAdminApprovalEmail($to, $name, $username, $password) {
    try {
        $subject = "Your Admin Account Has Been Approved";
        $message = "Hello $name,\n\n";
        $message .= "Your admin account has been approved with the following details:\n";
        $message .= "Username: $username\n";
        $message .= "Password: $password\n\n";
        $message .= "Please login and change your password immediately.\n";
        
        $headers = "From: no-reply@yourdomain.com" . "\r\n";
        
        // Send email
        mail($to, $subject, $message, $headers);
        
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        // Continue anyway since account was created
        return false;
    }
}