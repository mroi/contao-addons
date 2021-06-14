<?php
namespace Mroi\ContaoAddons;

use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;


/* describe how the Symfony bundle class needs to be loaded by Contao */
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

/* the bundle just tells Symfony that we implement a service extension */
class Bundle extends \Symfony\Component\HttpKernel\Bundle\Bundle {

	public function getContainerExtension() {
		return new Extension();
	}
}

/* the Symfony extension registers our addon services, registrations are cached */
class Extension extends \Symfony\Component\DependencyInjection\Extension\Extension {

	public function getAlias(): string {
		// our non-standard class naming requires an explicit alias
		return 'addons';
	}

	public function load(array $configs, ContainerBuilder $container): void {
		$loader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader(
			$container,
			new \Symfony\Component\Config\FileLocator(__DIR__ . '/..')
		);

		$loader->load('services.yml');
	}
}
