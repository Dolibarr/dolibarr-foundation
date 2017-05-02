<?php
/**
 * tutoriel de C��dric Keiflin pour cr��er son template Joomla! 3.x
 * http://www.joomlack.fr - http://www.template-creator.com
 */

defined('_JEXEC') or die;

function modChrome_perso($module, &$params, &$attribs)
{
	if (empty ($module->content)) return; 
	$titres = explode(' ', $module->title);
	$titre = str_replace($titres[0], '<span class="titreperso">'. $titres[0] .'</span>', $module->title);
	?>
		<div class="moduletable<?php echo htmlspecialchars($params->get('moduleclass_sfx')); ?>">
		<?php if ((bool) $module->showtitle) : ?>
			<h3><?php echo $titre; ?></h3>
		<?php endif; ?>
			<?php echo $module->content; ?>
		</div>
		<?php
}