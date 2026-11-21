<?php
/**
 * Plugin Name: SAMI Prime Landing CTA Repair
 * Description: Reversible front-end fixes for published SAMI PRIME RESEARCH funnel links.
 * Version: 0.1.3
 * Author: SAMI PRIME RESEARCH
 */
defined('ABSPATH') || exit;

add_action('wp_footer', static function () {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (rtrim((string) $path, '/') !== '/stock-market-research-education') {
        return;
    }
    ?>
<script id="spr-landing-cta-repair">
(function () {
  'use strict';
  // Isolated repair only; does not edit Membership Funnel HTML, pricing or analytics.
  const destinations = Object.freeze({
    combo: 'https://cosmofeed.com/vig/68d656dd7972d000136fcc0f',
    starter: 'https://cosmofeed.com/vig/68d64aef5dfbeb001365f320',
    telegram: 'https://t.me/SAMI_Prime_Research'
  });
  const buttons = Object.freeze({
    combo: '[data-t="heroCombo"], [data-t="sampleComboCTA"], [data-t="compareCTA"], [data-t="mobileCombo"]',
    starter: '[data-t="heroStarter"], [data-t="sampleStarterCTA"], [data-t="compareStarterCTA"], [data-t="mobileStarter"]',
    telegram: '#freeTelegram'
  });

  // Dataset/Pixel ID shown in the owner's Meta Events Manager.
  const PIXEL_ID = '1321259323253046';
  const mounted = new WeakSet();
  const trackingDocs = new WeakSet();
  let pixelStarted = false;
  let pageViewSent = false;

  function analyticsAllowed() {
    try { return window.localStorage.getItem('spr_marketing_consent') === 'allow'; }
    catch (_) { return false; }
  }

  function startPixel() {
    if (!analyticsAllowed()) return false;
    if (!pixelStarted) {
      if (typeof window.fbq !== 'function') {
        !function(f,b,e,v,n,t,s){
          if(f.fbq)return;
          n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};
          if(!f._fbq)f._fbq=n;
          n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];
          t=b.createElement(e);t.async=!0;t.src=v;
          s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s);
        }(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
      }
      try {
        const existing = window.fbq.getState && window.fbq.getState();
        const found = existing && Array.isArray(existing.pixels) &&
          existing.pixels.some(function(p){return String(p.id) === PIXEL_ID;});
        if (!found) window.fbq('init', PIXEL_ID);
        pixelStarted = true;
      } catch (_) { return false; }
    }
    if (!pageViewSent) {
      try { window.fbq('trackSingle', PIXEL_ID, 'PageView'); pageViewSent = true; }
      catch (_) {}
    }
    return true;
  }

  function trackCheckoutIntent(plan) {
    if (!startPixel()) return;
    // Outbound intent ONLY, never claim Cosmofeed payment completion or Purchase.
    try { window.fbq('trackSingle', PIXEL_ID, 'InitiateCheckout',
      {content_category:'research_membership', content_name:plan}); } catch (_) {}
  }

  function wireTracking(doc) {
    if (trackingDocs.has(doc)) return;
    trackingDocs.add(doc);
    const wireAccept = function(ev) {
      const el = ev.target && ev.target.closest && ev.target.closest('#acceptAnalytics');
      if (el) setTimeout(startPixel, 80); // Let existing consent handler save preference.
    };
    doc.addEventListener('click', wireAccept, true);
    // The existing pricing-table checkout buttons are working and stay untouched.
    // Read-only event listener records the explicit click before original navigation.
    doc.addEventListener('click', function(ev) {
      const target = ev.target && (ev.target.nodeType === 3 ? ev.target.parentElement : ev.target);
      if (!target || !target.closest || destinationFor(target)) return;
      const combo = target.closest('#comboBuy, [data-checkout="combo"]');
      const starter = target.closest('#starterBuy, [data-checkout="starter"]');
      const swing = target.closest('[data-checkout="swing"]');
      const longterm = target.closest('[data-checkout="longterm"]');
      const plan = combo ? 'combo' : starter ? 'starter' : swing ? 'swing' : longterm ? 'longterm' : null;
      if (plan) trackCheckoutIntent(plan);
    }, true);
    startPixel();
  }

  const watched = new WeakSet();

  function destinationFor(element) {
    if (!element || !element.closest) return null;
    for (const type of Object.keys(buttons)) {
      if (element.closest(buttons[type])) return destinations[type];
    }
    return null;
  }

  function mount(doc) {
    try {
      if (!doc || !doc.querySelector('#sprApp')) return;
      // The exit-intent overlay uses position:fixed inside a page-height iframe;
      // its dialog can be offscreen while a dark backdrop covers all content.
      // Disable that optional popup only. Keep privacy and language choices intact.
      if (!doc.getElementById('spr-repair-no-exit-shade')) {
        const style = doc.createElement('style');
        style.id = 'spr-repair-no-exit-shade';
        style.textContent = '#sprApp #exitDialog{display:none !important}';
        (doc.head || doc.documentElement).appendChild(style);
      }
      wireTracking(doc);
      if (mounted.has(doc)) return;
      mounted.add(doc);
      doc.addEventListener('click', function (event) {
        const raw = event.target;
        const destination = destinationFor(raw && raw.nodeType === 3 ? raw.parentElement : raw);
        if (!destination) return;
        // Only the explicitly listed CTA buttons and the Telegram link are handled.
        // Working pricing-table checkout buttons retain their existing functionality.
        event.preventDefault();
        // CTA repair intercepts this click before the funnel's own navigation.
        if (destination === destinations.combo) trackCheckoutIntent('combo');
        else if (destination === destinations.starter) trackCheckoutIntent('starter');
        event.stopImmediatePropagation();
        event.stopPropagation();
        // Navigate the top-level page to prevent iframe sandbox/popup restrictions.
        // A short delay allows the browser Pixel request to dispatch.
        if (analyticsAllowed()) setTimeout(function(){ window.location.assign(destination); }, 150);
        else window.location.assign(destination);
      }, true);
    } catch (e) {
      // Cross-origin or sandboxed frames are not bypassed.
    }
  }

  function scan() {
    mount(document);
    document.querySelectorAll('iframe').forEach(function (frame) {
      try {
        if (!watched.has(frame)) {
          watched.add(frame);
          frame.addEventListener('load', scan);
        }
        mount(frame.contentDocument);
      } catch (e) {}
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan, {once: true});
  } else {
    scan();
  }
  if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(scan);
    observer.observe(document.documentElement, {childList: true, subtree: true});
  }
  [350, 1000, 2500, 6000, 12000].forEach(function (ms) { setTimeout(scan, ms); });
})();
</script>
    <?php
}, 100);
