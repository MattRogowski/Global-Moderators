Name: Global Moderators
Description: Apply moderator permissions globally to all forums
Website: https://github.com/MattRogowski/Global-Moderators
Author: Matt Rogowski
Authorsite: https://matt.rogow.ski
Version: 1.0.0
Compatibility: 1.8.x
Files: 3
Database changes: 1 new table

To Install:
Upload ./inc/plugins/globalmoderators.php to ./inc/plugins/
Upload ./admin/modules/user/globalmoderators.php to ./admin/modules/user/
Upload ./inc/languages/english/admin/user_globalmoderators.lang.php to ./inc/languages/english/admin/
Go to ACP > Plugins > Install and Activate
Go to ACP > Users & Groups > Global Moderators.

Information:
This plugin will allow you to give users or usergroups moderator permissions globally in all forums.

This differs from super moderators, who have full moderator permissions in all forums - instead, this plugin lets you add more specific permissions, replicating the default forum moderator functionality, but lets you apply globally, to save having to apply the same permissions to every forum.

Change Log:
29/12/16 - v0.0.1 -> Initial beta release.
28/01/17 - v0.0.1 -> v1.0.0 -> Fixed a bug with text not appearing when using PHP 7. Fixed a bug with some permissions not being applied correctly. To upgrade, reupload ./inc/plugins/globalmoderators.php, ./admin/modules/user/globalmoderators.php and ./inc/languages/english/admin/user_globalmoderators.lang.php.

Copyright 2017 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.