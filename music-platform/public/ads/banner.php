<?php if (!defined('ADS_ENABLED')) die('Direct access denied'); ?>
<?php if (!ADS_ENABLED): ?>
  <div class="ad-placeholder banner-ad">
    <span>📢 Advertisement</span>
    <div class="ad-label">Banner 728x90</div>
  </div>
<?php else: ?>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= ADSENSE_PUBLISHER_ID ?>" crossorigin="anonymous"></script>
  <!-- BeatWave Banner Ad -->
  <ins class="adsbygoogle"
       style="display:inline-block;width:728px;height:90px"
       data-ad-client="<?= ADSENSE_PUBLISHER_ID ?>"
       data-ad-slot="<?= AD_SLOT_BANNER ?>"
       data-ad-format="auto"
       data-full-width-responsive="true"></ins>
  <script>
    (adsbygoogle = window.adsbygoogle || []).push({});
  </script>
<?php endif; ?>
