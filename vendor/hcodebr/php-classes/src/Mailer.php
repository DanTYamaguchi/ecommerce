<?php
namespace Hcode;

use Rain\Tpl;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;


class Mailer {


    const USERNAME = ""; //EMAIL REMETENTE
    const PASSWORD = ""; //SENHA REMETENTE
    const NAME_FROM = "Hcode Store";

    private $mail;
    public $toAddress;
    public $toName;
    public $subject;


    public function __construct($toAddress, $toName, $subject, $tplName, $data = array()) {

        $config = array(
		    "base_url"      => null,
		    "tpl_dir"       => $_SERVER['DOCUMENT_ROOT']."/views/email/",
		    "cache_dir"     => $_SERVER['DOCUMENT_ROOT']."/views-cache/",
		    "debug"         => false
		);

		Tpl::configure( $config );

		$tpl = new Tpl;

        foreach ($data as $key => $value) {
            $tpl->assign($key, $value);
        }

        $html = $tpl->draw($tplName, true);
        
        


        $this->mail = new PHPMailer(true);
        
         
        $this->mail->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
        $this->mail->isSMTP();                      //Define o uso de SMTP no envio
        $this->mail->SMTPAuth = true;               //Habilita a autenticação SMTP
        $this->mail->Username   = Mailer::USERNAME;
        $this->mail->Password   = Mailer::PASSWORD; // app password do gmail

        // Criptografia do envio SSL também é aceito
        $this->mail->SMTPSecure = "tls"; //PHPMailer::ENCRYPTION_STARTTLS;

        // Informações específicadas pelo Google
        $this->mail->Host = 'smtp-mail.outlook.com';
        $this->mail->Port = 587;

        // Define o remetente
        $this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);

        // Define o destinatário
        $this->mail->addAddress($toAddress, $toName);

        // Conteúdo da mensagem
        $this->mail->isHTML(true);  // Seta o formato do e-mail para aceitar conteúdo HTML
        $this->mail->Subject = $subject;
        $this->mail->msgHTML($html);   //'Este é o corpo da mensagem <b>Olá em negrito!</b>';

        
    }


    public function send() {

        return $this->mail->send();

    }
    
}

?>