<?php if (!defined('ADS_ENABLED')) die('Direct access denied'); ?>
<?php if (!ADS_ENABLED): ?>
  <div class="ad-placeholder sidebar-ad">
    <span>📢 Advertisement</span>
    <div class="ad-label">Sidebar 300x600</div>
  </div>
<?php else: ?>
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= ADSENSE_PUBLISHER_ID ?>" crossorigin="anonymous"></script>
  <!-- BeatWave Sidebar Ad -->
  <ins class="adsbygoogle"
       style="display:inline-block;width:300px;height:600px"
       data-ad-client="<?= ADSENSE_PUBLISHER_ID ?>"
       data-ad-slot="<?= AD_SLOT_SIDEBAR ?>"
       data-ad-format="auto"
       data-full-width-responsive="true"></ins>
  <script>
    (adsbygoogle = window.adsbygoogle || []).push({});
  </script>
<?php endif; ?>
