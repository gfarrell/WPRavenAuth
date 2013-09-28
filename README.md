WPRavenAuth - Raven authentication for Wordpress
================================================

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

Install the plugin as you would a normal WP plugin, then create a directory called "keys" in the plugin directory (that's "WPRavenAuth" or whatever you called it, not the `wp-content/plugins` directory), and add the files from https://raven.cam.ac.uk/project/keys/ (but call them *2* and *2.crt* instead of *pubkey2* and *pubkey2.crt*).

Usage
-----

The plugin will replace the login system with a Raven login page - if a user who has never used your site before logs in with their Raven account, a new Wordpress account will be automatically created for them (with their CRSID as the name of the account).

If any existing users are set up with their univeristy email addresses *@cam.ac.uk* for the email field, they will never be able to log in with the new system (unless their username is also their crsid in lower case). If such users exist, they should be deleted, or if their username is NOT their crsid, they can change the email associated with their user to an external (i.e. non *@cam.ac.uk*) address. This should be done before activating this plugin.

By default, these new users with have *Subscriber* permissions. To promote a user to another permission level, find their account in the normal Wordpress *Users* section and modify it in the normal manner.
