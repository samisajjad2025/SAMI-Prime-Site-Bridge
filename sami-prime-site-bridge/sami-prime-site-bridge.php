<?php
/**
 * Plugin Name: SAMI Prime Site Bridge
 * Plugin URI: https://samiprimeresearch.in/
 * Description: Private, approval-gated content proposals for Elementor pages. No remote publishing, code execution, or filesystem API.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: SAMI Prime Research
 * License: GPL-2.0-or-later
 * Update URI: https://github.com/samisajjad2025/SAMI-Prime-Site-Bridge
 * Text Domain: sami-prime-site-bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class SPSB_Site_Bridge {
    const VERSION = '0.1.0';
    const NS = 'sami-bridge/v1';
    const OPT_ENABLED = 'spsb_enabled';
    const OPT_PROPOSALS = 'spsb_proposals';
    const OPT_AUDIT = 'spsb_audit';
    const REPO = 'samisajjad2025/SAMI-Prime-Site-Bridge';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'rest_routes' ) );
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
        add_action( 'admin_post_spsb_settings', array( __CLASS__, 'save_settings' ) );
        add_action( 'admin_post_spsb_review', array( __CLASS__, 'review' ) );
        add_action( 'admin_post_spsb_rollback', array( __CLASS__, 'rollback' ) );
        add_filter( 'update_plugins_github.com', array( __CLASS__, 'github_update' ), 10, 4 );
    }

    public static function activate() {
        add_role( 'spsb_agent', 'SAMI Site Bridge Agent', array( 'read' => true, 'spsb_propose' => true ) );
        add_option( self::OPT_ENABLED, '0', '', false );
        add_option( self::OPT_PROPOSALS, array(), '', false );
        add_option( self::OPT_AUDIT, array(), '', false );
    }

    private static function can_read() {
        return ( '1' === get_option( self::OPT_ENABLED, '0' ) ) && ( current_user_can( 'spsb_propose' ) || current_user_can( 'manage_options' ) );
    }

    private static function permission() {
        return self::can_read() ? true : new WP_Error( 'spsb_forbidden', 'Bridge disabled or authenticated user lacks bridge capability.', array( 'status' => 403 ) );
    }

    public static function rest_routes() {
        $get = array( 'methods' => WP_REST_Server::READABLE, 'permission_callback' => array( __CLASS__, 'permission' ) );
        register_rest_route( self::NS, '/health', array_merge( $get, array( 'callback' => array( __CLASS__, 'rest_health' ) ) ) );
        register_rest_route( self::NS, '/pages', array_merge( $get, array( 'callback' => array( __CLASS__, 'rest_pages' ) ) ) );
        register_rest_route( self::NS, '/pages/(?P<id>\d+)/widgets', array_merge( $get, array( 'callback' => array( __CLASS__, 'rest_widgets' ) ) ) );
        register_rest_route( self::NS, '/proposals', array(
            array( 'methods' => WP_REST_Server::READABLE, 'permission_callback' => array( __CLASS__, 'permission' ), 'callback' => array( __CLASS__, 'rest_proposals' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'permission_callback' => array( __CLASS__, 'permission' ), 'callback' => array( __CLASS__, 'rest_propose' ) ),
        ) );
    }

    public static function rest_health() {
        return array( 'ok' => true, 'version' => self::VERSION, 'site' => home_url(), 'elementor_active' => class_exists( '\Elementor\Plugin' ), 'approval_required' => true, 'remote_publish' => false );
    }

    public static function rest_pages( $request ) {
        $search = sanitize_text_field( (string) $request->get_param( 'search' ) );
        $pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => 50, 's' => $search, 'orderby' => 'title', 'order' => 'ASC', 'suppress_filters' => false ) );
        return array_map( function ( $page ) {
            return array( 'id' => (int) $page->ID, 'title' => get_the_title( $page->ID ), 'url' => get_permalink( $page->ID ), 'elementor' => (bool) get_post_meta( $page->ID, '_elementor_edit_mode', true ) );
        }, $pages );
    }

    private static function load_page( $id ) {
        $page = get_post( absint( $id ) );
        if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
            return new WP_Error( 'spsb_page', 'Only existing published pages are supported.', array( 'status' => 404 ) );
        }
        $json = get_post_meta( $page->ID, '_elementor_data', true );
        $elements = is_string( $json ) ? json_decode( $json, true ) : null;
        if ( ! is_array( $elements ) ) {
            return new WP_Error( 'spsb_not_elementor', 'Page does not contain readable Elementor JSON.', array( 'status' => 422 ) );
        }
        return array( 'page' => $page, 'raw' => $json, 'elements' => $elements );
    }

    private static function widget_values( $items, &$output ) {
        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            $type = isset( $item['widgetType'] ) ? $item['widgetType'] : '';
            $field = 'text-editor' === $type ? 'editor' : ( 'heading' === $type ? 'title' : '' );
            if ( 'widget' === ( isset( $item['elType'] ) ? $item['elType'] : '' ) && $field && isset( $item['id'], $item['settings'][ $field ] ) && is_string( $item['settings'][ $field ] ) ) {
                $value = $item['settings'][ $field ];
                $output[] = array( 'widget_id' => $item['id'], 'type' => $type, 'hash' => hash( 'sha256', $value ), 'preview' => wp_html_excerpt( wp_strip_all_tags( $value ), 240, '…' ) );
            }
            if ( isset( $item['elements'] ) && is_array( $item['elements'] ) ) {
                self::widget_values( $item['elements'], $output );
            }
        }
    }

    private static function widget_content( $items, $id ) {
        foreach ( $items as $item ) {
            if ( ! is_array( $item ) ) { continue; }
            $type = isset( $item['widgetType'] ) ? $item['widgetType'] : '';
            $field = 'text-editor' === $type ? 'editor' : ( 'heading' === $type ? 'title' : '' );
            if ( 'widget' === ( isset( $item['elType'] ) ? $item['elType'] : '' ) && $field && isset( $item['id'], $item['settings'][ $field ] ) && (string) $item['id'] === $id && is_string( $item['settings'][ $field ] ) ) {
                return array( 'type' => $type, 'value' => $item['settings'][ $field ] );
            }
            if ( isset( $item['elements'] ) && is_array( $item['elements'] ) ) {
                $result = self::widget_content( $item['elements'], $id );
                if ( null !== $result ) { return $result; }
            }
        }
        return null;
    }

    private static function replace_widget( &$items, $id, $content ) {
        foreach ( $items as &$item ) {
            if ( ! is_array( $item ) ) { continue; }
            $type = isset( $item['widgetType'] ) ? $item['widgetType'] : '';
            $field = 'text-editor' === $type ? 'editor' : ( 'heading' === $type ? 'title' : '' );
            if ( 'widget' === ( isset( $item['elType'] ) ? $item['elType'] : '' ) && $field && isset( $item['id'], $item['settings'][ $field ] ) && (string) $item['id'] === $id && is_string( $item['settings'][ $field ] ) ) {
                $item['settings'][ $field ] = $content;
                return true;
            }
            if ( isset( $item['elements'] ) && is_array( $item['elements'] ) && self::replace_widget( $item['elements'], $id, $content ) ) {
                return true;
            }
        }
        return false;
    }

    public static function rest_widgets( $request ) {
        $data = self::load_page( $request['id'] );
        if ( is_wp_error( $data ) ) { return $data; }
        $out = array();
        self::widget_values( $data['elements'], $out );
        return array( 'page_id' => (int) $data['page']->ID, 'widgets' => $out );
    }

    public static function rest_proposals() {
        $all = get_option( self::OPT_PROPOSALS, array() );
        $result = array();
        foreach ( $all as $proposal ) {
            $result[] = array( 'id' => $proposal['id'], 'page_id' => $proposal['page_id'], 'widget_id' => $proposal['widget_id'], 'status' => $proposal['status'], 'created_at' => $proposal['created_at'] );
        }
        return array_values( array_reverse( $result ) );
    }

    public static function rest_propose( $request ) {
        $page_id = absint( $request->get_param( 'page_id' ) );
        $widget_id = sanitize_key( (string) $request->get_param( 'widget_id' ) );
        $expected = strtolower( trim( (string) $request->get_param( 'expected_hash' ) ) );
        $content = $request->get_param( 'content' );
        if ( ! $page_id || ! preg_match( '/^[a-z0-9_-]{3,64}$/', $widget_id ) || ! preg_match( '/^[a-f0-9]{64}$/', $expected ) || ! is_string( $content ) || strlen( $content ) > 50000 ) {
            return new WP_Error( 'spsb_input', 'Invalid page, widget ID, hash or content (max 50 KB).', array( 'status' => 400 ) );
        }
        $data = self::load_page( $page_id );
        if ( is_wp_error( $data ) ) { return $data; }
        $widget = self::widget_content( $data['elements'], $widget_id );
        if ( ! $widget ) { return new WP_Error( 'spsb_widget', 'Supported Text Editor or Heading widget not found.', array( 'status' => 404 ) ); }
        if ( ! hash_equals( hash( 'sha256', $widget['value'] ), $expected ) ) {
            return new WP_Error( 'spsb_conflict', 'Widget content changed. Refresh the widget hash before proposing.', array( 'status' => 409 ) );
        }
        $clean = 'heading' === $widget['type'] ? sanitize_text_field( $content ) : wp_kses_post( $content );
        if ( $clean === $widget['value'] ) { return new WP_Error( 'spsb_no_change', 'Proposed content is already current.', array( 'status' => 400 ) ); }
        $proposals = get_option( self::OPT_PROPOSALS, array() );
        if ( count( array_filter( $proposals, function ( $p ) { return 'pending' === $p['status']; } ) ) >= 25 ) {
            return new WP_Error( 'spsb_limit', 'Too many pending proposals. Review existing proposals first.', array( 'status' => 429 ) );
        }
        $id = wp_generate_uuid4();
        $proposals[ $id ] = array(
            'id' => $id, 'page_id' => $page_id, 'widget_id' => $widget_id, 'type' => $widget['type'],
            'old_hash' => $expected, 'new_content' => $clean, 'created_at' => current_time( 'mysql' ),
            'proposed_by' => get_current_user_id(), 'status' => 'pending',
        );
        update_option( self::OPT_PROPOSALS, $proposals, false );
        self::audit( 'proposed', $page_id, $widget_id, $id );
        return new WP_REST_Response( array( 'id' => $id, 'status' => 'pending', 'content_sanitized' => $clean !== $content, 'requires_wp_admin_approval' => true ), 201 );
    }

    private static function audit( $action, $page_id, $widget_id, $proposal_id ) {
        $events = get_option( self::OPT_AUDIT, array() );
        $events[] = array( 'time' => current_time( 'mysql' ), 'action' => $action, 'page_id' => (int) $page_id, 'widget_id' => $widget_id, 'proposal_id' => $proposal_id, 'user_id' => get_current_user_id() );
        update_option( self::OPT_AUDIT, array_slice( $events, -100 ), false );
    }

    public static function admin_menu() {
        add_management_page( 'SAMI Site Bridge', 'SAMI Site Bridge', 'manage_options', 'sami-site-bridge', array( __CLASS__, 'render_admin' ) );
    }

    private static function admin_url() {
        return admin_url( 'tools.php?page=sami-site-bridge' );
    }

    public static function render_admin() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.' ); }
        $enabled = '1' === get_option( self::OPT_ENABLED, '0' );
        $proposals = get_option( self::OPT_PROPOSALS, array() );
        $audits = array_reverse( get_option( self::OPT_AUDIT, array() ) );
        ?>
        <div class="wrap"><h1>SAMI Prime Site Bridge</h1>
        <p><strong>Safe mode:</strong> Remote clients may only read published page widget information and submit proposals. Only a logged-in WordPress administrator may approve, reject or roll back. No remote filesystem, plugin-management, or publishing operation is exposed.</p>
        <?php if ( isset( $_GET['spsb_notice'] ) ) : ?><div class="notice notice-info"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['spsb_notice'] ) ) ); ?></p></div><?php endif; ?>
        <h2>Connection switch</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'spsb_settings' ); ?><input type="hidden" name="action" value="spsb_settings" />
        <label><input type="checkbox" name="enabled" value="1" <?php checked( $enabled ); ?> /> Enable authenticated Site Bridge REST endpoints</label>
        <?php submit_button( 'Save setting' ); ?></form>
        <p>Create a dedicated WordPress user with the role <strong>SAMI Site Bridge Agent</strong>, then generate a WordPress <em>Application Password</em> for that user. Never send either password into a chat or GitHub. The integration tool must store the Application Password securely.</p>
        <p>REST root: <code><?php echo esc_html( rest_url( self::NS ) ); ?></code></p>
        <h2>Pending and recent proposals</h2>
        <table class="widefat striped"><thead><tr><th>Page</th><th>Widget</th><th>Proposed content (safe preview)</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ( array_reverse( $proposals ) as $p ) : ?>
            <tr><td><?php echo esc_html( get_the_title( $p['page_id'] ) ); ?> (#<?php echo (int) $p['page_id']; ?>)</td>
            <td><code><?php echo esc_html( $p['widget_id'] ); ?></code> (<?php echo esc_html( $p['type'] ); ?>)</td>
            <td><textarea readonly rows="5" cols="58" style="max-width:100%;"><?php echo esc_textarea( $p['new_content'] ); ?></textarea></td>
            <td><?php echo esc_html( $p['status'] ); ?></td><td>
            <?php if ( 'pending' === $p['status'] ) : ?>
                <?php foreach ( array( 'approve' => 'Approve and apply LIVE', 'reject' => 'Reject' ) as $decision => $title ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;">
                <?php wp_nonce_field( 'spsb_review_' . $p['id'] ); ?>
                <input type="hidden" name="action" value="spsb_review"/><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $p['id'] ); ?>"/>
                <input type="hidden" name="decision" value="<?php echo esc_attr( $decision ); ?>"/>
                <button type="submit" class="button <?php echo 'approve' === $decision ? 'button-primary' : ''; ?>" <?php if ( 'approve' === $decision ) : ?>onclick="return confirm('Apply this change to the LIVE website? Confirm complaint figures and page backup first.');"<?php endif; ?>><?php echo esc_html( $title ); ?></button>
                </form><?php endforeach; ?>
            <?php elseif ( 'approved' === $p['status'] && ! empty( $p['backup_option'] ) ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'spsb_rollback_' . $p['id'] ); ?>
                <input type="hidden" name="action" value="spsb_rollback"/><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $p['id'] ); ?>"/>
                <button class="button" type="submit" onclick="return confirm('Restore the exact Elementor page data saved before this approval? This only works if the page has not changed since.');">Rollback if unchanged</button></form>
            <?php endif; ?>
            </td></tr>
        <?php endforeach; ?>
        <?php if ( ! $proposals ) : ?><tr><td colspan="5">No proposals yet.</td></tr><?php endif; ?>
        </tbody></table>
        <h2>Recent audit events</h2><table class="widefat striped"><thead><tr><th>Time</th><th>Action</th><th>Page</th><th>Widget</th><th>WordPress user ID</th></tr></thead><tbody>
        <?php foreach ( array_slice( $audits, 0, 20 ) as $event ) : ?><tr><td><?php echo esc_html( $event['time'] ); ?></td><td><?php echo esc_html( $event['action'] ); ?></td><td><?php echo (int) $event['page_id']; ?></td><td><?php echo esc_html( $event['widget_id'] ); ?></td><td><?php echo (int) $event['user_id']; ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <?php
    }

    public static function save_settings() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.' ); }
        check_admin_referer( 'spsb_settings' );
        update_option( self::OPT_ENABLED, isset( $_POST['enabled'] ) ? '1' : '0', false );
        wp_safe_redirect( add_query_arg( 'spsb_notice', 'Connection setting saved.', self::admin_url() ) );
        exit;
    }

    private static function clear_elementor_cache() {
        if ( class_exists( '\Elementor\Plugin' ) ) {
            $plugin = \Elementor\Plugin::instance();
            if ( isset( $plugin->files_manager ) && method_exists( $plugin->files_manager, 'clear_cache' ) ) {
                $plugin->files_manager->clear_cache();
            }
        }
    }

    private static function write_elementor( $page_id, $elements ) {
        $encoded = wp_json_encode( $elements );
        if ( ! is_string( $encoded ) || ! $encoded ) { return false; }
        $result = update_post_meta( $page_id, '_elementor_data', wp_slash( $encoded ) );
        if ( ! $result ) { return false; }
        clean_post_cache( $page_id );
        self::clear_elementor_cache();
        return true;
    }

    public static function review() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.' ); }
        $id = isset( $_POST['proposal_id'] ) ? sanitize_text_field( wp_unslash( $_POST['proposal_id'] ) ) : '';
        check_admin_referer( 'spsb_review_' . $id );
        $decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
        if ( ! in_array( $decision, array( 'approve', 'reject' ), true ) ) { wp_die( 'Invalid decision.' ); }
        $proposals = get_option( self::OPT_PROPOSALS, array() );
        if ( ! isset( $proposals[ $id ] ) || 'pending' !== $proposals[ $id ]['status'] ) { wp_die( 'Proposal is missing or no longer pending.' ); }
        $p = $proposals[ $id ];
        if ( 'reject' === $decision ) {
            $proposals[ $id ]['status'] = 'rejected';
            update_option( self::OPT_PROPOSALS, $proposals, false );
            self::audit( 'rejected', $p['page_id'], $p['widget_id'], $id );
            wp_safe_redirect( add_query_arg( 'spsb_notice', 'Proposal rejected.', self::admin_url() ) ); exit;
        }
        $data = self::load_page( $p['page_id'] );
        if ( is_wp_error( $data ) ) { wp_die( esc_html( $data->get_error_message() ) ); }
        $widget = self::widget_content( $data['elements'], $p['widget_id'] );
        if ( ! $widget || ! hash_equals( $p['old_hash'], hash( 'sha256', $widget['value'] ) ) || $widget['type'] !== $p['type'] ) {
            wp_die( 'Live page has changed or widget type differs. Reject this proposal and request a fresh one; no changes were made.' );
        }
        $before = $data['raw'];
        $after = $data['elements'];
        if ( ! self::replace_widget( $after, $p['widget_id'], $p['new_content'] ) ) { wp_die( 'Cannot locate widget.' ); }
        $backup_option = 'spsb_backup_' . str_replace( '-', '', $id );
        if ( ! add_option( $backup_option, $before, '', false ) ) { wp_die( 'Backup could not be stored. No changes made.' ); }
        if ( ! self::write_elementor( $p['page_id'], $after ) ) {
            delete_option( $backup_option );
            wp_die( 'Page update failed. Inspect page before retrying.' );
        }
        $raw_after = get_post_meta( $p['page_id'], '_elementor_data', true );
        $proposals[ $id ]['status'] = 'approved';
        $proposals[ $id ]['backup_option'] = $backup_option;
        $proposals[ $id ]['approved_after_hash'] = hash( 'sha256', $raw_after );
        $proposals[ $id ]['reviewed_at'] = current_time( 'mysql' );
        update_option( self::OPT_PROPOSALS, $proposals, false );
        self::audit( 'approved_live', $p['page_id'], $p['widget_id'], $id );
        wp_safe_redirect( add_query_arg( 'spsb_notice', 'Approved proposal applied to live Elementor page. Please inspect published page.', self::admin_url() ) ); exit;
    }

    public static function rollback() {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Access denied.' ); }
        $id = isset( $_POST['proposal_id'] ) ? sanitize_text_field( wp_unslash( $_POST['proposal_id'] ) ) : '';
        check_admin_referer( 'spsb_rollback_' . $id );
        $all = get_option( self::OPT_PROPOSALS, array() );
        if ( ! isset( $all[ $id ] ) || 'approved' !== $all[ $id ]['status'] || empty( $all[ $id ]['backup_option'] ) ) { wp_die( 'Cannot roll back this proposal.' ); }
        $p = $all[ $id ];
        $raw = get_post_meta( $p['page_id'], '_elementor_data', true );
        if ( ! hash_equals( $p['approved_after_hash'], hash( 'sha256', $raw ) ) ) {
            wp_die( 'The page has changed since approval. Automatic rollback stopped to avoid overwriting newer edits.' );
        }
        $backup = get_option( $p['backup_option'], null );
        if ( ! is_string( $backup ) || ! is_array( json_decode( $backup, true ) ) ) { wp_die( 'Backup is missing or invalid.' ); }
        if ( ! update_post_meta( $p['page_id'], '_elementor_data', wp_slash( $backup ) ) ) { wp_die( 'Rollback write failed.' ); }
        clean_post_cache( $p['page_id'] );
        self::clear_elementor_cache();
        $all[ $id ]['status'] = 'rolled_back';
        update_option( self::OPT_PROPOSALS, $all, false );
        self::audit( 'rolled_back', $p['page_id'], $p['widget_id'], $id );
        wp_safe_redirect( add_query_arg( 'spsb_notice', 'Prior Elementor page state restored. Check live page.', self::admin_url() ) ); exit;
    }

    /** GitHub public release updater. A repository and tagged release must exist first. */
    public static function github_update( $update, $plugin_data, $plugin_file, $locales ) {
        if ( plugin_basename( __FILE__ ) !== $plugin_file ) { return $update; }
        $cached = get_transient( 'spsb_github_release_v1' );
        if ( false === $cached ) {
            $resp = wp_remote_get( 'https://api.github.com/repos/' . self::REPO . '/releases/latest', array(
                'timeout' => 7, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'SAMI-Prime-Site-Bridge/' . self::VERSION ),
            ) );
            $cached = 'none';
            if ( ! is_wp_error( $resp ) && 200 === wp_remote_retrieve_response_code( $resp ) ) {
                $release = json_decode( wp_remote_retrieve_body( $resp ), true );
                if ( is_array( $release ) && ! empty( $release['tag_name'] ) && preg_match( '/^v?(\d+\.\d+\.\d+)$/', $release['tag_name'], $match ) && ! empty( $release['assets'] ) ) {
                    foreach ( $release['assets'] as $asset ) {
                        if ( isset( $asset['name'], $asset['browser_download_url'] ) && 'sami-prime-site-bridge.zip' === $asset['name'] && strpos( $asset['browser_download_url'], 'https://github.com/' . self::REPO . '/releases/download/' ) === 0 ) {
                            $cached = array( 'version' => $match[1], 'url' => esc_url_raw( $asset['browser_download_url'] ) );
                            break;
                        }
                    }
                }
            }
            set_transient( 'spsb_github_release_v1', $cached, 6 * HOUR_IN_SECONDS );
        }
        if ( is_array( $cached ) && version_compare( $cached['version'], self::VERSION, '>' ) ) {
            return array(
                'id' => 'https://github.com/' . self::REPO,
                'slug' => 'sami-prime-site-bridge',
                'plugin' => plugin_basename( __FILE__ ),
                'version' => $cached['version'],
                'url' => 'https://github.com/' . self::REPO,
                'package' => $cached['url'],
                'requires_php' => '7.4',
            );
        }
        return false;
    }
}

register_activation_hook( __FILE__, array( 'SPSB_Site_Bridge', 'activate' ) );
SPSB_Site_Bridge::init();
