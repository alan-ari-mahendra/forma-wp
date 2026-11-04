# FORMA Core
- `inc/fields.php` — SCF field groups (Hero…Trial on the front page; announcement, nav CTA, contact, hours, socials under **FORMA Settings**).
- `inc/render.php` — shortcodes `[forma_hero|marquee|intro|programs|facilities|schedule|coaches|pricing|testimonials|faq|trial]`, one per Elementor section container.
- `inc/shell.php` — announcement/header/footer fallback; auto-disabled when Pro Elements Theme Builder has a header/footer template.
- `inc/trial.php` — REST `POST /wp-json/forma/v1/trial|newsletter`, stored as private `FORMA Leads`, emailed to admin.
- `inc/schema.php` — ExerciseGym + FAQPage merged into Rank Math's graph (from the same SCF data).
- `assets/forma.css|js` — design tokens, layout, tabs, schedule filters, FAQ, forms (vanilla, deferred, not loaded in Elementor editor).
- `wp forma seed --images=<dir>` — idempotent bootstrap (page, fields, menus, kit colors/fonts).
Schedule status: waitlist flag or 0 spots = Full; ≤3 = Almost full. Replace placeholder images in Media Library (alt text already set).
