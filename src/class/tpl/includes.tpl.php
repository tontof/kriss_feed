<title><?php echo $pagetitle;?></title>
<meta charset="utf-8">

<?php if (is_file('inc/favicon.ico')) { ?>
<link href="inc/favicon.ico" rel="icon" type="image/x-icon">
<?php } else { ?>
<link href="?file=favicon" rel="icon" type="image/x-icon">
<?php } ?>
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
