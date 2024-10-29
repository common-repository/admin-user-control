=== Admin User Control ===
Contributors: pressmaninc, naokiikeda, pmfujimori, hiroshisekiguchi, kazunao, pmyosuke, kengotakeuchi
Tags: pressman, login, logout, user, users, notification, notifications, maintenance, admin, administrator
Requires PHP: 7.1.24
License: GNU General Public License, v2 or higher
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
Stable tag: 2.0.0
Requires at least: 5.4
Tested up to: 5.6

This plugin adds a useful feature to the administration screen that allows administrators to control the users involved in their operations.

= Notification function =
* Administrators can post notifications for users.
* Notifications are listed on the dashboard.
* Users can mark each notification as read.
* A warning will appear in the toolbar for users with unread notifications.

= Maintenance Notification Function =
* Administrators can post maintenance announcements for users. At this time, you can register the start time and end time of the maintenance and the message to be displayed on the login screen during the maintenance.
* During a maintenance period, non-administrative users are forced to logout of the administration screen. Also they will not be able to login to the administration screen until the end of the maintenance period.

= Login user monitoring function =
This feature is the successor to [Login Monitor](https://www.wordpress.org/plugins/login-monitor/).

* The user who is currently logged in to the administration screen is displayed in real time in the toolbar.
* Clicking on a user in the list of users will take you to that user's profile screen.

== Precautions ==
Internet Explorer is not supported.

== Installation ==
1. Upload the plugin package to the plugins directory.
2. Activate the plugin through the \'Plugins\' menu in WordPress.

== Screenshots ==
1. Dashboard widget for notifications with a warning in toolbar
2. Dashboard widget for maintenances
3. Forced-logout modal
4. Login page during a maintenance

== Changelog ==

= 2.0 =
Changed to enable only necessary functions in the settings screen.
Added enable/disable setting for toolbars in settings screen.
Changed to show user avatar in icon.
Added filter hook for user's link URL.
Added saving IP address to user meta information when using Ajax communication.
A minor bug fix.
Tested up to 5.5.1.

= 1.0 =
* first version.