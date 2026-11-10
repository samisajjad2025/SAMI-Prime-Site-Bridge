<?php
/* Minimal static smoke test; NOT a live WordPress integration test. */
define( 'ABSPATH', '/tmp' );
function add_action() {}
function add_filter() {}
function register_activation_hook() {}
function wp_strip_all_tags( $t ) { return strip_tags( $t ); }
function wp_html_excerpt( $t, $n, $more = '' ) { return strlen( $t ) > $n ? substr( $t, 0, $n ) . $more : $t; }
require dirname( __DIR__ ) . '/sami-prime-site-bridge/sami-prime-site-bridge.php';
function call_private( $name, $arguments ) {
    $m = new ReflectionMethod( 'SPSB_Site_Bridge', $name );
    $m->setAccessible( true );
    return $m->invokeArgs( null, $arguments );
}
$nested = array( array( 'id' => 'row1', 'elType' => 'container', 'elements' => array(
    array( 'id' => 'abcdef12', 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => array( 'editor' => '<table><tr><td>April 2026</td></tr></table>' ), 'elements' => array() ),
    array( 'id' => '12345678', 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => array( 'title' => 'Monthly complaints' ), 'elements' => array() ),
    array( 'id' => 'aaabbaab', 'elType' => 'widget', 'widgetType' => 'image', 'settings' => array( 'url' => 'x' ), 'elements' => array() ),
) ) );
$items = array();
call_private( 'widget_values', array( $nested, &$items ) );
if ( count( $items ) !== 2 || $items[0]['type'] !== 'text-editor' || $items[1]['type'] !== 'heading' ) { throw new Exception( 'Supported widget filtering failed' ); }
$old = call_private( 'widget_content', array( $nested, 'abcdef12' ) );
if ( $old['type'] !== 'text-editor' || strpos( $old['value'], 'April 2026' ) === false ) { throw new Exception( 'Widget lookup failed' ); }
$changed = call_private( 'replace_widget', array( &$nested, 'abcdef12', '<table><tr><td>October 2026</td></tr></table>' ) );
if ( ! $changed || strpos( call_private( 'widget_content', array( $nested, 'abcdef12' ) )['value'], 'October 2026' ) === false ) { throw new Exception( 'Text Editor replace failed' ); }
if ( call_private( 'widget_content', array( $nested, '12345678' ) )['value'] !== 'Monthly complaints' ) { throw new Exception( 'Neighbor widget changed unexpectedly' ); }
if ( call_private( 'replace_widget', array( &$nested, 'aaabbaab', 'xyz' ) ) ) { throw new Exception( 'Unsupported image widget was changed' ); }
echo "PASS: Elementor nested widget filtering, read and edit preservation.\n";
