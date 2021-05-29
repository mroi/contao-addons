<?php
namespace Mroi\ContaoAddons;

use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;


/* describe how the bundle class needs to be loaded */
class Plugin implements \Contao\ManagerPlugin\Bundle\BundlePluginInterface {

	public function getBundles(ParserInterface $parser): array {
		return [
			BundleConfig::create(Bundle::class)->setLoadAfter([
				\Contao\CoreBundle\ContaoCoreBundle::class,
				\Contao\NewsletterBundle\ContaoNewsletterBundle::class
			])
		];
	}
}

/* from here it’s boilerplate to register the actual functionality */
class Bundle extends \Symfony\Component\HttpKernel\Bundle\Bundle {
}
