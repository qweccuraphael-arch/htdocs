<!-- ads/interstitial.php -->
<?php if (!defined('ADSENSE_PUBLISHER_ID')) require_once dirname(__DIR__, 2) . '/config/ads.php'; ?>
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="<?= ADSENSE_PUBLISHER_ID ?>"
     data-ad-slot="<?= AD_SLOT_INTERSTITIAL ?>"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
