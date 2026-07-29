<?php
/**
 * ============================================================
 *  content-defaults.php
 *  Master list of editable text on the site. Each entry is the
 *  ORIGINAL wording — if an admin never edits a key, this is
 *  exactly what visitors see, so this file is always a safe
 *  fallback (works even before site_content has any rows).
 *
 *  'html' => true means the value may contain simple inline
 *  tags (like <em>) and is inserted with innerHTML on the page.
 * ============================================================
 */

return [

    // ---------- Home page ----------
    'idx_hero_h1' => [
        'group' => 'Home',
        'label' => 'Hero heading',
        'value' => 'Inner <em>Peace</em>',
        'html'  => true,
    ],
    'idx_hero_sub' => [
        'group' => 'Home',
        'label' => 'Hero subtitle',
        'value' => 'Saraṇa is a small meditation center set among tea hills outside Kandy — a place for guided sittings, silent retreats, and the slow work of paying attention.',
    ],
    'idx_h2_intro' => [
        'group' => 'Home',
        'label' => 'Intro heading',
        'value' => 'A center built around one idea: sit down, and stay a while.',
    ],

    // ---------- About page ----------
    'about_h1' => [
        'group' => 'About',
        'label' => 'Page heading',
        'value' => 'Thirteen years of sitting still on this hillside.',
    ],
    'about_h2_intro' => [
        'group' => 'About',
        'label' => 'Intro heading',
        'value' => 'Built by practitioners, not by a brand.',
    ],

    // ---------- Services page ----------
    'services_h1' => [
        'group' => 'Services',
        'label' => 'Page heading',
        'value' => 'Practices held daily, open to every level.',
    ],
    'services_h2_hall' => [
        'group' => 'Services',
        'label' => 'Gallery heading',
        'value' => 'The hall, the terraces, the hills',
    ],

    // ---------- Retreats page ----------
    'retreats_h1' => [
        'group' => 'Retreats',
        'label' => 'Page heading',
        'value' => 'Go deeper, on the hillside, for a few days at a time.',
    ],
    'retreats_h2_intro' => [
        'group' => 'Retreats',
        'label' => 'Intro heading',
        'value' => 'Sit With the Hills Around You',
    ],

    // ---------- Contact page ----------
    'contact_h1' => [
        'group' => 'Contact',
        'label' => 'Page heading',
        'value' => 'Plan your visit to the hillside.',
    ],
    'contact_h2_intro' => [
        'group' => 'Contact',
        'label' => 'Intro heading',
        'value' => "You'll Be Welcomed, Not Rushed",
    ],

    // ---------- Deep Stillness Retreat ----------
    'rds_h1' => [
        'group' => 'Deep Stillness Retreat',
        'label' => 'Page heading',
        'value' => 'Deep Stillness Retreat',
    ],
    'rds_h2_intro' => [
        'group' => 'Deep Stillness Retreat',
        'label' => 'Intro heading',
        'value' => 'The one guests come back for.',
    ],

    // ---------- Long Silent Retreat ----------
    'rls_h1' => [
        'group' => 'Long Silent Retreat',
        'label' => 'Page heading',
        'value' => 'Long Silent Retreat',
    ],
    'rls_h2_intro' => [
        'group' => 'Long Silent Retreat',
        'label' => 'Intro heading',
        'value' => 'Room to go further.',
    ],

    // ---------- Reset Weekend ----------
    'rrw_h1' => [
        'group' => 'Reset Weekend',
        'label' => 'Page heading',
        'value' => 'Reset Weekend',
    ],
    'rrw_h2_intro' => [
        'group' => 'Reset Weekend',
        'label' => 'Intro heading',
        'value' => 'A short retreat that still goes deep.',
    ],

    // ---------- Booking confirmation email (sent automatically to the visitor) ----------
    'email_confirm_subject' => [
        'group' => 'Booking confirmation email',
        'label' => 'Subject line',
        'value' => 'We received your request — Saraṇa Meditation Center',
    ],
    'email_confirm_body' => [
        'group' => 'Booking confirmation email',
        'label' => 'Body (use {{name}}, {{program}}, {{dates}} as placeholders)',
        'value' => "<p>Dear {{name}},</p>\n<p>Thank you for reaching out to Saraṇa Meditation Center. We've received your request for <strong>{{program}}</strong> on <strong>{{dates}}</strong>.</p>\n<p>Thank you for submitting! We will contact you soon.</p>\n<p style='color:#8a7a63;'>— Saraṇa Meditation Center, Kandy, Sri Lanka</p>",
        'html'  => true,
    ],

];
