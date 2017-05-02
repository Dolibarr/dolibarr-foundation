<?php
/**
 * @copyright	Copyright (C) 2014 Laurent Destailleur
 * @license		GNU/GPL
 **/

defined('_JEXEC') or die;
$app = JFactory::getApplication();
JHtml::_('behavior.framework');	// If we don't add this, the "/media/system/js/mootools-core.js" and /media/system/js/core.js" lib are not loaded making errors on "Class" method not found.
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<jdoc:include type="head" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/system.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/system/css/general.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl ?>/templates/<?php echo $this->template ?>/css/template.css" type="text/css" />
<link rel="image_src" type="image/jpeg" href="http://www.dolibarr.fr/images/stories/dolibarr.png" />
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
<link href="https://plus.google.com/+DolibarrFr" rel="publisher" />
<meta name="verify-v1" content="5uTEtcSaRHlZVnb3L4x4QrpRzdw3zMZ51+mJxf/4Cd8=" />
<meta name="verify-v1" content="ygCOli7T1nnmmIz2ikasGV2Y+1DLmLcsblrDp+tSo/Q=" />
<!-- Updowner tags -->
<meta name="Updowner-verification" content="1328821adac0666e174e84809a4cc72e" />
<!-- Twitter tags -->
<meta name="twitter:card" content="summary">
<meta name="twitter:site" content="@dolibarr_france">
<meta name="twitter:title" content="Dolibarr ERP CRM Portail France">
<meta name="twitter:description" content="Dolibarr ERP/CRM (PGI) est un logiciel Open Source de gestion commerciale gratuit pour gérer une activitée professionnelle ou associative (PME/PMI, travailleurs indépendants, associations, artisans, auto-entrepreneurs), son fonctionnement modulaire permet d'alléger son interface en fonction des besoins de l'entreprise">
<meta name="twitter:creator" content="@dolibarr_france">
<meta name="twitter:image:src" content="http://www.dolibarr.fr/images/stories/dolibarr.png">
<meta name="twitter:domain" content="dolibarr.fr">
<meta name="twitter:app:name:iphone" content="">
<meta name="twitter:app:name:ipad" content="">
<meta name="twitter:app:name:googleplay" content="">
<meta name="twitter:app:url:iphone" content="">
<meta name="twitter:app:url:ipad" content="">
<meta name="twitter:app:url:googleplay" content="">
<meta name="twitter:app:id:iphone" content="">
<meta name="twitter:app:id:ipad" content="">
<meta name="twitter:app:id:googleplay" content="">
</head>
<body class="body">

<script>
// Create the state-indicator element
var indicator = document.createElement('div');
indicator.className = 'state-indicator';
document.body.appendChild(indicator);
// Create a method which returns device state
function getDeviceState() {
    return parseInt(window.getComputedStyle(indicator).getPropertyValue('z-index'), 10);
}
//alert(getDeviceState());

/* Information on media 
    var dpr = window.devicePixelRatio,
    msg = '';
	if (dpr) { msg += 'devicePixelRatio: ' + dpr; } // devicePixelRatio property
	
	if (window.matchMedia) { // matchMedia method
    if (window.matchMedia('(min-resolution: 96dpi)').matches) { msg += '\ndpi: true'; }	// resolution feature & dpi unit
    if (window.matchMedia('(min-resolution: 1dppx)').matches) { msg += '\ndppx: true'; }	// resolution feature & dppx unit
    if (window.matchMedia('(-webkit-min-device-pixel-ratio: 1)').matches) { msg += '\n-wk-dpr: true'; }	// -webkit-device-pixel-ratio feature
    if (window.matchMedia('(-o-min-device-pixel-ratio: 1/1)').matches) { msg += '\n-o-dpr: true'; }	// -o-device-pixel-ratio feature
}
window.alert(msg);
*/
</script>

<!-- Google analytics -->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-9049390-9', 'dolibarr.fr');
  ga('require', 'displayfeatures');
  ga('send', 'pageview');

</script>
<!-- End google analytics --> 
	
<div class="plusone">
	<!-- Plus one -->
	<g:plusone size="small" href="http://www.dolibarr.fr/"></g:plusone>
	<script type="text/javascript">
	(function() {
	var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
	po.src = 'http://apis.google.com/js/plusone.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	})();
	</script>
</div>

<span itemscope itemtype="http://schema.org/SoftwareApplication">
<header>
	<div id="header" class="clearfix">
			<a id="logo" href="<?php echo $this->baseurl; ?>">
				<img src="<?php echo JURI::root() ?>templates/<?php echo $this->template ?>/images/bg2.png" alt="<?php echo $app->getCfg('sitename') ?>" width="200" height="59"/> <?php if ($this->params->get('sitedescription')) { echo '<div class="site-description">'. htmlspecialchars($this->params->get('sitedescription')) .'</div>'; } ?>
			</a>
			<div id="headermodule">
				<jdoc:include type="modules" name="position-0" style="none" />
			</div>
		</div>
		
		<?php if ($this->countModules('position-1')): ?>
		<div id="nav" class="">
			<div id="decalmenu" class="backshadow">
			<jdoc:include type="modules" name="position-1" style="none" />
			</div>
			<div class="backshadow arrowafterul"></div>
			<div class="backshadow">
			<jdoc:include type="modules" name="position-2" style="none" />
			</div>
		</div>
		<?php endif; ?>
	</div>
</header>

<div id="main-surround" class="main-shadows-light">

		<div class="wrapper">
<div class="shadow-left"><div class="shadow-right">

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
			<?php if ($this->countModules('position-7')): ?>
			<div id="left">
				<div class="inner rounded">
					<jdoc:include type="modules" name="position-7" style="xhtml" />
				</div>
			</div>
			<?php endif; ?>
			
			<div id="center" class="rounder white">
				<?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
				<div id="slideshow">
					<jdoc:include type="modules" name="position-3" style="xhtml" />
				</div>
				<?php } ?>
								
				<div class="inner">
					<jdoc:include type="modules" name="position-5" style="xhtml" />
					<jdoc:include type="message" />
					<?php if (empty($_SERVER['SCRIPT_URL']) || in_array($_SERVER['SCRIPT_URL'],array('/')) && ! isset($_GET['layout']) && ! isset($_GET['searchword']) && ! isset($_GET['start'])) { ?>
					<h3>Dernières actualités...</h3><hr />
					<?php } ?>
					<jdoc:include type="component" />
					<jdoc:include type="modules" name="position-2" style="xhtml" />
				</div>
			</div>
			<?php if ($this->countModules('position-6')) : ?>
			<div id="right">
				<div class="inner rounded">
					<jdoc:include type="modules" name="position-6" style="xhtml" />
				</div>
			</div>
			<?php endif; ?>
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

</div></div>	
	</div>
</div>

<div id="body2">
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
				<span itemprop="name">Dolibarr ERP &amp; CRM</span> &copy; 2003-2014 - Gestion d'entreprise ou d'association<br/>
			</div>
		</div>
</div></span>

<jdoc:include type="modules" name="debug" style="none" />

</body>
</html>
