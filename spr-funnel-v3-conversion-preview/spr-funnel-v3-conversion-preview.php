<?php
/**
 * Plugin Name: SAMI Prime Funnel V3 - Conversion Preview
 * Description: Approval-gated additive learning and plan-selection section. Does not replace the original funnel or CTA repair.
 * Version: 0.1.0
 * Author: SAMI PRIME RESEARCH
 */
defined('ABSPATH') || exit;

// Preview is deliberately restricted to authenticated site administrators.
// No visitor-facing publication or checkout/analytics event changes.
add_action('template_redirect', static function () {
    if (isset($_GET['spr_v3_preview']) && current_user_can('manage_options')) {
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        nocache_headers();
    }
});
add_action('wp_footer', static function () {
    $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (rtrim((string) $path, '/') !== '/stock-market-research-education'
        || !isset($_GET['spr_v3_preview']) || !current_user_can('manage_options')) return;
    ?>
<script id="spr-v3-preview">
(function () {
'use strict';
const copy = {
en: {
k:'RESEARCH + STRUCTURED LEARNING', h:'Understand the research. Build your own process.', p:'Choose a research membership that fits your time horizon. Explore the logic, risk and method behind decisions — not promises of returns.',
s:'Start with Intraday', sd:'7 days of Intraday research and one guided educational setup walkthrough. A separate paid membership, not a Combo trial.', sb:'See Starter fees & terms',
c:'Learn across all three horizons', cd:'Combo combines Intraday, Swing and Long-Term research with eligible educational workshops at 10:35 PM on trading days.', cb:'See Combo fees & terms',
mh:'Explore the proposed 18-module learning roadmap', mp:'Six topics per research horizon. Sessions and eligibility are subject to the approved membership schedule. Examples use hypothetical or permitted historical data.',
groups:['Intraday research','Swing research','Long-Term research'],
topics:[['Market context and sector strength','Pre-open and opening-range research','Price action and market structure','Volume, VWAP and liquidity','Entry, invalidation and risk-reward','Research review and journal'],['Trend and multi-timeframe structure','Support, resistance and consolidation','Breakout, retest and pullback','Volume and momentum confirmation','Position sizing and exit planning','Research case review'],['Business model and industry','Financial statements and cash flows','Balance-sheet quality and debt','Valuation and expectations','Management, catalysts and risks','Research thesis and ongoing review']],
r:'Research memberships do not assure returns. Securities markets involve risk. Review scope, fees, terms, KYC and disclosures before subscribing.', preview:'ADMIN PREVIEW — curriculum copy and benefits require owner/compliance approval.'
},
hg: {
k:'RESEARCH + STRUCTURED LEARNING', h:'Research ka logic samjhiye. Apna process banaiye.', p:'Apne trading horizon ke hisaab se research membership chuniye. Har decision ke peeche logic, risk aur method samjhiye — returns ka vaada nahi.',
s:'Intraday se shuruaat', sd:'7 din ki Intraday research aur ek guided educational setup walkthrough. Yeh separate paid membership hai, Combo trial nahi.', sb:'Starter fees aur terms dekhein',
c:'Teen horizons ki research aur learning', cd:'Combo mein Intraday, Swing aur Long-Term research, saath hi eligible educational workshops trading days par 10:35 PM.', cb:'Combo fees aur terms dekhein',
mh:'Proposed 18-module learning roadmap dekhein', mp:'Har research horizon mein 6 topics. Sessions aur eligibility approved membership schedule par depend karte hain. Examples hypothetical ya permitted historical data par honge.',
groups:['Intraday research','Swing research','Long-Term research'],
topics:[['Market context aur sector strength','Pre-open aur opening range','Price action aur market structure','Volume, VWAP aur liquidity','Entry, invalidation aur risk-reward','Research review aur journal'],['Trend aur multiple timeframes','Support, resistance aur consolidation','Breakout, retest aur pullback','Volume aur momentum confirmation','Position sizing aur exit planning','Research case review'],['Business model aur industry','Financial statements aur cash flows','Balance sheet aur debt','Valuation aur expectations','Management, catalysts aur risks','Research thesis aur ongoing review']],
r:'Research se assured returns nahi milte. Securities markets mein risk hai. Subscribe karne se pehle scope, fees, terms, KYC aur disclosures dekhein.',preview:'ADMIN PREVIEW — curriculum aur benefits ke liye owner/compliance approval zaroori hai.'
},
hi: {
k:'रिसर्च और व्यवस्थित सीखना', h:'रिसर्च का तर्क समझें। अपनी प्रक्रिया बनाएं।', p:'अपने समय और उद्देश्य के अनुसार रिसर्च सदस्यता चुनें। निर्णय के पीछे तर्क, जोखिम और विधि समझें — रिटर्न का वादा नहीं।',
s:'इंट्राडे से शुरुआत',sd:'7 दिन की इंट्राडे रिसर्च और एक निर्देशित शैक्षिक सेटअप सत्र। यह अलग सशुल्क सदस्यता है, कॉम्बो ट्रायल नहीं।',sb:'स्टार्टर शुल्क और शर्तें देखें',
c:'तीनों अवधियों की रिसर्च और सीखना',cd:'कॉम्बो में इंट्राडे, स्विंग और लंबी अवधि की रिसर्च तथा पात्र सदस्यों के लिए ट्रेडिंग दिनों में रात 10:35 बजे शैक्षिक सत्र।',cb:'कॉम्बो शुल्क और शर्तें देखें',
mh:'प्रस्तावित 18 मॉड्यूल देखें',mp:'हर अवधि में छह विषय। सत्र और पात्रता स्वीकृत सदस्यता कार्यक्रम के अनुसार। उदाहरण काल्पनिक या अनुमत ऐतिहासिक डेटा पर आधारित होंगे।',
groups:['इंट्राडे रिसर्च','स्विंग रिसर्च','लंबी अवधि की रिसर्च'],
topics:[['बाजार और सेक्टर की स्थिति','प्री-ओपन और ओपनिंग रेंज','प्राइस एक्शन और बाजार संरचना','वॉल्यूम, VWAP और लिक्विडिटी','एंट्री, अमान्यता और जोखिम-प्रतिफल','रिसर्च समीक्षा और जर्नल'],['ट्रेंड और विभिन्न टाइमफ्रेम','सपोर्ट, रेजिस्टेंस और कंसोलिडेशन','ब्रेकआउट, रीटेस्ट और पुलबैक','वॉल्यूम और मोमेंटम','पोजिशन साइज और एग्जिट','रिसर्च केस समीक्षा'],['बिजनेस मॉडल और उद्योग','वित्तीय विवरण और नकदी प्रवाह','बैलेंस शीट और ऋण','वैल्यूएशन और अपेक्षाएं','प्रबंधन, कारक और जोखिम','रिसर्च थीसिस और समीक्षा']],
r:'रिसर्च सुनिश्चित रिटर्न नहीं देती। प्रतिभूति बाजार में जोखिम है। सदस्यता से पहले सेवा, शुल्क, शर्तें, KYC और प्रकटीकरण देखें।',preview:'एडमिन पूर्वावलोकन — मालिक और अनुपालन की स्वीकृति आवश्यक।'
},
bn: {
k:'গবেষণা ও কাঠামোবদ্ধ শিক্ষা',h:'গবেষণার যুক্তি বুঝুন। নিজের পদ্ধতি তৈরি করুন।',p:'আপনার সময়সীমা অনুযায়ী গবেষণা সদস্যপদ বেছে নিন। সিদ্ধান্তের যুক্তি, ঝুঁকি ও পদ্ধতি বুঝুন — রিটার্নের প্রতিশ্রুতি নয়।',
s:'ইন্ট্রাডে দিয়ে শুরু',sd:'৭ দিনের ইন্ট্রাডে গবেষণা ও একটি নির্দেশিত শিক্ষামূলক সেটআপ সেশন। এটি আলাদা অর্থপ্রদত্ত সদস্যপদ, কম্বোর ট্রায়াল নয়।',sb:'স্টার্টারের ফি ও শর্ত দেখুন',
c:'তিন সময়সীমায় গবেষণা ও শিক্ষা',cd:'কম্বোতে ইন্ট্রাডে, সুইং ও দীর্ঘমেয়াদি গবেষণা এবং যোগ্য সদস্যদের জন্য ট্রেডিং দিনে রাত ১০:৩৫-এর শিক্ষামূলক কর্মশালা।',cb:'কম্বোর ফি ও শর্ত দেখুন',
mh:'প্রস্তাবিত ১৮টি মডিউল দেখুন',mp:'প্রতি সময়সীমায় ছয়টি বিষয়। সেশন ও যোগ্যতা অনুমোদিত সময়সূচি অনুযায়ী। উদাহরণ কাল্পনিক বা অনুমোদিত ঐতিহাসিক ডেটা ব্যবহার করবে।',
groups:['ইন্ট্রাডে গবেষণা','সুইং গবেষণা','দীর্ঘমেয়াদি গবেষণা'],
topics:[['বাজার ও সেক্টরের অবস্থা','প্রি-ওপেন ও ওপেনিং রেঞ্জ','প্রাইস অ্যাকশন ও বাজার কাঠামো','ভলিউম, VWAP ও লিকুইডিটি','এন্ট্রি, বাতিলের শর্ত ও ঝুঁকি-রিটার্ন','গবেষণা পর্যালোচনা ও জার্নাল'],['ট্রেন্ড ও বিভিন্ন টাইমফ্রেম','সাপোর্ট, রেজিস্ট্যান্স ও কনসোলিডেশন','ব্রেকআউট, রিটেস্ট ও পুলব্যাক','ভলিউম ও মোমেন্টাম','পজিশনের আকার ও এক্সিট','গবেষণা কেস পর্যালোচনা'],['ব্যবসার মডেল ও শিল্প','আর্থিক বিবরণ ও নগদ প্রবাহ','ব্যালান্স শিট ও ঋণ','মূল্যায়ন ও প্রত্যাশা','পরিচালনা, প্রভাবক ও ঝুঁকি','গবেষণা থিসিস ও পর্যালোচনা']],
r:'গবেষণা নিশ্চিত রিটার্ন দেয় না। সিকিউরিটিজ বাজারে ঝুঁকি রয়েছে। সদস্যপদের আগে ফি, শর্ত, KYC ও প্রকাশনা দেখুন।',preview:'অ্যাডমিন প্রিভিউ — মালিক ও কমপ্লায়েন্সের অনুমোদন প্রয়োজন।'
},
ur: {
k:'تحقیق اور منظم تعلیم',h:'تحقیق کی منطق سمجھیں۔ اپنا طریقہ بنائیں۔',p:'اپنی مدت کے مطابق تحقیقی رکنیت منتخب کریں۔ فیصلے کی منطق، خطرہ اور طریقہ سمجھیں — منافع کا وعدہ نہیں۔',
s:'انٹرا ڈے سے آغاز',sd:'سات دن کی انٹرا ڈے تحقیق اور ایک رہنمائی والا تعلیمی سیشن۔ یہ الگ بامعاوضہ رکنیت ہے، کومبو ٹرائل نہیں۔',sb:'اسٹارٹر کی فیس اور شرائط دیکھیں',
c:'تین مدتوں کی تحقیق اور تعلیم',cd:'کومبو میں انٹرا ڈے، سوئنگ اور طویل مدتی تحقیق، اور اہل اراکین کے لیے تجارتی دنوں میں رات 10:35 بجے تعلیمی ورکشاپ۔',cb:'کومبو کی فیس اور شرائط دیکھیں',
mh:'مجوزہ 18 تعلیمی ماڈیول دیکھیں',mp:'ہر مدت کے چھ موضوعات۔ سیشن اور اہلیت منظور شدہ شیڈول کے مطابق ہیں۔ مثالیں فرضی یا اجازت یافتہ تاریخی ڈیٹا استعمال کریں گی۔',
groups:['انٹرا ڈے تحقیق','سوئنگ تحقیق','طویل مدتی تحقیق'],
topics:[['مارکیٹ اور سیکٹر کی صورتحال','پری اوپن اور اوپننگ رینج','پرائس ایکشن اور مارکیٹ ساخت','والیوم، VWAP اور لیکویڈیٹی','انٹری، منسوخی اور رسک ریوارڈ','تحقیق کا جائزہ اور جرنل'],['ٹرینڈ اور مختلف ٹائم فریم','سپورٹ، ریزسٹنس اور کنسولیڈیشن','بریک آؤٹ، ری ٹیسٹ اور پل بیک','والیوم اور مومینٹم','پوزیشن سائز اور اخراج','تحقیقی کیس کا جائزہ'],['کاروباری ماڈل اور صنعت','مالی بیانات اور نقد بہاؤ','بیلنس شیٹ اور قرض','ویلیوایشن اور توقعات','انتظامیہ، عوامل اور خطرات','تحقیقی نظریہ اور مسلسل جائزہ']],
r:'تحقیق یقینی منافع نہیں دیتی۔ سیکیورٹیز مارکیٹ میں خطرات ہیں۔ رکنیت سے پہلے فیس، شرائط، KYC اور انکشافات دیکھیں۔',preview:'ایڈمن پیش منظر — مالک اور تعمیل کی منظوری درکار ہے۔'
}
};
const css = '#spr-v3-learning{padding:clamp(24px,5vw,56px) 16px;background:#f1f7f4;color:#142c28;font:inherit}#spr-v3-learning *{box-sizing:border-box}#spr-v3-learning .v3-wrap{max-width:1100px;margin:auto}#spr-v3-learning h2{font-size:clamp(24px,4vw,38px);line-height:1.18;margin:8px 0 12px;color:#142c28}#spr-v3-learning h3{font-size:19px;margin:4px 0 10px;color:#142c28}#spr-v3-learning p{font-size:15px;line-height:1.55;margin:8px 0 14px}#spr-v3-learning .v3-k{font-size:12px;font-weight:800;letter-spacing:.08em;color:#17634c}#spr-v3-learning .v3-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin:18px 0}#spr-v3-learning .v3-card{background:#fff;border:1px solid #cbded4;padding:18px;border-radius:14px;min-width:0}#spr-v3-learning .v3-feature{border:2px solid #17634c}#spr-v3-learning button{font:inherit;font-weight:700;display:block;width:100%;padding:12px;border:1px solid #17634c;border-radius:9px;background:#17634c;color:#fff;cursor:pointer;min-height:44px;white-space:normal}#spr-v3-learning button:focus-visible,#spr-v3-learning summary:focus-visible{outline:3px solid #c17a22;outline-offset:3px}#spr-v3-learning .v3-roadmap{background:#fff;border:1px solid #cbded4;border-radius:14px;padding:16px}#spr-v3-learning summary{font-size:17px;font-weight:800;cursor:pointer}#spr-v3-learning .v3-modules{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:14px}#spr-v3-learning .v3-modules ol{padding-inline-start:23px;margin:0;font-size:14px;line-height:1.5}#spr-v3-learning .v3-modules li{margin:5px 0;overflow-wrap:anywhere}#spr-v3-learning .v3-risk{font-size:13px;color:#334a44}#spr-v3-learning .v3-preview{font-size:12px;font-weight:700;color:#744400}@media(max-width:650px){#spr-v3-learning .v3-grid,#spr-v3-learning .v3-modules{grid-template-columns:1fr}}';
const wired = new WeakSet(), frames = new WeakSet();
function node(doc, tag, cls, text) {
 const el=doc.createElement(tag); if(cls) el.className=cls; if(text) el.textContent=text; return el;
}
function render(doc, section, lang) {
 const t=copy[lang] || copy.en, old=section.querySelector('details'), expanded=old?old.open:true;
 section.dir=lang==='ur'?'rtl':'ltr'; section.lang=lang==='hg'?'hi-Latn':lang;
 section.replaceChildren();
 const wrap=node(doc,'div','v3-wrap'); section.appendChild(wrap);
 wrap.append(node(doc,'div','v3-preview',t.preview),node(doc,'div','v3-k',t.k),node(doc,'h2','',t.h),node(doc,'p','',t.p));
 const grid=node(doc,'div','v3-grid'); wrap.appendChild(grid);
 function card(title,description,button,target,featured) {
  const a=node(doc,'article','v3-card'+(featured?' v3-feature':''));
  const b=node(doc,'button','',button); b.type='button';
  b.addEventListener('click',function(){const dest=doc.getElementById(target); if(dest){dest.scrollIntoView({behavior:'smooth',block:'start'});const h=dest.querySelector('h3');if(h){h.setAttribute('tabindex','-1');h.focus({preventScroll:true});}}});
  a.append(node(doc,'h3','',title),node(doc,'p','',description),b);grid.appendChild(a);
 }
 card(t.s,t.sd,t.sb,'starter',false);card(t.c,t.cd,t.cb,'combo',true);
 const details=node(doc,'details','v3-roadmap');details.open=expanded;
 details.append(node(doc,'summary','',t.mh),node(doc,'p','',t.mp));const mods=node(doc,'div','v3-modules');
 t.groups.forEach(function(group,i){const c=node(doc,'article','v3-card'), list=node(doc,'ol','');list.start=i*6+1;t.topics[i].forEach(function(topic){list.appendChild(node(doc,'li','',topic));});c.append(node(doc,'h3','',group),list);mods.appendChild(c);});
 details.appendChild(mods);wrap.append(details,node(doc,'p','v3-risk',t.r));
}
function mount(doc){
 try {
 if(!doc || wired.has(doc)) return;
 const app=doc.getElementById('sprApp'),offers=doc.getElementById('offers'),combo=doc.getElementById('combo'),starter=doc.getElementById('starter'),select=doc.getElementById('langSelect');
 if(!app||!offers||!combo||!starter||!select||!app.contains(offers)) return;
 if(doc.getElementById('spr-v3-learning')) return;
 const style=node(doc,'style','',css);style.id='spr-v3-css';(doc.head||doc.documentElement).appendChild(style);
 const section=node(doc,'section','');section.id='spr-v3-learning';offers.parentNode.insertBefore(section,offers);
 render(doc,section,select.value);select.addEventListener('change',function(){render(doc,section,select.value);});wired.add(doc);
 }catch(e){/* fail closed; do not change original checkout */}
}
function scan(){mount(document);document.querySelectorAll('iframe').forEach(function(frame){try{if(!frames.has(frame)){frames.add(frame);frame.addEventListener('load',scan);}mount(frame.contentDocument);}catch(e){}});}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',scan,{once:true});else scan();
 [350,1000,2500,6000,12000].forEach(function(ms){setTimeout(scan,ms);});
})();
</script>
    <?php
}, 110);
