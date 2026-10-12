# EventNest team setup guide

Follow this guide on each teammate's Windows computer to reach the same working setup. The shared Git branch contains the EventNest theme and plugin. Each computer has its own WordPress installation, database, accounts, and uploaded media.

## 1. Install WordPress locally

1. Install XAMPP and Git for Windows.
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. Create a database in `http://localhost/phpmyadmin/`, for example `eventnest_db`.
4. Download WordPress and extract it into `C:\xampp\htdocs\EventNest`.
5. Open `http://localhost/EventNest/` and complete the WordPress installer using the database you created. For a standard local XAMPP setup, the database user is often `root` with a blank password; use the values configured on that computer.

Do not copy another developer's `wp-config.php`, database dump, or user accounts. Those belong to their local installation.

## 2. Get the EventNest code

Open PowerShell in the WordPress folder:

```powershell
cd C:\xampp\htdocs\EventNest
git init
git remote add origin https://github.com/Gun-007/webui_project.git
git fetch origin
git switch --track -c prototype origin/prototype
```

If Git says `origin` already exists, keep it and run `git fetch origin`, then switch to `prototype`. If the branch is already present locally, run `git switch prototype` and `git pull`.

The branch adds `wp-content/plugins/eventnest-core` and `wp-content/themes/eventnest`. WordPress core and local secrets remain ignored by Git. Do not run `git clean`, `git reset --hard`, or delete existing files to fix a checkout problem; ask the team to inspect the folder first.

## 3. Activate EventNest

In WordPress Admin (`http://localhost/EventNest/wp-admin/`):

1. Go to **Plugins → Installed Plugins** and activate **EventNest Core**.
2. Go to **Appearance → Themes** and activate **EventNest**.
3. Go to **Settings → Permalinks**, choose **Post name**, and click **Save Changes**.

EventNest Core creates its custom roles, post types, registration tables, and event categories when activated. Sample clubs/events are opt-in from **Events → Starter Demo Content** and are not added to normal installations automatically.

## 4. Create the EventNest pages

Pages live in each computer's WordPress database, so create them once on every new local site. For each page, use a **Shortcode** block and enter the shortcode exactly as shown:

| Page title | Suggested slug | Shortcode |
| --- | --- | --- |
| Login | `login` | `[eventnest_login]` |
| Register Student | `register-student` | `[eventnest_register_student]` |
| Register Faculty | `register-faculty` | `[eventnest_register_faculty]` |
| Dashboard | `dashboard` | `[eventnest_dashboard]` |
| Competitions | `competitions` | `[eventnest_competitions]` |
| Event Registrations | `event-registrations` | `[eventnest_all_registrations]` (role and department scoped) |
| My Registrations | `my-registrations` | `[eventnest_my_registrations]` |
| My Clubs | `my-clubs` | `[eventnest_my_clubs]` |
| Submit Proposal | `submit-proposal` | `[eventnest_submit_proposal]` |
| My Proposals | `my-proposals` | `[eventnest_my_proposals]` |
| Review Proposals | `review-proposals` | `[eventnest_review_proposals]` |
| About | `about` | Leave content empty; the EventNest theme uses `page-about.php` automatically |

Publish each page. The About page must use the slug `about`; it does not need a Shortcode block because the theme renders its full layout from `wp-content/themes/eventnest/page-about.php`. The EventNest fallback navigation links visitors to proposal submission, students to My Proposals, and reviewers to Review Proposals. If you create a custom WordPress menu, set its links to the page slugs above.

The **My Clubs** page is created automatically if it does not already exist.

The **My Profile** page is created automatically for students; it lets them update their name, college email, mobile, course, and batch. PRN remains read-only because it is the account and registration identifier.

EventNest Core also creates **Share Your Story** (`/share-story/`) and **My Stories** (`/my-stories/`) automatically. The public **Student Stories** archive is `/student-stories/` and needs no WordPress Page. Add Student Stories and Share Your Story to a custom navigation menu if you are not using the theme's fallback menu.

## 5. Create users and assign reviewer roles

- Students can use **Register Student**. Their unique PRN can be used to log in.
- Faculty can request access through **Register Faculty**. Their account stays a limited Subscriber until an administrator verifies it.
- Administrators create departments under **Users → Departments**, then review faculty requests under **Users → Faculty Requests**. Approval can assign Faculty, Faculty Head, or Club Head and select a department for faculty roles. Departments can also be changed from the WordPress user editor.
- To create a Deputy Director, Director, or Admin account, an administrator goes to **Users → Add New** and assigns the correct role. Do not let public registration choose a privileged role.

## 6. Try the event registration flow

1. As an administrator or authorized event editor, open **Events → Add New**.
2. Add a title, description, future event date, venue, registration deadline, optional capacity, and featured image; then publish the event. For competitions, also assign the **Competition** event type and select **Intra-College** or **Inter-College** scope.
3. Log in as a student and open the event. Choose **Register**.
4. Confirm the event appears in **My Registrations**. Students may cancel before the event date.

The Competitions page lists upcoming published events marked as Competition and can filter by scope, category, search text, and date range.

## Clubs directory and event participants

- The public Clubs directory is the Club post type archive at `/clubs/`; it does not need a WordPress Page or shortcode. Add club images and leadership under **Clubs → Edit Club**; assign each club to a Department there and choose an in-charge Faculty member from the same department. Add events under **Events → Edit Event** and choose the club or assign the event directly to a department.
- The **Event Registrations** page displays private student names, PRNs, and email addresses. Administrators, Directors, and Deputy Directors can see institute-wide records; Faculty Heads and assigned Faculty are limited to their department; Club Heads are limited to their assigned clubs. Create the page using the table above; authorized reviewers can open it from their EventNest Dashboard or use `/event-registrations/`.
- Students apply from an individual club page. Their pending applications appear on that page only to its assigned Club Head, Faculty in-charge, and Administrators. After approval, the student must sign in to the same account to see **You are a member of this club** and the updated member count; the status also appears under **Dashboard → My Clubs**. Reviewers return to the club's application queue and should not see the student application form. Club membership data is stored locally with the WordPress database.
- Students can edit their application note while it is pending. The applicant and authorized reviewers can expand **Application history** to see earlier notes and decisions. A decided application is locked; a rejected student can apply again if the club is accepting applications.
- Administrators manage per-club head assignments under **Clubs → Club Leadership**. Assign a successor or mark the club vacant and enter a reason. Queue permissions follow the current assignment; membership data and historical content remain intact.
- Administrators manage access under **Users → EventNest Accounts**. Disabling an account requires a reason, blocks new logins, revokes active sessions, and preserves existing content and registrations. Reactivation restores the previous pending/active state.
- Use **Events → Competitions** to manage competitions separately from regular Events. **Add Competition** creates a draft with the Competition type and intra-college scope selected; edit scope to Inter-College where appropriate. These are still event records and retain their existing registration links.

Only published upcoming events appear in event listings. An approved proposal initially creates a draft event and will not appear until staff complete and publish it.

## Student stories

- Signed-in students submit an experience from **Share Your Story**. Each submission includes its title, story text, event, rating, course, year of study, and optional favourite moment and lesson learned.
- Students can add an optional JPG, PNG, GIF, or WebP photo up to 5 MB to a story or event proposal. The proposal photo carries into its draft event after approval. Events and clubs created in WordPress Admin can use the standard **Featured image** panel.
- Submissions are private while pending. Administrators review them under **Student Stories** in WordPress Admin and publish approved stories. Published entries appear at `/student-stories/` and their story detail pages.
- Students can check submission status in **My Stories**. The author name and submitted profile/story details are public after approval, so students should avoid putting private information in their story.

## 7. Try the proposal approval flow

1. Log in as a student and submit an event idea through **Submit Proposal**.
2. View its status and review history in **My Proposals**.
3. A Faculty or Club Head reviewer opens **Review Proposals** and can approve, request changes, or reject. A comment is required when requesting changes or rejecting.
4. The default approval order is Faculty/Club Head, then Deputy Director. Final approval creates a draft event.
5. An administrator completes the event details under **Events → All Events** and publishes it. The proposal then shows **Published**.

Students may only edit/resubmit a proposal after a reviewer chooses **Needs changes**. Submitted versions, including photos, are shown under **Submitted versions** in My Proposals. Other proposal states are locked to the student.

## 8. Share code changes with the team

The shared branch is `prototype`. Coordinate before editing the same files. Before starting, update your checkout:

```powershell
git switch prototype
git pull origin prototype
```

After changing theme or plugin source files, review and commit only the intended code:

```powershell
git status
git add wp-content\themes\eventnest wp-content\plugins\eventnest-core docs README.md
git commit -m "Describe the EventNest change"
git push origin HEAD:refs/heads/prototype
```

If someone else pushed first, pull the latest `prototype` branch and resolve any conflicts before pushing. Never add `wp-config.php`, database exports, passwords, or local uploads to a commit.

## Troubleshooting

- **Shortcode text appears on a page:** confirm EventNest Core is active and that the text is inside a Shortcode block with square brackets.
- **Page or event URL gives a 404:** save **Settings → Permalinks** again.
- **Review Proposals says access is required:** confirm the user has Faculty, Club Head, Deputy Director, Director, or Administrator review permissions.
- **Event is missing from the listing:** confirm it is published and its event date is in the future.
- **Git refuses a branch switch:** stop and inspect `git status`; do not clean or overwrite files unless you have verified what they are and have a backup.

## Current project boundaries

The repository does not include WordPress core, local databases, media uploads, email verification, online payments, or the student-facing team-registration form. Keep local development credentials private and do not use this XAMPP setup as a public production server.
