<?php
/**
 * Template dedicated to Dolibarr
 **/

defined('_JEXEC') or die;
$app = JFactory::getApplication();
// If we don't add this, the "/media/system/js/mootools-core.js" and /media/system/js/core.js" lib are not loaded making errors on "Class" method not found.
//JHtml::_('bootstrap.framework');
//JHtml::_('behavior.framework');
// Disable some script we don't need
unset($this->_scripts['/media/system/js/tabs-state.js']);
unset($this->_scripts['/media/jui/js/jquery-noconflict.js']);
unset($this->_scripts['/media/jui/js/jquery-migrate.min.js']);
//unset($this->_scripts['/media/system/js/mootools-core.js']);	// Required to manage popup for screenshot
//unset($this->_scripts['/media/system/js/mootools-more.js']);  // Required to manage popup for screenshot
//unset($this->_scripts['/media/jui/js/bootstrap.min.js']);	// Need bootstrap to allow edit by admin and avoid "uncaught type error"
?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>" xmlns:og="http://ogp.me/ns#">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<jdoc:include type="head" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/template.css" type="text/css" />
<link rel="image_src" type="image/jpeg" href="/images/stories/dolibarr.png" />
	<?php
	if ($this->params->get('templateColor'))
	{
	?>
		<style type="text/css">
			body
			{
				background-color: <?php echo $this->params->get('templateBackgroundColor');?>
			}
		</style>
	<?php
	}
	?>
	<?php
	// verifies si on a besoin des colonnes
	$mainclass = '';
	if (!$this->countModules('position-7'))
	{
		$mainclass .= " noleft";
	}
	if (!$this->countModules('position-6') || $app->input->getCmd('task', '') == 'edit')
	{
		$mainclass .= " noright";
	}
	$mainclass = trim($mainclass);
	?>
	<?php
	$nbmodulesrow1 = (bool)$this->countModules('position-8') + (bool)$this->countModules('position-9') + (bool)$this->countModules('position-10') + (bool)$this->countModules('position-11');
	?>
	<?php
	$nbmodulesrow2 = (bool)$this->countModules('position-12') + (bool)$this->countModules('position-13') + (bool)$this->countModules('position-14') + (bool)$this->countModules('position-15');
	?>
	<?php
	$nbmodulesrow3 = (bool)$this->countModules('position-16') + (bool)$this->countModules('position-17') + (bool)$this->countModules('position-18') + (bool)$this->countModules('position-19');
	?>
<!-- Google tags -->
<link href="https://plus.google.com/+DolibarrOrg" rel="publisher" />
<meta name="google-site-verification" content="Zrd0m6CGqK2Qgzw2JB7wn6D6TUBe2xuGMOfm92mDEmo" />
<meta name="verify-v1" content="5uTEtcSaRHlZVnb3L4x4QrpRzdw3zMZ51+mJxf/4Cd8=" />
<meta name="verify-v1" content="ygCOli7T1nnmmIz2ikasGV2Y+1DLmLcsblrDp+tSo/Q=" />
<meta name="msvalidate.01" content="97D254FD131581C03545FB5D9A228AD7" />
<!-- OpenGraph tags -->
<!-- Returns error with debuger https://developers.facebook.com/tools/debug/ -->
<meta property="og:type" content="website"/>
<meta property="og:title" content="Dolibarr ERP CRM Open Source software"/>
<meta property="og:url" content="https://www.dolibarr.org/"/>
<meta property="og:image" content="https://www.dolibarr.org/images/dolibarr_logo1.png"/>
<meta property="og:description" content="Dolibarr ERP CRM is an Open Source software package for small, medium or large companies, freelancers or foundations to manage business. It's a PHP project built by modules addition (invoice, proposal, contact, stock, order, hr, agenda...). Running as a Web server, Dolibarr main goal is to provide an easy to use ERP and CRM solution." />
<meta property="fb:app_id" content="177908922592070"/>
<!-- Twitter tags -->
<meta name="twitter:card" content="summary">
<meta name="twitter:site" content="@dolibarr">
<meta name="twitter:title" content="Dolibarr ERP CRM Open Source software">
<meta name="twitter:description" content="Dolibarr ERP CRM is an Open Source software packagefor small, medium or large companies, freelancers or foundations to manage business. It's a PHP project built by modules addition (invoice, proposal, contact, stock, order, hr, agenda...). Running as a Web server, Dolibarr main goal is to provide an easy to use ERP and CRM solution.">
<meta name="twitter:creator" content="@dolibarr">
<meta name="twitter:image:src" content="https://www.dolibarr.org/images/stories/dolibarr.png">
<meta name="twitter:domain" content="https://www.dolibarr.org">
<meta name="twitter:app:name:iphone" content="">
<meta name="twitter:app:name:ipad" content="">
<meta name="twitter:app:name:googleplay" content="">
<meta name="twitter:app:url:iphone" content="">
<meta name="twitter:app:url:ipad" content="">
<meta name="twitter:app:url:googleplay" content="">
<meta name="twitter:app:id:iphone" content="">
<meta name="twitter:app:id:ipad" content="">
<meta name="twitter:app:id:googleplay" content="">
<!-- Twitter stats -->
<meta property="twitter:account_id" content="1512914126" />
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','//connect.facebook.net/en_US/fbevents.js');

fbq('init', '1998533953704960');
fbq('track', "PageView");</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1998533953704960&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
<!-- Google analytics -->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-9049390-1', 'dolibarr.org');
  ga('require', 'displayfeatures');
  ga('send', 'pageview');

</script>
<!-- End google analytics -->
</head>
<body class="body">

<div class="plusone">
	<!-- Plus one -->
	<g:plusone size="small" href="https://www.dolibarr.org/"></g:plusone>
	<script type="text/javascript">
	(function() {
	var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
	po.src = 'https://apis.google.com/js/plusone.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	})();
	</script>
</div>

<span itemscope itemtype="http://schema.org/SoftwareApplication">
	
<header class="dolheader">
	<div id="header" class="clearfix">
			<a id="logo" href="<?php echo $this->baseurl; ?>">
				<img class="toplogo" src="<?php echo JURI::root() ?>templates/<?php echo $this->template ?>/images/bg2.png" alt="<?php echo $app->getCfg('sitename') ?>" /> <?php if ($this->params->get('sitedescription')) { echo '<div class="site-description">'. htmlspecialchars($this->params->get('sitedescription')) .'</div>'; } ?>
			</a>
			<div id="headermodule">
<?php if (preg_match('/(ipod|ipad|iphone|android)/i',$_SERVER['HTTP_USER_AGENT'])) { ?>
				<jdoc:include type="modules" name="user5" style="none" />
<?php } else { ?>
				<jdoc:include type="modules" name="user1" style="none" />
<?php } ?>
			</div>
		</div>
		
		<?php if ($this->countModules('position-1')): ?>
		<div id="nav" class="">
			<div id="decalmenu" class="backshadow">
			<jdoc:include type="modules" name="position-1" style="none" />
			</div>
			<div class="backshadow arrowafterul"></div>
			<div class="backshadow">
			</div>
		</div>
		<?php endif; ?>
	</div>
</header>

<div id="main-surround" class="main-shadows-light">

	<?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
	<jdoc:include type="modules" name="position-2" style="none" />
	<?php } ?>

	<div class="wrapper">

		<?php if ($nbmodulesrow1): ?>
		<div id="row1modules" class="clearfix <?php echo 'n'.$nbmodulesrow1 ?>">
			<?php if ($this->countModules('position-8')) : ?>
			<div class="row1module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-8" style="perso" />
				</div>
			</div>
			<?php endif; ?>
			<?php if ($this->countModules('position-9')) : ?>
			<div class="row1module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-9" style="perso" />
				</div>
			</div>
			<?php endif; ?>
			<?php if ($this->countModules('position-10')) : ?>
			<div class="row1module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-10" style="perso" />
				</div>
			</div>
			<?php endif; ?>
			<?php if ($this->countModules('position-11')) : ?>
			<div class="row1module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-11" style="perso" />
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		
		
		<div id="main" class="clearfix <?php echo $mainclass ?>">
			
			<div id="center" class="rounder white">
				
				<jdoc:include type="message" />
				
				<?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
				<div id="slideshow">
					<!-- position 3 start -->
					<jdoc:include type="modules" name="position-3" style="xhtml" />
					<!-- position 3 end -->
				</div>
				<?php } ?>
								
				<div class="inner">
					<?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
					<!-- position 5 start -->
					<a class="anchor" name="features">&nbsp;</a>
					<jdoc:include type="modules" name="position-5" style="xhtml" />
					<!-- position 5 end -->
					<?php } ?>
					
				</div>
				
				<div class="clearboth"></div>
				<?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
				<?php if ($this->countModules('position-7')): ?>
				<div class="inner">
				
					<!-- position 7 start -->
					<jdoc:include type="modules" name="position-7" style="xhtml" />
					<!-- position 7 end -->
					
				</div>
				<?php endif; ?>
				<?php } ?>

				<div class="inner">
			        <?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
					<a class="anchor" name="lastnews">&nbsp;</a>
					<div class="moduletable">
						<h2 class="dolibarrh2" style="padding-bottom: 5px;">Last news...</h2>
				    </div>
					<?php } ?>	

					<!-- position 4 start -->
					<jdoc:include type="modules" name="position-4" style="xhtml" />
					<!-- position 4 end -->

					<!-- position news start -->
					<jdoc:include type="component" />
					<!-- position news end -->
				
				</div>

				<div class="inner">
			        <?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
					<!-- position 6 start -->
					<a class="anchor" name="community">&nbsp;</a>
					<jdoc:include type="modules" name="position-6" style="xhtml" />
					<!-- position 6 end -->
				<?php } ?>	
				</div>

				
			</div>
		</div>

		<?php if ($nbmodulesrow2): ?>
		<div id="row2modules" class="clearfix <?php echo 'n'.$nbmodulesrow2 ?>">
			<?php if ($this->countModules('position-12')) : ?>
			<div class="row2module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-12" style="perso" />
				</div>
			</div>
			<?php endif; ?>
			<?php if ($this->countModules('position-13')) : ?>
			<div class="row2module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-13" style="perso" />
				</div>
			</div>
			<?php endif; ?>
			<?php if ($this->countModules('position-14')) : ?>
			<div class="row2module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-14" style="perso" />
				</div>
			</div>
			<?php endif; ?>
			<?php if ($this->countModules('position-15')) : ?>
			<div class="row2module">
				<div class="inner rounded white">
					<jdoc:include type="modules" name="position-15" style="perso" />
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php endif; ?>

	</div>
</div>

<div id="body2" class="clearboth">
		<div class="wrapper2">
			<?php if ($nbmodulesrow3): ?>
			<div id="row3modules" class="clearfix <?php echo 'n'.$nbmodulesrow2 ?>">
					<?php if ($this->countModules('position-16')) : ?>
					<div class="row3module">
						<div class="inner">
							<jdoc:include type="modules" name="position-16" style="xhtml" />
						</div>
					</div>
					<?php endif; ?>
					<?php if ($this->countModules('position-17')) : ?>
					<div class="row3module">
						<div class="inner">
							<jdoc:include type="modules" name="position-17" style="xhtml" />
						</div>
					</div>
					<?php endif; ?>
					<?php if ($this->countModules('position-18')) : ?>
					<div class="row3module">
						<div class="inner">
							<jdoc:include type="modules" name="position-18" style="xhtml" />
						</div>
					</div>
					<?php endif; ?>
					<?php if ($this->countModules('position-19')) : ?>
					<div class="row3module">
						<div class="inner">
							<jdoc:include type="modules" name="position-19" style="xhtml" />
						</div>
					</div>
					<?php endif; ?>
			</div>
			<?php endif; ?>
			<div id="footer">
				<jdoc:include type="modules" name="position-4" style="none" />
				<span itemprop="name">Dolibarr ERP CRM</span> &copy; 2003-2016 - Open Source Software for business or foundation
				<!--<a href="https://sourceforge.net/projects/dolibarr/"><img src="https://sourceforge.net/sflogo.php?group_id=153900&type=1" width="1" height="1" border="0" alt="SourceForge"></a>-->

				<a id="l1" class="icons" target="_blank" href="https://www.facebook.com/dolibarr"><img title="Facebook" src="/templates/dolibarr/images/more/Facebook.png" alt="Facebook"></a><a id="l3" class="icons" target="_blank" href="https://twitter.com/dolibarr"><img title="Twitter" src="/templates/dolibarr/images/more/Twitter.png" alt="Twitter"></a><a id="ll1" class="icons" target="_blank" href="https://www.linkedin.com/company/association-dolibarr"><img title="LinkedIn" src="/templates/dolibarr/images/more/Linkedin.png" alt="LinkedIn"></a><a id="l14" class="icons" target="_blank" href="https://www.dolibarr.org/rss"><img title="RSS Feed" src="/templates/dolibarr/images/more/Rss.png" alt="RSS Feed"></a><a id="l16" class="icons" target="_blank" href="https://plus.google.com/+DolibarrOrg"><img title="Google+ page" src="/templates/dolibarr/images/more/Google.png" alt="Google+ Page"></a><a id="l18" class="icons" target="_blank" href="https://www.youtube.com/user/DolibarrERPCRM"><img title="Youtube channel" src="/templates/dolibarr/images/more/YouTube.png" alt="Youtube channel"></a>

			</div>
		</div>
</div>

</span>

<jdoc:include type="modules" name="debug" style="none" />

<!-- Twitter ad collector -->
<script src="//platform.twitter.com/oct.js" type="text/javascript"></script>
<script type="text/javascript">twttr.conversion.trackPid('ntm4n', { tw_sale_amount: 0, tw_order_quantity: 0 });</script>
<noscript>
<img height="1" width="1" style="display:none;" alt="" src="https://analytics.twitter.com/i/adsct?txn_id=ntm4n&p_id=Twitter&tw_sale_amount=0&tw_order_quantity=0" />
<img height="1" width="1" style="display:none;" alt="" src="//t.co/i/adsct?txn_id=ntm4n&p_id=Twitter&tw_sale_amount=0&tw_order_quantity=0" />
</noscript>

</body>
</html>

