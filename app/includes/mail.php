<?php
// Petit wrapper autour de mail() pour centraliser l'envoi des emails.
// En developpement, les emails sont captures par Mailhog (voir docker-compose.yml)
// et consultables sur http://localhost:8025

function send_app_mail(string $to, string $subject, string $htmlBody): bool
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>';

    $wrapped = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#1a1a2e;">'
        . '<h2 style="color:#162b4d;">Heros-SOS</h2>'
        . $htmlBody
        . '<p style="margin-top:32px;font-size:12px;color:#777;">Cet email a été envoyé automatiquement par la plateforme Heros-SOS.</p>'
        . '</div>';

    return @mail($to, $subject, $wrapped, implode("\r\n", $headers));
}
