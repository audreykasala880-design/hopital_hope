<?php
/**
 * ====================================================================
 * CLIENT SMTP NATIF ULTRA-LÉGER EN PUR PHP (ZÉRO DÉPENDANCE)
 * ====================================================================
 * Permet d'envoyer de vrais emails via un serveur SMTP distant
 * (Gmail, Brevo, Outlook, etc.) sans nécessiter PHPMailer ou Composer.
 */

/**
 * Envoie un email via connexion socket SMTP native
 * 
 * @param string $to Adresse email destinataire
 * @param string $subject Sujet de l'email
 * @param string $htmlBody Contenu au format HTML
 * @param array $config Paramètres SMTP
 * @return array ['success' => bool, 'error' => string|null]
 */
function envoyerEmailViaSmtp($to, $subject, $htmlBody, $config = []) {
    $host       = $config['host']       ?? (defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com');
    $port       = $config['port']       ?? (defined('SMTP_PORT') ? SMTP_PORT : 587);
    $user       = $config['user']       ?? (defined('SMTP_USER') ? SMTP_USER : '');
    $pass       = $config['pass']       ?? (defined('SMTP_PASS') ? SMTP_PASS : '');
    $fromEmail  = $config['from_email'] ?? (defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $user);
    $fromName   = $config['from_name']  ?? (defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Centre Médical Hope');

    $timeout = 15;
    $context = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        ]
    ]);

    $remote = ($port == 465 ? "ssl://$host:$port" : "tcp://$host:$port");
    $socket = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

    if (!$socket) {
        return ['success' => false, 'error' => "Connexion impossible au serveur SMTP ($errstr)"];
    }

    $readResponse = function() use ($socket) {
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $response;
    };

    $sendCommand = function($cmd) use ($socket, $readResponse) {
        fputs($socket, $cmd . "\r\n");
        return $readResponse();
    };

    $readResponse(); // Salutation serveur

    // EHLO
    $resp = $sendCommand("EHLO localhost");
    if (substr($resp, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'error' => "EHLO rejeté : $resp"];
    }

    // STARTTLS si port 587
    if ($port == 587) {
        $resp = $sendCommand("STARTTLS");
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'error' => "STARTTLS refusé : $resp"];
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            fclose($socket);
            return ['success' => false, 'error' => "Échec négociation chiffrement TLS"];
        }
        // Répéter EHLO sous TLS
        $sendCommand("EHLO localhost");
    }

    // Authentification
    if (!empty($user) && !empty($pass)) {
        $resp = $sendCommand("AUTH LOGIN");
        if (substr($resp, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'error' => "AUTH LOGIN refusé : $resp"];
        }
        $resp = $sendCommand(base64_encode($user));
        if (substr($resp, 0, 3) !== '334') {
            fclose($socket);
            return ['success' => false, 'error' => "Nom d'utilisateur SMTP rejeté : $resp"];
        }
        $resp = $sendCommand(base64_encode($pass));
        if (substr($resp, 0, 3) !== '235') {
            fclose($socket);
            return ['success' => false, 'error' => "Mot de passe SMTP rejeté : $resp"];
        }
    }

    // MAIL FROM
    $resp = $sendCommand("MAIL FROM: <$fromEmail>");
    if (substr($resp, 0, 3) !== '250') {
        fclose($socket);
        return ['success' => false, 'error' => "MAIL FROM rejeté : $resp"];
    }

    // RCPT TO
    $resp = $sendCommand("RCPT TO: <$to>");
    if (substr($resp, 0, 3) !== '250' && substr($resp, 0, 3) !== '251') {
        fclose($socket);
        return ['success' => false, 'error' => "Destinataire rejeté : $resp"];
    }

    // DATA
    $resp = $sendCommand("DATA");
    if (substr($resp, 0, 3) !== '354') {
        fclose($socket);
        return ['success' => false, 'error' => "DATA refusé : $resp"];
    }

    // Construction des en-têtes MIME RFC 2822
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Date: " . date('r') . "\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";

    $message = $headers . "\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.\r\n";
    fputs($socket, $message);
    $resp = $readResponse();

    $sendCommand("QUIT");
    fclose($socket);

    if (substr($resp, 0, 3) === '250') {
        return ['success' => true, 'error' => null];
    } else {
        return ['success' => false, 'error' => "Échec envoi données : $resp"];
    }
}
