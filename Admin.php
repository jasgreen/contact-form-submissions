<?php
class WPCF7SAdmin {
    
    function __construct() {
        add_action('admin_menu', array($this, 'menu') );

        add_filter('manage_wpcf7s_posts_columns', array($this, 'set_columns'), 999 );
        add_action('manage_wpcf7s_posts_custom_column' , array($this, 'column'), 10, 2 );
        add_action('restrict_manage_posts',array($this, 'filters'));

        add_action('add_meta_boxes', array($this, 'meta_boxes'), 25 );

        add_action('pre_get_posts', array($this, 'admin_posts') );
        add_action('pre_get_posts', array($this, 'set_post_order') );

        add_filter('page_row_actions',array($this, 'action_row'), 25, 2);
        add_action('admin_enqueue_scripts', array($this, 'scripts') );

        add_filter('views_edit-wpcf7s', array($this, 'views'), 999);

        add_filter('gettext', array($this, 'custom_status'), 20, 3);
    }

    function custom_status($translations, $text, $domain){
        if('Published' === $text){
            $translations = __('Submitted', WPCF7S_TEXT_DOMAIN);
        }
        return $translations;
    }

    function set_post_order($query = false) {
        global $pagenow, $post_type;
        if ('wpcf7s' === $post_type && is_admin() && 'edit.php' == $pagenow && !isset($_GET['orderby'])) {
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
        }
    }

    function views($views){
        if(isset( $views['publish'] ) ){
            $views['publish'] = str_replace( 'Published', __('Submitted', WPCF7S_TEXT_DOMAIN), $views['publish'] );
        }
        $keep_views = array('all', 'publish', 'trash');
        // remove others
        foreach($views as $key => $view){
            if(!in_array($key, $keep_views)){
                unset($views[$key]);
            }
        }

        return $views;
    }

    function filters() {
        //execute only on the 'post' content type
        global $post_type;
        if($post_type == 'wpcf7s'){
            $args = array(
                'post_type'      =>'wpcf7_contact_form',
                'posts_per_page' => '-1'
            );
            $forms = get_posts($args);
            ?>
            <select name="wpcf7_contact_form">
                <option value="0"><?php _e('Contact Form', WPCF7S_TEXT_DOMAIN); ?></option>
                <?php foreach($forms as $post){ ?>
                    <?php $selected = ($post->ID == $_GET['wpcf7_contact_form']) ? 'selected' : ''; ?>
                    <option value="<?php echo $post->ID; ?>" <?php echo $selected; ?>><?php echo $post->post_title; ?></option>
                <?php } ?>
            </select>
            <?php
        }
    }

    function scripts(){
        if('wpcf7s' === get_post_type()){
            wp_enqueue_style('wpcf7s-style',plugins_url('/css/admin.css', WPCF7S_FILE));
        }
    }

    function action_row($actions, $post){
        global $post_type;
        if ('wpcf7s' === $post_type){
            // remove defaults
            unset($actions['edit']);
            unset($actions['inline hide-if-no-js']);

            $actions = array_merge(array('aview' => '<a href="' . get_edit_post_link( $post->ID ) . '">View</a>'), $actions);
        }
        return $actions;
    }

    function admin_posts($query){
        global $post_type;
        if($query->is_admin && 'wpcf7s' === $post_type && $query->is_main_query()){
            $form_id = esc_attr($_GET['wpcf7_contact_form']);
            if(!empty($form_id)){
                $query->set( 'meta_query', array(
                    array(
                        'key'     => 'form_id',
                        'value'    => $form_id,
                        'compare' => '='
                    )
                ));
            }
        }
    }

    function set_columns($columns) {
        $columns = array(
            'cb'            => '<input type="checkbox">',
            'submission'    => __('Submission', WPCF7S_TEXT_DOMAIN),
            'form'          => __('Contact Form', WPCF7S_TEXT_DOMAIN),
            'date'          => __('Date', WPCF7S_TEXT_DOMAIN)
        );

        return $columns;
    }

    function column( $column, $post_id ) {
        $form_id = get_post_meta($post_id, 'form_id', true);
        $post_parent = wp_get_post_parent_id($post_id);
        $nested = ($post_parent > 0) ? '&mdash; ' : '';

        switch ( $column ) {

            case 'form' :
                ?><a href="<?php echo add_query_arg(array('page'=>'wpcf7', 'post'=>$form_id, 'action'=>'edit'), admin_url('admin.php')); ?>"><?php echo get_the_title($form_id); ?></a><?php
                break;
            case 'sent' :
                ?><a href="<?php echo add_query_arg(array('page'=>'wpcf7', 'post'=>$form_id, 'action'=>'edit'), admin_url('admin.php')); ?>"><?php echo get_the_title($form_id); ?></a><?php
                break;
            case 'submission' :
                ?>
                <strong>
                <a class="row-title" href="<?php echo get_edit_post_link($post_id); ?>">
                    <?php echo $nested . htmlspecialchars(get_post_meta($post_id, 'sender', true)); ?>
                </a>
                </strong>
                <?php
                break;
        }
    }

    function meta_boxes(){
        add_meta_box( 'wpcf7s_mail', __('Mail', WPCF7S_TEXT_DOMAIN), array($this, 'mail_meta_box'), 'wpcf7s', 'normal');
        add_meta_box( 'wpcf7s_actions', __('Overview', WPCF7S_TEXT_DOMAIN), array($this, 'actions_meta_box'), 'wpcf7s', 'side');
        remove_meta_box( 'submitdiv', 'wpcf7s', 'side' );
    }

    function mail_meta_box($post){
        $form_id = get_post_meta($post->ID, 'form_id', true);
        $sender = get_post_meta($post->ID, 'sender', true);
        $sender_mailto = preg_replace('/([a-zA-Z0-9_\-\.]*@\\S+\\.\\w+)/', '<a href="mailto:$1">$1</a>', $sender);
        $recipient = get_post_meta($post->ID, 'recipient', true);
        $recipient_mailto = preg_replace('/([a-zA-Z0-9_\-\.]*@\\S+\\.\\w+)/', '<a href="mailto:$1">$1</a>', $recipient);

        $additional_headers = get_post_meta($post->ID, 'additional_headers', true);
        ?>
        <table class="form-table wpcf7s" style="width: 100%;">
            <tbody>
                <tr>
                    <th scope="row">Contact Form</th>
                    <td><a href="<?php echo add_query_arg(array('page'=>'wpcf7', 'post'=>$form_id, 'action'=>'edit'), admin_url('admin.php')); ?>"><?php echo get_the_title($form_id); ?></a></td>
                </tr>
                <tr>
                    <th scope="row">Sender</th>
                    <td><?php echo $sender_mailto; ?></td>
                </tr>
                <tr>
                    <th scope="row">Recipient</th>
                    <td><?php echo $recipient_mailto; ?></td>
                </tr>
                <tr>
                    <th scope="row">Subject</th>
                    <td><?php echo get_post_meta($post->ID, 'subject', true); ?></td>
                </tr>
                <tr>
                    <th scope="row">Body</th>
                    <td><?php echo apply_filters('the_content', $post->post_content); ?></td>
                </tr>
                <?php if(!empty($additional_headers)){ ?>
                    <tr>
                        <th scope="row">Additional Headers</th>
                        <td><?php echo get_post_meta($post->ID, 'additional_headers', true); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <?php
    }
    function actions_meta_box($post){
        $datef = __( 'M j, Y @ H:i' );
        $date = date_i18n( $datef, strtotime( $post->post_date ) );
        ?>
        <div id="minor-publishing">

            <div id="misc-publishing-actions">
                <div class="misc-pub-section curtime misc-pub-curtime">
                    <span id="timestamp"><?php _e('Submitted', WPCF7S_TEXT_DOMAIN); ?> : <b><?php echo $date; ?></b></span>
                </div>
            </div>
            <div class="clear"></div>
        </div>
        <?php
    }
}