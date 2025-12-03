<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require '../PHPMailer/Exception.php';
require '../PHPMailer/PHPMailer.php';
require '../PHPMailer/SMTP.php';

session_start();

require_once '../../config/database.php';
$email = trim($_POST['email']);

$sql = "SELECT FROM clientes WHERE email = '$email'" AND status=1;
$result = mysqli_query($conexion, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $mail = new PHPMailer(true);

try {                    
    $mail->isSMTP();                                            
    $mail->Host       = 'smtp.gmail.com';                     
    $mail->SMTPAuth   = true;                                  
    $mail->Username   = 'lilianaperezchonta42@gmail.com';                    
    $mail->Password   = '#Michuelo94'; 
    $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;                              
    $mail->Port       = 587;                                    

    //Recipients
    $mail->setFrom('lilianaperezchonta42@gmail.com', 'Ecogarden');
    $mail->addAddress('joe@example.net', 'Joe User');     
    

    //Attachments
    $mail->addAttachment('/var/tmp/file.tar.gz');         //Add attachments
    $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    //Optional name

    //Content
    $mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = 'Recuperacion de contraseña';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';
    $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}


    } else {
        $error = 'No existe una cuenta con este email.';
    }

?>