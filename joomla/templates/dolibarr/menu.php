<div id="menu">
	<div class="menuc">
		<div id="topnavi">
			<ul>
			<?php
				$item_id = (int) mosGetParam($_REQUEST, 'Itemid', 0);
				$qry = "SELECT id, name, link FROM #__menu WHERE menutype='mainmenu' and parent='0' AND access<='$gid' AND sublevel='0' AND published='1' ORDER BY ordering";
				$database->setQuery($qry);
				$rows = $database->loadObjectList();
				foreach($rows as $row) {
					//if (($GLOBALS['mosConfig_lang'] == 'fr-FR') || $GLOBALS['mosConfig_lang'] == 'french'){
					//	$lang = 'fr';
					//}else{
					//	$lang = 'en';
					//}
					$useitem = !in_array($row->id, array(61,62,65)) ? '&Itemid='.$row->id : '';
					$target = in_array($row->id, array(61,62,65)) ? 'target="_blank"' : '';
					$href = $row->link.$useitem;
					echo '<!-- row->id='.$row->id.' --><li><a href="'.$href.'"'.( $row->id == $item_id ? ' class="current"' : "" ).' '.$target.'><span>'.$row->name.'</span></a></li>'."\n";
				}
			?>
			</ul>

		</div>

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

		<div align="right">
		<?php # Disable language links mosLoadModules('user4'); ?>
		<table cellpadding="0" cellspacing="0" class="moduletabletable.moduletable">
			<tr>
			<td>
			<div id="jflanguageselection"><div class="rawimages">
			<span><a href="http://www.dolibarr.es/" target="_blank"><img src="/images/flags/flags_es.png" alt="Dolibarr spanish portal" title="Dolibarr spanish portal" /></a></span>
			<span><a href="http://www.dolibarr.fr/" target="_blank"><img src="/images/flags/flags_fr.png" alt="Dolibarr french portal" title="Dolibarr french portal" /></a></span>
			</div></div>
			</td>
			</tr>
		</table>
		</div>

<!--		<div id="submenu">
			<ul>
			<?php
			/*
				$qry = "SELECT * FROM #__menu WHERE menutype='mainmenu' and parent='{$item_id}' AND access<='$gid' AND sublevel='0' AND published='1' ORDER BY ordering";
				$database->setQuery($qry);
				$rows = $database->loadObjectList();
				if (!empty($rows)) {
					foreach($rows as $row) {
						echo '<li><a href="'.$row->link."&Itemid=".$row->id.'">'.$row->name.'</a></li>';
					}
				}
			*/
			?>
			</ul>
		</div>
-->
	</div>
</div>
