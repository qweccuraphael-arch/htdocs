<?php if (!defined('ADS_ENABLED')) die('Direct access denied'); ?>
<?php if (!ADS_ENABLED): ?>
  <div class="ad-placeholder interstitial-ad">
    <span>📢 Advertisement</span>
    <div class="ad-label">Interstitial 336x280</div>
  </div>
<?php else: ?>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= ADSENSE_PUBLISHER_ID ?>" crossorigin="anonymous"></script>
  <!-- BeatWave Interstitial Ad -->
  <ins class="adsbygoogle"
       style="display:inline-block;width:336px;height:280px"
       data-ad-client="<?= ADSENSE_PUBLISHER_ID ?>"
       data-ad-slot="<?= AD_SLOT_INTERSTITIAL ?>"
       data-ad-format="auto"
       data-full-width-responsive="true"></ins>
  <script>
    (adsbygoogle = window.adsbygoogle || []).push({});
  </script>
<?php endif; ?>
