<?php
namespace SAMLBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\Util\XmlUtils;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SAMLExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $container->setParameter('saml_sp_config', $config['default_sp']);
        $this->populateSAMLIDP($container);
    }
    
    public function prepend(ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('saml-sp.yml');
    }
    
    private function populateSAMLIDP(ContainerBuilder $container)
    {
        $config = [];
        $dom = XmlUtils::loadFile(__DIR__.'/../Resources/config/saml-idp.xml');
        
        $entityId = $dom->getElementsByTagName('EntityDescriptor')->item(0)
                    ->attributes->getNamedItem('entityID')->value;
        $config['entityID'] = $entityId;
        
        $claimTypeNodes = $dom->getElementsByTagName('ClaimType');
        foreach($claimTypeNodes as $claimTypeNode) {
            $cliamValue = $claimTypeNode->attributes->getNamedItem("Uri")->value;
            $claimKey = substr(strrchr($cliamValue, '/'), 1);
            
            $config['claimTypes'][$claimKey] = $cliamValue;
        }

        $IDPSSODescriptor = [];
        $IDPSSODescriptorNode = $dom->getElementsByTagName('IDPSSODescriptor');
        $IDPSSODescriptorNodes = $IDPSSODescriptorNode->item(0)->childNodes;
        foreach ($IDPSSODescriptorNodes as $childNode) {
            if(!isset($IDPSSODescriptor[$childNode->nodeName])) {
                $IDPSSODescriptor[$childNode->nodeName] = [];
            }
            
              array_push($IDPSSODescriptor[$childNode->nodeName], [
                'Binding' => $childNode->attributes->getNamedItem('Binding')->value,
                'Location' => $childNode->attributes->getNamedItem('Location')->value
            ]);
        }
        $config['IDPSSODescriptor'] = $IDPSSODescriptor;
        $container->setParameter('saml_idp_config', $config);
    }
}