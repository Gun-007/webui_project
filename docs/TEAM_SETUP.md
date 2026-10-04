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
git switch --track -c vedant origin/vedant
```

If Git says `origin` already exists, keep it and run `git fetch origin`, then switch to `vedant`. If the branch is already present locally, run `git switch vedant` and `git pull`.

The branch adds `wp-content/plugins/eventnest-core` and `wp-content/themes/eventnest`. WordPress core and local secrets remain ignored by Git. Do not run `git clean`, `git reset --hard`, or delete existing files to fix a checkout problem; ask the team to inspect the folder first.

## 3. Activate EventNest

In WordPress Admin (`http://localhost/EventNest/wp-admin/`):

1. Go to **Plugins → Installed Plugins** and activate **EventNest Core**.
2. Go to **Appearance → Themes** and activate **EventNest**.
3. Go to **Settings → Permalinks**, choose **Post name**, and click **Save Changes**.

EventNest Core creates its custom roles, post types, registration tables, and starter event categories when activated.

## 4. Create the EventNest pages

Pages live in each computer's WordPress database, so create them once on every new local site. For each page, use a **Shortcode** block and enter the shortcode exactly as shown:

| Page title | Suggested slug | Shortcode |
| --- | --- | --- |
| Login | `login` | `[eventnest_login]` |
| Register Student | `register-student` | `[eventnest_register_student]` |
| Register Faculty | `register-faculty` | `[eventnest_register_faculty]` |
| Dashboard | `dashboard` | `[eventnest_dashboard]` |
| My Registrations | `my-registrations` | `[eventnest_my_registrations]` |
| Submit Proposal | `submit-proposal` | `[eventnest_submit_proposal]` |
| My Proposals | `my-proposals` | `[eventnest_my_proposals]` |
| Review Proposals | `review-proposals` | `[eventnest_review_proposals]` |

Publish each page. The EventNest fallback navigation links visitors to proposal submission, students to My Proposals, and reviewers to Review Proposals. If you create a custom WordPress menu, set its links to the page slugs above.

## 5. Create users and assign reviewer roles

- Students can use **Register Student**. Their unique PRN can be used to log in.
- Faculty can request access through **Register Faculty**. Their account stays a limited Subscriber until an administrator verifies it.
- Administrators review those requests under **Users → Faculty Requests** and approve them as **Faculty** or **Club Head**.
- To create a Deputy Director, Director, or Admin account, an administrator goes to **Users → Add New** and assigns the correct role. Do not let public registration choose a privileged role.

## 6. Try the event registration flow

1. As an administrator or authorized event editor, open **Events → Add New**.
2. Add a title, description, future event date, venue, registration deadline, optional capacity, and featured image; then publish the event.
3. Log in as a student and open the event. Choose **Register**.
4. Confirm the event appears in **My Registrations**. Students may cancel before the event date.

Only published upcoming events appear in event listings. An approved proposal initially creates a draft event and will not appear until staff complete and publish it.

## 7. Try the proposal approval flow

1. Log in as a student and submit an event idea through **Submit Proposal**.
2. View its status and review history in **My Proposals**.
3. A Faculty or Club Head reviewer opens **Review Proposals** and can approve, request changes, or reject. A comment is required when requesting changes or rejecting.
4. The default approval order is Faculty/Club Head, then Deputy Director. Final approval creates a draft event.
5. An administrator completes the event details under **Events → All Events** and publishes it. The proposal then shows **Published**.

## 8. Share code changes with the team

The shared branch is `vedant`. Coordinate before editing the same files. Before starting, update your checkout:

```powershell
git switch vedant
git pull origin vedant
```

After changing theme or plugin source files, review and commit only the intended code:

```powershell
git status
git add wp-content\themes\eventnest wp-content\plugins\eventnest-core docs README.md
git commit -m "Describe the EventNest change"
git push origin HEAD:refs/heads/vedant
```

If someone else pushed first, pull the latest `vedant` branch and resolve any conflicts before pushing. Never add `wp-config.php`, database exports, passwords, or local uploads to a commit.

## Troubleshooting

- **Shortcode text appears on a page:** confirm EventNest Core is active and that the text is inside a Shortcode block with square brackets.
- **Page or event URL gives a 404:** save **Settings → Permalinks** again.
- **Review Proposals says access is required:** confirm the user has Faculty, Club Head, Deputy Director, Director, or Administrator review permissions.
- **Event is missing from the listing:** confirm it is published and its event date is in the future.
- **Git refuses a branch switch:** stop and inspect `git status`; do not clean or overwrite files unless you have verified what they are and have a backup.

## Current project boundaries

The repository does not include WordPress core, local databases, media uploads, email verification, online payments, or the student-facing team-registration form. Keep local development credentials private and do not use this XAMPP setup as a public production server.
