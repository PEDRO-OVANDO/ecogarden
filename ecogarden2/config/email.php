<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Ajusta estas rutas según tu estructura
require_once '../../PHPMailer/Exception.php';
require_once '../../PHPMailer/PHPMailer.php';
require_once '../../PHPMailer/SMTP.php';

class EmailController {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        //configuracion del servidor SMTP
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'lilianaperezchonta42@gmail.com';
        $this->mail->Password = 'ukul qlxm xbch vknx';
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        
        //configuracion del remitente
        $this->mail->setFrom('lilianaperezchonta42@gmail.com', 'EcoGarden');
        $this->mail->isHTML(true);
        
        // Para debugging (opcional - quitar en producción)
        // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }
    
    public function enviarEmailRecuperacion($email_destino, $nombre_usuario, $token) {
        try {
            //destinatario
            $this->mail->addAddress($email_destino, $nombre_usuario);
            
            //asunto
            $this->mail->Subject = 'Recuperar contraseña - EcoGarden';
            
            //enlace de recuperacion
            $enlace_reset = "http://" . $_SERVER['HTTP_HOST'] . "/ecogarden2/views/clientes/reset_password.php?token=$token";
            
            //cuerpo del correo
            $this->mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #2d5a27; color: white; padding: 20px; text-align: center; }
                        .content { padding: 20px; background: #f8f9fa; }
                        .button { background: #2d5a27; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; }
                        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1> EcoGarden</h1>
                        </div>
                        <div class='content'>
                            <h2>Hola, $nombre_usuario</h2>
                            <p>Has solicitado recuperar tu contraseña en EcoGarden.</p>
                            <p>Para crear una nueva contraseña, haz clic en el siguiente enlace:</p>
                            <p style='text-align: center;'>
                                <a href='$enlace_reset' class='button'>Restablecer Contraseña</a>
                            </p>
                            <p><strong>Este enlace expirará en 1 hora.</strong></p>
                            <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                        </div>
                        <div class='footer'>
                            <p>© " . date('Y') . " EcoGarden</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Texto alternativo
            $this->mail->AltBody = "Hola $nombre_usuario,\n\nPara recuperar tu contraseña en EcoGarden, visita: $enlace_reset\n\nEste enlace expirará en 1 hora.";
            
            //enviar email
            if ($this->mail->send()) {
                error_log("Email enviado exitosamente a: $email_destino");
                return true;
            } else {
                error_log("Error al enviar email: " . $this->mail->ErrorInfo);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Excepción al enviar email: " . $this->mail->ErrorInfo);
            return false;
        }
    }
}
?>