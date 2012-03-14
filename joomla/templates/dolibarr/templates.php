<?if( $sg == 'banner' ):?>
 
 <?else:?>
<br/><br/>
Dolibarr ERP & CRM &copy; 2009-2012 - Professional or foundation's activity manager<br/>

<?php if (in_array($_SERVER['SCRIPT_URL'],array('/','/index.php')) && ! preg_match('/^\/forum\//',$_SERVER['SCRIPT_URL'])) { ?>
<a href="<?php echo $GLOBALS[mosConfig_live_site] ?>/index.php?option=com_bca-rss-syndicator&feed_id=1" target="_blank" >
<img src="<?php echo $GLOBALS[mosConfig_live_site] ?>/images/M_images/rss20.gif"  alt="RSS 2.0" name="RSS20" align="middle" border="0" /></a>
<a href="<?php echo $GLOBALS[mosConfig_live_site] ?>/index.php?option=com_bca-rss-syndicator&feed_id=3" target="_blank" >
<img src="<?php echo $GLOBALS[mosConfig_live_site] ?>/images/M_images/atom03.gif"  alt="ATOM 0.3" name="ATOM03" align="middle" border="0" /></a>
<br/>
<?php } ?>
<?php //mosLoadModules('user8'); ?>
<?endif;?>

