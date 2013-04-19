<?php
/**
 * Admin functions PubMed Publist plugin
 **/

//TODO: Add Purge cache buttons / functions.
//TODO: Add cache settings
//TODO: Add PMID exclude setting.

/* settings link in plugin management screen */
function pm_pubmed_settings_link( $links ) {
    return array_merge(
        array(
            'settings' => '<a href="options-general.php?page=pm_publist">Settings</a>'
        ),
        $links
    );
}
add_filter('plugin_action_links', 'pm_pubmed_settings_link');


class wctest{
    public function __construct(){
        if(is_admin()){
            add_action('admin_menu', array($this, 'pm_publist_add_plugin_page'));
            add_action('admin_init', array($this, 'pm_publist_page_init'));
        }
    }

    //Create settings page
    public function pm_publist_add_plugin_page(){
        // This page will be under "Settings"
        add_options_page( 'PubMed PubList Settings', 'PubMed Publist', 'manage_options', 'pm_publist', array($this, 'pm_publist_create_admin_page') );
    }

    //Create the settings page content
    public function pm_publist_create_admin_page(){
        ?>
    <div class="wrap">
        <?php screen_icon(); ?>
        <h2>PubMed Publist Settings</h2>
        <form method="post" action="options.php">
            <?php
                    // This prints out all hidden setting fields
            settings_fields('pm_publist_option_group');
            do_settings_sections('pm_publist-setting-admin');
        ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
    }

    public function pm_publist_page_init(){
        register_setting('pm_publist_option_group', 'array_key', array($this, 'pm_publist_check_searchStrings'));
        //TODO: Use single options array instead of multiple options.
        //First Search String
        add_settings_section(
            'pm_publist_searchStrings',
            'PubMed Search Strings',
            array($this, 'pm_publist_searchString_info'),
            'pm_publist-setting-admin'
        );
        add_settings_field(
            'searchString1',
            'Search String 1',
            array($this, 'pm_publist_create_searchString_field'),
            'pm_publist-setting-admin',
            'pm_publist_searchStrings',
            '1'
        );
        //Extra Search Strings
        add_settings_section(
            'pm_publist_extra_searchStrings',
            'Extra Search Strings',
            array($this, 'pm_publist_extra_searchString_info'),
            'pm_publist-setting-admin'
        );
        add_settings_field(
            'searchString2',
            'Search String 2',
            array($this, 'pm_publist_create_searchString_field'),
            'pm_publist-setting-admin',
            'pm_publist_extra_searchStrings',
            '2'
        );
        add_settings_field(
            'searchString3',
            'Search String 3',
            array($this, 'pm_publist_create_searchString_field'),
            'pm_publist-setting-admin',
            'pm_publist_extra_searchStrings',
            '3'
        );
        add_settings_field(
            'searchString4',
            'Search String 4',
            array($this, 'pm_publist_create_searchString_field'),
            'pm_publist-setting-admin',
            'pm_publist_extra_searchStrings',
            '4'
        );
        add_settings_field(
            'searchString5',
            'Search String 5',
            array($this, 'pm_publist_create_searchString_field'),
            'pm_publist-setting-admin',
            'pm_publist_extra_searchStrings',
            '5'
        );
        add_settings_field(
            'searchString6',
            'Search String 6',
            array($this, 'pm_publist_create_searchString_field'),
            'pm_publist-setting-admin',
            'pm_publist_extra_searchStrings',
            '6'
        );
        //Layout Info Boxes
        add_settings_section(
            'pm_publist_layoutInfo',
            'Using the Shortcode',
            array($this, 'pm_publist_use_info'),
            'pm_publist-setting-admin'
        );

    }

    public function pm_publist_check_searchStrings($input){
        // Loop through each input and clense
        foreach($input as $key => $val) {
            if(isset($key)) {
                $val = trim(wp_kses($val, ''));
            } // end if
        } // end foreach
        if(get_option('pm_publist_settings') === FALSE){
            add_option('pm_publist_settings', $input);
        }else{
            update_option('pm_publist_settings', $input);
        }
        return $input;
    }

    public function pm_publist_searchString_info(){
        print 'Please supply the seach string to retrive your publications from PubMed.<br/>Go to <a href="http://www.ncbi.nlm.nih.gov/pubmed/">http://www.ncbi.nlm.nih.gov/pubmed/</a> and perform your search in the search box. The results page URL will look like <code>http://www.ncbi.nlm.nih.gov/pubmed/?term=test</code> - copy everything after <code>?term=</code> and paste it into the box below:';
    }
    public function pm_publist_extra_searchString_info(){
        print 'PubMed searches have a maximum length. If your search exceeds this you can split it into multiple searches, and add them here. The results will all be merged, duplications removed, and sorted by time.';
    }
    public function pm_publist_create_searchString_field($int){
        $options = get_option('pm_publist_settings');
        ?><textarea style="width:100%;" rows="5" id="searchString<?php echo $int;?>" name="array_key[searchString<?php echo $int;?>]"><?php echo $options['searchString'.$int];?></textarea><?php
    }
    public function pm_publist_use_info(){
        print 'This plugin registers the shortcode <code>[recentpublications]</code>
        It accepts the following optional arguments:
        <ul>
            <li><code>show</code> - the number of publications to list in the first instance. The default is <code>5</code>.</li>
            <li><code>extra</code> - the number of papers (if any) to show in a second <code>&lt;ul&gt;</code> that can be shown/hidden with javascript, and styled independently.</li>
            <li><code>class</code> - use this to add any CSS classes you want to the <code>&lt;ul&gt;</code>. By default the class <code>pm_publist</code> is added to the first <code>&lt;ul&gt;</code> and the class <code>pm_publist_more</code> is added to the second.</li>
            </li><code>layout</code> - there are two layouts to choose from. Specify <code>alt</code> to use the alternative. They are illustrated below:</li>
        </ul>
        <br/>
        <h4>Examples of the layout options:</h4>
        <div style="float:left; margin-right:1em;"><h5 style="margin:0;">Default</h5>
        <div style="background:#f6f6f6;padding:1em;"><b>Surname, Initials., Surname, Initials., Surname, Initials.</b> (Day Month Year)<br><a href="#" target="_blank">Title of the paper</a><br><i>Journal</i> <b>Volume</b>(Issue): Pages</div></div>
        <div style="float:left;"><h5 style="margin:0;">Alternative</h5>
        <div style="background:#f6f6f6;padding:1em;"><a href="#" target="_blank">Title of the paper</a><br>Surname, Initials., Surname, Initials., Surname, Initials.<br><b>Journal</b> <b>Volume</b>(Issue): Pages.  <span class="small">(Day Month Year)</span></div></div><br style="clear:both;" />';
    }
}

$wctest = new wctest();

?>
