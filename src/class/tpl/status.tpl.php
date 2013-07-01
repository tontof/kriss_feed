<div id="status" class="text-center">
  <a href="http://github.com/tontof/kriss_feed">KrISS feed <?php echo $version; ?></a>
  <span class="hidden-phone"> - <?php echo Intl::msg('A simple and smart (or stupid) feed reader'); ?></span>. <?php /* KrISS: By Tontof */echo Intl::msg('By'); ?> <a href="http://tontof.net">Tontof</a>
<span id="flags-sel">
  <a id="hide-flags" href="#flags" class="flag <?php echo Intl::$langList[Intl::$lang]['class']; ?>" title="<?php echo Intl::$langList[Intl::$lang]['name']; ?>"></a>
  <a id="show-flags" href="#flags-sel" class="flag <?php echo Intl::$langList[Intl::$lang]['class']; ?>" title="<?php echo Intl::$langList[Intl::$lang]['name']; ?>"></a>
</span>
<div id="flags">
<?php foreach(Intl::$langList as $lang => $info) { ?>
<a href="?lang=<?php echo $lang; ?>" title="<?php echo $info['name']; ?>" class="flag <?php echo $info['class']; ?>"></a>
<?php } ?>
</div>
</div>
