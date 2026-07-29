<?php
/**
 * ============================================================
 *  confirmation-mail.php
 *  Builds + sends the "we received your request" email to a
 *  visitor, using whatever subject/body an admin has saved in
 *  /admin/content.php (or a beautiful default if untouched).
 *
 *  The default email includes a full sample day timetable so
 *  the visitor knows exactly what to expect on arrival.
 * ============================================================
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/content-store.php';
require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Returns the beautiful day-timetable HTML email body (default template).
 * Accepts {{name}}, {{program}}, {{dates}} placeholders.
 */
function get_default_confirmation_body(): string {
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Visit to Saraṇa</title>
</head>
<body style="margin:0;padding:0;background:#f7f3ee;font-family:'Georgia',serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f3ee;padding:32px 0;">
  <tr>
    <td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

        <!-- HEADER -->
        <tr>
          <td style="background:linear-gradient(135deg,#2c1a0e 0%,#4a2c14 60%,#6b3e1e 100%);
                     border-radius:16px 16px 0 0;padding:40px 40px 32px;text-align:center;">
            <p style="margin:0 0 6px;font-size:11px;letter-spacing:.18em;text-transform:uppercase;
                      color:#d4a96a;font-family:'Helvetica Neue',Arial,sans-serif;">
              Saraṇa Meditation Center
            </p>
            <h1 style="margin:0;font-size:28px;color:#fff;font-weight:400;font-family:'Georgia',serif;
                       line-height:1.25;">
              Your visit is confirmed 🌿
            </h1>
            <p style="margin:14px 0 0;color:#c8a77a;font-size:15px;font-family:'Helvetica Neue',Arial,sans-serif;">
              Kandy Hills, Sri Lanka
            </p>
          </td>
        </tr>

        <!-- GREETING -->
        <tr>
          <td style="background:#fff;padding:36px 40px 24px;">
            <p style="margin:0 0 16px;font-size:16px;color:#3b2612;line-height:1.7;">
              Dear <strong>{{name}}</strong>,
            </p>
            <p style="margin:0 0 16px;font-size:15px;color:#5a3e28;line-height:1.8;">
              Thank you for reaching out to us. We have received your enquiry for
              <strong style="color:#8a5c2e;">{{program}}</strong> on
              <strong style="color:#8a5c2e;">{{dates}}</strong> and we are delighted to welcome you
              to the hillside.
            </p>
            <p style="margin:0;font-size:15px;color:#5a3e28;line-height:1.8;">
              Our team will be in touch within <strong>24 hours</strong> to confirm availability,
              share directions, and answer any questions you may have. In the meantime, here is
              a glimpse of what a typical day at Saraṇa looks like:
            </p>
          </td>
        </tr>

        <!-- DAY TIMETABLE HEADER -->
        <tr>
          <td style="background:#fff;padding:0 40px 8px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="border-top:2px solid #e8ddd0;padding-top:24px;">
                  <p style="margin:0;font-size:11px;letter-spacing:.16em;text-transform:uppercase;
                             color:#a07850;font-family:'Helvetica Neue',Arial,sans-serif;font-weight:700;">
                    A Day at Saraṇa — Sample Timetable
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- TIMETABLE ROWS -->
        <?php
        // Timetable defined in PHP so we can loop cleanly
        $schedule = [
            ['05:30', 'Wake &amp; Silence', '#c97c30', 'Gentle bell. The day begins in quiet — no phones, no conversation until after breakfast.'],
            ['06:00', 'Morning Sitting Meditation', '#7a9e60', '60 minutes of guided sitting in the main hall, facing the valley mist.'],
            ['07:00', 'Walking Meditation', '#6b8fa8', 'Slow, mindful walking on the hillside terrace — barefoot on dew-wet grass.'],
            ['07:45', 'Breakfast', '#c97c30', 'A nourishing Sri Lankan vegetarian breakfast served in silence or gentle conversation.'],
            ['09:00', 'Yoga &amp; Movement', '#7a9e60', '75 minutes of gentle yoga and body-scan movement in the open-air pavilion.'],
            ['10:30', 'Free Practice / Rest', '#6b8fa8', 'Personal sitting, journaling, reading, or simply resting in the gardens.'],
            ['12:30', 'Lunch', '#c97c30', 'A wholesome midday meal — rice, curry, fresh fruits from the estate.'],
            ['14:00', 'Dharma Talk or Group Sharing', '#7a9e60', 'An optional 45-minute discussion on the day\'s theme — open to all levels.'],
            ['15:00', 'Sound Healing Session', '#6b8fa8', 'Singing bowls, chimes, and guided relaxation — deeply restorative.'],
            ['16:30', 'Tea &amp; Garden Walk', '#c97c30', 'Ceylon tea with biscuits in the garden, followed by a guided nature walk.'],
            ['18:00', 'Evening Sitting Meditation', '#7a9e60', '45 minutes — candle-lit hall, metta (loving-kindness) practice.'],
            ['19:00', 'Light Dinner', '#c97c30', 'Simple, nourishing supper served at sunset.'],
            ['20:00', 'Chanting &amp; Reflection', '#6b8fa8', 'Optional evening chanting or free time for personal practice.'],
            ['21:00', 'Noble Silence Begins', '#8a5c2e', 'The grounds settle into stillness until morning. Rest well.'],
        ];
        foreach ($schedule as $i => $item):
            $bg = $i % 2 === 0 ? '#fffdf9' : '#fff';
        ?>
        <tr>
          <td style="background:<?= $bg ?>;padding:0 40px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td width="72" valign="top" style="padding:14px 14px 14px 0;">
                  <span style="display:inline-block;background:<?= $item[2] ?>;color:#fff;
                               font-family:'Helvetica Neue',Arial,sans-serif;font-size:11px;
                               font-weight:700;letter-spacing:.04em;padding:5px 9px;
                               border-radius:100px;white-space:nowrap;">
                    <?= $item[0] ?>
                  </span>
                </td>
                <td valign="top" style="padding:14px 0;border-bottom:1px solid #f0ebe4;">
                  <p style="margin:0 0 3px;font-size:14px;font-weight:700;color:#3b2612;
                             font-family:'Helvetica Neue',Arial,sans-serif;">
                    <?= $item[1] ?>
                  </p>
                  <p style="margin:0;font-size:13px;color:#7a5c3e;line-height:1.6;
                             font-family:'Helvetica Neue',Arial,sans-serif;">
                    <?= $item[3] ?>
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <?php endforeach; ?>

        <!-- NOTE BELOW TIMETABLE -->
        <tr>
          <td style="background:#fff;padding:20px 40px 32px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="background:#fdf6ec;border-left:3px solid #c97c30;
                           border-radius:0 8px 8px 0;padding:16px 18px;">
                  <p style="margin:0;font-size:13px;color:#6b4626;line-height:1.7;
                             font-family:'Helvetica Neue',Arial,sans-serif;">
                    <strong>Note:</strong> Timetables may vary slightly depending on the program and retreat format.
                    Not all sessions are mandatory — we honour your pace. Guests are welcome to rest, write,
                    or simply sit with the hills.
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- WHAT TO BRING -->
        <tr>
          <td style="background:#f4efe8;padding:28px 40px;">
            <p style="margin:0 0 14px;font-size:11px;letter-spacing:.16em;text-transform:uppercase;
                       color:#a07850;font-family:'Helvetica Neue',Arial,sans-serif;font-weight:700;">
              What to bring
            </p>
            <table width="100%" cellpadding="0" cellspacing="0">
              <?php
              $items = [
                'Comfortable loose clothing (layers for early mornings)',
                'A journal or notebook',
                'Open-toed footwear or sandals',
                'Any personal medications',
                'Minimal luggage — simplicity is part of the practice',
              ];
              foreach ($items as $it):
              ?>
              <tr>
                <td width="22" valign="top" style="padding:4px 0;color:#c97c30;font-size:16px;">●</td>
                <td style="padding:4px 0;font-size:13px;color:#5a3e28;line-height:1.6;
                            font-family:'Helvetica Neue',Arial,sans-serif;">
                  <?= $it ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </table>
          </td>
        </tr>

        <!-- CLOSING -->
        <tr>
          <td style="background:#fff;padding:32px 40px 28px;">
            <p style="margin:0 0 16px;font-size:15px;color:#5a3e28;line-height:1.8;">
              We look forward to welcoming you.
              If you have any questions before then, simply reply to this email.
            </p>
            <p style="margin:0;font-size:15px;color:#8a5c2e;font-style:italic;line-height:1.7;">
              — The Saraṇa Team
            </p>
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="background:#2c1a0e;border-radius:0 0 16px 16px;padding:24px 40px;text-align:center;">
            <p style="margin:0 0 6px;font-size:13px;color:#d4a96a;
                      font-family:'Helvetica Neue',Arial,sans-serif;">
              Saraṇa Meditation Center
            </p>
            <p style="margin:0;font-size:12px;color:#8a7060;
                      font-family:'Helvetica Neue',Arial,sans-serif;">
              Galahitiyawa Road, Kandy Hills, Central Province, Sri Lanka<br>
              +94 81 234 5678 &nbsp;|&nbsp; hello@saranameditation.lk
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
HTML;
}

/**
 * Fill {{name}}, {{program}}, {{dates}} placeholders into the
 * saved subject/body template.
 */
function render_confirmation_email(array $contentMap, string $name, string $program, ?string $dates): array {
    $subject = $contentMap['email_confirm_subject']
             ?? 'Your visit to Saraṇa is confirmed — here\'s your day plan';

    // Use custom body from admin if set, otherwise use the rich default template
    $hasCustomBody = isset($contentMap['email_confirm_body'])
                  && trim(strip_tags($contentMap['email_confirm_body'])) !== '';

    $body = $hasCustomBody
          ? $contentMap['email_confirm_body']
          : get_default_confirmation_body();

    $replacements = [
        '{{name}}'    => htmlspecialchars($name,    ENT_QUOTES, 'UTF-8'),
        '{{program}}' => htmlspecialchars($program, ENT_QUOTES, 'UTF-8'),
        '{{dates}}'   => $dates ? htmlspecialchars($dates, ENT_QUOTES, 'UTF-8') : 'a date to be confirmed',
    ];

    $subject = strtr($subject, $replacements);
    $body    = strtr($body,    $replacements);
    $altBody = trim(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], "\n", $body)));

    return [$subject, $body, $altBody];
}

/**
 * Sends the confirmation email. Throws PHPMailerException on failure —
 * callers decide how to report that (see send-booking.php / admin/resend-confirmation.php).
 */
function send_visitor_confirmation_email(string $toEmail, string $toName, string $subject, string $bodyHtml, string $altBody): void {
    $mail = new PHPMailer(true);
    if (MAIL_DEBUG) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(SMTP_USERNAME, MAIL_FROM_NAME);
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $bodyHtml;
    $mail->AltBody = $altBody;

    $mail->send();
}
