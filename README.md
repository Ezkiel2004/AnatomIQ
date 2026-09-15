# AnatomIQ

An interactive anatomy learning system with teacher and student portals. Application data is read from PHP APIs backed by MySQL/MariaDB. Student records, lessons, assessment results, school settings, anatomy descriptions, model URLs, achievement goals, and report grading thresholds are stored in the database.

## Start using this installation

1. Start Apache and MySQL in XAMPP, then open http://localhost/Prototype2/.
2. Sign in with your existing teacher account. Teacher accounts and their passwords were preserved.
3. Open **School & Anatomy Content**. Set the school name, school year, subject, default grade, recovery contact, and report grading thresholds. Blank settings are shown as unconfigured, not replaced by sample school details.
4. Use **Students → Add Student** to enter real student information. Each student needs an explicit password, grade level and school year. No shared default student password is supplied.
5. Use **Modules** to create or edit modules and add lessons. Publish both the module and lesson to make them available to students. Reading text, uploaded images, PDFs, videos and GLB attachments are supported.
6. Use **Assessments** to create questions, set attempt limits and passing scores, then publish the assessment. Teachers can configure identification, multiple-choice, true/false and image hotspot questions.

## Anatomy content

Teachers can open **Explore 3D Anatomy** from the dashboard or **3D Anatomy Explorer** in the sidebar. Teacher view includes hidden systems and links to edit the selected system or open its related module lessons. **Student preview** shows only systems visible to students, using the same viewer and descriptions. Neither teacher mode records student exploration progress. **Presentation mode** hides portal navigation and enlarges the viewer; use its exit button or Escape to return.

Use the Media Library to upload a GLB model, then choose its URL in School & Anatomy Content. Add the system description, key facts, structure descriptions and source/attribution information. A structure's optional model part name maps its description to a named mesh or parent group in the GLB file.

The existing skeleton asset is in system_model/. To use it, enter its project-relative web URL in the appropriate system's model field. Its filename is intentionally not embedded in the application code. A model must contain separately named parts for individual part selection; a single combined mesh cannot provide independent bone selection without editing the model.

The explorer loads the configured real model. Systems without models still display their database content with an explicit model-unavailable state. No procedural substitute or fabricated anatomical facts are displayed. Quick practice uses the structure descriptions configured by the teacher and does not award assessment grades.

## Database behavior

- The requested cleanup removed 10 sample student accounts and 36 lessons, with dependent student progress and submissions removed by foreign keys. Both teacher accounts were preserved.
- Existing database-managed body systems, modules, assessments, announcements and media were preserved. They are editable content, not frontend fixtures. Review and publish only the content intended for the new students.
- The installer SQL now contains structure only. It creates no demo teachers, students, lessons, scores, announcements or default school identity.
- The live database migration added app_settings, anatomy_content, achievement_rules, notification_receipts and quiz deadline/draft fields without replacing the existing teacher accounts. Announcement audiences now support actual student section names rather than a fixed section list.
- A complete pre-cleanup backup and the original supplied SQL export are preserved outside the web directory in C:/Users/ezeki/Documents/AnatomIQ-backups/. The live backup is before-cleanup-20260912-183309.sql; the original export is original-export-1789230304747.sql.
- To restore, import a backup into a separate recovery database using phpMyAdmin, verify it, then deliberately switch the application database if needed. The backup contains account hashes and school records and should not be served publicly.

## Quiz and reporting rules

Quiz answers are graded on the server. Identification keys and hotspot answer coordinates are omitted from student question responses. Hotspot questions use a teacher-selected image, target location and tolerance; they do not use a fixed heart diagram.

Answers are saved during an attempt. Refreshing resumes the same attempt with its saved answers and remaining server time. When the deadline has passed, the server grades only answers saved before that deadline. Repeated submission requests return the existing grade. Editing/deleting questions and changing grading rules are blocked after the first attempt to preserve historical results.

Average quiz score is the mean of a student's graded attempts. The class average is the mean of those student averages. Participation counts distinct student/assessment pairs for active assessments. Trend charts use actual submission dates. Achievement goals and report grade thresholds are configurable; no goals or report grading policy are invented for a fresh installation.

Lesson completion is a student's self-reported progress marker, not proof of mastery. Active reading time is measured separately and bounded on the server. Use assessment results when evaluating understanding.

## Fresh installation

Requires PHP 8.1 or newer, PDO MySQL, fileinfo, mbstring, Apache and MySQL/MariaDB. The included XAMPP PHP was used for validation.

1. Create an empty database named anatomiq_db in phpMyAdmin.
2. Configure ANATOMIQ_DB_HOST, ANATOMIQ_DB_NAME, ANATOMIQ_DB_USER and ANATOMIQ_DB_PASS in the server environment as needed. Local XAMPP defaults are used when absent.
3. From the project directory run:

~~~powershell
& C:/xampp/php/php.exe database/schema.php
~~~

4. For a new installation only, set ANATOMIQ_BOOTSTRAP_USERNAME, ANATOMIQ_BOOTSTRAP_NAME, ANATOMIQ_BOOTSTRAP_TEACHER_ID and ANATOMIQ_BOOTSTRAP_PASSWORD in the process environment, then run database/create_teacher.php with PHP. Remove the password variable afterward. Existing installations do not need a new teacher account.
5. For future non-destructive schema upgrades run database/migrate.php with PHP.

Maintenance scripts are CLI-only and database/, tests/ and .runtime/ are denied web access by Apache rules. Do not run reset or cleanup scripts as routine setup. Never use a production database for integration tests.

Password recovery is teacher-managed. The public recovery form shows the configured contact, and a signed-in teacher can reset a student's password from the student profile. No email delivery is claimed and reset tokens are never exposed. Password changes invalidate existing sessions; HTTPS enables the secure cookie flag automatically.

## Shared navigation

All teacher and student pages load their portal sidebar from assets/js/sidebar.js. Update navigation there so links, labels, icons and section headings stay consistent across pages. The shared toggle supports desktop collapse, mobile drawers, overlay dismissal and Escape.

Apache revalidates HTML and shared navigation assets through the root .htaccess. Sidebar page links and asset URLs carry a release version so old cached page copies do not reappear when changing sections. After this update, open http://localhost/Prototype2/teacher/dashboard.html?nav=20260914-icons1 once to load the current navigation. Advance the navigation version in sidebar.js and the shared asset versions in portal pages when releasing future navigation changes.

## Verification

The completed validation passed 72 integration checks and browser checks across 19 portal pages, including student registration, quiz refresh/resume, real GLB rendering, teacher/student anatomy layouts, hidden-system preview filtering, and presentation controls. Syntax checks passed for 27 JavaScript files/blocks and 39 PHP files, with static local file references also checked. Teacher exploration was verified to send no student progress records. The temporary test database and credentials were removed afterward.

~~~powershell
node tests/check-syntax.mjs
~~~

The integration suite uses an isolated database whose name must begin with anatomiq_test_. tests/database.php refuses to create, expire attempts or remove a database outside that prefix. Set ANATOMIQ_DB_NAME to a unique test name, run tests/database.php setup, and launch a separate PHP development server on 127.0.0.1:8091 using tests/router.php. Then run tests/integration.mjs. The browser suite uses a separate headless Chrome profile and debugging port 9225 and runs after the integration suite. It checks the real student creation form, quiz refresh/resume, all portal pages, a real GLB load and mobile anatomy layout.

The focused sidebar regression suite, node tests/navigation.mjs, passed on all 19 portal pages. It verifies complete and consistent menus, active links, icons, profile links, desktop collapse and mobile controls. Run it against a fresh isolated test database and the same server/browser setup; it creates its own student fixture and does not require the integration suite.

Finish by stopping the temporary server/browser and running tests/database.php cleanup with the same test database name. Test credentials and screenshots are stored only under .runtime/, excluded from source control and denied web access.

## Interface icons

Teacher and student portals use a shared, locally served Phosphor SVG icon set selected through Supericons. Navigation, dashboard cards, notifications, media types, modal controls, and common actions share the same family. See assets/icons/README.md for usage and assets/icons/LICENSE for attribution. Branding and database-configured anatomy symbols remain separate.
