
<ul class="inline"><?php
foreach(array_keys($paging) as $pagingOpt) {
  switch($pagingOpt) {
    case 'pagingItem': ?>

  <li>
    <div class="btn-group">
      <a class="btn btn2 btn-info previous-item" href="<?php echo $query.'previous='.$currentItemHash; ?>" title="<?php echo Intl::msg("Previous item"); ?>"><?php echo Intl::msg("Previous item"); ?></a>
      <a class="btn btn2 btn-info next-item" href="<?php echo $query.'next='.$currentItemHash; ?>" title="<?php echo Intl::msg("Next item"); ?>"><?php echo Intl::msg("Next item"); ?></a>
    </div>
  </li><?php
      break;
    case 'pagingMarkAs': ?>

  <li>
    <div class="btn-group">
      <a class="btn btn-info" href="<?php echo $query.'read='.$currentHash; ?>" title="<?php echo Intl::msg("Mark as read"); ?>"><?php echo Intl::msg("Mark as read"); ?></a>
    </div>
  </li><?php
      break;
    case 'pagingPage': ?>

  <li>
    <div class="btn-group">
      <a class="btn btn3 btn-info previous-page<?php echo ($currentPage === 1)?' disabled':''; ?>" href="<?php echo $query.'previousPage='.$currentPage; ?>" title="<?php echo Intl::msg("Previous page"); ?>"><?php echo Intl::msg("Previous page"); ?></a>
      <button class="btn btn3 disabled current-max-page"><?php echo $currentPage.' / '.$maxPage; ?></button>
      <a class="btn btn3 btn-info next-page<?php echo ($currentPage === $maxPage)?' disabled':''; ?>" href="<?php echo $query.'nextPage='.$currentPage; ?>" title="<?php echo Intl::msg("Next page"); ?>"><?php echo Intl::msg("Next page"); ?></a>
    </div>
  </li><?php
      break;
    case 'pagingByPage': ?>

  <li>
    <div class="btn-group">
      <form class="form-inline" action="" method="GET">
        <div class="input-prepend input-append paging-by-page">
          <a class="btn btn3 btn-info" href="<?php echo $query.'byPage=1'; ?>">1</a>
          <a class="btn btn3 btn-info" href="<?php echo $query.'byPage=10'; ?>">10</a>
          <a class="btn btn3 btn-info" href="<?php echo $query.'byPage=50'; ?>">50</a>
          <div class="btn-break"></div>
          <input class="btn2 input-by-page input-mini" type="text" name="byPage">
          <input type="hidden" name="currentHash" value="<?php echo $currentHash; ?>">
          <button type="submit" class="btn btn2"><?php echo Intl::msg("Items per page"); ?></button>
        </div>
      </form>
    </div>
  </li><?php
      break;
    default:
      break;
  }
} ?>

</ul>
<div class="clear"></div>
