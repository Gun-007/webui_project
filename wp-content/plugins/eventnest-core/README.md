# EventNest Core
Roles, data model, proposal approval and event registration for the EventNest college portal.
Approval order is the `enc_approval_chain` option (default: faculty -> deputy). Use ['faculty'] for A, or filter `enc_approval_chain`.

## Account pages

Create these WordPress pages and place the matching shortcode in each page's content:

| Page slug | Shortcode |
| --- | --- |
| `login` | `[eventnest_login]` |
| `register-student` | `[eventnest_register_student]` |
| `register-faculty` | `[eventnest_register_faculty]` |
| `dashboard` | `[eventnest_dashboard]` |
| `event-registrations` | `[eventnest_all_registrations]` |
| `my-registrations` | `[eventnest_my_registrations]` |
| `my-clubs` | `[eventnest_my_clubs]` |
| `submit-proposal` | `[eventnest_submit_proposal]` |
| `my-proposals` | `[eventnest_my_proposals]` |
| `review-proposals` | `[eventnest_review_proposals]` |
| `share-story` | `[eventnest_submit_story]` |
| `my-stories` | `[eventnest_my_stories]` |

Student registration requires a unique PRN, college email, mobile, name and a password of at least 10 characters. Students can sign in with username, email or PRN. Faculty registration creates a low-privilege Subscriber account marked as pending; an administrator must verify the person and assign the appropriate EventNest role in Users before they receive faculty or club permissions. Never let a public registration form choose an elevated role.

The **Share Your Story** and **My Stories** pages are created automatically if missing; the other shortcode pages in the table are created manually by the site administrator.

Administrators can review pending staff signups under **Users → Faculty Requests** and approve each as Faculty or Club Head. Approval assigns the role and clears the pending state.

Faculty, Club Heads, Deputy Directors, Directors and Administrators with the corresponding review capability can review submissions on the `review-proposals` page. Decisions are recorded in the proposal approval history; rejecting or requesting changes requires a reviewer comment.

These forms use WordPress accounts and nonces. Passwords are stored through WordPress's password API; EventNest stores the additional profile fields as user metadata. Email verification and automated account approval are not included yet.

## Student participation pages

The event detail page uses EventNest's internal registration system when this plugin is active. Registrations are limited to signed-in accounts with the `en_register_event` capability. The My Registrations page shows only the current user's records and permits cancellation before the event date. Team-registration data functions exist, but the student-facing team form is a later enhancement. Proposal submissions use the core approval workflow; students can see their proposal status and review history on My Proposals, and can edit/resubmit proposals marked Needs changes.

The Event Registrations shortcode is administrator-only and shows participant name, PRN, email, event, team, status, and registration time, with filters and pagination. The public Clubs archive and club detail templates live in the EventNest theme at `/clubs/` and `/clubs/{club}/`.

On the next site request, EventNest creates starter records for Tech, Cultural, Dance, Music, Sports, Photography, Coding, and Management clubs, and one future-dated sample event for each. Existing records with those slugs are preserved. These demo listings can be edited or deleted under **Clubs** and **Events**; replace their dates, venues, and descriptions with confirmed information before sharing them as real campus events.

Students can apply to join a club from its public detail page. Only that club's assigned faculty in-charge or club head, plus administrators, can review pending applications. Students see application decisions on the club page and their My Clubs page.

Student Stories are public entries at `/student-stories/`. Students submit stories from **Share Your Story**; submissions stay pending until an administrator reviews and publishes them under **Student Stories** in WordPress Admin. **My Stories** lets each student check pending and published submissions. Story author name, course, year, event, rating, written experience, favourite moment and learning are shown publicly after approval. The Share Your Story and My Stories pages are created automatically; add **Student Stories** to any custom navigation menu.
