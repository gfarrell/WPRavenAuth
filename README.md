WPRavenAuth - Raven authentication for Wordpress
================================================

:warning: As per [#59](https://github.com/gfarrell/WPRavenAuth/issues/59) this project is now being maintained by [@mo-g](https://github.com/mo-g) and can be found at [mo-g/WPRavenAuth](https://github.com/mo-g/WPRavenAuth). :warning:

Version: 0.1.1
License: [BSD 3-Clause](http://opensource.org/licenses/BSD-3-Clause)

License
-------

Copyright (c) 2012, Gideon Farrell <me@gideonfarrell.co.uk>
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
Neither the name of the <ORGANIZATION> nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Requirements
------------

WPRavenAuth requires hosting *within the University of Cambridge network*, so that it may perform LDAP lookups, which is what we use to determine College and so on. Other than that it can run on any webserver (it doesn't require `mod_ucam_webauth`).

The plugin does require PHP > 5.3 because it uses the namespace construct, which was introduced in PHP 5.3.

Installation
------------

To install the plugin, cd to the `wp-content/plugins` directory, and then run `git clone --recursive https://github.com/gfarrell/WPRavenAuth.git`.

In the `WPRavenAuth` directory, create a directory called "keys", and add the Raven public key/cert. (Filenames should be `2` and `2.crt` instead of `pubkey2` and `pubkey2.crt` as they are commonly distributed).

Current canonical source for the keys is https://wiki.cam.ac.uk/raven/Raven_keys however there is a mirror at https://w3.charliejonas.co.uk/mirror/raven/keys/ in case there is a change of availability at the canonical source - follow best practice about validating the keys. Previous source https://raven.cam.ac.uk/project/keys/ still contains the key, but not the cert.

N.B. If you choose to install by downloading a zip from GitHub, it will not include the submodule for ACF (in `app/lib`), and you will need to download the zip for that repository and unpack it in the correct location manually.

Also N.B. That you may need to modify the `lib/advanced-custom-fields` directory to give it 775 permissions before the custom fields will function and display correctly.

Once you've done that, activate the plugin and go to the WPRavenAuth settings in the Wordpress Dashboard (under Settings). Here you can configure which colleges should be available to select for individual post or page visibility. You MUST also change the cookie key to be a long random string with alphanumeric characters and punctuation, which is used for preventing malicious attacks via cookie tampering. You MUST do this immediately after plugin activation or the plugin will continue to throw a warning.

Note that the `php_override.ini` file included in the root of the plugin directory should be moved to the root of your `public_html` directory if you are using the SRCF server for hosting. This is required to enable the `allow_fopen_url` directive, which Ibis requires to function.

Usage
-----

The plugin will replace the login system with a Raven login page - if a user who has never used your site before logs in with their Raven account, a new Wordpress account will be automatically created for them (with their CRSID as the username of the account and their lookup visible name as their display name).

NB: You can access the original Wordpress Login by adding `?super-admin=1` to your login url (e.g. `http://www.mywebsite.com/wp-login.php?super-admin=1`).

If any existing users are set up with their univeristy email addresses *@cam.ac.uk* for the email field, they will never be able to log in with the new system (unless their username is also their crsid in lower case). If such users exist, they should be deleted, or if their username is NOT their crsid, they can change the email associated with their user to an external (i.e. non *@cam.ac.uk*) address. This should be done before activating this plugin.

By default, the newly created users will have *Subscriber* permissions. To promote a user to another permission level, find their account in the normal Wordpress *Users* section and modify it in the normal manner.

To use the visibility settings, you can select the desired levels of visibility for any page or post individually. These options should appear as custom fields on every post or page. You can also configure the error message which is displayed to users with insufficient privilidges to view the content.

The plugin can also be used in combination with other visibility plugins, such as for menu item visibility, with something like the following as the visibility criterion:

    ((is_user_logged_in()) && (WPRavenAuth\Ibis::isMemberOfCollege(WPRavenAuth\Ibis::getPerson(wp_get_current_user()->user_login), 'KINGS')))
