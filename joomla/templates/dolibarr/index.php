<?php defined( "_VALID_MOS" ) or die( "Direct Access to this location is not allowed." );$iso = split( '=', _ISO );echo '<?xml version="1.0" encoding="'. $iso[1] .'"?' .'>';?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php mosShowHead(); ?>
<meta http-equiv="Content-Type" content="text/html" <?php echo _ISO; ?> />
<meta name="Updowner-verification" content="1328821adac0666e174e84809a4cc72e" />
<meta name="verify-v1" content="5uTEtcSaRHlZVnb3L4x4QrpRzdw3zMZ51+mJxf/4Cd8=" />
<meta name="verify-v1" content="ygCOli7T1nnmmIz2ikasGV2Y+1DLmLcsblrDp+tSo/Q=" />
<?php if ( $my->id ) { initEditor(); } ?>
<?php echo "<link rel=\"stylesheet\" href=\"$GLOBALS[mosConfig_live_site]/templates/$GLOBALS[cur_template]/css/template_css.css\" type=\"text/css\"/>" ; ?>
<!-- Google analytics -->
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-9049390-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<!-- End google analytics --> 
<style type="text/css" media="screen">
      .ribbon {
        background-color: #a00;
        overflow: hidden;
        /* top left corner */
        position: fixed;
        left: -3em;
        top: 2.5em;
        /* 45 deg ccw rotation */
        -moz-transform: rotate(-45deg);
        -webkit-transform: rotate(-45deg);
        -moz-box-shadow: 0 0 1em #888;
        -webkit-box-shadow: 0 0 1em #888;
      }
      .ribbon a {
        border: 1px solid #faa;
        color: #fff;
        display: block;
        font: bold 81.25% 'Helvetiva Neue', Helvetica, Arial, sans-serif;
        margin: 0.05em 0 0.075em 0;
        padding: 0.5em 3.5em;
        text-align: center;
        text-decoration: none;
        text-shadow: 0 0 0.5em #444;
      }
</style>
</head>
<body class="body">

<div class="ribbon">
    <a href="http://github.com/Dolibarr/dolibarr" target="_blank">Fork me on GitHub</a>
</div>


<style type="text/css">
<!--
#rt-main-surround, #rt-variation .bg3 .module-content, #rt-variation .title3 .module-title {background:#ffffff;}
#rt-variation .bg3, #rt-variation .bg3 .title, #rt-variation .title3 .title, #rt-variation .bg3 ul.menu li > a:hover, #rt-variation .bg3 ul.menu li.active > a {color:#474747;}
#rt-variation .bg3 a, #rt-variation .bg3 .title span, #rt-variation .bg3 .button, #rt-variation .title3 .title span {color:#2c68a3;}
#rt-main-header, .menutop ul, .menutop .drop-top, #rt-variation .bg1 .module-content, #rt-variation .title1 .module-title {background:#2c68a3;}
#rt-main-header, #rt-main-header .title, #rt-header, #rt-main-header .menutop li > .item, .menutop ul li .item, #rt-variation .bg1, #rt-variation .bg1 .title, #rt-variation .title1 .title, #rt-variation .bg1 ul.menu li > a:hover, #rt-variation .bg1 ul.menu li.active > a, #rt-navigation li.root .item {color:#dedede;}
#rt-main-header .title span, #rt-variation .bg1 a, #rt-variation .bg1 .title span, #rt-variation .bg1 .button, #rt-variation .title1 .title span {color:#cce6ff;}
#rt-feature, #rt-utility, #roksearch_results, #roksearch_results .rokajaxsearch-overlay, #rt-variation .bg2 .module-content, #rt-variation .title2 .module-title {background:#ffffff;}
#rt-feature, #rt-feature .title, #rt-utility, #rt-utility .title, #roksearch_results, #roksearch_results span, #rt-variation .bg2, #rt-variation .bg2 .title, #rt-variation .title2 .title, #rt-variation .bg2 ul.menu li > a:hover, #rt-variation .bg2 ul.menu li.active > a {color:#474747;}
#rt-feature a, #rt-utility a, #rt-feature .title span, #rt-utility .title span, #roksearch_results a, #roksearch_results h3, #rt-variation .bg2 a, #rt-variation .bg2 .title span, #rt-variation .bg2 .button, #rt-variation .title2 .title span {color:#2c68a3;}
#rt-mainbody-bg, #rt-variation .bg4 .module-content, #rt-variation .title4 .module-title {background:#ffffff;}
#rt-mainbody-bg, #rt-mainbody-bg .title, #rt-mainbody-bg .rt-article-title, #rt-mainbody-bg ul.menu li > a:hover, #rt-mainbody-bg ul.menu li.active > a, #rt-variation .bg4, #rt-variation .bg4 .title, #rt-variation .title4 .title, #rt-variation .bg4 ul.menu li > a:hover, #rt-variation .bg4 ul.menu li.active > a {color:#474747;}
#rt-mainbody-bg a, #rt-mainbody-bg .title span, #rt-mainbody-bg .rt-article-title span, #rt-variation .bg4 a, #rt-variation .bg4 .title span, #rt-variation .bg4 .button, #rt-variation .title4 .title span {color:#2c68a3;}
#rt-bottom, #rt-main-footer, #rt-variation .bg5 .module-content, #rt-variation .title5 .module-title {background:#2c68a3;}
#rt-bottom, #rt-bottom .title, #rt-footer, #rt-footer .title, #rt-copyright, #rt-copyright .title, #rt-debug, #rt-debug .title, #rt-variation .bg5, #rt-variation .bg5 .title, #rt-variation .title5 .title, #rt-variation .bg5 ul.menu li > a:hover, #rt-variation .bg5 ul.menu li.active > a {color:#474747;}
#rt-bottom a, #rt-bottom .title span, #rt-footer a, #rt-footer .title span, #rt-copyright a, #rt-copyright .title span, #rt-debug a, #rt-debug .title span, #rt-variation .bg5 a, #rt-variation .bg5 .title span, #rt-variation .bg5 .button, #rt-variation .title5 .title span {color:#2c68a3;}
-->
</style>
<div id="rt-main-header" class="header-shadows-light"><div id="rt-header-overlay" class="header-overlay-none">
<div id="rt-main-header2">
<div id="rt-header-graphic" class="header-graphic-header-6">


<!-- Top banner Logo -->
<div id="rt-header">
<div class="rt-container">
<div class=""><div class="shadow-right"><div class="shadow-bottom">
					
<div class="rt-grid-12 rt-alpha rt-omega">
<div class="rt-block" style="height: 130px;">

<div style="float: left">
<a href="/"><img src="/templates/dolibarr/images/bg2.png" id="rt-logo"></a>
</div>
<div style="float: right" id="dolbanner">
<?php mosLoadModules('banner'); ?>
</div>

</div>
</div>

<div class="clear"></div>
</div></div></div>
</div>
</div>
<!-- End top banner logo -->

<!-- navigation -->
<div id="rt-navigation" style="height: 54px;"><div id="rt-navigation2" style="height: 54px;"><div id="rt-navigation3" style="height: 54px;">
<div class="rt-container" style="height: 54px;">
<div class="shadow-left" style="height: 54px;"><div class="shadow-right" style="height: 54px;">

<?php include('menu.php'); ?>	

<div class="plusone">
<!-- Plus one -->
<g:plusone size="small" href="http://www.dolibarr.org/"></g:plusone>
<script type="text/javascript">
(function() {
var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
po.src = 'http://apis.google.com/js/plusone.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
})();
</script>
</div>

<div class="clear"></div>
</div></div>
</div>
</div></div></div>
<!-- End navigation -->



<div id="main-surround" class="main-shadows-light">
<div id="container"><div class="shadow-left"><div class="shadow-right">




<table cellpadding="0" cellspacing="0">

	<tr>
<?php if (preg_match('/\/forum/',$_SERVER['SCRIPT_URL'])) { ?>
	<td class="leftcol" valign="top">
			<?php mosLoadModules('left'); ?>
	</td>
<?php } ?>
	<td class="maincol" valign="top">


<!-- Intro -->
<?php 
if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['searchword']))
{
	print '<div style="padding:8px 6px 40px 6px;">';
	print '<img style="padding-left: 6px; padding-top: 10px; float: left; width: 150px;" src="/images/stories/dolibarr_box.png">';
	print '<div style="padding-left: 174px; ">';
	print '<strong>Dolibarr ERP & CRM</strong> is a modern web software to manage your company or foundation activity (contacts, invoices, orders, stocks, agenda, ...). ';
	print '<br>It\'s an <strong>opensource and free software</strong> designed for small and medium companies, foundations and freelances.<br>';
	print 'You can <strong>install, use and distribute</strong> it as a standalone application or as an online web application (on a mutualized or dedicated server) to use it from anywhere.<br>';
	print 'Dolibarr is also available on ready to use Cloud services.<br>';
	print '<br>';
	print 'This is official Portal with <strong>news</strong>, <strong>forum</strong>, <strong>demo</strong> and <strong>download</strong> area of Dolibarr project.<br>';
	print 'For more information on <strong>features</strong>, <strong>roadmap</strong>, <strong>documentation</strong> and <strong>faq</strong> on project, see <a target="_blank" href="http://wiki.dolibarr.org/index.php/Main_Page">the Dolibarr wiki documentation</a>.';
	print '</div></div>';

	print '<table><tr><td valign="top">';
	mosLoadModules('user1');
	print '</td><td valign="top">';
	mosLoadModules('user2');
	print '</td></tr></table>';

	print '<br>';

	if (in_array($_SERVER['SCRIPT_URL'],array('/','/lastnews')) && ! preg_match('/searchword/',$_SERVER['QUERY_STRING'])) print '<br><div class="componentheading" style="margin-left:6px;">Last news</div><br>';
}
if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/foundation','/foundation?jid=3')))
{

}
?>

		<div style="padding:0px 2px 0px 2px;">
			<?php mosMainBody(); ?>
		</div>
	</td>

<?php if (! preg_match('/\/forum/',$_SERVER['SCRIPT_URL'])) { ?>

	<td class="rightcol" valign="top">

		<!-- search form -->
		<?php if (! preg_match('/(component\/user|component\/search)/',$_SERVER['SCRIPT_URL']) && ! isset($_GET['searchword'])) { ?>
		<div id="rt-utility" class="feature-shadows-light">
		<div id="rt-utility2" style="height: 40px;"><div id="rt-utility3">

		<div class="rt-grid-4">
		<form name="rokajaxsearch" id="rokajaxsearch" class="light" action="http://dolibarr.org/" method="get">
		<div class="rokajaxsearchbg1">
		<div class="roksearch-wrapper">
		<input id="roksearch_search_str" name="searchword" type="text" class="inputbox" value="" autocomplete="off" value="<?php echo $_POST['searchword']; ?>">
		</div>
		<input type="hidden" name="searchphrase" value="all">
		<input type="hidden" name="limit" value="50">
		<input type="hidden" name="ordering" value="newest">
		<input type="hidden" name="view" value="search">
		<input type="hidden" name="Itemid" value="99999999">
		<input type="hidden" name="option" value="com_search">
		<input type="hidden" name="areas[0]" value="content">
		<input type="hidden" name="areas[1]" value="categories">
		<input type="hidden" name="areas[2]" value="sections">
		<input type="hidden" name="areas[3]" value="newsfeeds">
		<!--<input type="hidden" name="areas[4]" value="plug_kunenasearch">-->
		</div>
		<div id="rokajaxsearch_tmp" style="visibility:hidden;display:none;"></div>
		</form>	
		</div>

		</div>
		</div>
		</div>
		<?php } ?>
		<!-- end search form -->

		<?php mosLoadModules('right'); ?>
		<?php $sg = 'banner'; include "templates.php"; ?>
	</td>

<?php } ?>

	</tr>
</table>

<div class="footer_bg">
<div class="footer">
<?php $sg = ''; include "templates.php"; ?>
<A href="http://sourceforge.net/projects/dolibarr/"><img src="http://sourceforge.net/sflogo.php?group_id=153900&type=1" width="1" height="1" border="0" alt="SourceForge"></A>
</div>
</div>

</div>
</div>
</div>
</div>

</center>
</body>
</html>

