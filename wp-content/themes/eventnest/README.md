# EventNest theme

## Install
WordPress Admin → Appearance → Themes → Add New → Upload Theme → choose `eventnest.zip` → Activate.

## First setup
1. Settings → Permalinks → choose **Post name** and save.
2. Plugins → Installed Plugins → activate **EventNest Core**. It provides event and club records, roles, proposals and registration tables.
3. Events → Categories: choose or add categories. Event types and competition scope are provided by EventNest Core.
4. Events → Add New: add the title, description and featured image. EventNest Core provides dates, venue, deadline, capacity, fee and competition fields; the theme adds rules, highlights and optional external registration details.
5. Settings → Reading → “Your homepage displays” → choose a static page. The theme's `front-page.php` is used automatically for the homepage.
6. Appearance → Menus: create a menu and assign it to **Primary menu**.
7. Appearance → Customize → EventNest settings: set the homepage subtitle, login link, hero image and footer text.

## Notes
- Events with no date or a date in the past are hidden from listings.
- The event archive supports text search, city, category, date range and free-event filters.
- The homepage's closing-soon section uses registration deadlines in the next seven days.
- EventNest Core owns the Events and Clubs post types. The theme registers the city taxonomy and handles presentation.
- The event registration button uses an external link until the front-end registration flow is connected to EventNest Core.
