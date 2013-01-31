<div class="wrap">
<?php screen_icon(); ?>
<h2>Raven Auth Settings</h2>

private $cfg = array(
        // default options
        'ldap'   => array(
            'server' => 'ldap.lookup.cam.ac.uk',
            'base'   => 'ou=people,o=University of Cambridge,dc=cam,dc=ac,dc=uk',
            'port'   => '636'
        ),
        'cookie' => 'WPRavenAuth'
    );

<?php if(current_user_can('edit_users')):
    $fields = Config::get();
    function create_settings_field($name, $value, $prefices = array('WPRavenAuth')) {
        if(!is_array($value)) {
            
        }
    }
?>
<form method="post" action="options.php">
    <!-- OK, I know that this is a table-based layout, which makes me want to kill myself, but that's all wordpress wants me to do, and it's a piece of shit framework -->
    <table class="form-table">
        <tbody>
            <?php foreach($fields['ldap'])
            <tr>
                <th><label for="WPRavenAuthLdapServer">LDAP Server</label></th>
                <td>
                    <input type="text" id="WPRavenAuthLdapServer" name="WPRavenAuth[ldap][server]" value="<?php echo WPRavenAuth\Config::get('ldap.server'); ?>"
                </td>
            </tr>
        </tbody>
    </table>
</form>
    <fieldset>
        <legend>LDAP Settings</legend>

    </fieldset>
    <fieldset>
        <legend>Other Settings</legend>
        <label for="WPRavenAuthCookie">Cookie name</label>
        <input type="text" value="<?php echo WPRavenAuth\Config::get('cookie'); ?>" id="WPRavenAuthCookie" />
    </fieldset>
</form>


<?php else: ?>

<p class="red">Sorry, you do not have permission to access this!</p>

<?php endif; ?>
</div>