# EventNest theme

## Install
WordPress Admin → Appearance → Themes → Add New → Upload Theme → choose `eventnest.zip` → Activate.

## First setup (5 minutes)
1. Settings → Permalinks → choose "Post name" and Save (makes /events/ work).
2. Events → Categories and Events → Cities: add a few (e.g. Music, Workshops; your city).
3. Events → Add new: fill title, description, featured image and the "Event details" box. The box includes date and time, venue, registration deadline, capacity, rules, highlights, organizer and a registration link.
4. Settings → Reading → "Your homepage displays" → A static page (any page; the theme's front-page.php is used automatically).
5. Appearance → Menus: create a menu and assign it to "Primary menu".
6. Appearance → Customize → EventNest settings: headline, button, WhatsApp, brand colour.
7. Create a page called "Host an event" (the header button points to /host-an-event/ by default).

## Notes
- Events with no date, or a date in the past, are hidden from listings.
- The event archive supports text search, city, category, date range and free-event filters.
- The homepage's closing-soon section uses registration deadlines in the next seven days.
- The registration button uses the "Ticket or registration link" field. Built-in registration and participant tracking are planned for a later phase.
- The Events post type lives in `inc/events.php`. Move it into a plugin (`eventnest-core`) before switching themes in future.
