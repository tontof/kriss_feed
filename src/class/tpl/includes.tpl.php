<title><?php echo $pagetitle;?></title>
<meta charset="utf-8">

<!-- <link href="images/favicon.ico" rel="shortcut icon" type="image/x-icon"> -->
<?php if (is_file('inc/style.css')) { ?>
<link type="text/css" rel="stylesheet" href="inc/style.css?version=<?php echo $version;?>" />
<?php } else { ?>
<style>
<?php include("inc/style.css"); ?>
</style>
<?php } ?>
<?php if (is_file('inc/user.css')) { ?>
<link type="text/css" rel="stylesheet" href="inc/user.css?version=<?php echo $version;?>" />
<?php } ?>
<meta name="viewport" content="width=device-width">
