<?php
/**
 * Plugin Name: SAMI Prime Research — Membership Funnel
 * Description: A multilingual research membership funnel with consent-based leads, independent Cosmofeed channels and free follow links.
 * Version: 1.6.5
 * Author: Sami Prime Research
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * License: Proprietary
 */
if (!defined('ABSPATH')) { exit; }

define('SPR_FUNNEL_VERSION', '1.6.5');

/** Defaults include the two user-supplied public Cosmofeed channel URLs. */
function spr_funnel_defaults() {
    return array(
        'logo_url' => '',
        'portrait_url' => '',
        'combo_fee' => '',
        'combo_validity' => '',
        'starter_validity' => '',
        'whatsapp' => '919953262896',
        'meta_pixel_id' => '',
        'ga4_id' => '',
        'approved_for_ads' => '0',
        'owner_approved' => '0',
        'feedback_approved' => '0',
        'feedback_approval_ref' => '',
    );
}
function spr_funnel_options() {
    return wp_parse_args(get_option('spr_funnel_settings', array()), spr_funnel_defaults());
}
function spr_funnel_activate() {
    spr_funnel_register_lead_post_type();
    if (get_page_by_path('trade-with-logic')) { return; }
    $post_id = wp_insert_post(array(
       'post_title' => 'Trade with Logic | SAMI PRIME RESEARCH',
       'post_name' => 'trade-with-logic',
       'post_status' => 'draft',
       'post_type' => 'page',
       'post_content' => '[sami_prime_funnel]',
       'comment_status' => 'closed',
       'ping_status' => 'closed',
    ), true);
    if (!is_wp_error($post_id) && $post_id) {
        // Elementor Canvas removes unrelated site navigation on this dedicated sales page.
        // If unavailable, WordPress falls back to the normal theme.
        if (defined('ELEMENTOR_VERSION')) { update_post_meta($post_id, '_wp_page_template', 'elementor_canvas'); }
        update_post_meta($post_id, '_spr_funnel_draft', '1');
    }
}
register_activation_hook(__FILE__, 'spr_funnel_activate');

/** Lead records are private WordPress records, not public content. */
function spr_funnel_register_lead_post_type() {
    register_post_type('spr_funnel_lead', array(
        'labels' => array('name' => 'Sami Funnel Leads', 'singular_name' => 'Sami Funnel Lead'),
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => false,
        'show_in_rest' => false,
        'exclude_from_search' => true,
        'supports' => array('title'),
    ));
}
add_action('init', 'spr_funnel_register_lead_post_type');

function spr_funnel_clean_settings($raw) {
    if (!current_user_can('manage_options')) { return spr_funnel_options(); }
    $raw = is_array($raw) ? $raw : array();
    $out = spr_funnel_defaults();
    foreach (array('logo_url','portrait_url') as $k) {
        $u = isset($raw[$k]) ? esc_url_raw(trim((string)$raw[$k])) : '';
        // Assets must be served over HTTPS. Upload original images to WordPress media.
        $out[$k] = (strpos($u,'https://') === 0) ? $u : '';
    }
    foreach (array('combo_fee','combo_validity','starter_validity','feedback_approval_ref') as $k) {
        $out[$k] = isset($raw[$k]) ? sanitize_text_field($raw[$k]) : '';
    }
    $out['whatsapp'] = isset($raw['whatsapp']) ? preg_replace('/[^0-9]/','', (string)$raw['whatsapp']) : '919953262896';
    $out['meta_pixel_id'] = isset($raw['meta_pixel_id']) ? preg_replace('/[^0-9]/','',(string)$raw['meta_pixel_id']) : '';
    $out['ga4_id'] = isset($raw['ga4_id']) ? strtoupper(preg_replace('/[^A-Za-z0-9-]/','',(string)$raw['ga4_id'])) : '';
    $out['approved_for_ads'] = isset($raw['approved_for_ads']) && (string)$raw['approved_for_ads'] === '1' ? '1' : '0';
    $out['owner_approved'] = isset($raw['owner_approved']) && (string)$raw['owner_approved'] === '1' ? '1' : '0';
    $out['feedback_approved'] = isset($raw['feedback_approved']) && (string)$raw['feedback_approved'] === '1' ? '1' : '0';
    return $out;
}
add_action('admin_init', function() {
    register_setting('spr_funnel_settings_group', 'spr_funnel_settings', array(
        'type' => 'array',
        'sanitize_callback' => 'spr_funnel_clean_settings',
        'default' => spr_funnel_defaults(),
    ));
});

function spr_funnel_admin_menu() {
    add_options_page('Sami Prime Funnel','Sami Prime Funnel','manage_options','spr-funnel','spr_funnel_settings_page');
    add_management_page('Sami Funnel Leads','Sami Funnel Leads','manage_options','spr-funnel-leads','spr_funnel_leads_page');
}
add_action('admin_menu','spr_funnel_admin_menu');
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'settings_page_spr-funnel') { wp_enqueue_media(); }
});

function spr_funnel_settings_page() {
    if (!current_user_can('manage_options')) { wp_die('Forbidden'); }
    $opt = spr_funnel_options();
    $fields = array(
        'logo_url' => 'Unaltered original logo URL (upload via WordPress Media)',
        'portrait_url' => 'Hero portrait PNG — use transparent image from WordPress Media Library (no face edits)',
        'combo_fee' => 'Verified Combo fee (display text)',
        'combo_validity' => 'Verified Combo validity',
        'starter_validity' => '₹799 Starter validity (owner reports one week / 7 days; verify the offer)',
        'whatsapp' => 'WhatsApp number including country code',
        'meta_pixel_id' => 'Meta Pixel ID (optional)',
        'ga4_id' => 'GA4 Measurement ID (optional)',
        'feedback_approval_ref' => 'BSE/RAASB customer-feedback creative approval reference (required before showing publicly)',
    );
    echo '<div class="wrap"><h1>SAMI PRIME RESEARCH — Funnel</h1>';
    echo '<div class="notice notice-warning inline"><p><strong>Internal launch controls (not shown on the landing page):</strong> The page remains draft until you explicitly approve publication; the shortcode is a preview, not a public deployment. Cosmofeed checkout links have been checked by the owner, but successful test payments and automated purchase tracking have NOT been independently verified. The ₹799 starter period is owner-stated as one week (7 days), but its exact activation window and matching Cosmofeed checkout term remain to be independently verified. Also verify prices, 7-day activation dates, privacy/consent settings, all five languages, mobile rendering, lead delivery, and applicable SEBI/BSE advertising approval before publishing or launching ads.</p></div>';
    echo '<p>Combo: <code>https://cosmofeed.com/vig/68d656dd7972d000136fcc0f</code><br>Intraday: <code>https://cosmofeed.com/vig/68d64aef5dfbeb001365f320</code><br>Swing: <code>https://cosmofeed.com/vig/68d647704b66b80013cb6ae2</code><br>Long-term: <code>https://cosmofeed.com/vig/68d64cc430a078001395737b</code></p>';
    echo '<form method="post" action="options.php">';
    settings_fields('spr_funnel_settings_group');
    echo '<table class="form-table"><tbody>';
    foreach ($fields as $key => $label) {
        echo '<tr><th scope="row"><label for="spr_'.esc_attr($key).'">'.esc_html($label).'</label></th><td><input class="regular-text" id="spr_'.esc_attr($key).'" type="text" name="spr_funnel_settings['.esc_attr($key).']" value="'.esc_attr($opt[$key]).'" autocomplete="off">';
        if ($key === 'logo_url' || $key === 'portrait_url') {
            echo ' <button type="button" class="button spr-pick-image" data-input="spr_'.esc_attr($key).'">Select / Upload Approved Image</button>';
        }
        if ($key === 'portrait_url') {
            echo '<p class="description"><strong>Select your approved transparent PNG.</strong> The hero uses this exact Media Library image URL, without AI editing or mask. If blank, the original bundled JPG is used as fallback. Save settings and refresh the draft preview.</p>';
        }
        if ($key === 'starter_validity') {
            echo '<p class="description"><strong>Owner-stated ₹799 term:</strong> 1 week (7 days). The landing page shows this without inventing the activation start date. Confirm the exact start/end, taxes and plan match on Cosmofeed before advertising. This text input may be used to override the stated duration once verified.</p>';
        }
        echo '</td></tr>';
    }
    echo '<tr><th scope="row">Enable ad analytics after approvals</th><td>';
    echo '<label><input type="checkbox" name="spr_funnel_settings[approved_for_ads]" value="1" '.checked($opt['approved_for_ads'],'1',false).'> Only after privacy consent flow and applicable SEBI/BSE compliance are approved; this does NOT itself publish anything.</label>';
    echo '</td></tr>';
    echo '<tr><th scope="row">Customer feedback on the landing page</th><td><label>';
    echo '<input type="checkbox" name="spr_funnel_settings[feedback_approved]" value="1" '.checked($opt['feedback_approved'],'1',false).'> Display three sourced, carefully abbreviated Google-review excerpts on the public funnel ONLY after specific applicable BSE/RAASB approval. Requires the approval reference above; the separate owner publication permission is unchanged. Automatically disabled on 7 April 2027 under the announced common advertisement code unless regulations change.';
    echo '</label><p class="description">In private logged-in WordPress preview, three draft feedback cards are visible for design inspection. Never present the remaining profit/return-related review excerpts as promotional proof or as PaRRVA-verified results.</p></td></tr>';
    echo '<tr><th scope="row">Owner publication approval</th><td><label>';
    echo '<input type="checkbox" name="spr_funnel_settings[owner_approved]" value="1" '.checked($opt['owner_approved'],'1',false).'> I, the website owner, have reviewed the complete final preview and explicitly approve publication of this landing page. <strong>Leave unchecked until I approve.</strong>';
    echo '</label></td></tr></tbody></table>';
    submit_button('Save funnel settings');
    echo '</form><p>WP Draft: Pages → All Pages → <strong>Trade with Logic</strong>. Leads: Tools → Sami Funnel Leads. No purchases are recorded by this plugin without separately verified Cosmofeed payment notifications.</p>';
    echo '<script>(function(){document.querySelectorAll(".spr-pick-image").forEach(function(b){b.addEventListener("click",function(){if(!window.wp||!wp.media)return;var selector=wp.media({title:"Select the original unchanged image",button:{text:"Use original image"},multiple:false,library:{type:"image"}});selector.on("select",function(){var a=selector.state().get("selection").first().toJSON();document.getElementById(b.dataset.input).value=a.url;});selector.open();});});})();</script>';
    echo '</div>';
}
function spr_funnel_leads_page() {
    if (!current_user_can('manage_options')) { wp_die('Forbidden'); }
    $items = get_posts(array('post_type'=>'spr_funnel_lead','post_status'=>'private','numberposts'=>100,'orderby'=>'date','order'=>'DESC'));
    echo '<div class="wrap"><h1>Sami Funnel Leads</h1><p>Submitted with affirmative contact consent. Private admin records. Marketing opt-in is separate.</p>';
    echo '<table class="widefat striped"><thead><tr><th>Date</th><th>Name</th><th>Phone</th><th>Email</th><th>Interest</th><th>Request</th><th>Preferred time</th><th>Marketing opt-in</th><th>Source</th><th>Medium</th><th>Campaign</th><th>Creative</th></tr></thead><tbody>';
    foreach ($items as $p) {
        $fields=array('spr_name','spr_phone','spr_email','spr_plan','spr_type','spr_preferred','spr_marketing','spr_source','spr_medium','spr_campaign','spr_content');
        echo '<tr><td>'.esc_html(get_the_date('Y-m-d H:i',$p)).'</td>';
        foreach ($fields as $k) { echo '<td>'.esc_html(get_post_meta($p->ID,$k,true)).'</td>'; }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

/** Same-origin shortcode iframe: avoids overriding existing Elementor/theme styles. */
function spr_funnel_shortcode() {
    $opt = spr_funnel_options();
    $is_v6 = is_page('stock-market-research-education');
    $page = plugins_url($is_v6 ? 'funnel.html' : 'funnel-v1.4.6.1.html', __FILE__);
    // This promo testimonial requires separate approval and automatically sunsets.
    $feedback_private_preview = current_user_can('manage_options') && is_preview();
    $feedback_public_approved = ($opt['feedback_approved'] === '1')
        && (trim((string)$opt['feedback_approval_ref']) !== '')
        && (gmdate('Y-m-d') < '2027-04-07');
    $feedback_allowed = $feedback_private_preview || $feedback_public_approved;
    $query = array(
       'logo' => $opt['logo_url'],
       // Explicitly selected WordPress Media image takes precedence; original JPG is fallback only.
       'portrait' => !empty($opt['portrait_url']) ? $opt['portrait_url'] : plugins_url('assets/sami-original-photo.jpg', __FILE__),
       'combo_fee' => $opt['combo_fee'],
       'combo_validity' => $opt['combo_validity'],
       'starter_validity' => $opt['starter_validity'],
       'wa' => $opt['whatsapp'],
       'meta_pixel' => $opt['meta_pixel_id'],
       'ga4' => $opt['ga4_id'],
       'approved' => $opt['approved_for_ads'],
       'ajax' => admin_url('admin-ajax.php'),
       'utm_source' => isset($_GET['utm_source']) ? sanitize_text_field(wp_unslash($_GET['utm_source'])) : '',
       'utm_medium' => isset($_GET['utm_medium']) ? sanitize_text_field(wp_unslash($_GET['utm_medium'])) : '',
       'utm_campaign' => isset($_GET['utm_campaign']) ? sanitize_text_field(wp_unslash($_GET['utm_campaign'])) : '',
       'utm_content' => isset($_GET['utm_content']) ? sanitize_text_field(wp_unslash($_GET['utm_content'])) : '',
    );
    $query['version'] = SPR_FUNNEL_VERSION;
    $src = add_query_arg($query, $page);
    $id = 'spr_funnel_iframe_'.wp_rand(1000,9999);
    ob_start(); ?>
    <div class="spr-funnel-shell" style="width:100%;margin:0;padding:0">
      <iframe id="<?php echo esc_attr($id); ?>" title="SAMI PRIME RESEARCH membership landing page"
        src="<?php echo esc_url($src); ?>" style="width:100%;height:1700px;border:0;display:block;background:#091b31"
        referrerpolicy="strict-origin-when-cross-origin" scrolling="no"></iframe>
    </div>
    <?php if ($is_v6) { ?>
      <style>.spr-v6-parent-controls{display:none;position:fixed;bottom:0;left:0;right:0;padding:9px 12px max(9px,env(safe-area-inset-bottom));background:#071a32;z-index:9998;gap:8px;box-shadow:0 -4px 16px #001b3044}.spr-v6-parent-controls a{flex:1;text-align:center;border-radius:8px;padding:11px 6px;color:#102e46;font:bold 13px/1.3 system-ui;text-decoration:none;background:#f0c66c}.spr-v6-parent-controls a:nth-child(2){background:#8fe2b5}.spr-v6-parent-controls a:nth-child(3){flex:0 0 65px;background:#143b53;color:white}@media(max-width:700px){.spr-v6-parent-controls:not([hidden]){display:flex}.spr-funnel-shell{padding-bottom:65px!important}}</style>
      <nav class="spr-v6-parent-controls" aria-label="Research plan shortcuts" hidden>
      <a href="https://cosmofeed.com/vig/68d64aef5dfbeb001365f320" data-v6-parent-plan="starter">₹799 Starter</a><a href="https://cosmofeed.com/vig/68d656dd7972d000136fcc0f" data-v6-parent-plan="combo">Combo Plans</a><a href="#" data-v6-parent-help="1">Connect</a></nav>
    <?php } ?>
    <script>
    (function(){
      const frame=document.getElementById(<?php echo wp_json_encode($id); ?>);
      if(!frame)return;
      const controls=frame.closest('.spr-funnel-shell').nextElementSibling;
      const bar=document.querySelector('.spr-v6-parent-controls');
      if(bar){
        bar.hidden=false;
        bar.addEventListener('click',function(e){const a=e.target.closest('a');if(!a)return;
          try{if(a.dataset.v6ParentHelp){e.preventDefault();frame.contentWindow.postMessage({type:'spr-v6-help'},window.location.origin);return;}
          const plan=a.dataset.v6ParentPlan,link=frame.contentDocument.querySelector('[data-checkout="'+plan+'"]');
          if(link){e.preventDefault();link.click();}}
          catch(err){} // The original href remains a fallback if the child is not accessible.
        });
      }
      const approvedFeedback=<?php echo wp_json_encode($feedback_allowed); ?>;
      const feedbackPayload={
        type:'spr-funnel-feedback-approved',
        cards:[
          // All ratings verified as 5 stars in the owner's original Google-review screenshots.
          // Text is deliberately limited to research/learning/support excerpts, never outcome claims.
          {name:'SYED ASIF AKHTER', rating:5, text:'Good knowledge of Stock market research, Explained the market in very simplest way..', source:'Google Review · original excerpt'},
          {name:'Shaik Arif', rating:5, text:'Personal attention in learning…', source:'Google Review · abbreviated excerpt'},
          {name:'vikas kainth', rating:5, text:'…support for stock trading.', source:'Google Review · abbreviated excerpt'}
        ]
      };
      frame.addEventListener('load',function(){
        if(approvedFeedback){frame.contentWindow.postMessage(feedbackPayload,window.location.origin);}
      });
      window.addEventListener('message',function(e){
        if(e.origin!==window.location.origin||e.source!==frame.contentWindow||!e.data||typeof e.data!=='object')return;
        if(e.data.type==='spr-funnel-ready'&&approvedFeedback){
          frame.contentWindow.postMessage(feedbackPayload,window.location.origin);
        }
        if(e.data.type==='spr-funnel-resize'){
          const h=Number(e.data.height);
          if(Number.isFinite(h)&&h>700&&h<50000)frame.style.height=Math.ceil(h+2)+'px';
        }
        // Track approved, non-PII funnel interactions through Site Kit's GA4 tag.
        // The iframe emits events only after the visitor permits analytics.
        if(e.data.type==='spr-funnel-event'&&e.data.consent==='allow'){
          const allowedEvents=['landing_loaded','language_select','view_memberships',
            'checkout_link_click','lead_form_open','lead_contact','lead_submitted',
            'free_channel_click','faq_open','faq_topic','exit_prompt'];
          const eventName=e.data.event;
          if(typeof eventName!=='string'||!allowedEvents.includes(eventName))return;
          const safe=function(x){return typeof x==='string' ? x.replace(/[^a-z0-9_-]/gi,'').slice(0,60) : '';};
          const fields=['plan','language','method','interest','channel','request_type','section','topic'];
          const params={};
          fields.forEach(function(key){const val=safe(e.data[key]);if(val)params[key]=val;});
          params.event_category='sami_prime_funnel';
          const gaEvent='spr_'+eventName;
          if(typeof window.gtag==='function'){
            window.gtag('event',gaEvent,params);
          } else {
            // GTM fallback: a dataLayer custom event alone is not proof of GA4 receipt.
            window.dataLayer=window.dataLayer||[];
            window.dataLayer.push(Object.assign({event:gaEvent},params));
          }
        }
      });
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('sami_prime_funnel','spr_funnel_shortcode');

/**
 * Mandatory owner sign-off: never publish/schedule this funnel from the WordPress
 * editor, REST endpoint or third-party editor while owner_approved is unchecked.
 * The user explicitly requested a final approval BEFORE publication.
 */
function spr_funnel_block_unapproved_publish($data, $postarr) {
    if (($data['post_type'] ?? '') !== 'page'
        || !in_array(($data['post_status'] ?? ''), array('publish','future'), true)) {
        return $data;
    }
    $post_id = isset($postarr['ID']) ? (int)$postarr['ID'] : 0;
    $is_funnel = ($post_id > 0 && get_post_meta($post_id, '_spr_funnel_draft', true) === '1')
        || (($data['post_name'] ?? '') === 'trade-with-logic');
    if (!$is_funnel) { return $data; }
    $settings = spr_funnel_options();
    if (($settings['owner_approved'] ?? '0') !== '1') {
        $data['post_status'] = 'draft';
        set_transient('spr_funnel_publish_blocked_'.get_current_user_id(), 1, 60);
    }
    return $data;
}
add_filter('wp_insert_post_data', 'spr_funnel_block_unapproved_publish', 10, 2);
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) { return; }
    $key = 'spr_funnel_publish_blocked_'.get_current_user_id();
    if (get_transient($key)) {
        delete_transient($key);
        echo '<div class="notice notice-warning is-dismissible"><p><strong>SAMI Prime Funnel:</strong> Publication blocked. The owner must approve the final preview under Settings &rarr; Sami Prime Funnel before this draft can be published.</p></div>';
    }
});


/** Callback/meeting enquiries: rate limit, honeypot, server validation, explicit consent. */
function spr_funnel_submit_lead() {
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        wp_send_json_error(array('message'=>'Method not allowed'),405);
    }
    if (!empty($_POST['website'])) { wp_send_json_success(array('received'=>true)); }
    $ip = sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    $throttle = 'spr_funnel_rate_'.md5($ip);
    $count = (int)get_transient($throttle);
    if ($count >= 5) { wp_send_json_error(array('message'=>'Too many requests. Please contact us on WhatsApp.'),429); }
    set_transient($throttle, $count+1, 10*MINUTE_IN_SECONDS);
    $post = wp_unslash($_POST);
    $name = isset($post['name']) ? sanitize_text_field($post['name']) : '';
    $phone = isset($post['phone']) ? preg_replace('/[^0-9]/','', (string)$post['phone']) : '';
    $email = isset($post['email']) ? sanitize_email($post['email']) : '';
    $plan = isset($post['plan']) ? sanitize_key($post['plan']) : '';
    $type = isset($post['request_type']) ? sanitize_key($post['request_type']) : '';
    $preferred = isset($post['preferred']) ? sanitize_text_field($post['preferred']) : '';
    // Voluntary contact forms retain ad attribution only for enquiries actually submitted.
    $source = isset($post['utm_source']) ? substr(sanitize_text_field($post['utm_source']),0,150) : '';
    $medium = isset($post['utm_medium']) ? substr(sanitize_text_field($post['utm_medium']),0,150) : '';
    $campaign = isset($post['utm_campaign']) ? substr(sanitize_text_field($post['utm_campaign']),0,150) : '';
    $content = isset($post['utm_content']) ? substr(sanitize_text_field($post['utm_content']),0,150) : '';
    $marketing = !empty($post['marketing_opt_in']) ? 'YES' : 'NO';
    $consent = !empty($post['contact_consent']);
    if (!$consent || strlen($name)<2 || strlen($name)>100 || strlen($phone)<10 || strlen($phone)>15
      || ($email!==''&&!is_email($email)) || !in_array($plan,array('combo','starter','unsure'),true)
      || !in_array($type,array('callback','meeting'),true)) {
       wp_send_json_error(array('message'=>'Please enter valid contact information and agree to be contacted about your enquiry.'),400);
    }
    $id = wp_insert_post(array(
       'post_type'=>'spr_funnel_lead',
       'post_status'=>'private',
       'post_title'=>'Enquiry '.current_time('mysql').' – '.$name,
       'post_content'=>'',
       'comment_status'=>'closed',
       'ping_status'=>'closed'
    ),true);
    if (is_wp_error($id) || !$id) { wp_send_json_error(array('message'=>'Could not save enquiry. Please contact us on WhatsApp.'),500); }
    $values=array(
       'spr_name'=>$name,'spr_phone'=>$phone,'spr_email'=>$email,
       'spr_plan'=>$plan,'spr_type'=>$type,'spr_preferred'=>$preferred,
       'spr_marketing'=>$marketing,'spr_source'=>$source,'spr_medium'=>$medium,'spr_campaign'=>$campaign,'spr_content'=>$content,
       'spr_contact_consent'=>'YES','spr_created_at'=>current_time('mysql')
    );
    foreach ($values as $key=>$val) { update_post_meta($id,$key,$val); }
    // Notification contains no full contact data; view it securely in WordPress.
    wp_mail(get_option('admin_email'),'New SAMI PRIME RESEARCH membership enquiry',
      'A new consented '.$type.' enquiry was saved in WordPress. View Tools > Sami Funnel Leads. Lead ID: '.(int)$id);
    wp_send_json_success(array('message'=>'Your request was recorded. We will contact you about your enquiry.'));
}
add_action('wp_ajax_spr_funnel_submit_lead','spr_funnel_submit_lead');
add_action('wp_ajax_nopriv_spr_funnel_submit_lead','spr_funnel_submit_lead');
